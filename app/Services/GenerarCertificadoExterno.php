<?php

namespace App\Services;

use App\Models\ApiCliente;
use App\Models\CertificadoExterno;
use App\Models\PlantillaCertificado;
use App\Support\CertificadoPdfAssets;
use App\Support\CertificadoVariables;
use App\Support\NombreCertificado;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Emite un certificado externo (por ejemplo, tutores académicos de PPS):
 * resuelve plantilla y participante, crea el registro, genera el PDF con QR y lo
 * guarda en el disco privado. La plantilla es la misma vista Blade que los eventos.
 */
class GenerarCertificadoExterno
{
    public function __construct(private ResolverParticipanteExterno $resolver) {}

    /**
     * @param  array<string, mixed>  $datos
     */
    public function emitir(ApiCliente $cliente, array $datos): CertificadoExterno
    {
        if (! empty($datos['external_ref'])) {
            $existente = CertificadoExterno::query()
                ->where('api_cliente_id', $cliente->api_cliente_id)
                ->where('external_ref', $datos['external_ref'])
                ->first();

            if ($existente) {
                return $existente;
            }
        }

        $tutor = $datos['tutor'] ?? [];
        $match = $this->resolver->resolver($tutor);
        $plantilla = $this->resolverPlantilla($datos);

        if (! $plantilla) {
            throw new RuntimeException('No hay una plantilla configurada para el tipo de certificado solicitado.');
        }

        try {
            $certificado = DB::transaction(fn () => CertificadoExterno::create([
                'api_cliente_id' => $cliente->api_cliente_id,
                'external_ref' => $datos['external_ref'],
                'tipo' => $datos['tipo'] ?? 'tutor_academico',
                'plantilla_certificado_id' => $plantilla->plantilla_id,
                'contexto_id' => $plantilla->contexto_id ?? ($datos['contexto_id'] ?? null),
                'participante_id' => $match['participante']->participante_id,
                'datos' => $datos,
                'receptor_nombre' => NombreCertificado::formatear(
                    $tutor['apellido'] ?? '',
                    $tutor['nombres'] ?? ($tutor['nombre'] ?? '')
                ),
                'receptor_dni' => (string) ($tutor['dni'] ?? ''),
                'receptor_email' => (string) ($tutor['email'] ?? ($tutor['mail'] ?? '')),
                'match_estado' => $match['match_estado'],
                'match_detalle' => $match['match_detalle'],
                'codigo_verificacion' => $this->codigoUnico(),
                'estado' => CertificadoExterno::ESTADO_EMITIDO,
            ]));
        } catch (QueryException $e) {
            // Carrera por idempotencia: otro request creó la misma external_ref.
            $existente = CertificadoExterno::query()
                ->where('api_cliente_id', $cliente->api_cliente_id)
                ->where('external_ref', $datos['external_ref'])
                ->first();

            if ($existente) {
                return $existente;
            }

            throw $e;
        }

        $this->generar($certificado);

        return $certificado->refresh();
    }

    public function generar(CertificadoExterno $certificado): string
    {
        $certificado->loadMissing(['plantilla.contexto.firmantes', 'contexto.firmantes']);

        $plantilla = $certificado->plantilla;

        if (! $plantilla) {
            throw new RuntimeException('El certificado no tiene plantilla asociada.');
        }

        $contexto = $plantilla->contexto ?? $certificado->contexto;

        $url = route('verificar.externo', ['codigo' => $certificado->codigo_verificacion]);
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
        $qrcode = (new Writer($renderer))->writeString($url);
        $qr = 'data:image/svg+xml;base64,'.base64_encode($qrcode);

        $firmantes = CertificadoVariables::firmantesDe($contexto);
        foreach ($firmantes as &$firmante) {
            $firmante['imagen'] = CertificadoPdfAssets::resolvePrivatePath($firmante['imagen_path']);
        }
        unset($firmante);

        $datos = $certificado->datos ?? [];
        $tutor = $datos['tutor'] ?? [];

        $variables = CertificadoVariables::paraExterno($datos, $contexto, $firmantes);
        $background = CertificadoPdfAssets::prepareBackgroundForPdf($plantilla->imagen_path);

        $contenido = Pdf::loadView('certificado', [
            'nombre' => $tutor['nombres'] ?? ($tutor['nombre'] ?? ''),
            'apellido' => $tutor['apellido'] ?? '',
            'dni' => $tutor['dni'] ?? '',
            'qr' => $qr,
            'background' => $background,
            'layout' => $plantilla->layout,
            'texto' => $plantilla->texto,
            'variables' => $variables,
            'firmas' => $firmantes,
        ])->setPaper('a4', 'landscape')->output();

        $filename = $this->rutaArchivo($certificado);

        if ($certificado->certificado_path
            && $certificado->certificado_path !== $filename
            && Storage::disk('private')->exists($certificado->certificado_path)) {
            Storage::disk('private')->delete($certificado->certificado_path);
        }

        Storage::disk('private')->put($filename, $contenido);

        $certificado->update([
            'certificado_path' => $filename,
            'qrcode' => $qrcode,
        ]);

        return $filename;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function resolverPlantilla(array $datos): ?PlantillaCertificado
    {
        $tipo = $datos['tipo'] ?? 'tutor_academico';
        $contextoId = $datos['contexto_id'] ?? null;

        if (! empty($datos['plantilla_codigo'])) {
            $plantilla = PlantillaCertificado::query()
                ->where('tipo', $tipo)
                ->where('nombre', $datos['plantilla_codigo'])
                ->orderByDesc('por_defecto')
                ->first();

            if ($plantilla) {
                return $plantilla;
            }
        }

        $query = PlantillaCertificado::query()
            ->where('tipo', $tipo)
            ->whereNotNull('contexto_id')
            ->whereNotNull('layout');

        if ($contextoId) {
            $query->where('contexto_id', $contextoId);
        }

        return $query->orderByDesc('por_defecto')->first();
    }

    public function rutaArchivo(CertificadoExterno $certificado): string
    {
        $datos = $certificado->datos ?? [];
        $tutor = $datos['tutor'] ?? [];

        $apellido = Str::slug((string) ($tutor['apellido'] ?? 'tutor'));
        $nombre = Str::slug((string) ($tutor['nombres'] ?? ($tutor['nombre'] ?? '')));
        $dni = preg_replace('/\D+/', '', (string) ($tutor['dni'] ?? '')) ?: 's-dni';
        $tipo = Str::slug((string) $certificado->tipo);

        return sprintf(
            'certificados_externos/%s/%s/%s_%s_%s.pdf',
            now()->year,
            $tipo,
            $apellido ?: 'tutor',
            $nombre ?: 's-nombre',
            $dni
        );
    }

    private function codigoUnico(): string
    {
        do {
            $codigo = Str::random(48);
        } while (CertificadoExterno::where('codigo_verificacion', $codigo)->exists());

        return $codigo;
    }
}
