<?php

use App\Enums\AcquiredVia;
use App\Enums\ExpenseCategory;
use App\Enums\ListingStatus;
use App\Enums\OdometerSource;
use App\Filament\Resources\Vehicles\Pages\ManageVehicles;
use App\Livewire\AddVehicle;
use App\Livewire\RecordForm;
use App\Livewire\Vehicle\Documents;
use App\Livewire\Vehicle\History;
use App\Livewire\Vehicle\Maintenance;
use App\Livewire\Vehicle\Readings;
use App\Livewire\Vehicle\Settings;
use App\Models\Document;
use App\Models\Reminder;
use App\Models\ServiceRecord;
use App\Models\Shop;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\OwnershipReviewRequested;
use App\Services\CostReport;
use App\Services\MaintenancePlanner;
use App\Services\OdometerAnalyzer;
use App\Services\PassportScore;
use App\Services\ShopVerifier;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(fn () => Notification::fake());

/**
 * Hand the car to a new owner, the way a completed sale does.
 */
function newOwner(Vehicle $vehicle): User
{
    $buyer = User::factory()->create();
    $vehicle->currentOwnership->update(['ended_on' => now()->toDateString()]);
    $vehicle->ownerships()->create([
        'user_id' => $buyer->id, 'owner_number' => 2, 'acquired_via' => AcquiredVia::Platform,
        'started_on' => now()->toDateString(), 'start_mileage' => $vehicle->current_mileage,
    ]);
    $vehicle->update(['user_id' => $buyer->id]);

    return $buyer;
}

function verify(ServiceRecord $record, string $email, bool $confirmed = true): void
{
    $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', $email);
    app(ShopVerifier::class)->answer($verification, $confirmed, 'Dee', $confirmed ? null : 'Not our work', '127.0.0.1');
}

// --- Shop verification -------------------------------------------------------------------------------------

it('rejects verification requests sent to the owner\'s own inbox under another spelling', function (string $owner, string $shop) {
    $vehicle = car();
    $vehicle->owner->update(['email' => $owner]);
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);

    expect(fn () => app(ShopVerifier::class)->request($record, $vehicle->owner->fresh(), 'My Garage', $shop))
        ->toThrow(ValidationException::class);
    Notification::assertNothingSent();
})->with([
    'plus tag' => ['sam@gmail.com', 'sam+shop@gmail.com'],
    'gmail dots' => ['sam.smith@gmail.com', 'samsmith@googlemail.com'],
    'case' => ['sam@example.com', 'SAM@Example.com'],
    'own company domain' => ['sam@smithlogistics.com', 'service@smithlogistics.com'],
]);

it('still lets an owner on webmail ask a shop on the same webmail provider', function () {
    $vehicle = car();
    $vehicle->owner->update(['email' => 'sam@gmail.com']);
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);

    app(ShopVerifier::class)->request($record, $vehicle->owner->fresh(), 'Joe\'s Garage', 'joesgarage@gmail.com');

    expect($record->fresh()->pendingVerification)->not->toBeNull();
});

it('lists a shop only once owners of two different accounts have had work confirmed, or staff vetted it', function () {
    verify(ServiceRecord::factory()->create(['vehicle_id' => car()->id]), 'desk@eastside.test');
    $shop = Shop::firstWhere('email', 'desk@eastside.test');

    $this->get(route('shops.show', $shop))->assertNotFound();
    $this->get(route('shops.index'))->assertDontSee('Eastside');

    // The same owner confirming more work doesn't count twice.
    $vehicle = Vehicle::whereHas('records', fn ($q) => $q->where('shop_id', $shop->id))->first();
    verify(ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'title' => 'Second job']), 'desk@eastside.test');
    $this->get(route('shops.show', $shop))->assertNotFound();

    verify(ServiceRecord::factory()->create(['vehicle_id' => car()->id]), 'desk@eastside.test');
    $this->get(route('shops.show', $shop))->assertOk();

    $solo = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    verify($solo, 'desk@soloshop.test');
    $soloShop = Shop::firstWhere('email', 'desk@soloshop.test');
    $this->get(route('shops.show', $soloShop))->assertNotFound();
    $soloShop->update(['vetted_at' => now()]);
    $this->get(route('shops.show', $soloShop))->assertOk();
});

