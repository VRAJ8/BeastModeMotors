<?php

namespace App\Http\Controllers;

use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Listing;
use App\Services\ListingPublisher;
use App\Services\OdometerAnalyzer;
use App\Services\PassportScore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(): View
    {
        return view('marketplace.index');
    }

    public function show(Request $request, Listing $listing, PassportScore $scorer, OdometerAnalyzer $odometer, ListingPublisher $publisher): View
    {
        $user = $request->user();
        // "Seller" only while they still own the car: after a sale the listing must not become a window
        // into the new owner's passport.
        $isSeller = $user !== null && $user->getKey() === $listing->seller_id && $listing->vehicle->user_id === $listing->seller_id;

        abort_unless($listing->isPublic() || $isSeller || $user?->is_admin, 404);

        if (! $isSeller) {
            $listing->increment('views');
        }

        $vehicle = $listing->vehicle->load([
            'photos', 'ownerships', 'readings', 'reminders', 'recalls',
            'records' => fn ($q) => $q->withCount('documents')->with(['shop', 'documents' => fn ($d) => $d->transferable()]),
        ]);

        $publisher->refreshScore($listing);

        return view('marketplace.show', [
            'listing' => $listing->load('seller', 'shareLink'),
            'vehicle' => $vehicle,
            'score' => $scorer->for($vehicle),
            'anomalies' => $odometer->anomalies($vehicle->readings),
            'milesPerYear' => $odometer->milesPerYear($vehicle->readings),
            'isSeller' => $isSeller,
            'existingDeal' => $user ? Deal::where('listing_id', $listing->getKey())
                ->where('buyer_id', $user->getKey())
                ->whereIn('status', [DealStatus::Open, DealStatus::Agreed])
                ->first() : null,
            'similar' => Listing::public()
                ->whereKeyNot($listing->getKey())
                ->whereHas('vehicle', fn ($q) => $q->where('make', $vehicle->make))
                ->with('vehicle.photos')
                ->take(3)
                ->get(),
        ]);
    }

    public function saved(Request $request): View
    {
        return view('marketplace.saved', [
            'listings' => $request->user()->savedListings()->with('vehicle.photos')->latest('saved_listings.created_at')->get(),
        ]);
    }
}
