<?php

use App\Models\ServiceRecord;
use App\Models\User;
use App\Notifications\VerifyServiceRecord;
use App\Services\ShopVerifier;
use Illuminate\Mail\Markdown;
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

it('sends a plain-text part that reads like the email, without markdown escapes', function () {
    Notification::fake();
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id, 'title' => 'Brake pads & rotors (front)']);
    $record->vehicle->owner->update(['name' => 'Alex O\'Brien']);
    $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');

    $mail = (new VerifyServiceRecord($verification))->toMail(new AnonymousNotifiable);
    $text = (string) app(Markdown::class)->renderText('notifications::email', $mail->data());

    expect($text)->toContain('Brake pads & rotors (front)')
        ->and($text)->toContain('Alex O\'Brien has logged work')
        ->and($text)->not->toContain('\\(')
        ->and($text)->not->toContain('**');
});

it('gives everyone a sensible short public name, whatever they typed', function (string $name, string $public) {
    expect(User::factory()->create(['name' => $name])->publicName())->toBe($public);
})->with([
    ['Alex Rivera', 'Alex R.'],
    ['Cher', 'Cher'],
    ['Alex O\'Brien [*test*] <b>x</b>.', 'Alex X.'],
    ['Sam 123 !!!', 'Sam'],
    ['Zoë Ångström', 'Zoë Å.'],
]);
