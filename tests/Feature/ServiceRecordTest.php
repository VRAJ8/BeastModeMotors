<?php

use App\Livewire\RecordForm;
use App\Livewire\Vehicle\History;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Notifications\VerifyServiceRecord;
use App\Services\MaintenancePlanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();
    $this->vehicle = car(['current_mileage' => 30000]);
    app(MaintenancePlanner::class)->createDefaults($this->vehicle, $this->vehicle->fuel_type);
    $this->owner = $this->vehicle->owner;
});

it('logs a record with a receipt, an odometer reading and the maintenance it covered', function () {
    $oil = $this->vehicle->reminders()->firstWhere('task', 'Engine oil & filter');

    Livewire::actingAs($this->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('title', '30k service')
        ->set('performed_on', now()->subDays(3)->toDateString())
        ->set('mileage', 31250)
        ->set('provider_name', 'Main Street Auto')
        ->set('reminderIds', [$oil->id])
        ->set('receipts', [UploadedFile::fake()->create('invoice.pdf', 120, 'application/pdf')])
        ->set('items', [['description' => 'Oil', 'kind' => 'part', 'amount' => '64.50'], ['description' => 'Labor', 'kind' => 'labor', 'amount' => '80']])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('vehicles.history', $this->vehicle));

    $record = ServiceRecord::first();

    expect($record->cost_cents)->toBe(14450)
        ->and($record->evidence())->toBe('documented')
        ->and($record->tasks)->toBe(['Engine oil & filter'])
        ->and($record->reading->reading)->toBe(31250)
        ->and($this->vehicle->fresh()->current_mileage)->toBe(31250)
        ->and($oil->fresh()->last_done_mileage)->toBe(31250);

    Storage::disk('local')->assertExists($record->documents->first()->path);
});

it('asks for confirmation before saving a reading lower than an earlier one', function () {
    $component = Livewire::actingAs($this->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('title', 'Wipers')
        ->set('provider_type', 'diy')
        ->set('performed_on', now()->toDateString())
        ->set('mileage', 21000)
        ->call('save')
        ->assertHasErrors('mileage');

    $component->set('confirmLowerMileage', true)->call('save')->assertHasNoErrors();
});

it('emails the shop a verification request when asked', function () {
    Livewire::actingAs($this->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('title', 'Brake pads')
        ->set('performed_on', now()->toDateString())
        ->set('mileage', 30100)
        ->set('provider_name', 'Main Street Auto')
        ->set('provider_email', 'service@mainstreet.test')
        ->set('requestVerification', true)
        ->call('save');

    Notification::assertSentOnDemand(VerifyServiceRecord::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === ['service@mainstreet.test' => 'Main Street Auto']);
    expect(ServiceRecord::first()->pendingVerification)->not->toBeNull();
});

it('locks the facts of a shop-verified record', function () {
    $record = ServiceRecord::factory()->verified()->create(['vehicle_id' => $this->vehicle->id, 'mileage' => 29000, 'title' => 'Timing belt']);

    Livewire::actingAs($this->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle, 'record' => $record])
        ->set('title', 'Something else')
        ->set('mileage', 1)
        ->set('description', 'Added a note')
        ->call('save');

    $record->refresh();
    expect($record->title)->toBe('Timing belt')->and($record->mileage)->toBe(29000)->and($record->description)->toBe('Added a note');
});

it('deletes a record together with its reading and receipts', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id, 'mileage' => 30500]);
    Storage::disk('local')->put('r.pdf', 'x');
    $record->documents()->create(['vehicle_id' => $this->vehicle->id, 'type' => 'receipt', 'name' => 'r', 'path' => 'r.pdf']);

    Livewire::actingAs($this->owner)->test(History::class, ['vehicle' => $this->vehicle])->call('delete', $record->id);

    expect(ServiceRecord::count())->toBe(0)
        ->and($this->vehicle->readings()->whereNotNull('service_record_id')->count())->toBe(0);
    Storage::disk('local')->assertMissing('r.pdf');
});

it('does not let someone else touch the records', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);
    $stranger = User::factory()->create();

    $this->actingAs($stranger)->get(route('vehicles.history', $this->vehicle))->assertForbidden();
    $this->actingAs($stranger)->get(route('records.edit', [$this->vehicle, $record]))->assertForbidden();
    Livewire::actingAs($stranger)->test(History::class, ['vehicle' => $this->vehicle])->assertForbidden();
});
