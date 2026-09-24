<?php

namespace App\Services;

use App\Models\CertificadoEmitido;
use App\Models\EventoParticipante;
use App\Models\Participante;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReemitirCertificadosParticipante
{
    public function __construct(
        private readonly GenerarCertificadoEvento $generador,
    ) {}

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
        return $this->generador->generar($relacion) !== null;
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

    protected function reemplazarArchivo(?string $anterior, string $nuevo, string $contenido): void
    {
        $disk = Storage::disk('private');

        if ($anterior && $anterior !== $nuevo && $disk->exists($anterior)) {
            $disk->delete($anterior);
        }

        $disk->put($nuevo, $contenido);
    }
}
