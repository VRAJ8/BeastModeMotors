<?php

use App\Enums\AcquiredVia;
use App\Enums\DealStatus;
use App\Enums\ListingStatus;
use App\Enums\OfferStatus;
use App\Livewire\DealRoom;
use App\Livewire\RecordForm;
use App\Livewire\Vehicle\History;
use App\Models\Deal;
use App\Models\Expense;
use App\Models\ServiceRecord;
use App\Models\User;
use App\Notifications\DealUpdate;
use App\Services\DealFlow;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->flow = app(DealFlow::class);
});

function agreedDeal(): Deal
{
    $deal = deal();
    $flow = app(DealFlow::class);
    $offer = $flow->offer($deal, $deal->buyer, 2_300_000);
    $flow->respond($offer, $deal->seller, true);

    return $deal->fresh();
}

function tickAll(Deal $deal): void
{
    foreach (config('passport.handover') as $key => $item) {
        if ($item['required']) {
            app(DealFlow::class)->toggleHandover($deal->fresh(), $item['by'] === 'buyer' ? $deal->buyer : $deal->seller, $key);
        }
    }
}

it('starts a deal from a listing and notifies the seller', function () {
    $listing = liveListing();
    $buyer = User::factory()->create();

    $deal = $this->flow->start($listing, $buyer, 'Is it still available?');

    expect($deal->status)->toBe(DealStatus::Open)->and($deal->messages()->count())->toBe(1);
    Notification::assertSentTo($listing->seller, DealUpdate::class);

    // Contacting again reuses the same deal.
    expect($this->flow->start($listing, $buyer, 'Hello again?')->id)->toBe($deal->id);
});

it('does not let sellers start deals on their own car', function () {
    $listing = liveListing();

    expect(fn () => $this->flow->start($listing, $listing->seller, 'Hi me'))->toThrow(ValidationException::class);
});

it('flags risky messages for the recipient', function () {
    $deal = deal();
    $message = $this->flow->post($deal, $deal->buyer, 'I am deployed overseas, my shipping agent will collect it.');

    expect(collect($message->risk_flags)->pluck('key'))->toContain('remote_seller', 'fake_escrow');

    Livewire::actingAs($deal->seller)->test(DealRoom::class, ['deal' => $deal])
        ->assertSee('Says they can\'t meet in person');
});

it('replaces the open offer with a counter', function () {
    $deal = deal();
    $first = $this->flow->offer($deal, $deal->buyer, 2_000_000);
    $counter = $this->flow->offer($deal->fresh(), $deal->seller, 2_250_000);

    expect($first->fresh()->status)->toBe(OfferStatus::Countered)
        ->and($counter->status)->toBe(OfferStatus::Pending)
        ->and(fn () => $this->flow->respond($counter, $deal->seller, true))->toThrow(ValidationException::class);
});

it('agrees a price, marks the listing pending and tells other buyers', function () {
    $deal = deal();
    $other = deal($deal->listing);

    $offer = $this->flow->offer($deal, $deal->buyer, 2_300_000);
    $this->flow->respond($offer, $deal->seller, true);

    expect($deal->fresh()->status)->toBe(DealStatus::Agreed)
        ->and($deal->fresh()->agreed_price_cents)->toBe(2_300_000)
        ->and($deal->listing->fresh()->status)->toBe(ListingStatus::Pending)
        ->and($deal->inspection()->exists())->toBeTrue()
        ->and($other->messages()->whereNull('user_id')->count())->toBe(1);

    // The other buyer can no longer make offers.
    expect(fn () => $this->flow->offer($other->fresh(), $other->buyer, 2_400_000))->toThrow(ValidationException::class);
});

