<?php

namespace App\Console\Commands;

use App\Enums\OfferStatus;
use App\Enums\VerificationStatus;
use App\Models\Offer;
use App\Models\ShopVerification;
use Illuminate\Console\Command;

class Housekeeping extends Command
{
    protected $signature = 'passport:housekeeping';

    protected $description = 'Expire stale offers and unanswered shop verification requests';

    public function handle(): int
    {
        $offers = Offer::where('status', OfferStatus::Pending)->where('expires_at', '<=', now())->update(['status' => OfferStatus::Expired]);
        $requests = ShopVerification::where('status', VerificationStatus::Pending)->where('expires_at', '<=', now())->update(['status' => VerificationStatus::Expired]);

        $this->components->info("Expired {$offers} offers and {$requests} verification requests.");

        return self::SUCCESS;
    }
}
