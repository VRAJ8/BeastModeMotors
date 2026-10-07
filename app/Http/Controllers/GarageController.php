<?php

namespace App\Http\Controllers;

use App\Enums\DealStatus;
use App\Enums\VerificationStatus;
use App\Models\Deal;
use App\Models\Document;
use App\Models\Reminder;
use App\Models\ShopVerification;
use App\Models\Vehicle;
use App\Services\PassportScore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageController extends Controller
{
    public function index(Request $request, PassportScore $scorer): View
    {
        $user = $request->user();

        $vehicles = $user->vehicles()
            ->with(['photos', 'reminders', 'recalls', 'openListing', 'readings', 'ownerships', 'records' => fn ($q) => $q->withCount('documents')])
            ->get();

        $scores = $vehicles->mapWithKeys(fn (Vehicle $v) => [$v->getKey() => $scorer->for($v)]);

        // Everything that needs the owner's attention, most urgent first.
        $alerts = collect();

        foreach ($vehicles as $vehicle) {
            foreach ($vehicle->recalls->filter->isOpen() as $recall) {
                $alerts->push(['tone' => 'danger', 'title' => "Open recall: {$recall->component}", 'meta' => $vehicle->displayName(), 'url' => route('vehicles.recalls', $vehicle)]);
            }

            foreach ($vehicle->reminders as $reminder) {
                $status = $reminder->status($vehicle->current_mileage);

                if (in_array($status, [Reminder::OVERDUE, Reminder::DUE_SOON], true)) {
                    $alerts->push([
                        'tone' => $status === Reminder::OVERDUE ? 'danger' : 'warning',
                        'title' => $reminder->task.($status === Reminder::OVERDUE ? ' is overdue' : ' is due soon'),
                        'meta' => $vehicle->displayName().' · due '.$reminder->dueLabel($vehicle->current_mileage),
                        'url' => route('vehicles.maintenance', $vehicle),
                    ]);
                }
            }
        }

        Document::whereIn('vehicle_id', $vehicles->modelKeys())
            ->whereNotNull('expires_on')
            ->where('expires_on', '<=', now()->addDays(30))
            ->with('vehicle')
            ->get()
            ->each(fn (Document $doc) => $alerts->push([
                'tone' => $doc->expires_on->isPast() ? 'danger' : 'warning',
                'title' => $doc->type->getLabel().($doc->expires_on->isPast() ? ' expired ' : ' expires ').$doc->expires_on->diffForHumans(),
                'meta' => $doc->vehicle->displayName().' · '.$doc->name,
                'url' => route('vehicles.documents', $doc->vehicle_id),
            ]));

        $pending = ShopVerification::where('status', VerificationStatus::Pending)
            ->where('expires_at', '>', now())
            ->whereHas('record', fn ($q) => $q->whereIn('vehicle_id', $vehicles->modelKeys()))
            ->count();

        $deals = Deal::involving($user)
            ->whereIn('status', [DealStatus::Open, DealStatus::Agreed])
            ->with(['vehicle.photos', 'buyer', 'seller', 'listing'])
            ->latest('updated_at')
            ->get();

        return view('garage.index', [
            'vehicles' => $vehicles,
            'scores' => $scores,
            'alerts' => $alerts->sortBy(fn ($a) => $a['tone'] === 'danger' ? 0 : 1)->values(),
            'pendingVerifications' => $pending,
            'deals' => $deals,
        ]);
    }

    public function create(): View
    {
        return view('garage.create');
    }
}
