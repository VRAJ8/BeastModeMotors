<?php

use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/**
 * A car owned by a fresh user (with an ownership and opening reading).
 */
function car(array $attributes = []): Vehicle
{
    return Vehicle::factory()->create($attributes)->fresh();
}

/**
 * A published listing for a fresh car.
 */
function liveListing(array $attributes = []): Listing
{
    $vehicle = car();
    $vehicle->photos()->create(['path' => 'https://example.com/car.jpg']);

    return Listing::factory()->create(['vehicle_id' => $vehicle->id] + $attributes)->fresh();
}

/**
 * A deal between a new buyer and the listing's seller.
 */
function deal(?Listing $listing = null, DealStatus $status = DealStatus::Open): Deal
{
    $listing ??= liveListing();

    return Deal::create([
        'listing_id' => $listing->id,
        'vehicle_id' => $listing->vehicle_id,
        'buyer_id' => User::factory()->create()->id,
        'seller_id' => $listing->seller_id,
        'status' => $status,
    ])->fresh();
}
