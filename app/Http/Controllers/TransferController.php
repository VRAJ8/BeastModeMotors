<?php

namespace App\Http\Controllers;

use App\Models\PassportTransfer;
use App\Services\PassportTransfers;
use App\Support\OdometerDisclosure;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TransferController extends Controller
{
    /**
     * The page a transfer link opens. Guests see what's being offered and are sent back here after signing in.
     */
    public function show(Request $request, string $token, PassportTransfers $transfers): View
    {
        $transfer = $transfers->find($token);
        abort_unless($transfer, 404);

        if (! $request->user()) {
            redirect()->setIntendedUrl($request->fullUrl());
        }

        return view('transfers.show', ['transfer' => $transfer->load('vehicle', 'sender')]);
    }

    public function odometerDisclosure(Request $request, PassportTransfer $transfer): Response
    {
        $user = $request->user();
        abort_unless(in_array($user->getKey(), [$transfer->from_user_id, $transfer->to_user_id], true), 403);
        abort_unless(in_array($transfer->status(), ['pending', 'accepted'], true), 404);
        $transfer->load('vehicle', 'sender', 'recipient');
        abort_unless(OdometerDisclosure::required($transfer->vehicle, $transfer->accepted_at), 404);

        return Pdf::loadView('pdf.odometer-disclosure', [
            'reference' => 'BMM-T'.str_pad($transfer->id, 6, '0', STR_PAD_LEFT),
            'vehicle' => $transfer->vehicle,
            'mileage' => $transfer->sale_mileage,
            'status' => $transfer->odometer_status,
            'date' => $transfer->accepted_at,
            'sellerName' => $transfer->sender->name,
            'buyerName' => $transfer->recipient?->name,
        ])->setPaper('letter')->download('odometer-disclosure-'.str($transfer->vehicle->title())->slug().'.pdf');
    }
}
