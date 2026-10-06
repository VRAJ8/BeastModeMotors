<?php

namespace App\Services;

use App\Enums\DealStatus;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Models\Deal;
use App\Models\DealMessage;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\User;
use App\Notifications\DealUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every state change in a private sale goes through here, so the rules live in one place:
 * talk → offer / counter → agree → inspect → hand over → both confirm → ownership transfers.
 */
class DealFlow
{
    public const OFFER_HOURS = 72;

    public function __construct(private ScamShield $shield, private OwnershipTransfer $transfer) {}

    public function start(Listing $listing, User $buyer, string $message): Deal
    {
        if ($listing->status !== ListingStatus::Active) {
            throw ValidationException::withMessages(['message' => 'This car isn\'t taking new enquiries.']);
        }

        if ($listing->seller_id === $buyer->getKey()) {
            throw ValidationException::withMessages(['message' => 'You can\'t start a deal on your own car.']);
        }

        $deal = Deal::where('listing_id', $listing->getKey())
            ->where('buyer_id', $buyer->getKey())
            ->whereIn('status', [DealStatus::Open, DealStatus::Agreed])
            ->first();

        $deal ??= Deal::create([
            'listing_id' => $listing->getKey(),
            'vehicle_id' => $listing->vehicle_id,
            'buyer_id' => $buyer->getKey(),
            'seller_id' => $listing->seller_id,
            'status' => DealStatus::Open,
        ]);

        $this->post($deal, $buyer, $message);

        return $deal;
    }

    public function post(Deal $deal, User $author, string $body): DealMessage
    {
        $this->ensureParticipant($deal, $author);

        if (! $deal->status->isActive()) {
            throw ValidationException::withMessages(['body' => 'This deal is closed.']);
        }

        $flags = $this->shield->scan($body);

        $message = $deal->messages()->create([
            'user_id' => $author->getKey(),
            'body' => trim($body),
            'risk_flags' => $flags ?: null,
        ]);

        $deal->touch();

        $deal->counterparty($author)->notify(new DealUpdate(
            $deal,
            "New message from {$author->publicName()} about the {$deal->vehicle->title()}",
            str($body)->limit(140)->toString(),
            email: false,
        ));

        return $message;
    }

    public function offer(Deal $deal, User $author, int $amountCents, ?string $note = null): Offer
    {
        $this->ensureParticipant($deal, $author);

        if ($deal->status !== DealStatus::Open || $deal->listing->status !== ListingStatus::Active) {
            throw ValidationException::withMessages(['amount' => 'Offers can only be made while the car is still for sale.']);
        }

        if ($amountCents < 50000) {
            throw ValidationException::withMessages(['amount' => 'Enter a realistic amount.']);
        }

        return DB::transaction(function () use ($deal, $author, $amountCents, $note) {
            // A new offer replaces whatever was on the table: a counter if it came from the other side.
            if ($previous = $deal->pendingOffer) {
                $previous->update([
                    'status' => $previous->user_id === $author->getKey() ? OfferStatus::Withdrawn : OfferStatus::Countered,
                    'responded_at' => now(),
                ]);
            }

            $offer = $deal->offers()->create([
                'user_id' => $author->getKey(),
                'amount_cents' => $amountCents,
                'note' => $note,
                'status' => OfferStatus::Pending,
                'expires_at' => now()->addHours(self::OFFER_HOURS),
            ]);

            $role = $deal->roleOf($author);
            $verb = $previous && $previous->user_id !== $author->getKey() ? 'countered with' : 'offered';
            $this->system($deal, ucfirst($role)." {$verb} ".money($amountCents).'.');
            $this->quote($deal, $author, $note);

            $deal->counterparty($author)->notify(new DealUpdate(
                $deal,
                "{$author->publicName()} {$verb} ".money($amountCents)." for the {$deal->vehicle->title()}",
                'The offer is open for '.self::OFFER_HOURS.' hours.',
            ));

            return $offer;
        });
    }

    public function respond(Offer $offer, User $responder, bool $accept): void
    {
        $deal = $offer->deal;
        $this->ensureParticipant($deal, $responder);

        if ($offer->user_id === $responder->getKey()) {
            throw ValidationException::withMessages(['offer' => 'You can\'t answer your own offer.']);
        }

        if (! $offer->isOpen() || $deal->status !== DealStatus::Open) {
            throw ValidationException::withMessages(['offer' => 'This offer is no longer open.']);
        }

        if (! $accept) {
            $offer->update(['status' => OfferStatus::Declined, 'responded_at' => now()]);
            $this->system($deal, ucfirst($deal->roleOf($responder)).' declined the offer of '.money($offer->amount_cents).'.');
            $offer->user->notify(new DealUpdate($deal, 'Your offer of '.money($offer->amount_cents).' was declined', 'You can make another offer in the deal room.', tone: 'danger'));

            return;
        }

        DB::transaction(function () use ($offer, $deal) {
            $listing = Listing::whereKey($deal->listing_id)->lockForUpdate()->first();

            if ($listing->status !== ListingStatus::Active) {
                throw ValidationException::withMessages(['offer' => 'The seller has already agreed a sale with someone else.']);
            }

            $offer->update(['status' => OfferStatus::Accepted, 'responded_at' => now()]);
            $deal->update(['status' => DealStatus::Agreed, 'agreed_price_cents' => $offer->amount_cents, 'agreed_at' => now()]);
            $deal->inspection()->firstOrCreate([]);
            $listing->update(['status' => ListingStatus::Pending]);

            $this->system($deal, 'Price agreed at '.money($offer->amount_cents).'. Next: inspection and handover.');

            Deal::where('listing_id', $listing->getKey())
                ->whereKeyNot($deal->getKey())
                ->where('status', DealStatus::Open)
                ->get()
                ->each(fn (Deal $other) => $this->system($other, 'The seller has agreed a sale with another buyer. If it falls through, the car will be back on the market.'));
        });

        foreach ([$deal->buyer, $deal->seller] as $user) {
            $user->notify(new DealUpdate($deal, "Price agreed: {$deal->vehicle->title()} for ".money($offer->amount_cents), 'Book an inspection and work through the handover checklist together.', tone: 'success'));
        }
    }

