<?php

use App\Enums\ListingStatus;
use App\Livewire\ListingActions;
use App\Livewire\Marketplace;
use App\Livewire\Vehicle\Sell;
use App\Models\Listing;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Services\ListingPublisher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

it('only shows public listings, best history first', function () {
    $good = liveListing(['score' => 92]);
    $okay = liveListing(['score' => 61]);
    $draft = liveListing(['status' => ListingStatus::Draft]);
    $sold = liveListing(['status' => ListingStatus::Sold]);

    Livewire::test(Marketplace::class)
        ->assertSeeInOrder([$good->vehicle->title(), $okay->vehicle->title()])
        ->assertViewHas('listings', fn ($listings) => $listings->pluck('id')->sort()->values()->all() === [$good->id, $okay->id]);
});

it('filters by minimum score, make, price and state', function () {
    $porsche = liveListing(['score' => 90, 'price_cents' => 9_000_000, 'state' => 'FL']);
    $porsche->vehicle->update(['make' => 'Porsche', 'model' => '911']);
    $honda = liveListing(['score' => 55, 'price_cents' => 1_500_000, 'state' => 'TX']);

    $ids = fn ($component) => $component->viewData('listings')->pluck('id')->all();

    expect($ids(Livewire::test(Marketplace::class)->set('minScore', 85)))->toBe([$porsche->id])
        ->and($ids(Livewire::test(Marketplace::class)->set('make', 'Porsche')))->toBe([$porsche->id])
        ->and($ids(Livewire::test(Marketplace::class)->set('maxPrice', '25000')))->toBe([$honda->id])
        ->and($ids(Livewire::test(Marketplace::class)->set('state', 'TX')))->toBe([$honda->id])
        ->and($ids(Livewire::test(Marketplace::class)->set('q', '911')))->toBe([$porsche->id]);
});

it('shows a listing with its passport, and hides drafts from strangers', function () {
    $listing = liveListing();
    ServiceRecord::factory()->create(['vehicle_id' => $listing->vehicle_id, 'title' => 'Water pump replaced']);

    $this->get(route('listings.show', $listing))->assertOk()->assertSee('Water pump replaced')->assertSee('Passport Score');
    expect($listing->fresh()->views)->toBe(1);

    $draft = liveListing(['status' => ListingStatus::Draft]);
    $this->get(route('listings.show', $draft))->assertNotFound();
    $this->actingAs($draft->seller)->get(route('listings.show', $draft))->assertOk();
});

it('saves and reports listings', function () {
    $listing = liveListing();
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(ListingActions::class, ['listing' => $listing])
        ->call('toggleSave')
        ->set('reason', 'scam')
        ->call('report');

    expect($user->savedListings()->count())->toBe(1)->and($listing->reports()->count())->toBe(1);
});

it('sends guests to sign in before contacting a seller', function () {
    Livewire::test(ListingActions::class, ['listing' => liveListing()])
        ->call('contact')
        ->assertRedirect(route('login'));
});

it('will not publish a listing until the passport has enough to show', function () {
    $vehicle = car();
    $listing = Listing::factory()->draft()->create(['vehicle_id' => $vehicle->id]);

    expect(app(ListingPublisher::class)->blockers($listing))->toHaveCount(2);
    expect(fn () => app(ListingPublisher::class)->publish($listing))->toThrow(ValidationException::class);
});

it('publishes from the sell tab with photos, a share link and a score', function () {
    Storage::fake('public');
    $vehicle = car();
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);

    Livewire::actingAs($vehicle->owner)->test(Sell::class, ['vehicle' => $vehicle])
        ->set('photos', [UploadedFile::fake()->image('front.jpg')])
        ->set('price', '$24,500')
        ->set('city', 'Austin')
        ->set('state', 'TX')
        ->set('description', 'Garaged, serviced on time, every receipt is in the passport. Selling because we moved.')
        ->call('publish')
        ->assertHasNoErrors();

    $listing = $vehicle->listings()->first();
    expect($listing->status)->toBe(ListingStatus::Active)
        ->and($listing->price_cents)->toBe(2_450_000)
        ->and($listing->score)->toBeGreaterThan(0)
        ->and($listing->shareLink->show_full_vin)->toBeTrue()
        ->and($vehicle->photos()->count())->toBe(1);

    Livewire::actingAs($vehicle->owner)->test(Sell::class, ['vehicle' => $vehicle->fresh()])->call('withdraw');
    expect($listing->fresh()->status)->toBe(ListingStatus::Withdrawn)
        ->and($listing->shareLink->fresh()->isActive())->toBeFalse();
});
