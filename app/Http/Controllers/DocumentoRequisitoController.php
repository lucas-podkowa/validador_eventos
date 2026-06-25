<?php

namespace App\Http\Controllers;

use App\Models\DocumentoPresentado;
use Illuminate\Support\Facades\Storage;

class DocumentoRequisitoController extends Controller
{
    public function show(DocumentoPresentado $documento)
    {
        abort_if(! $documento->path, 404);
        abort_if(! Storage::disk('private')->exists($documento->path), 404);

        return Storage::disk('private')->download($documento->path);
    }
}