it('puts the car back on the market when an agreed deal is cancelled', function () {
    $deal = agreedDeal();
    $this->flow->cancel($deal, $deal->buyer, 'Inspection found rust');

    expect($deal->fresh()->status)->toBe(DealStatus::Cancelled)
        ->and($deal->listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('only lets each person tick their own handover steps', function () {
    $deal = agreedDeal();

    expect(fn () => $this->flow->toggleHandover($deal, $deal->buyer, 'title_signed'))->toThrow(ValidationException::class);

    $this->flow->toggleHandover($deal, $deal->seller, 'title_signed');
    expect($deal->fresh()->handover)->toHaveKey('title_signed');
});

it('needs the full checklist and a sensible odometer reading to confirm', function () {
    $deal = agreedDeal();

    expect(fn () => $this->flow->confirm($deal, $deal->seller, 99999))->toThrow(ValidationException::class);

    tickAll($deal);
    expect(fn () => $this->flow->confirm($deal->fresh(), $deal->seller, 10))->toThrow(ValidationException::class);
});

it('transfers the passport, and only the passport, when both sides confirm', function () {
    Storage::fake('local');
    $deal = agreedDeal();
    $vehicle = $deal->vehicle;
    $seller = $deal->seller;
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'mileage' => 29000]);

    Storage::disk('local')->put('r.pdf', 'r');
    Storage::disk('local')->put('ins.pdf', 'i');
    $receipt = $record->documents()->create(['vehicle_id' => $vehicle->id, 'type' => 'receipt', 'name' => 'r', 'path' => 'r.pdf']);
    $insurance = $vehicle->documents()->create(['type' => 'insurance', 'name' => 'i', 'path' => 'ins.pdf']);
    $expense = $vehicle->expenses()->create(['ownership_id' => $vehicle->currentOwnership->id, 'category' => 'fuel', 'amount_cents' => 5000, 'spent_on' => now()]);
    $link = $vehicle->shareLinks()->create(['label' => 'Old']);

    tickAll($deal);
    expect($this->flow->confirm($deal->fresh(), $deal->seller, 30500))->toBeFalse();
    expect($this->flow->confirm($deal->fresh(), $deal->buyer))->toBeTrue();

    $vehicle->refresh();
    $owners = $vehicle->ownerships()->get();

    expect($vehicle->user_id)->toBe($deal->buyer_id)
        ->and($deal->fresh()->status)->toBe(DealStatus::Completed)
        ->and($deal->listing->fresh()->status)->toBe(ListingStatus::Sold)
        ->and($owners)->toHaveCount(2)
        ->and($owners[0]->user_id)->toBe($seller->id)
        ->and($owners[0]->end_mileage)->toBe(30500)
        ->and($owners[1]->acquired_via)->toBe(AcquiredVia::Platform)
        ->and($owners[1]->purchase_price_cents)->toBe(2_300_000)
        ->and($vehicle->current_mileage)->toBe(30500)
        ->and($vehicle->records()->count())->toBe(1)
        ->and($receipt->fresh())->not->toBeNull()
        ->and($insurance->fresh())->toBeNull()
        ->and($link->fresh()->isActive())->toBeFalse()
        ->and(Expense::find($expense->id)->ownership_id)->toBe($owners[0]->id);

    Storage::disk('local')->assertExists('r.pdf');
    Storage::disk('local')->assertMissing('ins.pdf');

    // The new owner sees the history but not the previous owner's costs; the seller loses access.
    $this->actingAs($deal->buyer)->get(route('vehicles.history', $vehicle))->assertOk();
    $this->actingAs($deal->buyer)->get(route('vehicles.costs', $vehicle))->assertOk()->assertDontSee('$50.00');
    $this->actingAs($seller)->get(route('vehicles.show', $vehicle))->assertForbidden();
});

it('keeps earlier owners\' records read-only and their costs private', function () {
    $deal = agreedDeal();
    $vehicle = $deal->vehicle;
    $old = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'mileage' => 29000, 'cost_cents' => 123456, 'title' => 'Clutch by the old owner']);

    tickAll($deal);
    $this->flow->confirm($deal->fresh(), $deal->seller, 30500);
    $this->flow->confirm($deal->fresh(), $deal->buyer);
    $buyer = $deal->buyer;
    $vehicle->refresh();

    $this->actingAs($buyer)->get(route('vehicles.history', $vehicle))
        ->assertSee('Clutch by the old owner')->assertSee('Read-only')->assertDontSee('$1,234.56');
    $this->actingAs($buyer)->get(route('vehicles.show', $vehicle))->assertDontSee('$1,235');
    $this->actingAs($buyer)->get(route('records.edit', [$vehicle, $old]))->assertForbidden();

    Livewire::actingAs($buyer)->test(History::class, ['vehicle' => $vehicle])
        ->call('delete', $old->id)->assertNotFound();
    Livewire::actingAs($buyer)->test(RecordForm::class, ['vehicle' => $vehicle, 'record' => $old])->assertForbidden();

    expect($old->fresh())->not->toBeNull();

    // A share link the new owner creates with costs switched on still hides the old owner's costs.
    $link = $vehicle->shareLinks()->create(['label' => 'Insurer', 'show_costs' => true]);
    $this->get($link->url())->assertSee('Clutch by the old owner')->assertDontSee('$1,235');
});

it('runs the whole flow through the deal room', function () {
    $deal = deal();

    Livewire::actingAs($deal->buyer)->test(DealRoom::class, ['deal' => $deal])
        ->set('body', 'Would you take 21,000?')->call('send')
        ->set('amount', '21,000')->call('makeOffer')
        ->assertHasNoErrors();

    $offer = $deal->offers()->first();

    Livewire::actingAs($deal->seller)->test(DealRoom::class, ['deal' => $deal->fresh()])
        ->call('respond', $offer->id, true)
        ->assertSee('Pre-purchase inspection');

    Livewire::actingAs($deal->buyer)->test(DealRoom::class, ['deal' => $deal->fresh()])
        ->set('results.vin_match.result', 'pass')
        ->set('location', 'Main Street Auto')
        ->call('saveInspection', true);

    expect($deal->fresh()->inspection->completed_at)->not->toBeNull()
        ->and($deal->fresh()->stage())->toBe(2);

    $this->actingAs($deal->buyer)->get(route('deals.bill-of-sale', $deal))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('keeps deal rooms private', function () {
    $deal = deal();

    $this->actingAs(User::factory()->create())->get(route('deals.show', $deal))->assertForbidden();
    $this->actingAs($deal->buyer)->get(route('deals.show', $deal))->assertOk();
    Livewire::actingAs(User::factory()->create())->test(DealRoom::class, ['deal' => $deal])->assertForbidden();
});