it('never lets a confirmation clear a dispute', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');
    $record->update(['disputed_at' => now()]);

    app(ShopVerifier::class)->answer($verification, true, 'Dee', null, '127.0.0.1');

    expect($record->fresh()->evidence())->toBe(ServiceRecord::EVIDENCE_DISPUTED)
        ->and($record->fresh()->verified_at)->toBeNull();
});

it('answers a verification once, even if the form is submitted twice', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    $verification = app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');
    $stale = $verification->fresh();

    app(ShopVerifier::class)->answer($verification, true, 'Dee', null, '127.0.0.1');

    expect(fn () => app(ShopVerifier::class)->answer($stale, false, 'Mallory', 'Nope', '127.0.0.1'))->toThrow(HttpException::class);
    expect($record->fresh()->evidence())->toBe(ServiceRecord::EVIDENCE_VERIFIED);
});

it('freezes a record while the shop is looking at it', function () {
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id, 'title' => 'Timing belt', 'mileage' => 29000]);
    app(ShopVerifier::class)->request($record, $record->vehicle->owner, 'Eastside', 'desk@eastside.test');

    Livewire::actingAs($record->vehicle->owner)->test(RecordForm::class, ['vehicle' => $record->vehicle, 'record' => $record])
        ->assertSee('Waiting for Eastside to confirm')
        ->set('title', 'Engine rebuild')
        ->set('mileage', 29500)
        ->call('save');

    expect($record->fresh()->title)->toBe('Timing belt')->and($record->fresh()->mileage)->toBe(29000);
});

it('names the confirming shop by its own name, not what the owner typed', function () {
    $vehicle = car();
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'provider_name' => 'Totally Legit Dealer']);
    verify($record, 'desk@eastside.test');
    Shop::firstWhere('email', 'desk@eastside.test')->update(['name' => 'Eastside Euro Specialists']);

    $link = $vehicle->shareLinks()->create(['created_by' => $vehicle->user_id, 'label' => 'Buyers']);

    $this->get(route('passport.show', $link))->assertOk()
        ->assertSee('Eastside Euro Specialists')
        ->assertDontSee('Totally Legit Dealer');
});

// --- Records the shop answered for, and earlier owners' history ---------------------------------------------

it('keeps records the shop confirmed or disputed', function (bool $confirmed) {
    $record = ServiceRecord::factory()->create(['vehicle_id' => car()->id]);
    verify($record, 'desk@eastside.test', $confirmed);

    Livewire::actingAs($record->vehicle->owner)->test(History::class, ['vehicle' => $record->vehicle])
        ->call('delete', $record->id);

    expect($record->fresh())->not->toBeNull()->and($record->verifications()->count())->toBe(1);
})->with(['confirmed' => true, 'disputed' => false]);

it('does not let a later owner delete the passport or an earlier owner\'s receipts', function () {
    $vehicle = car();
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);
    $receipt = Document::create([
        'vehicle_id' => $vehicle->id, 'ownership_id' => $vehicle->currentOwnership->id, 'service_record_id' => $record->id,
        'uploaded_by' => $vehicle->user_id, 'type' => 'receipt', 'name' => 'Invoice', 'path' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 10,
    ]);
    $buyer = newOwner($vehicle);
    $vehicle = $vehicle->fresh();

    Livewire::actingAs($buyer)->test(Documents::class, ['vehicle' => $vehicle])
        ->call('delete', $receipt->id)
        ->assertNotFound();

    Livewire::actingAs($buyer)->test(Settings::class, ['vehicle' => $vehicle])
        ->set('confirmVin', substr($vehicle->vin, -6))
        ->call('delete')
        ->assertHasErrors('confirmVin');

    expect($receipt->fresh())->not->toBeNull()->and($vehicle->fresh())->not->toBeNull();
});

// --- Passport Score -------------------------------------------------------------------------------------------

