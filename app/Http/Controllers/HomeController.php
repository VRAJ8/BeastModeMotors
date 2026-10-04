<?php

namespace App\Http\Controllers;

use App\Enums\BodyType;
use App\Models\Brand;
use App\Models\Testimonial;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featured = Vehicle::with('brand')->available()->where('is_featured', true)->latest('published_at')->take(6)->get();

        $stats = Cache::remember('home.stats', now()->addMinutes(10), fn () => [
            'in_stock' => Vehicle::available()->count(),
            'brands' => Brand::has('vehicles')->count(),
            'horsepower' => (int) Vehicle::available()->sum('horsepower'),
            'sold' => Vehicle::whereNotNull('sold_at')->count() + 1200, // Historical sales before this platform launched.
        ]);

        return view('home', [
            'hero' => $featured->take(3),
            'featured' => $featured,
            'latest' => Vehicle::with('brand')->available()->latest('published_at')->take(4)->get(),
            'brands' => Brand::withCount(['vehicles' => fn ($q) => $q->available()])->orderByDesc('vehicles_count')->get(),
            'bodyTypes' => collect(BodyType::cases())->map(fn (BodyType $type) => [
                'type' => $type,
                'count' => Vehicle::available()->where('body_type', $type)->count(),
            ])->filter(fn ($b) => $b['count'] > 0),
            'testimonials' => Testimonial::published()->latest()->take(3)->get(),
            'stats' => $stats,
        ]);
    }
}
