<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Support\CompareList;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function index(CompareList $compare): View
    {
        $vehicles = $compare->vehicles();

        return view('compare', [
            'vehicles' => $vehicles,
            'best' => [
                'price' => $vehicles->min('price'),
                'horsepower' => $vehicles->max('horsepower'),
                'torque' => $vehicles->max('torque'),
                'zero_to_sixty' => $vehicles->min('zero_to_sixty'),
                'top_speed' => $vehicles->max('top_speed'),
                'mileage' => $vehicles->min('mileage'),
                'year' => $vehicles->max('year'),
            ],
        ]);
    }

    public function destroy(CompareList $compare, Vehicle $vehicle): RedirectResponse
    {
        $compare->remove($vehicle);

        return back()->with('status', "{$vehicle->title} removed from compare.");
    }

    public function clear(CompareList $compare): RedirectResponse
    {
        $compare->clear();

        return redirect()->route('compare');
    }
}
