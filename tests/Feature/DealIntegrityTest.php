<?php

use App\Enums\DealStatus;
use App\Enums\ListingStatus;
use App\Filament\Resources\Listings\ListingResource;
use App\Livewire\DealRoom;
use App\Livewire\Vehicle\Sell;
use App\Models\Deal;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\DealUpdate;
use App\Services\DealFlow;
use App\Services\OwnershipTransfer;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->flow = app(DealFlow::class);
});

function agree(Deal $deal, int $amount = 2_300_000): Deal
{
    $offer = app(DealFlow::class)->offer($deal, $deal->buyer, $amount);
    app(DealFlow::class)->respond($offer, $deal->seller, true);

    return $deal->fresh();
}

function tickEverything(Deal $deal): void
{
    foreach (config('passport.handover') as $key => $item) {
        if ($item['required']) {
            app(DealFlow::class)->toggleHandover($deal->fresh(), $item['by'] === 'buyer' ? $deal->buyer : $deal->seller, $key);
        }
    }
}

it('refuses to transfer a car the seller no longer owns', function () {
    $stale = agree(deal());
    tickEverything($stale);
    $vehicle = $stale->vehicle;

    // Meanwhile the car changed hands some other way.
    $vehicle->update(['user_id' => User::factory()->create()->id]);

    expect(fn () => $this->flow->confirm($stale->fresh(), $stale->seller, $vehicle->current_mileage + 10))->toThrow(ValidationException::class);
    expect(fn () => $this->flow->confirm($stale->fresh(), $stale->buyer))->toThrow(ValidationException::class);
    expect(fn () => app(OwnershipTransfer::class)->complete($stale->fresh()))->toThrow(ValidationException::class);
    expect($vehicle->fresh()->user_id)->not->toBe($stale->buyer_id);
});

it('cancels the deals on a listing trust & safety removes, so that sale can never complete', function () {
    $agreed = agree(deal());
    tickEverything($agreed);

    ListingResource::remove($agreed->listing, 'Stolen photos');

    expect($agreed->fresh()->status)->toBe(DealStatus::Cancelled);
    expect(fn () => $this->flow->confirm($agreed->fresh(), $agreed->seller, 99999))->toThrow(ValidationException::class);
    Notification::assertSentTo($agreed->buyer, DealUpdate::class, fn ($n) => str_contains($n->title, 'closed'));
});

it('only restores a removed listing while the seller still owns the car and has not relisted', function () {
    $listing = liveListing();
    ListingResource::remove($listing, 'Check');
    expect(ListingResource::canRelist($listing->fresh()))->toBeTrue();

    $listing->vehicle->update(['user_id' => User::factory()->create()->id]);
    expect(ListingResource::canRelist($listing->fresh()))->toBeFalse();
});

it('does not let a seller relist a car after its listing was removed', function () {
    $listing = liveListing();
    ListingResource::remove($listing, 'Misrepresented mileage');
    $vehicle = $listing->vehicle;

    Livewire::actingAs($vehicle->owner)->test(Sell::class, ['vehicle' => $vehicle])
        ->assertSee('Misrepresented mileage')
        ->set('price', '20000')->set('city', 'Austin')->set('state', 'TX')
        ->set('description', 'Relisting the very same car again straight after it was removed.')
        ->call('publish')
        ->assertHasErrors('publish');

    expect(Listing::where('vehicle_id', $vehicle->id)->count())->toBe(1);
});

it('ends every conversation about the car when it sells, including ones on older listings', function () {
    $listing = liveListing();
    $old = deal($listing);
    $listing->update(['status' => ListingStatus::Withdrawn]);

    $newListing = Listing::factory()->create(['vehicle_id' => $listing->vehicle_id]);
    $sale = agree(deal($newListing));
    tickEverything($sale);
    $this->flow->confirm($sale->fresh(), $sale->seller, $sale->vehicle->current_mileage + 5);
    $this->flow->confirm($sale->fresh(), $sale->buyer);

    expect($old->fresh()->status)->toBe(DealStatus::Cancelled);
    Notification::assertSentTo($old->buyer, DealUpdate::class, fn ($n) => str_contains($n->title, 'closed'));
});

