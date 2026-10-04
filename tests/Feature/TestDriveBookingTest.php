<?php

use App\Enums\TestDriveStatus;
use App\Livewire\BookTestDrive;
use App\Models\TestDrive;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TestDriveUpdated;
use App\Services\TestDriveScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    // Monday 8am: showroom opens at 10:00, Sunday is closed.
    $this->travelTo(CarbonImmutable::parse('2026-03-02 08:00'));
    Notification::fake();
    $this->vehicle = Vehicle::factory()->create();
});

it('offers hourly slots within opening hours', function () {
    $slots = app(TestDriveScheduler::class)->availableSlots($this->vehicle, now());

    expect($slots->first()->format('H:i'))->toBe('10:00')
        ->and($slots->last()->format('H:i'))->toBe('18:00')
        ->and($slots)->toHaveCount(9);
});

it('respects minimum notice and closed days', function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-02 11:30'));
    $scheduler = app(TestDriveScheduler::class);

    expect($scheduler->availableSlots($this->vehicle, now())->first()->format('H:i'))->toBe('14:00')
        ->and($scheduler->availableSlots($this->vehicle, CarbonImmutable::parse('2026-03-08')))->toBeEmpty()
        ->and($scheduler->bookableDates()->map->isoWeekday()->contains(7))->toBeFalse();
});

it('books a test drive and emails the customer', function () {
    Livewire::test(BookTestDrive::class, ['vehicle' => $this->vehicle])
        ->set('date', '2026-03-03')
        ->set('time', '11:00')
        ->set('name', 'Lewis Hamilton')
        ->set('email', 'lewis@example.com')
        ->call('book')
        ->assertHasNoErrors()
        ->assertSet('reference', fn ($ref) => str_starts_with($ref, 'TD-'));

    $drive = TestDrive::sole();
    expect($drive->scheduled_at->format('Y-m-d H:i'))->toBe('2026-03-03 11:00')
        ->and($drive->status)->toBe(TestDriveStatus::Pending);

    Notification::assertSentOnDemand(TestDriveUpdated::class);
});

it('links bookings to the signed-in customer', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(BookTestDrive::class, ['vehicle' => $this->vehicle])
        ->assertSet('email', $user->email)
        ->set('time', '12:00')
        ->call('book')
        ->assertHasNoErrors();

    expect(TestDrive::sole()->user_id)->toBe($user->id);
    Notification::assertSentTo($user, TestDriveUpdated::class);
});

it('prevents double booking a slot', function () {
    TestDrive::factory()->for($this->vehicle)->create(['scheduled_at' => '2026-03-03 11:00:00']);

    Livewire::test(BookTestDrive::class, ['vehicle' => $this->vehicle])
        ->set('date', '2026-03-03')
        ->set('time', '11:00')
        ->set('name', 'Max')
        ->set('email', 'max@example.com')
        ->call('book')
        ->assertHasErrors('time');

    expect(TestDrive::count())->toBe(1);
});

it('frees the slot again when a booking is cancelled', function () {
    TestDrive::factory()->for($this->vehicle)->create(['scheduled_at' => '2026-03-03 11:00:00', 'status' => TestDriveStatus::Cancelled]);

    expect(app(TestDriveScheduler::class)->isSlotAvailable($this->vehicle, CarbonImmutable::parse('2026-03-03 11:00')))->toBeTrue();
});

it('rejects slots outside opening hours', function () {
    Livewire::test(BookTestDrive::class, ['vehicle' => $this->vehicle])
        ->set('date', '2026-03-03')
        ->set('time', '21:00')
        ->set('name', 'Max')
        ->set('email', 'max@example.com')
        ->call('book')
        ->assertHasErrors('time');

    expect(TestDrive::count())->toBe(0);
});

it('does not book sold cars', function () {
    $sold = Vehicle::factory()->sold()->create();

    Livewire::test(BookTestDrive::class, ['vehicle' => $sold])
        ->set('date', '2026-03-03')
        ->set('time', '11:00')
        ->set('name', 'Max')
        ->set('email', 'max@example.com')
        ->call('book')
        ->assertHasErrors('time');

    expect(TestDrive::count())->toBe(0);
});

it('silently drops honeypot submissions', function () {
    Livewire::test(BookTestDrive::class, ['vehicle' => $this->vehicle])
        ->set('date', '2026-03-03')
        ->set('time', '11:00')
        ->set('name', 'Bot')
        ->set('email', 'bot@example.com')
        ->set('website', 'http://spam.example')
        ->call('book')
        ->assertNotSet('reference', null);

    expect(TestDrive::count())->toBe(0);
});

it('emails the customer when staff confirm', function () {
    $drive = TestDrive::factory()->for($this->vehicle)->create();
    Notification::fake();

    $drive->update(['status' => TestDriveStatus::Confirmed]);

    Notification::assertSentOnDemand(TestDriveUpdated::class, fn ($n) => $n->testDrive->status === TestDriveStatus::Confirmed);
});
