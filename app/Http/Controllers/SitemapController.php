<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Vehicle;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('vehicles.index'), 'priority' => '0.9'],
            ['loc' => route('brands.index'), 'priority' => '0.6'],
            ['loc' => route('sell'), 'priority' => '0.6'],
            ['loc' => route('about'), 'priority' => '0.4'],
            ['loc' => route('contact'), 'priority' => '0.4'],
        ])
            ->merge(Brand::all()->map(fn (Brand $b) => ['loc' => route('brands.show', $b), 'lastmod' => $b->updated_at, 'priority' => '0.5']))
            ->merge(Vehicle::published()->get()->map(fn (Vehicle $v) => ['loc' => route('vehicles.show', $v), 'lastmod' => $v->updated_at, 'priority' => '0.8']));

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
