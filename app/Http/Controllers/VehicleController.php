<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        return view('vehicles.index');
    }

    public function show(Request $request, Vehicle $vehicle): View
    {
        abort_unless($vehicle->published_at?->isPast(), 404);

        // Count one view per visitor session.
        $seen = $request->session()->get('viewed_vehicles', []);
        if (! in_array($vehicle->id, $seen, true)) {
            $vehicle->increment('views');
            $request->session()->push('viewed_vehicles', $vehicle->id);
        }

        $vehicle->load('brand');

        $similar = Vehicle::with('brand')
            ->available()
            ->whereKeyNot($vehicle->id)
            ->where(fn ($q) => $q->where('body_type', $vehicle->body_type)->orWhere('brand_id', $vehicle->brand_id))
            ->orderByRaw('ABS(price - ?)', [$vehicle->price])
            ->take(3)
            ->get();

        return view('vehicles.show', [
            'vehicle' => $vehicle,
            'similar' => $similar,
        ]);
    }
}