it('gives no coverage for disputed records', function () {
    $vehicle = car();
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id]);
    verify($record, 'desk@eastside.test', confirmed: false);

    $coverage = collect(app(PassportScore::class)->for($vehicle->fresh())['components'])->firstWhere('key', 'coverage');

    expect($coverage['points'])->toBe(0);
});

it('does not judge a car on a year of history it barely lived', function () {
    $vehicle = car();
    $vehicle->currentOwnership->update(['started_on' => now()->subMonths(13)->toDateString()]);
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subMonth()->toDateString()]);

    $coverage = collect(app(PassportScore::class)->for($vehicle->fresh())['components'])->firstWhere('key', 'coverage');

    expect($coverage['points'])->toBe(25);
});

// --- Maintenance ----------------------------------------------------------------------------------------------

it('rolls a reminder back when the record that completed it is unticked or deleted', function () {
    $vehicle = car(['current_mileage' => 40000]);
    $reminder = $vehicle->reminders()->create(['task' => 'Engine oil & filter', 'interval_miles' => 5000, 'interval_months' => 6]);
    $planner = app(MaintenancePlanner::class);

    // What the owner typed in by hand, before logging anything.
    Livewire::actingAs($vehicle->owner)->test(Maintenance::class, ['vehicle' => $vehicle])
        ->call('edit', $reminder->id)
        ->set('last_done_on', now()->subMonths(5)->toDateString())
        ->set('last_done_mileage', 34000)
        ->call('save');

    $older = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subMonths(3)->toDateString(), 'mileage' => 36000]);
    $planner->applyRecord($older, [$reminder->id]);
    $newer = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subWeek()->toDateString(), 'mileage' => 39500]);
    $planner->applyRecord($newer, [$reminder->id]);
    expect($reminder->fresh()->last_done_mileage)->toBe(39500);

    $newer->delete();
    expect($reminder->fresh()->last_done_mileage)->toBe(36000);

    $planner->applyRecord($older->fresh(), []);
    expect($reminder->fresh()->last_done_mileage)->toBe(34000)
        ->and($reminder->fresh()->last_done_on->toDateString())->toBe(now()->subMonths(5)->toDateString());
});

it('keeps a reminder linked to its record when the owner only changes the interval', function () {
    $vehicle = car();
    $reminder = $vehicle->reminders()->create(['task' => 'Brake fluid', 'interval_months' => 24]);
    $record = ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'mileage' => 29000]);
    app(MaintenancePlanner::class)->applyRecord($record, [$reminder->id]);

    Livewire::actingAs($vehicle->owner)->test(Maintenance::class, ['vehicle' => $vehicle])
        ->call('edit', $reminder->id)
        ->set('task', 'Brake fluid flush')
        ->set('interval_months', 36)
        ->call('save')
        ->assertHasNoErrors();

    $record->delete();

    expect($reminder->fresh()->last_done_on)->toBeNull()->and($reminder->fresh()->status())->toBe(Reminder::UNKNOWN);
});

it('says a reminder is unknown when it was last done on an axis it has no interval for', function () {
    $reminder = car()->reminders()->create(['task' => 'Coolant', 'interval_months' => 60, 'last_done_mileage' => 10000]);

    expect($reminder->status(30000))->toBe(Reminder::UNKNOWN)
        ->and($reminder->dueLabel(30000))->toContain('date');
});

// --- Odometer -------------------------------------------------------------------------------------------------

it('does not flag two readings from the same day as a rollback', function () {
    $vehicle = car();
    $vehicle->readings()->create(['reading' => 30010, 'recorded_on' => now()->toDateString(), 'source' => OdometerSource::Manual]);
    $vehicle->readings()->create(['reading' => 30005, 'recorded_on' => now()->toDateString(), 'source' => OdometerSource::Expense]);

    expect(app(OdometerAnalyzer::class)->anomalies($vehicle->fresh()->readings))->toBe([]);
});

