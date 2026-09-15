<?php

namespace App\Services;

use App\Models\CertificadoEmitido;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Support\CertificadoPdfAssets;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReemitirCertificadosParticipante
{
    /**
     * Re-emite todos los certificados de eventos y títulos de un participante.
     * Se usa cuando cambia su DNI, porque el DNI va impreso y en el nombre del archivo.
     *
     * @return array{eventos:int, titulos:int, omitidos:int}
     */
    public function participante(Participante $participante): array
    {
        $resultado = ['eventos' => 0, 'titulos' => 0, 'omitidos' => 0];

        $participante->eventoParticipantes()
            ->whereNotNull('certificado_path')
            ->with(['evento.tipoEvento', 'rol'])
            ->get()
            ->each(function (EventoParticipante $relacion) use (&$resultado) {
                $this->reemitirEvento($relacion)
                    ? $resultado['eventos']++
                    : $resultado['omitidos']++;
            });

        CertificadoEmitido::query()
            ->where('participante_id', $participante->participante_id)
            ->where('anulado', false)
            ->whereNotNull('certificado_path')
            ->get()
            ->each(function (CertificadoEmitido $certificado) use (&$resultado) {
                $this->reemitirTitulo($certificado)
                    ? $resultado['titulos']++
                    : $resultado['omitidos']++;
            });

        return $resultado;
    }

    public function reemitirEvento(EventoParticipante $relacion): bool
    {
        $evento = $relacion->evento;
        $participante = $relacion->participante;

        if (! $evento || ! $participante) {
            return false;
        }

        $plantilla = $this->plantillaPara($evento, $relacion);

        if (! $plantilla?->imagen_path) {
            return false;
        }

        $background = CertificadoPdfAssets::prepareBackgroundForPdf($plantilla->imagen_path);

        if (! $background) {
            Log::warning('No se pudo preparar la plantilla al re-emitir un certificado.', [
                'evento_id' => $evento->evento_id,
                'participante_id' => $participante->participante_id,
                'plantilla_id' => $plantilla->plantilla_id,
            ]);

            return false;
        }

        $pdf = Pdf::loadView('certificado', [
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'qr' => $relacion->qrcode ? 'data:image/svg+xml;base64,'.base64_encode($relacion->qrcode) : '',
            'background' => $background,
        ])->setPaper('a4', 'landscape');

        $folder = 'certificados/'.now()->year."/{$evento->tipoEvento->nombre}/{$evento->nombre}";
        $filename = "{$folder}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";

        $this->reemplazarArchivo($relacion->certificado_path, $filename, $pdf->output());
        $relacion->update(['certificado_path' => $filename]);

        return true;
    }

    protected function reemitirTitulo(CertificadoEmitido $certificado): bool
    {
        $participante = $certificado->participante;
        $titulo = $certificado->tituloIntermedio;

        if (! $participante || ! $titulo || ! $titulo->carrera) {
            return false;
        }

        $background = null;
        if ($titulo->imagen_plantilla_path) {
            $background = Storage::disk('public')->exists($titulo->imagen_plantilla_path)
                ? Storage::disk('public')->path($titulo->imagen_plantilla_path)
                : Storage::path($titulo->imagen_plantilla_path);
        }

        $pdf = Pdf::loadView('certificado_titulo', [
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'titulo' => $titulo->nombre,
            'carrera' => $titulo->carrera->nombre,
            'fecha' => now()->format('d/m/Y'),
            'background' => $background,
        ])->setPaper('a4', 'landscape');

        $filename = 'certificados_titulos/'.now()->year."/{$titulo->carrera->codigo}/{$titulo->id}/".
            "{$participante->apellido}_{$participante->nombre}_{$participante->dni}.pdf";

        $this->reemplazarArchivo($certificado->certificado_path, $filename, $pdf->output());
        $certificado->update(['certificado_path' => $filename]);

        return true;
    }

    protected function plantillaPara(Evento $evento, EventoParticipante $relacion): ?PlantillaCertificado
    {
        $rol = mb_strtolower((string) $relacion->rol?->nombre);

        $tipo = match (true) {
            str_contains($rol, 'disertante') => 'disertante',
            str_contains($rol, 'colaborador') => 'colaborador',
            $evento->por_aprobacion && $relacion->aprobado => 'aprobacion',
            default => 'asistencia',
        };

        return PlantillaCertificado::query()
            ->where('categoria_id', $evento->categoria_id)
            ->where('tipo', $tipo)
            ->orderByDesc('por_defecto')
            ->first();
    }

    protected function reemplazarArchivo(?string $anterior, string $nuevo, string $contenido): void
    {
        $disk = Storage::disk('private');

        if ($anterior && $anterior !== $nuevo && $disk->exists($anterior)) {
            $disk->delete($anterior);
        }

        $disk->put($nuevo, $contenido);
    }
}
