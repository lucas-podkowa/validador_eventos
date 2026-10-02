<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContextoExternoResource;
use App\Models\Contexto;
use App\Models\TipoReconocimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class ContextoExternoController extends Controller
{
    /**
     * Lista los contextos que tienen al menos una plantilla emitible (con layout).
     * PPS usa esta información para obtener el `contexto_id` y el `plantilla_codigo`
     * que debe enviar al emitir.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tipo = null;
        $tipoSlug = $request->query('tipo');

        if ($tipoSlug !== null && $tipoSlug !== '') {
            $slug = TipoReconocimiento::slugDesdeAlias((string) $tipoSlug);
            $tipo = TipoReconocimiento::where('slug', $slug)->first();

            if (! $tipo) {
                throw ValidationException::withMessages([
                    'tipo' => ["No existe el tipo de reconocimiento '{$slug}'."],
                ]);
            }
        }

        $filtrar = function ($query) use ($tipo) {
            $query->whereNotNull('layout');

            if ($tipo) {
                $query->where('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id);
            }
        };

        $contextos = Contexto::query()
            ->whereHas('plantillas', $filtrar)
            ->with(['plantillas' => function ($query) use ($filtrar) {
                $filtrar($query);
                $query->orderByDesc('por_defecto')->with('tipoReconocimiento');
            }])
            ->orderBy('nombre')
            ->get();

        return ContextoExternoResource::collection($contextos);
    }
}