it('only accepts readings that fit the timeline, and lets owners take their own back', function () {
    $vehicle = car(['current_mileage' => 30000]);
    $component = Livewire::actingAs($vehicle->owner)->test(Readings::class, ['vehicle' => $vehicle]);

    // Backdated, but higher than a later reading.
    $component->set('recorded_on', now()->subYear()->toDateString())->set('reading', 35000)->call('add')->assertHasErrors('reading');
    // Lower than an earlier reading.
    $component->set('recorded_on', now()->toDateString())->set('reading', 29000)->call('add')->assertHasErrors('reading');
    // Backdated and consistent on both sides.
    $component->set('recorded_on', now()->subYear()->toDateString())->set('reading', 20000)->call('add')->assertHasNoErrors();

    $entry = $vehicle->readings()->where('source', OdometerSource::Manual)->first();
    expect($entry->reading)->toBe(20000);

    $component->call('remove', $entry->id);
    expect($entry->fresh())->toBeNull();

    $service = $vehicle->readings()->where('source', '!=', OdometerSource::Manual)->first();
    $component->call('remove', $service->id)->assertNotFound();
});

// --- Costs ----------------------------------------------------------------------------------------------------

it('counts fills logged without an odometer reading in fuel economy', function () {
    $vehicle = car();
    $ownership = $vehicle->currentOwnership;
    foreach ([[30000, 10, 20], [null, 10, 15], [30600, 10, 10]] as [$odometer, $volume, $daysAgo]) {
        $vehicle->expenses()->create([
            'ownership_id' => $ownership->id, 'category' => ExpenseCategory::Fuel, 'amount_cents' => 4000,
            'spent_on' => now()->subDays($daysAgo)->toDateString(), 'odometer' => $odometer, 'volume' => $volume,
        ]);
    }

    // 600 miles on 20 gallons, not 600 on 10.
    expect(app(CostReport::class)->for($ownership)['economy'])->toMatchArray(['value' => 30.0, 'unit' => 'mpg', 'fills' => 3]);
});

it('does not count backfilled invoices from before the purchase as the owner\'s spending', function () {
    $vehicle = car();
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subYears(4)->toDateString(), 'mileage' => 5000, 'cost_cents' => 120000]);
    ServiceRecord::factory()->create(['vehicle_id' => $vehicle->id, 'performed_on' => now()->subMonth()->toDateString(), 'cost_cents' => 8900]);

    expect(app(CostReport::class)->for($vehicle->currentOwnership)['maintenance'])->toBe(8900);
});

// --- Marketplace scores ---------------------------------------------------------------------------------------

it('keeps stored listing scores in step with the passport', function () {
    $listing = liveListing();
    $listing->update(['score' => 1]);
    $withdrawn = liveListing(['status' => ListingStatus::Withdrawn]);
    $withdrawn->update(['score' => 1]);

    $this->artisan('passport:housekeeping')->assertSuccessful();

    expect($listing->fresh()->score)->toBe(app(PassportScore::class)->for($listing->vehicle)['total'])
        ->and($withdrawn->fresh()->score)->toBe(1);
});

// --- VIN claims -----------------------------------------------------------------------------------------------

it('lets the real owner of a claimed VIN ask for a review, and staff release it', function () {
    $squatted = car();
    $owner = User::factory()->create();

    Livewire::actingAs($owner)->test(AddVehicle::class)
        ->set('vin', $squatted->vin)
        ->call('decode')
        ->assertHasErrors('vin')
        ->assertSee('request a review')
        ->call('requestReview')
        ->assertSee('Review requested');

    Notification::assertSentOnDemand(OwnershipReviewRequested::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === config('passport.support_email'));

    $admin = User::factory()->create(['is_admin' => true]);
    Livewire::actingAs($admin)->test(ManageVehicles::class)
        ->assertTableActionVisible('release', $squatted)
        ->callTableAction('release', $squatted);

    expect(Vehicle::find($squatted->id))->toBeNull();
});

it('does not let staff release a passport that holds other owners\' history', function () {
    $vehicle = car();
    newOwner($vehicle);
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)->test(ManageVehicles::class)
        ->assertTableActionHidden('release', $vehicle);
});

it('does not offer a review for a VIN that is already the user\'s own', function () {
    $vehicle = car();

    Livewire::actingAs($vehicle->owner)->test(AddVehicle::class)
        ->set('vin', $vehicle->vin)
        ->call('decode')
        ->assertSet('contested', null)
        ->call('requestReview');

    Notification::assertNothingSent();
});
