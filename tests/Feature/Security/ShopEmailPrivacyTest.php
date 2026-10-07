<?php

use App\Livewire\RecordForm;
use App\Livewire\Vehicle\History;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Notifications\VerifyServiceRecord;
use App\Services\MaintenancePlanner;
use App\Services\ShopVerifier;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    // A listed shop: it has confirmed work for someone else.
    $other = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $v = app(ShopVerifier::class)->request($other, $other->vehicle->owner, 'Eastside', 'desk@eastside.test');
    app(ShopVerifier::class)->answer($v, true, 'Dee', null, '127.0.0.1');
    $this->shop = tap(Shop::firstWhere('email', 'desk@eastside.test'))->update(['vetted_at' => now()]);

    $this->vehicle = car(['current_mileage' => 30000]);
    $this->record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id, 'provider_name' => 'Eastside', 'provider_email' => 'desk@eastside.test']);
});

it('never puts a listed shop\'s email into the record form', function () {
    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle, 'record' => $this->record])
        ->assertSet('provider_email', '')
        ->assertSet('shopId', $this->shop->id)
        ->assertDontSee('desk@eastside.test');
});

it('never puts a listed shop\'s email into the re-verify dialog', function () {
    Livewire::actingAs($this->vehicle->owner)->test(History::class, ['vehicle' => $this->vehicle])
        ->call('startVerification', $this->record->id)
        ->assertSet('shopEmail', '')
        ->assertDontSee('desk@eastside.test')
        ->call('sendVerification')
        ->assertHasNoErrors();

    expect($this->record->fresh()->pendingVerification->shop_email)->toBe('desk@eastside.test');
});

it('keeps the maintenance checkboxes a list when editing', function () {
    app(MaintenancePlanner::class)->createDefaults($this->vehicle, $this->vehicle->fuel_type);
    $this->record->update(['tasks' => ['Cabin air filter']]);

    $ids = Livewire::actingAs($this->vehicle->owner)
        ->test(RecordForm::class, ['vehicle' => $this->vehicle->fresh(), 'record' => $this->record])
        ->get('reminderIds');

    expect(array_is_list($ids))->toBeTrue()->and($ids)->toHaveCount(1);
});

it('drops the shop when the work is switched to DIY', function () {
    Livewire::actingAs($this->vehicle->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('title', 'Wipers')->set('mileage', 30100)
        ->set('provider_name', 'Eastside')->call('pickShop', $this->shop->id)
        ->set('provider_type', 'diy')
        ->call('save')->assertHasNoErrors();

    $record = ServiceRecord::latest('id')->first();
    expect($record->provider_name)->toBeNull()->and($record->provider_email)->toBeNull();
    Notification::assertSentOnDemandTimes(VerifyServiceRecord::class, 1); // only the setup request
});
