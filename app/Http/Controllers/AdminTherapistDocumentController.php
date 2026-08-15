<?php

namespace App\Http\Controllers;

use App\Models\TherapistDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminTherapistDocumentController extends Controller
{
    /**
     * Sajikan berkas apa adanya agar bisa dipratinjau langsung di panel.
     * Tombol unduh cukup memakai atribut `download` di sisi HTML, jadi satu rute ini melayani keduanya.
     */
    public function __invoke(TherapistDocument $document): StreamedResponse
    {
        return Storage::disk('local')->response($document->path);
    }
}
