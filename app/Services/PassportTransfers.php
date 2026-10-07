<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Enums\DealStatus;
use App\Enums\ListingStatus;
use App\Enums\OdometerStatus;
use App\Models\Deal;
use App\Models\PassportTransfer;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TransferUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Moves a passport when the car was sold outside the marketplace: privately, as a trade-in, or to family.
 *
 * The owner makes a one-time link with the handover odometer reading and their odometer certification. The new
 * owner opens it, proves they have the car by typing the end of its VIN, and the passport moves exactly as it
 * does after a marketplace sale.
 */
class PassportTransfers
{
    public const VALID_DAYS = 7;

    /** How many characters from the end of the VIN the new owner types in. */
    public const VIN_TAIL = 6;

    public function __construct(private OwnershipTransfer $ownership, private DealFlow $deals) {}

    /**
     * @return array{0: PassportTransfer, 1: string} The transfer, and the token for its link (shown once, never stored).
     */
    public function create(Vehicle $vehicle, User $owner, ?int $saleMileage, OdometerStatus $odometerStatus): array
    {
        return DB::transaction(function () use ($vehicle, $owner, $saleMileage, $odometerStatus) {
            $vehicle = Vehicle::whereKey($vehicle->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($vehicle->user_id === $owner->getKey(), 403);

            if ($this->hasAgreedSale($vehicle)) {
                throw ValidationException::withMessages(['transfer' => 'You\'ve agreed a sale for this car in a deal room. Finish or cancel it there first.']);
            }

            $current = $vehicle->current_mileage;

            if ($saleMileage === null || $saleMileage < $current || $saleMileage > $current + DealFlow::MAX_HANDOVER_MILES) {
                throw ValidationException::withMessages([
                    'sale_mileage' => 'Enter the odometer reading at handover — between '.number_format($current).' and '.number_format($current + DealFlow::MAX_HANDOVER_MILES).' mi.',
                ]);
            }

            // One live link per car: a new one replaces the last.
            PassportTransfer::where('vehicle_id', $vehicle->getKey())->open()->update(['cancelled_at' => now()]);

            $token = Str::random(40);

            $transfer = PassportTransfer::create([
                'vehicle_id' => $vehicle->getKey(),
                'from_user_id' => $owner->getKey(),
                'token_hash' => PassportTransfer::hashToken($token),
                'sale_mileage' => $saleMileage,
                'odometer_status' => $odometerStatus,
                'expires_at' => now()->addDays(self::VALID_DAYS),
            ]);

            return [$transfer, $token];
        });
    }

    public function find(string $token): ?PassportTransfer
    {
        return PassportTransfer::firstWhere('token_hash', PassportTransfer::hashToken($token));
    }

    public function cancel(PassportTransfer $transfer, User $owner): void
    {
        abort_unless($transfer->from_user_id === $owner->getKey(), 403);

        PassportTransfer::whereKey($transfer->getKey())->open()->update(['cancelled_at' => now()]);
    }

    /**
     * Why this person can't accept this link right now, or null when they can.
     */
    public function problem(PassportTransfer $transfer, User $user): ?string
    {
        return match (true) {
            $transfer->accepted_at !== null => 'This passport has already been transferred.',
            $transfer->declined_at !== null => 'This transfer was declined.',
            $transfer->cancelled_at !== null => 'This link no longer works: the owner cancelled it or made a new one. Ask them for a fresh link.',
            $transfer->expires_at->isPast() => 'This link expired on '.$transfer->expires_at->format('F j').'. Ask the owner for a fresh one.',
            $transfer->from_user_id === $user->getKey() => 'This is your own transfer link. Send it to the person who has the car now.',
            $transfer->vehicle->user_id !== $transfer->from_user_id => 'The person who sent this link no longer owns the car.',
            $this->hasAgreedSale($transfer->vehicle) => 'The owner has agreed a sale for this car on Beast Mode Motors, so it can\'t be transferred with this link.',
            default => null,
        };
    }

    public function accept(PassportTransfer $transfer, User $user, string $vinTail, AcquiredVia $via, ?int $priceCents): Vehicle
    {
        [$vehicle, $ownerNumber] = DB::transaction(function () use ($transfer, $user, $vinTail, $via, $priceCents) {
            // Lock the link, then the car, so two people (or a marketplace sale) can't take it at the same time.
            $transfer = PassportTransfer::whereKey($transfer->getKey())->lockForUpdate()->firstOrFail();
            $vehicle = Vehicle::whereKey($transfer->vehicle_id)->lockForUpdate()->firstOrFail();
            $transfer->setRelation('vehicle', $vehicle);

            if ($problem = $this->problem($transfer, $user)) {
                throw ValidationException::withMessages(['transfer' => $problem]);
            }

            if (strtoupper(trim($vinTail)) !== substr($vehicle->vin, -self::VIN_TAIL)) {
                throw ValidationException::withMessages(['vin_tail' => 'That doesn\'t match this car. Check the last '.self::VIN_TAIL.' characters of the VIN on the windshield plate or the driver\'s door sticker.']);
            }

            if ($transfer->sale_mileage < $vehicle->current_mileage) {
                throw ValidationException::withMessages(['transfer' => 'A newer odometer reading was logged after this link was made, so its handover reading is out of date. Ask the owner for a fresh link.']);
            }

            $transfer->update(['to_user_id' => $user->getKey(), 'accepted_at' => now()]);
            $next = $this->ownership->handOver($vehicle, $user->getKey(), $transfer->sale_mileage, $via, $priceCents);

            $this->deals->cancelOthers($vehicle->getKey(), null, 'The car was sold outside Beast Mode Motors');
            $vehicle->listings()
                ->whereIn('status', [ListingStatus::Draft, ListingStatus::Active, ListingStatus::Pending])
                ->update(['status' => ListingStatus::Withdrawn]);

            return [$vehicle, $next->owner_number];
        });

        $transfer->sender->notify(new TransferUpdate(
            "{$user->publicName()} accepted the passport for your {$vehicle->title()}",
            "The car and its history are now in their garage as Owner {$ownerNumber}. Your running costs and personal documents stayed with you.",
            'success',
        ));

        return $vehicle;
    }

    public function decline(PassportTransfer $transfer, User $user): void
    {
        $declined = DB::transaction(function () use ($transfer, $user) {
            $transfer = PassportTransfer::whereKey($transfer->getKey())->lockForUpdate()->firstOrFail();

            if (! $transfer->isPending() || $transfer->from_user_id === $user->getKey()) {
                return false;
            }

            return $transfer->update(['to_user_id' => $user->getKey(), 'declined_at' => now()]);
        });

        if ($declined) {
            $transfer->sender->notify(new TransferUpdate(
                "{$user->publicName()} declined the passport for your {$transfer->vehicle->title()}",
                'The link no longer works. If it went to the wrong person, make a new one from the car\'s Sell page.',
                'danger',
            ));
        }
    }

    private function hasAgreedSale(Vehicle $vehicle): bool
    {
        return Deal::where('vehicle_id', $vehicle->getKey())->where('status', DealStatus::Agreed)->exists();
    }
}
