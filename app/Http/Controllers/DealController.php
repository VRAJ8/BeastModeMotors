<?php

namespace App\Http\Controllers;

use App\Enums\DealStatus;
use App\Models\Deal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DealController extends Controller
{
    public function index(Request $request): View
    {
        $deals = Deal::involving($request->user())
            ->with(['vehicle.photos', 'buyer', 'seller', 'listing', 'pendingOffer'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->whereNotNull('user_id')->where('user_id', '!=', $request->user()->getKey())])
            ->latest('updated_at')
            ->get();

        return view('deals.index', [
            'buying' => $deals->where('buyer_id', $request->user()->getKey()),
            'selling' => $deals->where('seller_id', $request->user()->getKey()),
        ]);
    }

    public function show(Deal $deal): View
    {
        return view('deals.show', compact('deal'));
    }

    public function billOfSale(Deal $deal): Response
    {
        abort_unless(in_array($deal->status, [DealStatus::Agreed, DealStatus::Completed], true), 404);

        $deal->load('vehicle', 'buyer', 'seller', 'listing');

        return Pdf::loadView('pdf.bill-of-sale', ['deal' => $deal])
            ->setPaper('letter')
            ->download('bill-of-sale-'.str($deal->vehicle->title())->slug().'.pdf');
    }
}
