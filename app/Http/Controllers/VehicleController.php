<?php

namespace App\Http\Controllers;

use App\Models\ServiceRecord;
use App\Models\Vehicle;
use App\Services\OdometerAnalyzer;
use App\Services\PassportScore;
use App\Support\Qr;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The private garage view of one car, split into tabs. Access is limited to the owner by route middleware.
 */
class VehicleController extends Controller
{
    public function overview(Vehicle $vehicle, PassportScore $scorer, OdometerAnalyzer $odometer): View
    {
        $vehicle->load(['photos', 'ownerships.user', 'readings', 'reminders', 'recalls', 'openListing', 'records' => fn ($q) => $q->withCount('documents')->with('shop')]);

        return view('vehicles.overview', [
            'vehicle' => $vehicle,
            'score' => $scorer->for($vehicle),
            'anomalies' => $odometer->anomalies($vehicle->readings),
            'milesPerYear' => $odometer->milesPerYear($vehicle->readings),
            'upcoming' => $vehicle->reminders
                ->reject(fn ($r) => $r->status($vehicle->current_mileage) === 'unknown')
                ->sortByDesc(fn ($r) => $r->progress($vehicle->current_mileage))
                ->take(4),
        ]);
    }

    public function history(Vehicle $vehicle): View
    {
        return view('vehicles.history', compact('vehicle'));
    }

    public function createRecord(Vehicle $vehicle): View
    {
        return view('vehicles.record-form', ['vehicle' => $vehicle, 'record' => null]);
    }

    public function editRecord(Vehicle $vehicle, ServiceRecord $record): View
    {
        abort_unless($record->isFromOwnership($vehicle->currentOwnership?->getKey()), 403, 'Records from previous owners can\'t be edited.');

        return view('vehicles.record-form', compact('vehicle', 'record'));
    }

    public function maintenance(Vehicle $vehicle): View
    {
        return view('vehicles.maintenance', compact('vehicle'));
    }

    public function documents(Vehicle $vehicle): View
    {
        return view('vehicles.documents', compact('vehicle'));
    }

    public function costs(Vehicle $vehicle): View
    {
        return view('vehicles.costs', compact('vehicle'));
    }

    public function recalls(Vehicle $vehicle): View
    {
        return view('vehicles.recalls', compact('vehicle'));
    }

    public function share(Vehicle $vehicle): View
    {
        return view('vehicles.share', compact('vehicle'));
    }

    public function sell(Vehicle $vehicle): View
    {
        return view('vehicles.sell', compact('vehicle'));
    }

    public function settings(Vehicle $vehicle): View
    {
        return view('vehicles.settings', compact('vehicle'));
    }

    /**
     * A printable "For sale" sign for the car window, with a QR code to the live passport.
     */
    public function windowSign(Vehicle $vehicle, PassportScore $scorer): View|RedirectResponse
    {
        $listing = $vehicle->openListing;
        $link = $listing?->shareLink?->isActive() ? $listing->shareLink : $vehicle->shareLinks->first(fn ($l) => $l->isActive());

        if (! $link) {
            return to_route('vehicles.share', $vehicle)->with('toast', 'Create a share link first — the sign’s QR code points to it.');
        }

        $target = $listing?->isPublic() ? route('listings.show', $listing) : $link->url();

        return view('vehicles.window-sign', [
            'vehicle' => $vehicle,
            'listing' => $listing?->isPublic() ? $listing : null,
            'score' => $scorer->for($vehicle),
            'qr' => Qr::svg($target, 320),
            'target' => $target,
        ]);
    }
}
