<?php

namespace App\Services;

use App\Models\Emision;
use App\Models\Evento;
use App\Models\EventoParticipante;
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
use Illuminate\Support\Facades\Storage;

class GenerarCertificadoEvento
{
    /**
     * Resuelve la plantilla a usar para un participante según su rol/aprobación.
     * Sólo se consideran las plantillas del contexto del evento.
     */
    public function plantillaPara(Evento $evento, EventoParticipante $relacion): ?PlantillaCertificado
    {
        return $this->buscarPlantilla($evento, $this->tipoPara($evento, $relacion));
    }

    public function tipoPara(Evento $evento, EventoParticipante $relacion): string
    {
        $rol = mb_strtolower((string) $relacion->rol?->nombre);

        return match (true) {
            str_contains($rol, 'disertante') => 'disertante',
            str_contains($rol, 'colaborador') => 'colaborador',
            $evento->por_aprobacion && $relacion->aprobado => 'aprobacion',
            default => 'asistencia',
        };
    }

    public function buscarPlantilla(Evento $evento, string $tipo): ?PlantillaCertificado
    {
        if (! $evento->contexto_id) {
            return null;
        }

        $tipoId = $this->tipoReconocimientoId($tipo);

        if (! $tipoId) {
            return null;
        }

        return PlantillaCertificado::query()
            ->where('contexto_id', $evento->contexto_id)
            ->where('tipo_reconocimiento_id', $tipoId)
            ->orderByDesc('por_defecto')
            ->first();
    }

    /**
     * Genera (o re-genera) el PDF de un participante y actualiza su ruta en el vínculo.
     *
     * @param  string|null  $backgroundAbsOverride  Ruta absoluta de un fondo legacy cargado manualmente.
     */
    public function generar(EventoParticipante $relacion, ?PlantillaCertificado $plantilla = null, ?string $backgroundAbsOverride = null): ?string
    {
        $evento = $relacion->evento()
            ->with(['tipoEvento', 'contexto.firmantes', 'categoria'])
            ->first();
        $participante = $relacion->participante;

        if (! $evento || ! $participante) {
            return null;
        }

        $plantilla = $plantilla ?? $this->plantillaPara($evento, $relacion);

        $emision = $this->emisionPara($relacion, $evento, $participante, $plantilla);

        // Los certificados nuevos validan por código unificado. Los ya impresos
        // (con url propia) conservan su QR legacy intacto.
        if (! $relacion->url) {
            $relacion->url = route('verificar', ['codigo' => $emision->codigo_verificacion]);
            $relacion->qrcode = $this->qrSvg($relacion->url);
            $relacion->save();
        }

        $contenido = $this->render($evento, $participante, $plantilla, $relacion->qrcode, $backgroundAbsOverride);

        if ($contenido === null) {
            return null;
        }

        $filename = $this->rutaArchivo($evento, $participante);

        if ($relacion->certificado_path && $relacion->certificado_path !== $filename && Storage::disk('private')->exists($relacion->certificado_path)) {
            Storage::disk('private')->delete($relacion->certificado_path);
        }

        Storage::disk('private')->put($filename, $contenido);
        $relacion->update(['certificado_path' => $filename]);

        $this->actualizarEmision($emision, $evento, $participante, $plantilla, $relacion, $filename);

        return $filename;
    }

    /**
     * Registra/actualiza la emisión unificada de un vínculo sin regenerar el PDF.
     * Se usa cuando el PDF ya fue generado por otro flujo (p. ej. fondo manual).
     */
    public function sincronizarEmision(EventoParticipante $relacion, ?PlantillaCertificado $plantilla = null): void
    {
        $evento = $relacion->evento()
            ->with(['tipoEvento', 'contexto.firmantes', 'categoria'])
            ->first();
        $participante = $relacion->participante;

        if (! $evento || ! $participante || ! $relacion->certificado_path) {
            return;
        }

        $plantilla = $plantilla ?? $this->plantillaPara($evento, $relacion);

        $emision = $this->emisionPara($relacion, $evento, $participante, $plantilla);

        $this->actualizarEmision($emision, $evento, $participante, $plantilla, $relacion, $relacion->certificado_path);
    }

