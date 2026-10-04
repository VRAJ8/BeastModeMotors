<?php

use App\Enums\TestDriveStatus;
use App\Enums\VehicleStatus;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\TestDrives\Pages\ListTestDrives;
use App\Filament\Resources\Vehicles\Pages\CreateVehicle;
use App\Filament\Resources\Vehicles\Pages\EditVehicle;
use App\Filament\Resources\Vehicles\Pages\ListVehicles;
use App\Models\Brand;
use App\Models\Lead;
use App\Models\TestDrive;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->admin()->create();
});

it('keeps customers out of the back-office', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('sends guests to the back-office login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('renders the dashboard and every resource for staff', function (string $url) {
    Vehicle::factory()->create();
    Lead::factory()->create();
    TestDrive::factory()->create();

    $this->actingAs($this->admin)->get($url)->assertOk();
})->with(['/admin', '/admin/vehicles', '/admin/vehicles/create', '/admin/brands', '/admin/test-drives', '/admin/leads', '/admin/testimonials', '/admin/users']);

it('creates a vehicle with uploaded and external photos', function () {
    $brand = Brand::factory()->create(['name' => 'Bugatti']);

    Livewire::actingAs($this->admin)
        ->test(CreateVehicle::class)
        ->fillForm([
            'brand_id' => $brand->id,
            'model' => 'Tourbillon',
            'year' => 2026,
            'price' => 4100000,
            'mileage' => 10,
            'body_type' => 'hypercar',
            'condition' => 'new',
            'status' => 'available',
            'fuel_type' => 'hybrid',
            'transmission' => 'dual_clutch',
            'drivetrain' => 'awd',
            'external_images' => ['https://example.com/tourbillon.jpg'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $vehicle = Vehicle::sole();
    expect($vehicle->slug)->toBe('2026-bugatti-tourbillon')
        ->and($vehicle->images)->toBe(['https://example.com/tourbillon.jpg']);
});

it('keeps external photos when editing', function () {
    $vehicle = Vehicle::factory()->create(['images' => ['https://example.com/a.jpg']]);

    Livewire::actingAs($this->admin)
        ->test(EditVehicle::class, ['record' => $vehicle->getRouteKey()])
        ->assertSchemaStateSet(['external_images' => ['https://example.com/a.jpg']])
        ->fillForm(['model' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vehicle->fresh())
        ->model->toBe('Renamed')
        ->images->toBe(['https://example.com/a.jpg']);
});

it('marks a vehicle as sold from the table', function () {
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListVehicles::class)
        ->callTableAction('markSold', $vehicle);

    expect($vehicle->fresh())
        ->status->toBe(VehicleStatus::Sold)
        ->sold_at->not->toBeNull();
});

it('confirms a pending test drive from the table', function () {
    $drive = TestDrive::factory()->create(['scheduled_at' => now()->addDay()]);

    Livewire::actingAs($this->admin)
        ->test(ListTestDrives::class)
        ->callTableAction('confirm', $drive);

    expect($drive->fresh()->status)->toBe(TestDriveStatus::Confirmed);
});

it('shows open leads by default', function () {
    $open = Lead::factory()->create();
    $won = Lead::factory()->create(['status' => 'won']);

    Livewire::actingAs($this->admin)
        ->test(ListLeads::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$won]);
});
