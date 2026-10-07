<?php

namespace App\Console\Commands;

use App\Enums\OfferStatus;
use App\Enums\VerificationStatus;
use App\Models\Listing;
use App\Models\Offer;
use App\Models\ShopVerification;
use App\Services\ListingPublisher;
use Illuminate\Console\Command;

class Housekeeping extends Command
{
    protected $signature = 'passport:housekeeping';

    protected $description = 'Expire stale offers and unanswered shop verification requests, and refresh marketplace scores';

    public function handle(ListingPublisher $publisher): int
    {
        $offers = Offer::where('status', OfferStatus::Pending)->where('expires_at', '<=', now())->update(['status' => OfferStatus::Expired]);
        $requests = ShopVerification::where('status', VerificationStatus::Pending)->where('expires_at', '<=', now())->update(['status' => VerificationStatus::Expired]);

        // Cards, filters and sorting read the stored score: keep it in step with records, recalls and reminders.
        $scores = 0;
        Listing::public()->with('vehicle')->chunkById(100, function ($listings) use ($publisher, &$scores) {
            foreach ($listings as $listing) {
                $before = $listing->score;
                $scores += (int) ($publisher->refreshScore($listing) !== $before);
            }
        });

        $this->components->info("Expired {$offers} offers and {$requests} verification requests; updated {$scores} listing scores.");

        return self::SUCCESS;
    }
}
