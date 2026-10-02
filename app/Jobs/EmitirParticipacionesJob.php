<?php

namespace App\Jobs;

use App\Actions\EmitirParticipacion;
use App\Models\Participacion;
use App\Models\PlantillaCertificado;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmitirParticipacionesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $participacionIds
     */
    public function __construct(
        public array $participacionIds,
        public int $plantillaId,
        public ?int $emitidoPor = null,
    ) {}

    public function handle(EmitirParticipacion $emitir): void
    {
        $plantilla = PlantillaCertificado::find($this->plantillaId);

        if (! $plantilla) {
            return;
        }

        Participacion::query()
            ->whereIn('participacion_id', $this->participacionIds)
            ->get()
            ->each(function (Participacion $participacion) use ($emitir, $plantilla) {
                try {
                    $emitir->handle($participacion, $plantilla, $this->emitidoPor);
                } catch (\Throwable $e) {
                    Log::error('Error al emitir participación en cola.', [
                        'participacion_id' => $participacion->participacion_id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }
}
