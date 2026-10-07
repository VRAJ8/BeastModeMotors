<?php

use App\Livewire\Marketplace;
use Livewire\Livewire;

/*
 * Postgres rejects out-of-range or malformed parameters that SQLite quietly accepts. Run this file on
 * Postgres (see README) to prove these stay 200s and 404s rather than 500s.
 */

it('treats numbers typed into marketplace search and filters as filters, not database errors', function (string $query) {
    liveListing();

    $this->get('/cars?'.$query)->assertOk();
})->with([
    'ZIP code in search' => 'q=90210',
    'mileage in search' => 'q=50000',
    'huge max miles' => 'max_miles=3000000000',
    'huge max price' => 'max_price=99999999999999999',
    'huge min year' => 'min_year=99999',
    'huge min score' => 'min_score=40000',
    'words in numeric filters' => 'max_miles=lots&max_price=cheap&min_year=old',
]);

it('still finds cars by model year in the search box', function () {
    $listing = liveListing();

    $ids = fn ($component) => $component->viewData('listings')->pluck('id')->all();

    expect($ids(Livewire::test(Marketplace::class)->set('q', (string) $listing->vehicle->year)))->toBe([$listing->id])
        ->and($ids(Livewire::test(Marketplace::class)->set('q', '1850')))->toBe([]);
});

it('answers malformed ids in URLs with a 404', function (string $path) {
    $vehicle = car();
    $link = $vehicle->shareLinks()->create(['created_by' => $vehicle->user_id, 'label' => 'Buyers']);

    $this->actingAs($vehicle->owner)->get(str_replace('{token}', $link->getRouteKey(), $path))->assertNotFound();
})->with([
    '/p/{token}/documents/abc',
    '/p/{token}/documents/99999999999999999999',
    '/deals/abc',
    '/garage/abc',
    '/notifications/abc',
]);
