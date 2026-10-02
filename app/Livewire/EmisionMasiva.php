<?php

namespace App\Livewire;

use App\Models\Contexto;
use App\Models\Participacion;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use App\Support\NormalizadorIdentidad;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EmisionMasiva extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $contexto_id;

    public $tipo_reconocimiento_id;

    public $plantilla_id;

    public $search = '';

    public $nuevo_dni = '';

    public $nuevo_nombre = '';

    public $nuevo_apellido = '';

    public $nuevo_mail = '';

    public $nuevo_telefono = '';

    public $csv;

    public $contextos = [];

    public $tipos = [];

    public $plantillas = [];

    public bool $procesando = false;

    public int $totalPendientes = 0;

    public int $emitidos = 0;

    public int $errores = 0;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->contextos = Contexto::orderBy('nombre')->get();
        $this->tipos = TipoReconocimiento::activos()->orderBy('orden')->get()->toArray();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedContextoId(): void
    {
        $this->plantilla_id = null;
        $this->cargarPlantillas();
        $this->resetPage();
    }

    public function updatedTipoReconocimientoId(): void
    {
        $this->plantilla_id = null;
        $this->cargarPlantillas();
        $this->resetPage();
    }

    private function cargarPlantillas(): void
    {
        if (! $this->tipo_reconocimiento_id || ! $this->contexto_id) {
            $this->plantillas = [];

            return;
        }

        $this->plantillas = PlantillaCertificado::query()
            ->where('tipo_reconocimiento_id', $this->tipo_reconocimiento_id)
            ->where('contexto_id', $this->contexto_id)
            ->whereNotNull('layout')
            ->orderByDesc('por_defecto')
            ->get()
            ->toArray();

        $porDefecto = collect($this->plantillas)->firstWhere('por_defecto', true) ?? $this->plantillas[0] ?? null;
        $this->plantilla_id = $porDefecto['plantilla_id'] ?? null;
    }

    private function contexto(): ?Contexto
    {
        return $this->contexto_id ? Contexto::find($this->contexto_id) : null;
    }

    public function agregarParticipacion(): void
    {
        $this->validate([
            'nuevo_dni' => 'required|string|max:15',
            'nuevo_nombre' => 'required|string|max:100',
            'nuevo_apellido' => 'required|string|max:100',
            'nuevo_mail' => 'required|email|max:100',
            'nuevo_telefono' => 'required|string|min:6|max:15',
        ]);

        if (! $this->contexto() || ! $this->tipo_reconocimiento_id) {
            $this->dispatch('oops', message: 'Seleccioná un contexto y un tipo de reconocimiento.');

            return;
        }

        $this->registrarParticipacion(
            $this->nuevo_dni,
            $this->nuevo_apellido,
            $this->nuevo_nombre,
            $this->nuevo_mail,
            $this->nuevo_telefono,
        );

        $this->reset(['nuevo_dni', 'nuevo_nombre', 'nuevo_apellido', 'nuevo_mail', 'nuevo_telefono']);
        $this->dispatch('alert', message: 'Persona agregada a la lista.');
    }

    public function importarCsv(): void
    {
        $this->validate(['csv' => 'required|file|mimes:csv,txt|max:5120']);

        if (! $this->contexto() || ! $this->tipo_reconocimiento_id) {
            $this->dispatch('oops', message: 'Seleccioná un contexto y un tipo de reconocimiento antes de importar.');

            return;
        }

        $handle = fopen($this->csv->getRealPath(), 'r');
        $importados = 0;
        $fila = 0;

        while (($columnas = fgetcsv($handle, 1000, ',')) !== false) {
            $fila++;

            if ($fila === 1 && in_array(mb_strtolower(trim((string) ($columnas[0] ?? ''))), ['dni', 'documento'], true)) {
                continue;
            }

            if (count($columnas) < 5 || trim((string) $columnas[0]) === '') {
                continue;
            }

            $this->registrarParticipacion(
                trim((string) $columnas[0]),
                trim((string) $columnas[2]),
                trim((string) $columnas[1]),
                trim((string) $columnas[3]),
                trim((string) $columnas[4]),
            );

            $importados++;
        }

        fclose($handle);
        $this->reset('csv');
        $this->dispatch('alert', message: "Se importaron {$importados} persona(s).");
    }

    public function descargarPlantillaCsv()
    {
        $contenido = "dni,apellido,nombre,mail,telefono\n30111222,Perez,Juan,juan@example.com,3764000000\n";

        return response()->streamDownload(function () use ($contenido) {
            echo $contenido;
        }, 'plantilla-emision-masiva.csv', ['Content-Type' => 'text/csv']);
    }

    private function registrarParticipacion(string $dni, string $apellido, string $nombre, string $mail, string $telefono): void
    {
        DB::transaction(function () use ($dni, $apellido, $nombre, $mail, $telefono) {
            $participante = Participante::where('dni', $dni)->first()
                ?? Participante::create([
                    'nombre' => NormalizadorIdentidad::titulo($nombre),
                    'apellido' => NormalizadorIdentidad::titulo($apellido),
                    'dni' => $dni,
                    'mail' => $mail,
                    'telefono' => $telefono,
                ]);

            Participacion::firstOrCreate(
                [
                    'participante_id' => $participante->participante_id,
                    'origen_type' => Contexto::class,
                    'origen_id' => (string) $this->contexto_id,
                    'tipo_reconocimiento_id' => $this->tipo_reconocimiento_id,
                ],
                ['estado' => Participacion::ESTADO_PENDIENTE]
            );
        });
    }

    public function eliminarParticipacion(string $id): void
    {
        $participacion = Participacion::findOrFail($id);

        if ($participacion->estaEmitida()) {
            $this->dispatch('oops', message: 'No se puede quitar una participación ya emitida.');

            return;
        }

        $participacion->delete();
        $this->dispatch('alert', message: 'Participación eliminada.');
    }

    public function emitirTodas(): void
    {
        if (! $this->plantilla_id || ! $this->contexto_id || ! $this->tipo_reconocimiento_id) {
            $this->dispatch('oops', message: 'Seleccioná contexto, tipo de reconocimiento y plantilla.');

            return;
        }

        $pendientes = $this->participacionesQuery()
            ->where('estado', Participacion::ESTADO_PENDIENTE)
            ->count();

        if ($pendientes === 0) {
            $this->dispatch('oops', message: 'No hay participaciones pendientes de emisión.');

            return;
        }

        // Con worker de colas disponible, despachamos todo el lote.
        if (config('emision.usar_cola')) {
            $ids = $this->participacionesQuery()
                ->where('estado', Participacion::ESTADO_PENDIENTE)
                ->pluck('participacion_id')
                ->all();

            \App\Jobs\EmitirParticipacionesJob::dispatch($ids, (int) $this->plantilla_id, auth()->id());

            $this->dispatch('alert', message: 'Se encolaron '.count($ids).' emisión(es).');

            return;
        }

        $this->totalPendientes = $pendientes;
        $this->emitidos = 0;
        $this->errores = 0;

        // Lotes chicos: se resuelven en un único request.
        if ($pendientes <= (int) config('emision.umbral_sincronico', 25)) {
            $this->emitirPendientesSync();
            $this->procesando = false;
            $this->dispatch('alert', message: $this->resumen());

            return;
        }

        // Lotes grandes: procesamiento por bloques con auto-avance (wire:poll).
        $this->procesando = true;
        $this->procesarLote();
    }

    public function continuarLote(): void
    {
        if (! $this->procesando) {
            return;
        }

        $this->procesarLote();
    }

    private function procesarLote(): void
    {
        $plantilla = PlantillaCertificado::find($this->plantilla_id);

        if (! $plantilla) {
            $this->procesando = false;
            $this->dispatch('oops', message: 'La plantilla seleccionada ya no está disponible.');

            return;
        }

        @set_time_limit(0);

        $ids = $this->participacionesQuery()
            ->where('estado', Participacion::ESTADO_PENDIENTE)
            ->orderBy('created_at')
            ->limit((int) config('emision.tamano_lote', 25))
            ->pluck('participacion_id')
            ->all();

        $this->emitirIds($ids, $plantilla);

        $restantes = $this->participacionesQuery()
            ->where('estado', Participacion::ESTADO_PENDIENTE)
            ->count();

        if ($restantes === 0) {
            $this->procesando = false;
            $this->dispatch('alert', message: 'Emisión finalizada. '.$this->resumen());
        }
    }

    private function emitirPendientesSync(): void
    {
        $plantilla = PlantillaCertificado::find($this->plantilla_id);

        if (! $plantilla) {
            $this->dispatch('oops', message: 'La plantilla seleccionada ya no está disponible.');

            return;
        }

        @set_time_limit(0);

        $ids = $this->participacionesQuery()
            ->where('estado', Participacion::ESTADO_PENDIENTE)
            ->orderBy('created_at')
            ->pluck('participacion_id')
            ->all();

        $this->emitirIds($ids, $plantilla);
    }

    /**
     * @param  array<int, string>  $ids
     */
    private function emitirIds(array $ids, PlantillaCertificado $plantilla): void
    {
        $emitir = app(\App\Actions\EmitirParticipacion::class);

        foreach ($ids as $id) {
            $participacion = Participacion::find($id);

            if (! $participacion) {
                continue;
            }

            try {
                $emitir->handle($participacion, $plantilla, auth()->id());
                $this->emitidos++;
            } catch (\Throwable $e) {
                $this->errores++;

                \Illuminate\Support\Facades\Log::error('Error en emisión masiva.', [
                    'participacion_id' => $id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    private function resumen(): string
    {
        $mensaje = $this->emitidos.' certificado(s) emitido(s)';

        if ($this->errores > 0) {
            $mensaje .= ', '.$this->errores.' con error';
        }

        return $mensaje.'.';
    }

    public function emitirUna(string $id): void
    {
        if (! $this->plantilla_id) {
            $this->dispatch('oops', message: 'Seleccioná una plantilla.');

            return;
        }

        $participacion = Participacion::findOrFail($id);
        $plantilla = PlantillaCertificado::find($this->plantilla_id);

        if (! $plantilla) {
            $this->dispatch('oops', message: 'La plantilla seleccionada ya no está disponible.');

            return;
        }

        try {
            app(\App\Actions\EmitirParticipacion::class)->handle($participacion, $plantilla, auth()->id());
            $this->dispatch('alert', message: 'Certificado emitido.');
        } catch (\Throwable $e) {
            $this->dispatch('oops', message: 'Error: '.$e->getMessage());
        }
    }

    private function participacionesQuery()
    {
        return Participacion::query()
            ->when($this->contexto_id, fn ($q) => $q->where('origen_type', Contexto::class)->where('origen_id', (string) $this->contexto_id))
            ->when($this->tipo_reconocimiento_id, fn ($q) => $q->where('tipo_reconocimiento_id', $this->tipo_reconocimiento_id));
    }

    public function render()
    {
        $participaciones = $this->participacionesQuery()
            ->with(['participante', 'emision'])
            ->when($this->search, function ($q) {
                $busqueda = '%'.$this->search.'%';
                $q->whereHas('participante', function ($p) use ($busqueda) {
                    $p->where('nombre', 'like', $busqueda)
                        ->orWhere('apellido', 'like', $busqueda)
                        ->orWhere('dni', 'like', $busqueda);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.emision-masiva', compact('participaciones'));
    }
}
