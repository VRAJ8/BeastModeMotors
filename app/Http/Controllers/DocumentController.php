<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function show(Vehicle $vehicle, Document $document): StreamedResponse
    {
        return self::stream($document);
    }

    public static function stream(Document $document): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        $extension = pathinfo($document->path, PATHINFO_EXTENSION);
        $filename = str($document->name)->slug().($extension ? ".{$extension}" : '');

        return Storage::disk('local')->response($document->path, $filename, [
            'Content-Type' => $document->mime ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0',
        ], 'inline');
    }
}
