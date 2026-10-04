<?php

use App\Livewire\Vehicle\Share;
use App\Models\ServiceRecord;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->vehicle = car();
    $this->record = ServiceRecord::factory()->create(['vehicle_id' => $this->vehicle->id, 'cost_cents' => 123456, 'title' => 'Clutch replacement']);
    Storage::disk('local')->put('receipt.pdf', '%PDF receipt');
    Storage::disk('local')->put('title.pdf', '%PDF title');
    $this->receipt = $this->record->documents()->create(['vehicle_id' => $this->vehicle->id, 'type' => 'receipt', 'name' => 'Invoice', 'path' => 'receipt.pdf', 'mime' => 'application/pdf']);
    $this->title = $this->vehicle->documents()->create(['type' => 'title', 'name' => 'Title', 'path' => 'title.pdf', 'mime' => 'application/pdf']);
});

it('creates a link with privacy settings from the share tab', function () {
    Livewire::actingAs($this->vehicle->owner)->test(Share::class, ['vehicle' => $this->vehicle])
        ->set('label', 'Insurer')
        ->set('show_costs', true)
        ->set('expires', '7')
        ->call('create')
        ->assertHasNoErrors();

    $link = $this->vehicle->shareLinks()->first();
    expect($link->show_costs)->toBeTrue()->and($link->expires_at->isFuture())->toBeTrue();
});

it('shows the passport without costs or the full VIN by default', function () {
    $link = $this->vehicle->shareLinks()->create(['label' => 'Buyer']);

    $this->get($link->url())
        ->assertOk()
        ->assertSee('Clutch replacement')
        ->assertSee($this->vehicle->maskedVin())
        ->assertDontSee($this->vehicle->vin)
        ->assertDontSee('$1,235');

    expect($link->fresh()->views)->toBe(1);
});

it('shows costs and the full VIN when the owner allows it', function () {
    $link = $this->vehicle->shareLinks()->create(['label' => 'Insurer', 'show_costs' => true, 'show_full_vin' => true]);

    $this->get($link->url())->assertSee('$1,235')->assertSee($this->vehicle->vin);
});

it('stops working when revoked or expired', function () {
    $revoked = $this->vehicle->shareLinks()->create(['label' => 'Old', 'revoked_at' => now()]);
    $expired = $this->vehicle->shareLinks()->create(['label' => 'Old', 'expires_at' => now()->subDay()]);

    $this->get($revoked->url())->assertNotFound();
    $this->get($expired->url())->assertNotFound();
});

it('serves receipts but never personal paperwork through a link', function () {
    $link = $this->vehicle->shareLinks()->create(['label' => 'Buyer']);

    $this->get(route('passport.document', [$link, $this->receipt]))->assertOk();
    $this->get(route('passport.document', [$link, $this->title]))->assertNotFound();

    $link->update(['show_documents' => false]);
    $this->get(route('passport.document', [$link, $this->receipt]))->assertNotFound();
});

it('refuses documents from a different car', function () {
    $link = car()->shareLinks()->create(['label' => 'Other']);

    $this->get(route('passport.document', [$link, $this->receipt]))->assertNotFound();
});

it('lets only the owner download private documents', function () {
    $this->actingAs($this->vehicle->owner)->get(route('documents.show', [$this->vehicle, $this->title]))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('documents.show', [$this->vehicle, $this->title]))->assertForbidden();
    auth()->logout();
    $this->get(route('documents.show', [$this->vehicle, $this->title]))->assertRedirect(route('login'));

    $other = car();
    $this->actingAs($other->owner)->get(route('documents.show', [$other, $this->title]))->assertNotFound();
});

it('produces a PDF report', function () {
    $link = $this->vehicle->shareLinks()->create(['label' => 'Buyer']);

    $this->get(route('passport.pdf', $link))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('prints a window sign with a QR code', function () {
    $this->vehicle->shareLinks()->create(['label' => 'Sign']);

    $this->actingAs($this->vehicle->owner)->get(route('vehicles.sign', $this->vehicle))
        ->assertOk()
        ->assertSee('FOR SALE')
        ->assertSee('<svg', false);
});
