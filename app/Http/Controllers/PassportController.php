<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ShareLink;
use App\Services\OdometerAnalyzer;
use App\Services\PassportScore;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public, read-only passports reached through a share link.
 */
class PassportController extends Controller
{
    public function __construct(private PassportScore $scorer, private OdometerAnalyzer $odometer) {}

    public function show(Request $request, ShareLink $shareLink): View
    {
        abort_unless($shareLink->isActive(), 404);

        if ($request->user()?->getKey() !== $shareLink->vehicle->user_id) {
            $shareLink->recordView();
        }

        return view('passport.show', $this->data($shareLink));
    }

    public function pdf(ShareLink $shareLink): Response
    {
        abort_unless($shareLink->isActive(), 404);

        $data = $this->data($shareLink);
        $filename = str($shareLink->vehicle->title())->slug().'-passport.pdf';

        return Pdf::loadView('pdf.passport', $data)->setPaper('letter')->download($filename);
    }

    public function document(ShareLink $shareLink, Document $document): StreamedResponse
    {
        abort_unless(
            $shareLink->isActive()
            && $shareLink->show_documents
            && $document->vehicle_id === $shareLink->vehicle_id
            && $document->service_record_id !== null
            && $document->type->transfersWithCar(),
            404,
        );

        return DocumentController::stream($document);
    }

    /**
     * @return array<string, mixed>
     */
    private function data(ShareLink $link): array
    {
        $vehicle = $link->vehicle->load([
            'photos', 'ownerships', 'readings', 'reminders', 'recalls',
            'records' => fn ($q) => $q->withCount('documents')->with(['documents' => fn ($d) => $d->transferable()]),
        ]);

        return [
            'link' => $link,
            'vehicle' => $vehicle,
            'score' => $this->scorer->for($vehicle),
            'anomalies' => $this->odometer->anomalies($vehicle->readings),
            'milesPerYear' => $this->odometer->milesPerYear($vehicle->readings),
            'listing' => $vehicle->openListing?->isPublic() ? $vehicle->openListing : null,
        ];
    }
}