it('transfers exactly once even if both sides confirm at the same time', function () {
    $sale = agree(deal());
    tickEverything($sale);
    $this->flow->confirm($sale->fresh(), $sale->seller, $sale->vehicle->current_mileage + 5);
    $this->flow->confirm($sale->fresh(), $sale->buyer);

    // A second, late confirmation (e.g. a double click from a stale page) must not run the transfer again.
    expect(fn () => $this->flow->confirm($sale, $sale->buyer))->toThrow(ValidationException::class);
    expect($sale->vehicle->ownerships()->count())->toBe(2);
});

it('bounds the handover odometer and re-checks it if the car moves on', function () {
    $sale = agree(deal());
    tickEverything($sale);
    $current = $sale->vehicle->current_mileage;

    expect(fn () => $this->flow->confirm($sale->fresh(), $sale->seller, $current + DealFlow::MAX_HANDOVER_MILES + 1))->toThrow(ValidationException::class);

    $this->flow->confirm($sale->fresh(), $sale->seller, $current + 10);
    $sale->vehicle->readings()->create(['reading' => $current + 500, 'recorded_on' => now(), 'source' => 'manual']);
    $sale->vehicle->refreshMileage();

    expect(fn () => $this->flow->confirm($sale->fresh(), $sale->buyer))->toThrow(ValidationException::class);
    expect($sale->fresh()->seller_confirmed_at)->toBeNull()
        ->and($sale->fresh()->status)->toBe(DealStatus::Agreed);
});

it('shows the buyer the seller\'s handover reading, and hides live mileage from everyone else', function () {
    $sale = agree(deal());
    tickEverything($sale);
    $this->flow->confirm($sale->fresh(), $sale->seller, $sale->vehicle->current_mileage + 42);

    Livewire::actingAs($sale->buyer)->test(DealRoom::class, ['deal' => $sale->fresh()])
        ->assertSee(number_format($sale->vehicle->current_mileage + 42))
        ->assertSet('saleMileage', null);
});

it('re-checks an offer under the lock, so a replaced offer cannot be accepted', function () {
    $deal = deal();
    $first = $this->flow->offer($deal, $deal->buyer, 2_000_000);
    $this->flow->offer($deal->fresh(), $deal->buyer, 2_100_000); // replaces the first

    expect(fn () => $this->flow->respond($first, $deal->seller, true))->toThrow(ValidationException::class);
    expect($deal->fresh()->status)->toBe(DealStatus::Open);
});

it('blocks deleting an account mid-sale, and cancels open conversations when it goes', function () {
    $agreed = agree(deal());
    $this->actingAs($agreed->buyer)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrorsIn('userDeletion', 'password');
    expect($agreed->buyer->fresh())->not->toBeNull();

    $open = deal();
    $buyer = $open->buyer;
    $this->actingAs($buyer)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

    $open->refresh();
    expect($open->status)->toBe(DealStatus::Cancelled)->and($open->buyer_id)->toBeNull()
        ->and($open->buyer->publicName())->toBe('Deleted account');
    Notification::assertSentTo($open->seller, DealUpdate::class);
});

it('keeps the buyer\'s record of a completed purchase when the seller deletes their account', function () {
    $sale = agree(deal());
    tickEverything($sale);
    $this->flow->confirm($sale->fresh(), $sale->seller, $sale->vehicle->current_mileage + 5);
    $this->flow->confirm($sale->fresh(), $sale->buyer);

    $this->actingAs($sale->seller)->delete(route('profile.destroy'), ['password' => 'password']);

    expect(Deal::find($sale->id))->not->toBeNull();
    $this->actingAs($sale->buyer)->get(route('deals.show', $sale))->assertOk()->assertSee('Deleted account');
    $this->actingAs($sale->buyer)->get(route('deals.index'))->assertOk();
});
