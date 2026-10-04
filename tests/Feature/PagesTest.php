<?php

use App\Livewire\VinCheck;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('serves the public pages', function (string $route) {
    $this->get(route($route))->assertOk();
})->with(['home', 'marketplace', 'how-it-works', 'safety', 'vin-check', 'login', 'register']);

it('lists public listings in the sitemap and keeps private areas out of robots', function () {
    $listing = liveListing();

    $this->get(route('sitemap'))->assertOk()->assertSee(route('listings.show', $listing));
    $this->get(route('robots'))->assertOk()->assertSee('Disallow: /garage');
});

it('checks a VIN for free', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    Livewire::test(VinCheck::class)
        ->set('vin', '1HGCM82633A004353')
        ->call('check')
        ->assertSee('should be')
        ->assertSee('Honda');
});

it('renders the signed-in pages', function (string $path) {
    $vehicle = car();
    $this->actingAs($vehicle->owner);

    foreach (['', '/history', '/history/new', '/maintenance', '/documents', '/costs', '/recalls', '/share', '/sell', '/settings'] as $tab) {
        $this->get(route('vehicles.show', $vehicle).$tab)->assertOk();
    }

    $this->get($path)->assertOk();
})->with(['/garage', '/garage/add', '/deals', '/saved', '/notifications', '/profile']);

it('seeds a complete demo', function () {
    $this->seed(DatabaseSeeder::class);

    $owner = User::firstWhere('email', 'owner@beastmodemotors.test');
    $this->actingAs($owner)->get(route('garage'))->assertOk()->assertSee('Porsche');

    foreach ($owner->vehicles as $vehicle) {
        $this->get(route('vehicles.show', $vehicle))->assertOk();
    }

    $buyer = User::firstWhere('email', 'buyer@beastmodemotors.test');
    foreach (Deal::involving($buyer)->get() as $deal) {
        $this->actingAs($buyer)->get(route('deals.show', $deal))->assertOk();
    }

    $this->get(route('marketplace'))->assertOk();
});
