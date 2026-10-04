<?php

namespace App\Http\Controllers;

use App\Enums\VehicleStatus;
use App\Models\Brand;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('brands.index', [
            'brands' => Brand::withCount(['vehicles' => fn ($q) => $q->available()])
                ->with(['vehicles' => fn ($q) => $q->available()->latest('published_at')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(Brand $brand): View
    {
        return view('brands.show', [
            'brand' => $brand,
            'vehicles' => $brand->vehicles()
                ->with('brand')
                ->published()
                ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [VehicleStatus::Sold->value])
                ->orderByDesc('price')
                ->get(),
        ]);
    }
}
