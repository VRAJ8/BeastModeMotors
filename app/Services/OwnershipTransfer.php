<?php

namespace App\Services;

use App\Enums\AcquiredVia;
use App\Enums\DealStatus;
use App\Enums\DocumentType;
use App\Enums\ListingStatus;
use App\Enums\OdometerSource;
use App\Models\Deal;
use App\Models\Document;
use App\Notifications\DealUpdate;
use Illuminate\Support\Facades\DB;

/**
 * Completes a sale: the car — and its entire history — moves to the buyer's garage.
 *
 * What travels with the car: service records, verifications, receipts, inspection reports, photos,
 * odometer history, recalls and the maintenance plan. What stays private to the seller: their
 * running costs, their personal paperwork (insurance, registration, title scans) and share links.
 */
class OwnershipTransfer
{
    public function complete(Deal $deal): void
    {
        DB::transaction(function () use ($deal) {
            $vehicle = $deal->vehicle;
            $current = $vehicle->currentOwnership;
            $today = now()->toDateString();

            $current?->update(['ended_on' => $today, 'end_mileage' => $deal->sale_mileage]);

            $vehicle->readings()->create([
                'ownership_id' => $current?->getKey(),
                'reading' => $deal->sale_mileage,
                'recorded_on' => $today,
                'source' => OdometerSource::Sale,
            ]);

            $next = $vehicle->ownerships()->create([
                'user_id' => $deal->buyer_id,
                'owner_number' => ($vehicle->ownerships()->max('owner_number') ?? 0) + 1,
                'acquired_via' => AcquiredVia::Platform,
                'started_on' => $today,
                'start_mileage' => $deal->sale_mileage,
                'purchase_price_cents' => $deal->agreed_price_cents,
            ]);

            Document::where('vehicle_id', $vehicle->getKey())
                ->whereNotIn('type', collect(DocumentType::cases())->filter->transfersWithCar()->map->value->all())
                ->get()
                ->each->delete();

            $vehicle->shareLinks()->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $vehicle->reminders()->update(['notified_at' => null]);
            $vehicle->update(['user_id' => $deal->buyer_id, 'nickname' => null]);
            $vehicle->refreshMileage();

            $deal->update(['status' => DealStatus::Completed, 'completed_at' => now()]);
            $deal->listing->update(['status' => ListingStatus::Sold, 'sold_at' => now()]);

            Deal::where('listing_id', $deal->listing_id)
                ->whereKeyNot($deal->getKey())
                ->whereIn('status', [DealStatus::Open, DealStatus::Agreed])
                ->update(['status' => DealStatus::Cancelled, 'cancelled_at' => now(), 'cancel_reason' => 'Car sold to another buyer']);

            app(DealFlow::class)->system($deal, "Sale complete. The passport now belongs to Owner {$next->owner_number}.");
        });

        $deal->buyer->notify(new DealUpdate($deal, "The {$deal->vehicle->title()} is yours", 'Its full history is now in your garage.', tone: 'success'));
        $deal->seller->notify(new DealUpdate($deal, "Sale complete: {$deal->vehicle->title()}", 'The passport has been transferred to the buyer.', tone: 'success'));
    }
}
