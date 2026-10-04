<?php

use App\Enums\VerificationStatus;
use App\Models\ServiceRecord;
use App\Notifications\VerificationAnswered;
use App\Services\ShopVerifier;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $this->owner = $this->record->vehicle->owner;
    $this->verification = app(ShopVerifier::class)->request($this->record, $this->owner, 'Main Street Auto', 'service@mainstreet.test');
});

it('shows the shop the record through a signed link', function () {
    $this->get($this->verification->signedUrl())
        ->assertOk()
        ->assertSee($this->record->title)
        ->assertSee($this->record->vehicle->vin);
});

it('refuses unsigned or tampered links', function () {
    $this->get(route('verify.show', $this->verification))->assertForbidden();
    $this->get($this->verification->signedUrl().'x')->assertForbidden();
});

it('stamps the record when the shop confirms and tells the owner', function () {
    $this->post($this->verification->signedUrl(), ['decision' => 'confirm', 'responder_name' => 'Dee'])
        ->assertOk()
        ->assertSee('Thank you');

    expect($this->record->fresh()->evidence())->toBe('verified')
        ->and($this->verification->fresh()->status)->toBe(VerificationStatus::Confirmed)
        ->and($this->verification->fresh()->responder_ip)->toBe('127.0.0.1');

    Notification::assertSentTo($this->owner, VerificationAnswered::class);
});

it('requires a reason to dispute, then marks the record disputed', function () {
    $this->post($this->verification->signedUrl(), ['decision' => 'dispute', 'responder_name' => 'Dee'])
        ->assertSessionHasErrors('response_note');

    $this->post($this->verification->signedUrl(), ['decision' => 'dispute', 'responder_name' => 'Dee', 'response_note' => 'Not our invoice'])->assertOk();

    expect($this->record->fresh()->evidence())->toBe('disputed');
});

it('can only be answered once', function () {
    $this->post($this->verification->signedUrl(), ['decision' => 'confirm', 'responder_name' => 'Dee']);
    $this->post($this->verification->signedUrl(), ['decision' => 'dispute', 'responder_name' => 'Eve', 'response_note' => 'x'])->assertStatus(410);

    expect($this->record->fresh()->evidence())->toBe('verified');
});

it('expires', function () {
    $url = URL::temporarySignedRoute('verify.show', now()->addMinute(), ['verification' => $this->verification->id]);
    $this->travel(2)->minutes();

    $this->get($url)->assertForbidden();
});

it('will not send a second request while one is pending, or to the owner themselves', function () {
    expect(fn () => app(ShopVerifier::class)->request($this->record, $this->owner, 'Again', 'again@shop.test'))->toThrow(ValidationException::class);

    $other = ServiceRecord::factory()->create(['vehicle_id' => $this->record->vehicle_id]);
    expect(fn () => app(ShopVerifier::class)->request($other, $this->owner, 'Me', $this->owner->email))->toThrow(ValidationException::class);
});

it('does not offer verification for DIY work', function () {
    $diy = ServiceRecord::factory()->create(['vehicle_id' => $this->record->vehicle_id, 'provider_type' => 'diy']);

    expect($diy->canRequestVerification())->toBeFalse();
});