    /**
     * Crea o recupera la emisión unificada asociada a un vínculo evento–participante.
     */
    private function emisionPara(EventoParticipante $relacion, Evento $evento, Participante $participante, ?PlantillaCertificado $plantilla): Emision
    {
        $tipoId = $this->tipoReconocimientoId($this->tipoPara($evento, $relacion));

        $emision = Emision::query()
            ->where('origen_type', Evento::class)
            ->where('origen_id', $evento->evento_id)
            ->where('participante_id', $participante->participante_id)
            ->where('tipo_reconocimiento_id', $tipoId)
            ->first();

        if ($emision) {
            return $emision;
        }

        return Emision::create([
            'participante_id' => $participante->participante_id,
            'tipo_reconocimiento_id' => $tipoId,
            'origen_type' => Evento::class,
            'origen_id' => $evento->evento_id,
            'alcance' => 'evento',
            'plantilla_id' => $plantilla?->plantilla_id,
            'estado' => Emision::ESTADO_EMITIDO,
            'emitido_por' => auth()->id(),
            'emitida_en' => now(),
        ]);
    }

    private function actualizarEmision(
        Emision $emision,
        Evento $evento,
        Participante $participante,
        ?PlantillaCertificado $plantilla,
        EventoParticipante $relacion,
        string $filename,
    ): void {
        $contexto = $evento->contexto;
        $firmantes = CertificadoVariables::firmantesDe($contexto);
        $variables = CertificadoVariables::paraEvento($evento, $participante, $contexto, $firmantes);

        $emision->update([
            'plantilla_id' => $plantilla?->plantilla_id,
            'certificado_path' => $filename,
            'datos' => $variables,
            'origen_snapshot' => [
                'tipo' => 'evento',
                'nombre' => $evento->nombre,
                'tipo_evento' => $evento->tipoEvento?->nombre,
                'contexto_nombre' => $contexto?->nombre,
                'contexto_denominacion' => $contexto?->denominacion,
                'institucion' => $contexto?->institucion,
                'resolucion' => $contexto?->resolucion,
                'lugar' => $contexto?->lugar,
                'anio' => $contexto?->anio,
            ],
            'texto_snapshot' => CertificadoVariables::reemplazar($plantilla?->texto, $variables, false),
        ]);
    }

    private function tipoReconocimientoId(string $tipo): ?int
    {
        return TipoReconocimiento::where('slug', $tipo)->value('tipo_reconocimiento_id');
    }

    private function qrSvg(string $url): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($url);
    }

    /**
     * Devuelve el binario del PDF o null si no se pudo resolver el fondo.
     */
    public function render(Evento $evento, Participante $participante, ?PlantillaCertificado $plantilla, ?string $qrSvg, ?string $backgroundAbsOverride = null): ?string
    {
        $qr = $qrSvg ? 'data:image/svg+xml;base64,'.base64_encode($qrSvg) : '';

        if ($plantilla?->esDinamica()) {
            return $this->renderDinamico($evento, $participante, $plantilla, $qr);
        }

        $background = $backgroundAbsOverride
            ?: ($plantilla?->imagen_path ? CertificadoPdfAssets::prepareBackgroundForPdf($plantilla->imagen_path) : null);

        if (! $background) {
            return null;
        }

        return $this->pdf([
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'qr' => $qr,
            'background' => $background,
            'layout' => null,
        ]);
    }

    private function renderDinamico(Evento $evento, Participante $participante, PlantillaCertificado $plantilla, string $qr): string
    {
        $contexto = $evento->contexto;

        $firmantes = CertificadoVariables::firmantesDe($contexto);
        foreach ($firmantes as &$firmante) {
            $firmante['imagen'] = CertificadoPdfAssets::resolvePrivatePath($firmante['imagen_path']);
        }
        unset($firmante);

        $variables = CertificadoVariables::paraEvento($evento, $participante, $contexto, $firmantes);

        $background = CertificadoPdfAssets::prepareBackgroundForPdf($plantilla->imagen_path);

        return $this->pdf([
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'qr' => $qr,
            'background' => $background,
            'layout' => $plantilla->layout,
            'texto' => $plantilla->texto,
            'variables' => $variables,
            'firmas' => $firmantes,
        ]);
    }

    private function pdf(array $datos): string
    {
        return Pdf::loadView('certificado', $datos)
            ->setPaper('a4', 'landscape')
            ->output();
    }

    public function rutaArchivo(Evento $evento, Participante $participante): string
    {
        $year = now()->year;
        $tipoEvento = $evento->tipoEvento->nombre;
        $nombreEvento = $evento->nombre;

        return "certificados/{$year}/{$tipoEvento}/{$nombreEvento}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";
    }
}
