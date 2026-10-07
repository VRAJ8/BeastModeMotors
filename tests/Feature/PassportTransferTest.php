<?php

use App\Enums\AcquiredVia;
use App\Enums\DealStatus;
use App\Enums\ListingStatus;
use App\Enums\OdometerSource;
use App\Enums\OdometerStatus;
use App\Livewire\AcceptTransfer;
use App\Livewire\Vehicle\Transfer;
use App\Models\PassportTransfer;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\DealUpdate;
use App\Notifications\TransferUpdate;
use App\Services\DealFlow;
use App\Services\PassportTransfers;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Notification::fake();
    $this->transfers = app(PassportTransfers::class);
});

/** @return array{0: PassportTransfer, 1: string} */
function transferLink(Vehicle $vehicle, int $extraMiles = 20, OdometerStatus $status = OdometerStatus::Actual): array
{
    return app(PassportTransfers::class)->create($vehicle, $vehicle->owner, $vehicle->current_mileage + $extraMiles, $status);
}

function vinTail(Vehicle $vehicle): string
{
    return substr($vehicle->vin, -PassportTransfers::VIN_TAIL);
}

it('makes a one-time link and keeps only a hash of it', function () {
    $vehicle = car();
    [$transfer, $token] = transferLink($vehicle);

    expect(strlen($token))->toBe(40)
        ->and($transfer->token_hash)->toBe(hash('sha256', $token))
        ->and(PassportTransfer::where('token_hash', $token)->exists())->toBeFalse()
        ->and($this->transfers->find($token)?->is($transfer))->toBeTrue()
        ->and($transfer->isPending())->toBeTrue()
        ->and($transfer->expires_at->isSameDay(now()->addDays(PassportTransfers::VALID_DAYS)))->toBeTrue();

    // A new link replaces the old one.
    [$second] = transferLink($vehicle);
    expect($transfer->fresh()->status())->toBe('cancelled')->and($second->isPending())->toBeTrue();
});

it('bounds the handover reading and refuses while a marketplace sale is agreed', function () {
    $vehicle = car();
    $current = $vehicle->current_mileage;

    expect(fn () => $this->transfers->create($vehicle, $vehicle->owner, $current - 1, OdometerStatus::Actual))->toThrow(ValidationException::class)
        ->and(fn () => $this->transfers->create($vehicle, $vehicle->owner, $current + DealFlow::MAX_HANDOVER_MILES + 1, OdometerStatus::Actual))->toThrow(ValidationException::class)
        ->and(fn () => $this->transfers->create($vehicle, User::factory()->create(), $current, OdometerStatus::Actual))->toThrow(HttpException::class);

    $deal = deal();
    $deal->update(['status' => DealStatus::Agreed]);
    expect(fn () => transferLink($deal->vehicle))->toThrow(ValidationException::class);
});

it('moves the passport to whoever accepts with the right VIN ending', function () {
    $listing = liveListing();
    $vehicle = $listing->vehicle;
    $seller = $vehicle->owner;
    $open = deal($listing);
    $vehicle->documents()->create(['type' => 'insurance', 'name' => 'Policy', 'path' => 'x.pdf']);
    [$transfer] = transferLink($vehicle, 40, OdometerStatus::ExceedsLimits);
    $buyer = User::factory()->create();

    expect(fn () => $this->transfers->accept($transfer, $buyer, 'ZZZZZZ', AcquiredVia::PrivateSale, null))->toThrow(ValidationException::class);

    $this->transfers->accept($transfer, $buyer, strtolower(vinTail($vehicle)), AcquiredVia::PrivateSale, 1_850_000);
    $vehicle->refresh();
    $ownership = $vehicle->currentOwnership;

    expect($vehicle->user_id)->toBe($buyer->id)
        ->and($ownership->user_id)->toBe($buyer->id)
        ->and($ownership->acquired_via)->toBe(AcquiredVia::PrivateSale)
        ->and($ownership->purchase_price_cents)->toBe(1_850_000)
        ->and($ownership->start_mileage)->toBe($transfer->sale_mileage)
        ->and($vehicle->ownerships()->where('user_id', $seller->id)->first()->ended_on)->not->toBeNull()
        ->and($vehicle->readings()->where('source', OdometerSource::Sale)->value('reading'))->toBe($transfer->sale_mileage)
        ->and($vehicle->documents()->where('type', 'insurance')->exists())->toBeFalse()
        ->and($transfer->fresh()->status())->toBe('accepted')
        ->and($transfer->fresh()->to_user_id)->toBe($buyer->id)
        ->and($open->fresh()->status)->toBe(DealStatus::Cancelled)
        ->and($open->fresh()->cancel_reason)->toBe('The car was sold outside Beast Mode Motors')
        ->and($listing->fresh()->status)->toBe(ListingStatus::Withdrawn);

    Notification::assertSentTo($seller, TransferUpdate::class, fn ($n) => str_contains($n->title, 'accepted'));
    Notification::assertSentTo($open->buyer, DealUpdate::class);

    // Single use.
    expect(fn () => $this->transfers->accept($transfer->fresh(), User::factory()->create(), vinTail($vehicle), AcquiredVia::PrivateSale, null))->toThrow(ValidationException::class);
});

