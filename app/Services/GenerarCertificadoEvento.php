<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Support\CertificadoPdfAssets;
use App\Support\CertificadoVariables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerarCertificadoEvento
{
    /**
     * Resuelve la plantilla a usar para un participante según su rol/aprobación.
     * Prioriza las plantillas del contexto del evento y cae a las de la categoría (legacy).
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
        if ($evento->contexto_id) {
            $plantilla = PlantillaCertificado::query()
                ->where('contexto_id', $evento->contexto_id)
                ->where('tipo', $tipo)
                ->orderByDesc('por_defecto')
                ->first();

            if ($plantilla) {
                return $plantilla;
            }
        }

        return PlantillaCertificado::query()
            ->where('categoria_id', $evento->categoria_id)
            ->whereNull('contexto_id')
            ->where('tipo', $tipo)
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

        return $filename;
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
