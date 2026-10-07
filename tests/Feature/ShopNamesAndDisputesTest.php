<?php

use App\Livewire\Vehicle\Settings;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\ShopVerification;
use App\Models\Vehicle;
use App\Services\AccountDeletion;
use App\Services\ShopVerifier;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

function askShop(string $typedName, string $email = 'desk@eastside.test'): ShopVerification
{
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);

    return app(ShopVerifier::class)->request($record, $record->vehicle->owner, $typedName, $email);
}

it('lets a shop correct the name an owner typed for it, the first time it confirms', function () {
    $verification = askShop('Totally Legit Dealer');

    $this->get($verification->signedUrl())->assertOk()->assertSee('Your shop\'s name', false)->assertSee('Totally Legit Dealer');

    $this->post($verification->signedUrl(), [
        'decision' => 'confirm', 'responder_name' => 'Dee', 'business_name' => 'Eastside Euro Specialists',
    ])->assertOk();

    $shop = Shop::firstWhere('email', 'desk@eastside.test');
    expect($shop->name)->toBe('Eastside Euro Specialists')->and($shop->hasConfirmedName())->toBeTrue();
});

it('does not let a later request rename a shop that has named itself', function () {
    $first = askShop('Eastside');
    $this->post($first->signedUrl(), ['decision' => 'confirm', 'responder_name' => 'Dee', 'business_name' => 'Eastside Euro Specialists']);

    $second = askShop('Cheapest Repairs In Town');
    $this->get($second->signedUrl())->assertSee('Answering as')->assertDontSee('Your shop\'s name', false);
    $this->post($second->signedUrl(), ['decision' => 'confirm', 'responder_name' => 'Dee', 'business_name' => 'Hijacked'])->assertOk();

    expect(Shop::firstWhere('email', 'desk@eastside.test')->name)->toBe('Eastside Euro Specialists');
});

it('needs the name to confirm, but not to dispute, and a dispute names nothing', function () {
    $verification = askShop('Totally Legit Dealer');

    $this->post($verification->signedUrl(), ['decision' => 'confirm', 'responder_name' => 'Dee', 'business_name' => ''])
        ->assertSessionHasErrors('business_name');

    $this->post($verification->signedUrl(), ['decision' => 'dispute', 'responder_name' => 'Dee', 'response_note' => 'Not our customer'])->assertOk();

    expect(Shop::firstWhere('email', 'desk@eastside.test')->hasConfirmedName())->toBeFalse();
});

it('counts completing the profile as the shop naming itself', function () {
    $verification = askShop('Eastside');
    $shop = $verification->shop;

    $this->post($shop->editUrl(), ['name' => 'Eastside Euro'])->assertRedirect();

    expect($shop->fresh()->hasConfirmedName())->toBeTrue()->and($shop->fresh()->name)->toBe('Eastside Euro');
});

it('keeps a shop\'s dispute with the car: the owner cannot delete the passport', function () {
    $verification = askShop('Eastside');
    app(ShopVerifier::class)->answer($verification, false, 'Dee', 'Not our invoice', '127.0.0.1');
    $vehicle = $verification->record->vehicle;

    Livewire::actingAs($vehicle->owner)->test(Settings::class, ['vehicle' => $vehicle])
        ->set('confirmVin', substr($vehicle->vin, -6))
        ->call('delete')
        ->assertHasErrors('confirmVin');

    expect(Vehicle::find($vehicle->id))->not->toBeNull();
});

it('keeps a disputed passport, unowned, when its only owner deletes their account', function () {
    $verification = askShop('Eastside');
    app(ShopVerifier::class)->answer($verification, false, 'Dee', 'Not our invoice', '127.0.0.1');
    $vehicle = $verification->record->vehicle;

    app(AccountDeletion::class)->delete($vehicle->owner);

    expect($vehicle->fresh())->not->toBeNull()
        ->and($vehicle->fresh()->user_id)->toBeNull()
        ->and($verification->record->fresh()->disputed_at)->not->toBeNull();
});
