<?php

use App\Enums\ListingStatus;
use App\Livewire\AddVehicle;
use App\Livewire\Vehicle\Share;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DealFlow;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

it('does not show a new owner the previous owner\'s share links', function () {
    $vehicle = car();
    $vehicle->shareLinks()->create(['label' => 'Old owner\'s insurer', 'created_by' => $vehicle->user_id, 'revoked_at' => now()]);
    $buyer = User::factory()->create();
    $vehicle->update(['user_id' => $buyer->id]);

    Livewire::actingAs($buyer)->test(Share::class, ['vehicle' => $vehicle->fresh()])
        ->assertDontSee('Old owner\'s insurer');
});

it('never points the window sign at a private share link', function () {
    $vehicle = car();
    $vehicle->shareLinks()->create(['label' => 'Insurer', 'created_by' => $vehicle->user_id, 'show_costs' => true, 'show_full_vin' => true]);

    $response = $this->actingAs($vehicle->owner)->get(route('vehicles.sign', $vehicle))->assertOk();
    $sign = ShareLink::firstWhere('label', 'Window sign');

    expect($sign)->not->toBeNull()
        ->and($sign->show_costs)->toBeFalse()
        ->and($sign->show_full_vin)->toBeFalse();
    $response->assertViewHas('target', $sign->url());

    // Printing again reuses the same safe link.
    $this->actingAs($vehicle->owner)->get(route('vehicles.sign', $vehicle));
    expect(ShareLink::where('label', 'Window sign')->count())->toBe(1);
});

it('closes a sold listing to its former seller', function () {
    $listing = liveListing(['status' => ListingStatus::Sold]);
    $seller = $listing->seller;
    $listing->vehicle->update(['user_id' => User::factory()->create()->id]);

    $this->actingAs($seller)->get(route('listings.show', $listing))->assertNotFound();
});

it('runs offer notes and cancel reasons through the scam shield', function () {
    Notification::fake();
    $deal = deal();
    $flow = app(DealFlow::class);

    $flow->offer($deal, $deal->buyer, 2_000_000, 'Pay me back the difference via gift cards');
    $flow->cancel($deal->fresh(), $deal->buyer, 'My shipping agent will handle it');

    $flagged = $deal->messages()->whereNotNull('risk_flags')->get();
    expect($flagged)->toHaveCount(2)
        ->and($flagged->every(fn ($m) => $m->user_id === $deal->buyer_id))->toBeTrue()
        ->and($deal->messages()->whereNull('user_id')->pluck('body')->implode(' '))->not->toContain('gift cards');
});

it('answers /shops with array parameters instead of crashing', function () {
    $this->get('/shops?q[]=x&state[]=FL')->assertOk();
});

it('re-checks the VIN at save time', function () {
    Http::fake(['*' => Http::response(null, 500)]);
    $taken = car();

    Livewire::actingAs(User::factory()->create())->test(AddVehicle::class)
        ->set('vin', '1HGCM82633A004352')->call('decode')
        ->set('model', 'Accord')->call('confirmDetails')
        ->set('vin', $taken->vin) // tampered after decoding
        ->set('start_mileage', 1)->set('current_mileage', 2)
        ->call('save')
        ->assertHasErrors('vin')
        ->assertNoRedirect();

    expect(Vehicle::count())->toBe(1);
});

it('locks wizard state that only the server should change', function () {
    Livewire::actingAs(User::factory()->create())->test(AddVehicle::class)->set('step', 3);
})->throws(CannotUpdateLockedPropertyException::class);
