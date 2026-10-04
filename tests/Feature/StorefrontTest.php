<?php

use App\Models\Brand;
use App\Models\Testimonial;
use App\Models\Vehicle;

it('renders every public page', function (string $route) {
    Vehicle::factory()->featured()->create();
    Testimonial::factory()->create();

    $this->get(route($route))->assertOk();
})->with(['home', 'vehicles.index', 'brands.index', 'compare', 'sell', 'about', 'contact']);

it('shows a published vehicle with its schema.org data', function () {
    $vehicle = Vehicle::factory()->create(['model' => 'Huracán', 'price' => 250000]);

    $this->get(route('vehicles.show', $vehicle))
        ->assertOk()
        ->assertSee('Huracán')
        ->assertSee('$250,000')
        ->assertSee('"@type":"Car"', escape: false);
});

it('hides draft and future-dated vehicles', function () {
    $draft = Vehicle::factory()->draft()->create();
    $scheduled = Vehicle::factory()->create(['published_at' => now()->addWeek()]);

    $this->get(route('vehicles.show', $draft))->assertNotFound();
    $this->get(route('vehicles.show', $scheduled))->assertNotFound();
});

it('counts a vehicle view once per session', function () {
    $vehicle = Vehicle::factory()->create(['views' => 0]);

    $this->get(route('vehicles.show', $vehicle));
    $this->get(route('vehicles.show', $vehicle));

    expect($vehicle->fresh()->views)->toBe(1);
});

it('generates unique slugs', function () {
    $brand = Brand::factory()->create(['name' => 'Ferrari']);
    $a = Vehicle::factory()->for($brand)->create(['year' => 2022, 'model' => '296', 'trim' => 'GTB']);
    $b = Vehicle::factory()->for($brand)->create(['year' => 2022, 'model' => '296', 'trim' => 'GTB']);

    expect($a->slug)->toBe('2022-ferrari-296-gtb')
        ->and($b->slug)->toBe('2022-ferrari-296-gtb-2');
});

it('lists a brand with its published cars', function () {
    $brand = Brand::factory()->create(['name' => 'McLaren']);
    Vehicle::factory()->for($brand)->create(['model' => 'Artura']);
    Vehicle::factory()->for($brand)->draft()->create(['model' => 'Senna']);

    $this->get(route('brands.show', $brand))
        ->assertOk()
        ->assertSee('Artura')
        ->assertDontSee('Senna');
});

it('serves a sitemap containing published vehicles only', function () {
    $live = Vehicle::factory()->create();
    $draft = Vehicle::factory()->draft()->create();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee(route('vehicles.show', $live))
        ->assertDontSee(route('vehicles.show', $draft));
});
