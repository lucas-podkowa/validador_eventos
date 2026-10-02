<?php

namespace App\Console\Commands;

use App\Models\Emision;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use App\Support\CertificadoVariables;
use Illuminate\Console\Command;

class BackfillEmisiones extends Command
{
    protected $signature = 'emisiones:backfill {--chunk=200}';

    protected $description = 'Crea emisiones unificadas para los certificados de evento ya emitidos (idempotente).';

    public function handle(): int
    {
        $creadas = 0;
        $existentes = 0;

        EventoParticipante::query()
            ->whereNotNull('certificado_path')
            ->with(['evento.tipoEvento', 'evento.contexto.firmantes', 'participante', 'rol'])
            ->chunkById('evento_participantes_id', (int) $this->option('chunk'), function ($relaciones) use (&$creadas, &$existentes) {
                foreach ($relaciones as $relacion) {
                    $evento = $relacion->evento;
                    $participante = $relacion->participante;

                    if (! $evento || ! $participante) {
                        continue;
                    }

                    $slug = $this->tipoSlug($relacion, $evento);
                    $tipoId = TipoReconocimiento::where('slug', $slug)->value('tipo_reconocimiento_id');

                    $existente = Emision::query()
                        ->where('origen_type', Evento::class)
                        ->where('origen_id', $evento->evento_id)
                        ->where('participante_id', $participante->participante_id)
                        ->where('tipo_reconocimiento_id', $tipoId)
                        ->first();

                    if ($existente) {
                        $existentes++;

                        continue;
                    }

                    $contexto = $evento->contexto;
                    $plantilla = $this->resolverPlantilla($evento, $slug);
                    $firmantes = CertificadoVariables::firmantesDe($contexto);
                    $variables = CertificadoVariables::paraEvento($evento, $participante, $contexto, $firmantes);

                    Emision::create([
                        'participante_id' => $participante->participante_id,
                        'tipo_reconocimiento_id' => $tipoId,
                        'origen_type' => Evento::class,
                        'origen_id' => $evento->evento_id,
                        'alcance' => 'evento',
                        'plantilla_id' => $plantilla?->plantilla_id,
                        'estado' => $relacion->aprobado === false && $evento->por_aprobacion
                            ? Emision::ESTADO_ANULADO
                            : Emision::ESTADO_EMITIDO,
                        'certificado_path' => $relacion->certificado_path,
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
                        'emitida_en' => now(),
                    ]);

                    $creadas++;
                }
            });

        $this->info("Emisiones creadas: {$creadas}. Ya existentes: {$existentes}.");

        return self::SUCCESS;
    }

    private function tipoSlug(EventoParticipante $relacion, Evento $evento): string
    {
        $rol = mb_strtolower((string) $relacion->rol?->nombre);

        return match (true) {
            str_contains($rol, 'disertante') => 'disertante',
            str_contains($rol, 'colaborador') => 'colaborador',
            $evento->por_aprobacion && $relacion->aprobado => 'aprobacion',
            default => 'asistencia',
        };
    }

    private function resolverPlantilla(Evento $evento, string $slug): ?PlantillaCertificado
    {
        $tipoId = TipoReconocimiento::where('slug', $slug)->value('tipo_reconocimiento_id');

        if (! $tipoId) {
            return null;
        }

        return PlantillaCertificado::query()
            ->where('tipo_reconocimiento_id', $tipoId)
            ->orderByDesc('por_defecto')
            ->first();
    }
}
