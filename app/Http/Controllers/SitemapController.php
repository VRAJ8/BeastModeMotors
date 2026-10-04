<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect(['home', 'marketplace', 'vin-check', 'how-it-works', 'safety'])
            ->map(fn (string $route) => ['loc' => route($route), 'lastmod' => null])
            ->merge(Listing::public()->latest('updated_at')->get()->map(fn (Listing $listing) => [
                'loc' => route('listings.show', $listing),
                'lastmod' => $listing->updated_at->toAtomString(),
            ]));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
