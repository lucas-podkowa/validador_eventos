<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmitirCertificadoExternoRequest;
use App\Http\Resources\CertificadoExternoResource;
use App\Mail\CertificadoTutorMail;
use App\Models\Emision;
use App\Services\GenerarCertificadoExterno;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class CertificadoExternoController extends Controller
{
    public function store(EmitirCertificadoExternoRequest $request, GenerarCertificadoExterno $generador): JsonResponse
    {
        $cliente = $request->user();
        $datos = $request->validated();

        $existia = Emision::query()
            ->where('api_cliente_id', $cliente->api_cliente_id)
            ->where('external_ref', $datos['external_ref'])
            ->exists();

        try {
            $certificado = $generador->emitir($cliente, $datos);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $certificado->loadMissing(['participante', 'tipoReconocimiento', 'plantilla', 'origen']);

        $email = $certificado->participante?->mail;

        if (! $existia && $email) {
            $mail = new CertificadoTutorMail($certificado);

            // Sin worker de colas configurado, el envío debe ser síncrono.
            config('emision.usar_cola')
                ? Mail::to($email)->queue($mail)
                : Mail::to($email)->send($mail);
        }

        $resource = (new CertificadoExternoResource($certificado))
            ->response()
            ->setStatusCode($existia ? 200 : 201);

        $resource->header('Idempotent-Replay', $existia ? 'true' : 'false');

        return $resource;
    }

    public function show(Emision $certificado): CertificadoExternoResource
    {
        $cliente = request()->user();

        abort_unless(
            $cliente && (int) $certificado->api_cliente_id === (int) $cliente->api_cliente_id,
            404
        );

        $certificado->loadMissing(['participante', 'tipoReconocimiento', 'plantilla', 'origen']);

        return new CertificadoExternoResource($certificado);
    }
}
