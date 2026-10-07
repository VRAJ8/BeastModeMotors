<?php

use App\Livewire\RecordForm;
use App\Livewire\Vehicle\Costs;
use App\Livewire\Vehicle\History;
use App\Livewire\Vehicle\Sell;
use App\Livewire\Vehicle\Settings;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\Vehicle;
use App\Services\ShopVerifier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->vehicle = car();
    $this->owner = $this->vehicle->owner;
});

it('shows the verifier\'s refusal under the shop email field', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id]);

    Livewire::actingAs($this->owner)->test(History::class, ['vehicle' => $this->vehicle])
        ->call('startVerification', $record->id)
        ->set('shopName', 'My Garage')
        ->set('shopEmail', $this->owner->email)
        ->call('sendVerification')
        ->assertHasErrors('shopEmail')
        ->assertSee('Use the shop');
});

it('explains a missing or absurd amount on an itemised line', function () {
    Livewire::actingAs($this->owner)->test(RecordForm::class, ['vehicle' => $this->vehicle])
        ->set('title', 'Brakes')
        ->set('provider_type', 'diy')
        ->set('mileage', 30100)
        ->set('items', [
            ['description' => 'Pads', 'kind' => 'part', 'amount' => ''],
            ['description' => 'Rotors', 'kind' => 'part', 'amount' => '99999999999'],
        ])
        ->call('save')
        ->assertHasErrors(['items.0.amount', 'items.1.amount'])
        ->assertSee('Enter an amount (0 is fine).');

    expect(ServiceRecord::count())->toBe(0);
});

it('lets the seller keep uploading photos after one is rejected', function () {
    Storage::fake(config('passport.disks.photos'));
    $component = Livewire::actingAs($this->owner)->test(Sell::class, ['vehicle' => $this->vehicle]);

    $component->set('photos', [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
        ->assertHasErrors('photos.0')
        ->assertSet('photos', []);

    $component->set('photos', [UploadedFile::fake()->image('car.jpg')])->assertHasNoErrors();

    expect($this->vehicle->photos()->count())->toBe(1);
});

it('goes back a page after deleting the last expense on the last page', function () {
    foreach (range(1, 11) as $i) {
        $this->vehicle->expenses()->create([
            'ownership_id' => $this->vehicle->currentOwnership->id, 'category' => 'parking',
            'amount_cents' => 100 * $i, 'spent_on' => now()->subDays($i)->toDateString(),
        ]);
    }
    $oldest = $this->vehicle->expenses()->reorder()->orderBy('spent_on')->first();

    Livewire::actingAs($this->owner)->test(Costs::class, ['vehicle' => $this->vehicle])
        ->call('gotoPage', 2)
        ->call('delete', $oldest->id)
        ->assertSet('paginators.page', 1);
});

it('accepts the VIN confirmation in any case', function () {
    $vehicle = car();

    Livewire::actingAs($vehicle->owner)->test(Settings::class, ['vehicle' => $vehicle])
        ->set('confirmVin', strtolower(substr($vehicle->vin, -6)))
        ->call('delete')
        ->assertHasNoErrors();

    expect(Vehicle::find($vehicle->id))->toBeNull();
});

it('shows the verifier\'s refusal when the shop was picked from the directory too', function () {
    $shop = Shop::forEmail('desk@eastside.test', 'Eastside');
    $shop->update(['vetted_at' => now()]);
    $confirmedFor = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $v = app(ShopVerifier::class)->request($confirmedFor, $confirmedFor->vehicle->owner, 'Eastside', 'desk@eastside.test');
    app(ShopVerifier::class)->answer($v, true, 'Dee', null, '127.0.0.1');

    $record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id, 'provider_email' => 'desk@eastside.test']);
    foreach (range(1, 5) as $i) {
        RateLimiter::hit('verify-shop:desk@eastside.test', 86400);
    }

    Livewire::actingAs($this->owner)->test(History::class, ['vehicle' => $this->vehicle])
        ->call('startVerification', $record->id)
        ->assertSet('shopId', $shop->id)
        ->call('sendVerification')
        ->assertHasErrors('shopEmail')
        ->assertSee('Too many verification requests today');
});
