<?php

namespace App\Http\Controllers;

use App\Models\TherapistDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTherapistDocumentController extends Controller
{
    public function __invoke(TherapistDocument $document): StreamedResponse
    {
        return Storage::disk('local')->download($document->path);
    }
}
