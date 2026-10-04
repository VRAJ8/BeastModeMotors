<?php

namespace App\Services;

use App\Enums\ListingStatus;
use App\Models\Listing;
use Illuminate\Validation\ValidationException;

class ListingPublisher
{
    public function __construct(private PassportScore $score) {}

    /**
     * What still stands between this listing and going live.
     *
     * @return list<string>
     */
    public function blockers(Listing $listing): array
    {
        $vehicle = $listing->vehicle;

        return array_values(array_filter([
            $vehicle->vin_valid ? null : 'The VIN must pass the check-digit test.',
            $vehicle->photos()->exists() ? null : 'Add at least one photo of the car.',
            $vehicle->records()->exists() ? null : 'Log at least one service record so buyers have something to go on.',
            $listing->price_cents >= 50000 ? null : 'Set an asking price.',
            mb_strlen((string) $listing->description) >= 40 ? null : 'Write a description of at least 40 characters.',
        ]));
    }

    public function publish(Listing $listing): void
    {
        if ($blockers = $this->blockers($listing)) {
            throw ValidationException::withMessages(['publish' => $blockers]);
        }

        $link = $listing->shareLink && $listing->shareLink->isActive()
            ? $listing->shareLink
            : $listing->vehicle->shareLinks()->create([
                'created_by' => $listing->seller_id,
                'label' => 'Marketplace listing',
                'show_costs' => false,
                'show_full_vin' => true,
                'show_documents' => true,
            ]);

        $listing->update([
            'status' => ListingStatus::Active,
            'share_link_id' => $link->getKey(),
            'published_at' => $listing->published_at ?? now(),
            'mileage' => $listing->vehicle->current_mileage,
            'score' => $this->score->for($listing->vehicle)['total'],
        ]);
    }

    public function withdraw(Listing $listing): void
    {
        abort_if($listing->status === ListingStatus::Pending, 422, 'Cancel the agreed deal before withdrawing the listing.');

        $listing->update(['status' => ListingStatus::Withdrawn]);
        $listing->shareLink?->update(['revoked_at' => now()]);
    }

    public function refreshScore(Listing $listing): int
    {
        $total = $this->score->for($listing->vehicle)['total'];

        if ($total !== $listing->score) {
            $listing->forceFill(['score' => $total])->saveQuietly();
        }

        return $total;
    }
}
