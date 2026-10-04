<?php

use App\Enums\BodyType;
use App\Livewire\Inventory;
use App\Models\Brand;
use App\Models\Vehicle;
use Livewire\Livewire;

beforeEach(function () {
    $this->porsche = Brand::factory()->create(['name' => 'Porsche']);
    $this->ferrari = Brand::factory()->create(['name' => 'Ferrari']);

    $this->gt3 = Vehicle::factory()->for($this->porsche)->create(['model' => 'GT3', 'price' => 200000, 'horsepower' => 502, 'body_type' => BodyType::Coupe, 'year' => 2023]);
    $this->cayenne = Vehicle::factory()->for($this->porsche)->create(['model' => 'Cayenne', 'price' => 120000, 'horsepower' => 650, 'body_type' => BodyType::Suv, 'year' => 2021]);
    $this->sf90 = Vehicle::factory()->for($this->ferrari)->create(['model' => 'SF90', 'price' => 520000, 'horsepower' => 986, 'body_type' => BodyType::Hypercar, 'year' => 2022]);
    $this->sold = Vehicle::factory()->for($this->ferrari)->sold()->create(['model' => 'Enzo']);
    $this->draft = Vehicle::factory()->for($this->ferrari)->draft()->create(['model' => 'Roma']);
});

it('lists published, unsold vehicles by default', function () {
    Livewire::test(Inventory::class)
        ->assertSee(['GT3', 'Cayenne', 'SF90'])
        ->assertDontSee('Enzo')
        ->assertDontSee('Roma');
});

it('can include sold vehicles', function () {
    Livewire::test(Inventory::class)
        ->set('includeSold', true)
        ->assertSee('Enzo')
        ->assertDontSee('Roma');
});

it('filters by brand, body type, price and horsepower', function () {
    Livewire::test(Inventory::class)
        ->set('brands', ['porsche'])
        ->assertSee(['GT3', 'Cayenne'])
        ->assertDontSee('SF90')
        ->set('body', 'suv')
        ->assertSee('Cayenne')
        ->assertDontSee('GT3');

    Livewire::test(Inventory::class)
        ->set('minPrice', 150000)
        ->set('maxPrice', 300000)
        ->assertSee('GT3')
        ->assertDontSee(['Cayenne', 'SF90']);

    Livewire::test(Inventory::class)
        ->set('minHp', 900)
        ->assertSee('SF90')
        ->assertDontSee(['GT3', 'Cayenne']);
});

it('searches across brand and model', function () {
    Livewire::test(Inventory::class)
        ->set('search', 'ferrari')
        ->assertSee('SF90')
        ->assertDontSee('GT3');

    Livewire::test(Inventory::class)
        ->set('search', 'porsche gt3')
        ->assertSee('GT3')
        ->assertDontSee('Cayenne');
});

it('sorts by price', function () {
    Livewire::test(Inventory::class)
        ->set('sort', 'price_desc')
        ->assertSeeInOrder(['SF90', 'GT3', 'Cayenne'])
        ->set('sort', 'price_asc')
        ->assertSeeInOrder(['Cayenne', 'GT3', 'SF90']);
});

it('reads filters from the query string', function () {
    $this->get(route('vehicles.index', ['body' => 'hypercar']))
        ->assertOk()
        ->assertSee('SF90')
        ->assertDontSee('Cayenne');
});

it('clears all filters', function () {
    Livewire::test(Inventory::class)
        ->set('brands', ['ferrari'])
        ->set('minHp', 900)
        ->call('clearFilters')
        ->assertSet('brands', [])
        ->assertSet('minHp', null)
        ->assertSee(['GT3', 'Cayenne', 'SF90']);
});