    public function cancel(Deal $deal, User $user, ?string $reason = null): void
    {
        $this->ensureParticipant($deal, $user);

        if (! $deal->status->isActive()) {
            throw ValidationException::withMessages(['cancel' => 'This deal is already closed.']);
        }

        DB::transaction(function () use ($deal, $user, $reason) {
            $wasAgreed = $deal->status === DealStatus::Agreed;

            $deal->update([
                'status' => DealStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $user->getKey(),
                'cancel_reason' => $reason,
            ]);
            $deal->offers()->where('status', OfferStatus::Pending)->update(['status' => OfferStatus::Withdrawn]);

            if ($wasAgreed && $deal->listing->status === ListingStatus::Pending) {
                $deal->listing->update(['status' => ListingStatus::Active]);
            }

            $this->system($deal, ucfirst($deal->roleOf($user)).' cancelled the deal.');
            $this->quote($deal, $user, $reason);
        });

        $deal->counterparty($user)->notify(new DealUpdate($deal, "{$user->publicName()} cancelled the deal for the {$deal->vehicle->title()}", $reason, tone: 'danger'));
    }

    public function toggleHandover(Deal $deal, User $user, string $key): void
    {
        $this->ensureParticipant($deal, $user);
        $item = config("passport.handover.{$key}");

        if ($deal->status !== DealStatus::Agreed || ! $item) {
            throw ValidationException::withMessages(['handover' => 'The handover checklist opens once a price is agreed.']);
        }

        if ($item['by'] !== $deal->roleOf($user)) {
            throw ValidationException::withMessages(['handover' => 'Only the '.$item['by'].' can tick this step.']);
        }

        $state = $deal->handover ?? [];
        $state[$key] = isset($state[$key]) ? null : now()->toIso8601String();

        // Changing the checklist invalidates any confirmations already given.
        $deal->update(['handover' => array_filter($state), 'buyer_confirmed_at' => null, 'seller_confirmed_at' => null]);
    }

    /**
     * Both sides confirm the sale is done. The second confirmation transfers ownership.
     */
    public function confirm(Deal $deal, User $user, ?int $saleMileage = null): bool
    {
        $this->ensureParticipant($deal, $user);

        if ($deal->status !== DealStatus::Agreed) {
            throw ValidationException::withMessages(['confirm' => 'There is no agreed sale to confirm.']);
        }

        if (! $deal->handoverComplete()) {
            throw ValidationException::withMessages(['confirm' => 'Finish every required handover step first.']);
        }

        $role = $deal->roleOf($user);

        if ($role === 'seller') {
            if ($saleMileage === null || $saleMileage < $deal->vehicle->current_mileage) {
                throw ValidationException::withMessages([
                    'sale_mileage' => 'Enter the odometer reading at handover (at least '.number_format($deal->vehicle->current_mileage).' mi).',
                ]);
            }

            $deal->update(['sale_mileage' => $saleMileage, 'seller_confirmed_at' => now()]);
        } else {
            $deal->update(['buyer_confirmed_at' => now()]);
        }

        $this->system($deal, ucfirst($role).' confirmed the handover is complete.');

        if ($deal->fresh()->buyer_confirmed_at && $deal->fresh()->seller_confirmed_at) {
            $this->transfer->complete($deal->fresh());

            return true;
        }

        $deal->counterparty($user)->notify(new DealUpdate($deal, "{$user->publicName()} confirmed the handover", 'Confirm on your side to complete the sale and transfer the passport.'));

        return false;
    }

    /**
     * Free text attached to an action (offer note, cancel reason) is posted as the user's own message,
     * so it is scanned and attributed like any other message.
     */
    private function quote(Deal $deal, User $author, ?string $text): void
    {
        if (blank($text)) {
            return;
        }

        $flags = $this->shield->scan($text);
        $deal->messages()->create(['user_id' => $author->getKey(), 'body' => trim($text), 'risk_flags' => $flags ?: null]);
    }

    public function system(Deal $deal, string $body): DealMessage
    {
        return $deal->messages()->create(['user_id' => null, 'body' => $body]);
    }

    private function ensureParticipant(Deal $deal, User $user): void
    {
        abort_unless($deal->isParticipant($user), 403);
    }
}
