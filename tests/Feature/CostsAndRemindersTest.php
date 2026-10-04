<?php

use App\Enums\OfferStatus;
use App\Livewire\Vehicle\Costs;
use App\Models\ServiceRecord;
use App\Notifications\DocumentExpiring;
use App\Notifications\MaintenanceDue;
use App\Notifications\RecallsFound;
use App\Services\CostReport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('works out running costs, cost per mile and fuel economy', function () {
    $vehicle = car(['current_mileage' => 30000]);
    $ownership = $vehicle->currentOwnership;
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'cost_cents' => 50000, 'mileage' => 29000]);

    Livewire::actingAs($vehicle->owner)->test(Costs::class, ['vehicle' => $vehicle])
        ->set('category', 'fuel')->set('amount', '50')->set('odometer', 29000)->set('volume', '12')->call('add')
        ->set('amount', '$45.50')->set('odometer', 29300)->set('volume', '10')->call('add')
        ->assertHasNoErrors();

    $report = app(CostReport::class)->for($ownership->fresh());

    expect($report['total'])->toBe(59550)
        ->and($report['by_category'])->toBe(['maintenance' => 50000, 'fuel' => 9550])
        ->and($report['economy'])->toBe(['value' => 30.0, 'unit' => 'mpg', 'fills' => 2])
        ->and($report['per_mile'])->toBe(0.03);
});

it('emails maintenance reminders once, not every day', function () {
    Notification::fake();
    $vehicle = car(['current_mileage' => 30000]);
    $vehicle->reminders()->create(['task' => 'Oil', 'interval_miles' => 5000, 'last_done_mileage' => 20000, 'last_done_on' => now()->subMonths(8)]);
    $vehicle->reminders()->create(['task' => 'Coolant', 'interval_miles' => 60000, 'last_done_mileage' => 20000, 'last_done_on' => now()->subMonth()]);

    $this->artisan('passport:send-reminders')->assertSuccessful();
    $this->artisan('passport:send-reminders')->assertSuccessful();

    Notification::assertSentToTimes($vehicle->owner, MaintenanceDue::class, 1);
    Notification::assertSentTo($vehicle->owner, MaintenanceDue::class, fn ($n) => $n->reminders->pluck('task')->all() === ['Oil']);
});

it('warns about expiring documents', function () {
    Notification::fake();
    $vehicle = car();
    $vehicle->documents()->create(['type' => 'insurance', 'name' => 'Card', 'path' => 'x.pdf', 'expires_on' => now()->addDays(10)]);

    $this->artisan('passport:send-reminders');

    Notification::assertSentTo($vehicle->owner, DocumentExpiring::class);
});

it('finds new recalls each week and tells the owner', function () {
    Notification::fake();
    Http::fake(['api.nhtsa.gov/*' => Http::response(['results' => [
        ['NHTSACampaignNumber' => '24V100000', 'Component' => 'AIR BAGS', 'Summary' => 'May not deploy.', 'ReportReceivedDate' => '01/02/2024'],
    ]])]);
    $vehicle = car();

    $this->artisan('passport:sync-recalls')->assertSuccessful();
    $this->artisan('passport:sync-recalls --force')->assertSuccessful();

    expect($vehicle->recalls()->count())->toBe(1)->and($vehicle->fresh()->recalls_checked_at)->not->toBeNull();
    Notification::assertSentToTimes($vehicle->owner, RecallsFound::class, 1);
});

it('expires stale offers', function () {
    $deal = deal();
    $offer = $deal->offers()->create(['user_id' => $deal->buyer_id, 'amount_cents' => 100000, 'status' => OfferStatus::Pending, 'expires_at' => now()->subMinute()]);

    $this->artisan('passport:housekeeping')->assertSuccessful();

    expect($offer->fresh()->status)->toBe(OfferStatus::Expired);
});
