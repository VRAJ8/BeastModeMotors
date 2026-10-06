<?php

use App\Models\ServiceRecord;
use App\Notifications\VerifyServiceRecord;
use App\Services\ShopVerifier;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('cannot break out of the listing JSON-LD script block', function () {
    $listing = liveListing(['description' => 'Great car </script><script>alert(document.cookie)</script> honest']);
    $listing->vehicle->update(['trim' => '</script><img src=x onerror=alert(1)>']);

    $html = $this->get(route('listings.show', $listing))->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(document.cookie)</script>')
        ->and($html)->not->toContain('<img src=x onerror=alert(1)>');
});

it('escapes owner-typed text in the email sent to a shop', function () {
    $record = ServiceRecord::factory()->create([
        'vehicle_id' => car()->id,
        'title' => 'Oil change [Click to claim your refund](https://evil.example) <img src=x>',
    ]);
    $verification = $record->verifications()->create(['shop_name' => '**Urgent** <b>Shop</b>', 'shop_email' => 's@shop.test', 'status' => 'pending', 'expires_at' => now()->addDay(), 'requested_by' => $record->vehicle->user_id]);

    $html = (string) (new VerifyServiceRecord($verification))->toMail(new AnonymousNotifiable)->render();

    expect($html)->not->toContain('href="https://evil.example"')
        ->and($html)->not->toContain('<img src=x>')
        ->and($html)->not->toContain('<b>Shop</b>')
        ->and($html)->toContain('Click to claim your refund');
});

it('caps how many verification emails an owner can send per day', function () {
    Notification::fake();
    $vehicle = car();

    foreach (range(1, 10) as $i) {
        $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);
        app(ShopVerifier::class)->request($record, $vehicle->owner, "Shop {$i}", "shop{$i}@shop.test");
    }

    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);
    expect(fn () => app(ShopVerifier::class)->request($record, $vehicle->owner, 'Shop 11', 'shop11@shop.test'))
        ->toThrow(ValidationException::class);
});
