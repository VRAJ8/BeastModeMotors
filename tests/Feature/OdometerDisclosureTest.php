<?php

use App\Enums\DealStatus;
use App\Enums\OdometerStatus;
use App\Livewire\DealRoom;
use App\Models\Deal;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DealFlow;
use App\Support\OdometerDisclosure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

/** An agreed deal with every required handover step ticked, ready for both sides to confirm. */
function readyToConfirm(int $year = 2018): Deal
{
    $deal = deal();
    $deal->vehicle->update(['year' => $year]);
    $flow = app(DealFlow::class);
    $flow->respond($flow->offer($deal, $deal->buyer, 2_000_000), $deal->seller, true);

    foreach (config('passport.handover') as $key => $item) {
        if ($item['required']) {
            $flow->toggleHandover($deal->fresh(), $item['by'] === 'buyer' ? $deal->buyer : $deal->seller, $key);
        }
    }

    return $deal->fresh();
}

it('requires a federal disclosure for model year 2011 and newer, until the car is 20', function () {
    $on = Carbon::create(2026, 10, 7);
    $car = fn (int $year) => new Vehicle(['year' => $year]);

    expect(OdometerDisclosure::required($car(2010), $on))->toBeFalse()
        ->and(OdometerDisclosure::required($car(2011), $on))->toBeTrue()
        ->and(OdometerDisclosure::required($car(2027), $on))->toBeTrue()
        ->and(OdometerDisclosure::required($car(2011), Carbon::create(2030, 12, 31)))->toBeTrue()
        ->and(OdometerDisclosure::required($car(2011), Carbon::create(2031, 1, 1)))->toBeFalse()
        ->and(OdometerDisclosure::exemption($car(2005), $on))->toContain('2011 and newer')
        ->and(OdometerDisclosure::exemption($car(2011), Carbon::create(2031, 1, 1)))->toContain('20 or more');
});

it('stores the seller\'s certification with the handover reading', function () {
    $deal = readyToConfirm();
    app(DealFlow::class)->confirm($deal, $deal->seller, $deal->vehicle->current_mileage + 5, OdometerStatus::ExceedsLimits);

    expect($deal->fresh()->odometer_status)->toBe(OdometerStatus::ExceedsLimits)
        ->and($deal->messages()->pluck('body')->implode("\n"))->toContain('exceeds the odometer\'s mechanical limits');
});

it('takes the certification from the seller in the deal room and shows it to the buyer', function () {
    $deal = readyToConfirm();

    Livewire::actingAs($deal->seller)->test(DealRoom::class, ['deal' => $deal])
        ->assertSee('Odometer certification')
        ->set('odometerStatus', 'made_up')
        ->call('confirm')
        ->assertHasErrors('odometer_status')
        ->set('odometerStatus', 'not_actual')
        ->call('confirm')
        ->assertHasNoErrors();

    expect($deal->fresh()->odometer_status)->toBe(OdometerStatus::NotActual);

    Livewire::actingAs($deal->buyer)->test(DealRoom::class, ['deal' => $deal->fresh()])
        ->assertDontSee('Odometer certification')
        ->assertSee('not the actual mileage');
});

it('warns the seller when the passport shows the odometer going backwards', function () {
    $deal = readyToConfirm();
    $vehicle = $deal->vehicle;
    $vehicle->readings()->create(['reading' => $vehicle->current_mileage + 900, 'recorded_on' => now()->subMonths(3), 'source' => 'manual']);
    $vehicle->readings()->create(['reading' => $vehicle->current_mileage + 100, 'recorded_on' => now()->subMonth(), 'source' => 'manual']);

    Livewire::actingAs($deal->seller)->test(DealRoom::class, ['deal' => $deal])
        ->assertSee('odometer going backwards');
});

it('generates the disclosure statement for the deal\'s parties only, when the car needs one', function () {
    $deal = readyToConfirm(2018);
    app(DealFlow::class)->confirm($deal, $deal->seller, $deal->vehicle->current_mileage + 5, OdometerStatus::Actual);
    $url = route('deals.odometer-disclosure', $deal);

    $this->actingAs($deal->buyer)->get($url)->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs($deal->seller)->get($url)->assertOk();
    $this->actingAs(User::factory()->create())->get($url)->assertForbidden();

    $html = view('pdf.odometer-disclosure', ['deal' => $deal->fresh()->load('vehicle', 'buyer', 'seller')])->render();
    expect($html)->toContain(number_format($deal->vehicle->current_mileage + 5))
        ->toContain('☒</span></td><td>'.OdometerStatus::Actual->certification())
        ->toContain('☐</span></td><td>'.OdometerStatus::NotActual->certification())
        ->toContain($deal->vehicle->vin);

    expect(view('pdf.bill-of-sale', ['deal' => $deal->fresh()->load('vehicle', 'buyer', 'seller', 'listing')])->render())
        ->toContain(OdometerStatus::Actual->certification());
});

it('explains instead of offering a disclosure for exempt cars, and only once a price is agreed', function () {
    $exempt = readyToConfirm(2008);

    $this->actingAs($exempt->buyer)->get(route('deals.odometer-disclosure', $exempt))->assertNotFound();
    Livewire::actingAs($exempt->buyer)->test(DealRoom::class, ['deal' => $exempt])
        ->assertSee('Paperwork')
        ->assertSee('Model year 2008 cars are exempt')
        ->assertDontSee(route('deals.odometer-disclosure', $exempt));

    $open = deal();
    $open->vehicle->update(['year' => 2020]);
    expect($open->status)->toBe(DealStatus::Open);
    $this->actingAs($open->buyer)->get(route('deals.odometer-disclosure', $open))->assertNotFound();
    Livewire::actingAs($open->buyer)->test(DealRoom::class, ['deal' => $open])->assertDontSee('Odometer disclosure');
});

it('keeps validating the handover reading alongside the certification', function () {
    $deal = readyToConfirm();

    expect(fn () => app(DealFlow::class)->confirm($deal, $deal->seller, null, OdometerStatus::Actual))->toThrow(ValidationException::class);
    expect($deal->fresh()->odometer_status)->toBeNull();
});
