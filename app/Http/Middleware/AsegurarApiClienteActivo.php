<?php

namespace App\Http\Middleware;

use App\Models\ApiCliente;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarApiClienteActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $cliente = $request->user();

        if ($cliente instanceof ApiCliente && ! $cliente->activo) {
            return response()->json([
                'message' => 'El cliente de API está inactivo.',
            ], 403);
        }

        return $next($request);
    }
}
