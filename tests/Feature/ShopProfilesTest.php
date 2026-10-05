<?php

use App\Enums\VerificationStatus;
use App\Filament\Resources\Shops\Pages\ManageShops;
use App\Livewire\RecordForm;
use App\Livewire\Vehicle\History;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\VerifyServiceRecord;
use App\Services\ShopVerifier;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

/**
 * A shop that has confirmed one record, answering after the given number of hours.
 */
function confirmedShop(string $email = 'desk@eastside.test', string $name = 'Eastside Euro', float $hours = 3): Shop
{
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id, 'title' => 'Brake fluid flush']);
    $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, $name, $email);
    test()->travel((int) ($hours * 60))->minutes();
    app(ShopVerifier::class)->answer($verification, true, 'Dee', null, '127.0.0.1');
    test()->travelBack();

    return Shop::firstWhere('email', strtolower($email));
}

it('creates one shop per email and links confirmed records to it', function () {
    $shop = confirmedShop('Desk@Eastside.test');
    $again = confirmedShop('desk@eastside.test', 'Eastside Euro Specialists');

    expect(Shop::count())->toBe(1)
        ->and($again->id)->toBe($shop->id)
        ->and($shop->name)->toBe('Eastside Euro')
        ->and(ServiceRecord::where('shop_id', $shop->id)->count())->toBe(2);
});

it('lists only shops that have confirmed work', function () {
    $listed = confirmedShop();

    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $pending = app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Never Answers Garage', 'nope@garage.test');

    $this->get(route('shops.index'))->assertOk()->assertSee('Eastside Euro')->assertDontSee('Never Answers Garage');
    $this->get(route('shops.show', $listed))->assertOk()->assertSee('Brake fluid flush');
    $this->get(route('shops.show', $pending->shop))->assertNotFound();

    $listed->update(['is_listed' => false]);
    $this->get(route('shops.show', $listed))->assertNotFound();
});

it('never shows owners, VINs or prices on a shop page', function () {
    $shop = confirmedShop();
    $record = ServiceRecord::where('shop_id', $shop->id)->first();
    $record->update(['cost_cents' => 98765]);

    $this->get(route('shops.show', $shop))
        ->assertDontSee($record->vehicle->vin)
        ->assertDontSee($record->vehicle->owner->name)
        ->assertDontSee('$987');
});

it('earns the fast badge after three quick answers, not before', function () {
    $shop = confirmedShop(hours: 2);
    confirmedShop(hours: 3);
    expect($shop->fresh()->stats()['fast'])->toBeFalse();

    confirmedShop(hours: 5);
    $stats = $shop->fresh()->stats();
    expect($stats['fast'])->toBeTrue()->and($stats['median_hours'])->toBe(3.0)->and($stats['response_rate'])->toBe(100);
});

it('counts requests that expire unanswered against the response rate', function () {
    $shop = confirmedShop();
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside Euro', 'desk@eastside.test');

    $this->travel(15)->days();
    $this->artisan('passport:housekeeping');

    expect($shop->fresh()->verifications()->where('status', VerificationStatus::Expired)->count())->toBe(1)
        ->and($shop->fresh()->stats()['response_rate'])->toBe(50);
});

it('lets the shop edit its profile through a signed link only', function () {
    $shop = confirmedShop();

    $this->get(route('shops.edit', $shop))->assertForbidden();
    $this->get($shop->editUrl())->assertOk()->assertSee($shop->email);

    $this->post($shop->editUrl(), [
        'name' => 'Eastside Euro Specialists', 'city' => 'Miami', 'state' => 'FL',
        'website' => 'https://eastside.example', 'specialties' => 'Porsche, BMW, , Audi',
    ])->assertRedirect();

    $shop->refresh();
    expect($shop->name)->toBe('Eastside Euro Specialists')
        ->and($shop->specialties)->toBe(['Porsche', 'BMW', 'Audi'])
        ->and($shop->profile_completed_at)->not->toBeNull();

    $this->get(route('shops.show', $shop))->assertSee('Miami, FL')->assertSee('Audi');
});

it('offers the profile link after a shop confirms', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');

    $this->post($verification->signedUrl(), ['decision' => 'confirm', 'responder_name' => 'Dee'])
        ->assertSee('Complete your profile');
});

it('lets owners pick a listed shop without ever seeing its email', function () {
    $shop = confirmedShop();
    $vehicle = car(['current_mileage' => 30000]);
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'provider_email' => null]);

    Livewire::actingAs($vehicle->owner)->test(History::class, ['vehicle' => $vehicle])
        ->call('startVerification', $record->id)
        ->set('shopName', 'east')
        ->assertSee('Eastside Euro')
        ->assertDontSee('desk@eastside.test')
        ->call('pickShop', $shop->id)
        ->assertSee('d•••@eastside.test')
        ->call('sendVerification')
        ->assertHasNoErrors();

    Notification::assertSentOnDemand(VerifyServiceRecord::class, fn ($n, $c, $notifiable) => array_key_first($notifiable->routes['mail']) === 'desk@eastside.test');

    Livewire::actingAs($vehicle->owner)->test(RecordForm::class, ['vehicle' => $vehicle])
        ->set('title', 'Oil service')->set('mileage', 30200)
        ->set('provider_name', 'eastside')
        ->call('pickShop', $shop->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceRecord::latest('id')->first()->provider_email)->toBe('desk@eastside.test');
});

it('will not let owners pick an unlisted shop', function () {
    $shop = confirmedShop();
    $shop->update(['is_listed' => false]);
    $vehicle = car();

    Livewire::actingAs($vehicle->owner)->test(RecordForm::class, ['vehicle' => $vehicle])
        ->call('pickShop', $shop->id)
        ->assertNotFound();
});

it('links verified records on a passport to the shop', function () {
    $shop = confirmedShop();
    $record = ServiceRecord::where('shop_id', $shop->id)->first();
    $link = $record->vehicle->shareLinks()->create(['label' => 'Buyer']);

    $this->get($link->url())->assertSee(route('shops.show', $shop), false);
});

it('can be moderated by staff', function () {
    $shop = confirmedShop();

    Livewire::actingAs(User::factory()->admin()->create())->test(ManageShops::class)
        ->assertCanSeeTableRecords([$shop])
        ->callTableAction('edit', $shop, ['name' => 'Eastside (renamed)', 'is_listed' => false]);

    expect($shop->fresh()->name)->toBe('Eastside (renamed)')->and($shop->fresh()->is_listed)->toBeFalse();
});