it('refuses links that are expired, your own, stale, or overtaken by a sale', function () {
    $vehicle = car();
    $buyer = User::factory()->create();

    [$own] = transferLink($vehicle);
    expect($this->transfers->problem($own, $vehicle->owner))->toContain('your own');

    $own->update(['expires_at' => now()->subMinute()]);
    expect($this->transfers->problem($own->fresh(), $buyer))->toContain('expired');

    [$stale] = transferLink($vehicle, 10);
    $vehicle->readings()->create(['reading' => $vehicle->current_mileage + 500, 'recorded_on' => today(), 'source' => 'manual']);
    $vehicle->refreshMileage();
    expect(fn () => $this->transfers->accept($stale, $buyer, vinTail($vehicle), AcquiredVia::PrivateSale, null))
        ->toThrow(ValidationException::class, 'out of date');

    // Sold through the marketplace while a link was out: the link dies with the sale.
    $listing = liveListing();
    [$link] = transferLink($listing->vehicle);
    $deal = deal($listing);
    $flow = app(DealFlow::class);
    $flow->respond($flow->offer($deal, $deal->buyer, 2_000_000), $deal->seller, true);
    expect($this->transfers->problem($link->fresh(), $buyer))->toContain('agreed a sale');

    foreach (config('passport.handover') as $key => $item) {
        if ($item['required']) {
            $flow->toggleHandover($deal->fresh(), $item['by'] === 'buyer' ? $deal->buyer : $deal->seller, $key);
        }
    }
    $flow->confirm($deal->fresh(), $deal->seller, $listing->vehicle->fresh()->current_mileage + 5);
    $flow->confirm($deal->fresh(), $deal->buyer);

    expect($link->fresh()->status())->toBe('cancelled');
});

it('lets the recipient decline, and tells the owner', function () {
    $vehicle = car();
    [$transfer] = transferLink($vehicle);
    $stranger = User::factory()->create();

    Livewire::actingAs($stranger)->test(AcceptTransfer::class, ['transfer' => $transfer])
        ->assertSee('Accept the passport')
        ->assertDontSee($vehicle->vin)
        ->call('decline')
        ->assertSee('declined');

    expect($transfer->fresh()->status())->toBe('declined')->and($vehicle->fresh()->user_id)->toBe($vehicle->user_id);
    Notification::assertSentTo($vehicle->owner, TransferUpdate::class, fn ($n) => str_contains($n->title, 'declined'));
});

it('accepts through the page, with only a few tries at the VIN', function () {
    $vehicle = car();
    [$transfer] = transferLink($vehicle);
    $buyer = User::factory()->create();
    $page = Livewire::actingAs($buyer)->test(AcceptTransfer::class, ['transfer' => $transfer]);

    foreach (range(1, 5) as $try) {
        $page->set('vinTail', 'AAAAAA')->call('accept')->assertHasErrors('vin_tail');
    }

    $page->set('vinTail', vinTail($vehicle))->call('accept')->assertHasErrors('vin_tail');
    expect($vehicle->fresh()->user_id)->not->toBe($buyer->id);

    RateLimiter::clear('transfer-vin:'.$transfer->id);
    $page->set('vinTail', vinTail($vehicle))->set('acquiredVia', 'dealer')->set('price', '')->call('accept')
        ->assertHasNoErrors()
        ->assertRedirect(route('vehicles.show', $vehicle));

    expect($vehicle->fresh()->user_id)->toBe($buyer->id)
        ->and($vehicle->fresh()->currentOwnership->acquired_via)->toBe(AcquiredVia::Dealer)
        ->and($vehicle->fresh()->currentOwnership->purchase_price_cents)->toBeNull();
});

it('lets the owner make, see once, and cancel a link from the Sell page', function () {
    $vehicle = car();

    $page = Livewire::actingAs($vehicle->owner)->test(Transfer::class, ['vehicle' => $vehicle])
        ->assertSee('Transfer the passport')
        ->call('start')
        ->assertSet('saleMileage', $vehicle->current_mileage)
        ->set('odometerStatus', 'bogus')->call('create')->assertHasErrors('odometer_status')
        ->set('odometerStatus', 'actual')->call('create')->assertHasNoErrors();

    $link = $page->get('link');
    expect($link)->toStartWith(url('/transfer/'))
        ->and($this->transfers->find(basename($link))?->vehicle_id)->toBe($vehicle->id);
    $page->assertSee($link);

    // After a reload the link itself is gone; only that one is waiting.
    Livewire::actingAs($vehicle->owner)->test(Transfer::class, ['vehicle' => $vehicle])
        ->assertDontSee($link)
        ->assertSee('waiting to be accepted')
        ->call('cancel');

    expect($vehicle->transfers()->first()->status())->toBe('cancelled');

    Livewire::actingAs(User::factory()->create())->test(Transfer::class, ['vehicle' => $vehicle])->assertForbidden();
});

it('shows guests the offer and brings them back after they sign up', function () {
    $vehicle = car();
    [, $token] = transferLink($vehicle);
    $url = route('transfers.show', $token);

    $this->get($url)->assertOk()->assertSee($vehicle->title())->assertSee('Create an account')->assertDontSee($vehicle->vin);
    $this->get(route('transfers.show', str_repeat('x', 40)))->assertNotFound();

    $this->post(route('register'), [
        'name' => 'New Owner', 'email' => 'new-owner@example.com', 'password' => 'password-123', 'password_confirmation' => 'password-123',
    ])->assertRedirect($url);

    $this->get($url)->assertOk()->assertSee('Accept the passport');
});

it('gives the odometer disclosure to the two people in the transfer only', function () {
    $vehicle = car(['year' => 2019]);
    [$transfer] = transferLink($vehicle);
    $url = route('transfers.odometer-disclosure', $transfer);
    $buyer = User::factory()->create();

    $this->actingAs($vehicle->owner)->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($buyer)->get($url)->assertForbidden();

    $this->transfers->accept($transfer, $buyer, vinTail($vehicle), AcquiredVia::PrivateSale, null);
    $this->actingAs($buyer)->get($url)->assertOk();

    $old = car(['year' => 2006]);
    [$exempt] = transferLink($old);
    $this->actingAs($old->owner)->get(route('transfers.odometer-disclosure', $exempt))->assertNotFound();
});
