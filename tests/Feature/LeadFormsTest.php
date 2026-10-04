<?php

use App\Enums\LeadType;
use App\Livewire\ContactForm;
use App\Livewire\MakeOffer;
use App\Livewire\TradeInForm;
use App\Models\Lead;
use App\Models\Vehicle;
use App\Notifications\NewLeadReceived;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(fn () => Notification::fake());

it('accepts an offer and alerts the sales team', function () {
    $vehicle = Vehicle::factory()->create(['price' => 200000]);

    Livewire::test(MakeOffer::class, ['vehicle' => $vehicle])
        ->set('amount', 185000)
        ->set('name', 'Charles')
        ->set('email', 'charles@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    $lead = Lead::sole();
    expect($lead->type)->toBe(LeadType::Offer)
        ->and($lead->offer_amount)->toBe(185000)
        ->and($lead->vehicle_id)->toBe($vehicle->id);

    Notification::assertSentOnDemand(NewLeadReceived::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === config('dealership.email'));
});

it('rejects lowball and above-asking offers', function (int $amount) {
    $vehicle = Vehicle::factory()->create(['price' => 200000]);

    Livewire::test(MakeOffer::class, ['vehicle' => $vehicle])
        ->set('amount', $amount)
        ->set('name', 'Charles')
        ->set('email', 'charles@example.com')
        ->call('submit')
        ->assertHasErrors('amount');
})->with([50000, 250000]);

it('estimates a trade-in and records the request', function () {
    Livewire::test(TradeInForm::class)
        ->set('make', 'Porsche')
        ->set('model', 'Cayenne')
        ->set('year', (int) date('Y') - 3)
        ->set('mileage', 24000)
        ->set('originalPrice', 120000)
        ->set('vehicleCondition', 'good')
        ->call('estimate')
        ->assertHasNoErrors()
        ->assertSet('step', 2)
        ->set('name', 'Lando')
        ->set('email', 'lando@example.com')
        ->call('submit')
        ->assertSet('sent', true);

    $lead = Lead::sole();
    expect($lead->type)->toBe(LeadType::TradeIn)
        ->and($lead->meta['make'])->toBe('Porsche')
        ->and($lead->meta['estimate']['low'])->toBeLessThan($lead->meta['estimate']['high']);
});

it('routes finance questions from the contact form', function () {
    Livewire::test(ContactForm::class, ['topic' => 'finance'])
        ->set('name', 'Oscar')
        ->set('email', 'oscar@example.com')
        ->set('message', 'What APR could I get on a 911?')
        ->call('submit')
        ->assertSet('sent', true);

    expect(Lead::sole()->type)->toBe(LeadType::Finance);
});

it('rate limits repeated submissions', function () {
    $form = Livewire::test(ContactForm::class)
        ->set('name', 'Spammy')
        ->set('email', 'spam@example.com')
        ->set('message', 'Hello hello hello');

    foreach (range(1, 5) as $i) {
        $form->call('submit')->assertHasNoErrors();
    }

    $form->call('submit')->assertHasErrors('form');
    expect(Lead::count())->toBe(5);
});
