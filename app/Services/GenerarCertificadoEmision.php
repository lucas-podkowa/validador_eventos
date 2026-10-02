<?php

namespace App\Services;

use App\Models\ApiCliente;
use App\Models\Contexto;
use App\Models\Emision;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use App\Support\CertificadoPdfAssets;
use App\Support\CertificadoVariables;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Emisor unificado de certificados. Crea la `emision` (fuente de verdad del QR),
 * congela los tokens interpolados y genera el PDF con su código de verificación.
 */
class GenerarCertificadoEmision
{
    /**
     * @param  array<string, mixed>  $datos  Tokens extra específicos de la emisión.
     * @param  array<string, mixed>  $matchDetalle
     */
    public function emitir(
        Participante $participante,
        TipoReconocimiento $tipo,
        ?PlantillaCertificado $plantilla,
        ?Model $origen = null,
        array $datos = [],
        ?string $alcance = null,
        ?int $emitidoPor = null,
        ?ApiCliente $apiCliente = null,
        ?string $externalRef = null,
        ?string $matchEstado = null,
        array $matchDetalle = [],
    ): Emision {
        if (! $plantilla) {
            throw new RuntimeException('No hay una plantilla configurada para el tipo de certificado solicitado.');
        }

        $alcance = $alcance ?: $this->alcancePorDefecto($tipo, $origen);

        if ($apiCliente && $externalRef) {
            $existente = Emision::query()
                ->where('api_cliente_id', $apiCliente->api_cliente_id)
                ->where('external_ref', $externalRef)
                ->first();

            if ($existente) {
                return $existente;
            }
        }

        $contexto = $this->contextoDe($origen);
        $firmantes = CertificadoVariables::firmantesDe($contexto);

        $variables = $this->construirVariables($participante, $origen, $datos, $contexto, $firmantes);
        $origenSnapshot = $this->construirSnapshot($origen);
        $textoSnapshot = CertificadoVariables::reemplazar($plantilla->texto, $variables, false);

        $origenType = $origen ? $origen::class : null;
        $origenId = $origen?->getKey();

        // Reemisión interna: misma persona + origen + tipo. Se reemplaza la emisión
        // existente (y su PDF) en lugar de crear un duplicado. No aplica al flujo
        // externo, que deduplica por api_cliente + external_ref.
        if ($apiCliente === null) {
            $existente = Emision::query()
                ->where('participante_id', $participante->participante_id)
                ->where('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id)
                ->where('estado', Emision::ESTADO_EMITIDO)
                ->whereNull('api_cliente_id')
                ->when($origenType, fn ($q) => $q->where('origen_type', $origenType), fn ($q) => $q->whereNull('origen_type'))
                ->when($origenId, fn ($q) => $q->where('origen_id', $origenId), fn ($q) => $q->whereNull('origen_id'))
                ->first();

            if ($existente) {
                $existente->update([
                    'alcance' => $alcance,
                    'plantilla_id' => $plantilla->plantilla_id,
                    'datos' => $variables,
                    'origen_snapshot' => $origenSnapshot,
                    'texto_snapshot' => $textoSnapshot,
                    'emitido_por' => $emitidoPor,
                    'emitida_en' => now(),
                ]);

                $this->generar($existente);

                return $existente->refresh();
            }
        }

        $emision = Emision::create([
            'participante_id' => $participante->participante_id,
            'tipo_reconocimiento_id' => $tipo->tipo_reconocimiento_id,
            'origen_type' => $origenType,
            'origen_id' => $origenId,
            'alcance' => $alcance,
            'plantilla_id' => $plantilla->plantilla_id,
            'estado' => Emision::ESTADO_EMITIDO,
            'datos' => $variables,
            'origen_snapshot' => $origenSnapshot,
            'texto_snapshot' => $textoSnapshot,
            'api_cliente_id' => $apiCliente?->api_cliente_id,
            'external_ref' => $externalRef,
            'match_estado' => $matchEstado,
            'match_detalle' => $matchDetalle ?: null,
            'emitido_por' => $emitidoPor,
            'emitida_en' => now(),
        ]);

        $this->generar($emision);

        return $emision->refresh();
    }

    /**
     * (Re)genera el PDF de una emisión usando sus tokens congelados.
     */
    public function generar(Emision $emision): string
    {
        $emision->loadMissing(['participante', 'plantilla.contexto.firmantes']);

        $participante = $emision->participante;
        $plantilla = $emision->plantilla;

        if (! $participante || ! $plantilla) {
            throw new RuntimeException('La emisión no tiene participante o plantilla asociada.');
        }

        $contexto = $this->contextoDe($emision->origen) ?? $plantilla->contexto;
        $firmantes = CertificadoVariables::firmantesDe($contexto);
        foreach ($firmantes as &$firmante) {
            $firmante['imagen'] = CertificadoPdfAssets::resolvePrivatePath($firmante['imagen_path']);
        }
        unset($firmante);

        $qrcode = $this->generarQr($emision);
        $qr = 'data:image/svg+xml;base64,'.base64_encode($qrcode);

        $variables = $emision->datos ?? [];
        $background = CertificadoPdfAssets::prepareBackgroundForPdf($plantilla->imagen_path);

        $contenido = Pdf::loadView('certificado', [
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'qr' => $qr,
            'background' => $background,
            'layout' => $plantilla->layout,
            'texto' => $emision->texto_snapshot ?? $plantilla->texto,
            'variables' => $variables,
            'firmas' => $firmantes,
        ])->setPaper('a4', 'landscape')->output();

        $filename = $this->rutaArchivo($emision, $participante);

        if ($emision->certificado_path
            && $emision->certificado_path !== $filename
            && Storage::disk('private')->exists($emision->certificado_path)) {
            Storage::disk('private')->delete($emision->certificado_path);
        }

        Storage::disk('private')->put($filename, $contenido);

        $emision->update([
            'certificado_path' => $filename,
            'qr' => $qrcode,
        ]);

        return $filename;
    }

    public function anular(Emision $emision): void
    {
        $emision->update(['estado' => Emision::ESTADO_ANULADO]);
    }

    private function generarQr(Emision $emision): string
    {
        $url = route('verificar', ['codigo' => $emision->codigo_verificacion]);
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<int, array{nombre?:string, cargo?:string, imagen_path?:string}>  $firmantes
     * @return array<string, string>
     */
    private function construirVariables(
        Participante $participante,
        ?Model $origen,
        array $datos,
        ?Contexto $contexto,
        array $firmantes,
    ): array {
        if ($origen instanceof Evento) {
            $base = CertificadoVariables::paraEvento($origen, $participante, $contexto, $firmantes);
        } elseif ($origen instanceof Contexto) {
            $base = CertificadoVariables::paraContexto($origen, $participante, [], $firmantes);
        } else {
            $base = CertificadoVariables::paraSinOrigen($participante, [], $firmantes);
        }

        foreach ($datos as $clave => $valor) {
            $base[(string) $clave] = (string) ($valor ?? '');
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function construirSnapshot(?Model $origen): array
    {
        if ($origen instanceof Evento) {
            $contexto = $origen->contexto;

            return [
                'tipo' => 'evento',
                'nombre' => $origen->nombre,
                'tipo_evento' => $origen->tipoEvento?->nombre,
                'contexto_nombre' => $contexto?->nombre,
                'contexto_denominacion' => $contexto?->denominacion,
                'institucion' => $contexto?->institucion,
                'resolucion' => $contexto?->resolucion,
                'lugar' => $contexto?->lugar,
                'anio' => $contexto?->anio,
            ];
        }

        if ($origen instanceof Contexto) {
            return [
                'tipo' => 'contexto',
                'nombre' => $origen->nombre,
                'denominacion' => $origen->denominacion,
                'institucion' => $origen->institucion,
                'resolucion' => $origen->resolucion,
                'lugar' => $origen->lugar,
                'anio' => $origen->anio,
            ];
        }

        return [];
    }

    private function contextoDe(?Model $origen): ?Contexto
    {
        if ($origen instanceof Contexto) {
            return $origen;
        }

        if ($origen instanceof Evento) {
            return $origen->contexto;
        }

        return null;
    }

    private function alcancePorDefecto(TipoReconocimiento $tipo, ?Model $origen): string
    {
        if ($origen instanceof Evento) {
            return 'evento';
        }

        if ($origen instanceof Contexto) {
            return 'contexto';
        }

        return $tipo->alcance_sugerido ?: 'sin_origen';
    }

    private function rutaArchivo(Emision $emision, Participante $participante): string
    {
        $slugTipo = Str::slug((string) ($emision->tipoReconocimiento?->slug ?: $emision->alcance));

        return sprintf(
            'certificados/%s/%s/%s_%s_%s.pdf',
            now()->year,
            $slugTipo ?: 'general',
            Str::slug((string) $participante->apellido) ?: 'sin-apellido',
            Str::slug((string) $participante->nombre) ?: 'sin-nombre',
            preg_replace('/\D+/', '', (string) $participante->dni) ?: substr($emision->emision_id, 0, 8)
        );
    }
}
