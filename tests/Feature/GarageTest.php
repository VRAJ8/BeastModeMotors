<?php

use App\Enums\TestDriveStatus;
use App\Livewire\VehicleActions;
use App\Models\TestDrive;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\PriceDropped;
use App\Support\CompareList;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('requires sign-in for the garage', function () {
    $this->get(route('garage'))->assertRedirect(route('login'));
});

it('shows saved cars and bookings in the garage', function () {
    $user = User::factory()->create();
    $saved = Vehicle::factory()->create(['model' => 'Chiron']);
    $user->favorites()->attach($saved);
    TestDrive::factory()->for($user)->create(['scheduled_at' => now()->addDays(2)]);

    $this->actingAs($user)->get(route('garage'))
        ->assertOk()
        ->assertSee('Chiron')
        ->assertSee('Pending');
});

it('sends guests to log in when saving a car', function () {
    $vehicle = Vehicle::factory()->create();

    Livewire::test(VehicleActions::class, ['vehicle' => $vehicle])
        ->call('toggleFavorite')
        ->assertRedirect(route('login'));
});

it('toggles a saved car', function () {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)->test(VehicleActions::class, ['vehicle' => $vehicle])->call('toggleFavorite');
    expect($user->hasFavorited($vehicle))->toBeTrue();

    Livewire::actingAs($user)->test(VehicleActions::class, ['vehicle' => $vehicle])->call('toggleFavorite');
    expect($user->hasFavorited($vehicle))->toBeFalse();
});

it('alerts customers who saved a car when its price drops', function () {
    Notification::fake();
    $fan = User::factory()->create();
    $other = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['price' => 300000]);
    $fan->favorites()->attach($vehicle);

    $vehicle->update(['price' => 285000]);

    expect($vehicle->fresh()->previous_price)->toBe(300000)
        ->and($vehicle->fresh()->has_price_drop)->toBeTrue();
    Notification::assertSentTo($fan, PriceDropped::class);
    Notification::assertNotSentTo($other, PriceDropped::class);
});

it('does not alert on price increases', function () {
    Notification::fake();
    $fan = User::factory()->create();
    $vehicle = Vehicle::factory()->create(['price' => 300000]);
    $fan->favorites()->attach($vehicle);

    $vehicle->update(['price' => 310000]);

    expect($vehicle->fresh()->previous_price)->toBeNull();
    Notification::assertNothingSent();
});

it('caps the compare list at three cars', function () {
    $vehicles = Vehicle::factory()->count(4)->create();

    foreach ($vehicles->take(3) as $vehicle) {
        Livewire::test(VehicleActions::class, ['vehicle' => $vehicle])->call('toggleCompare')->assertDispatched('compare-updated');
    }

    Livewire::test(VehicleActions::class, ['vehicle' => $vehicles[3]])
        ->call('toggleCompare')
        ->assertNotDispatched('compare-updated');

    expect(app(CompareList::class)->count())->toBe(3);
    $this->get(route('compare'))->assertOk()->assertSee($vehicles[0]->model);
});

it('lets customers cancel their own upcoming test drive', function () {
    Notification::fake();
    $user = User::factory()->create();
    $drive = TestDrive::factory()->for($user)->create(['scheduled_at' => now()->addDays(2)]);

    $this->actingAs($user)
        ->patch(route('garage.test-drives.cancel', $drive))
        ->assertRedirect();

    expect($drive->fresh()->status)->toBe(TestDriveStatus::Cancelled);
});

it('forbids cancelling someone else\'s test drive', function () {
    $drive = TestDrive::factory()->for(User::factory())->create();

    $this->actingAs(User::factory()->create())
        ->patch(route('garage.test-drives.cancel', $drive))
        ->assertForbidden();
});
