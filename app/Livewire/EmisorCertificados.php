<?php

namespace App\Livewire;

use App\Actions\BuscarParticipanteSimilar;
use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\DuplicadoRevision;
use App\Models\Emision;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use App\Rules\LargoNombreCertificado;
use App\Services\GenerarCertificadoEmision;
use App\Support\NormalizadorIdentidad;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EmisorCertificados extends Component
{
    use WithPagination;

    public $origen_tipo = 'evento';

    public $categoria_id;

    public $evento_id;

    public $contexto_id;

    public $tipo_reconocimiento_id;

    public $plantilla_id;

    public $nombre = '';

    public $apellido = '';

    public $dni = '';

    public $telefono = '';

    public $mail = '';

    public ?array $participanteExistente = null;

    public ?array $similar = null;

    public ?string $decision_similar = null;

    public $categorias = [];

    public $eventos = [];

    public $contextos = [];

    public $tipos = [];

    public $plantillas = [];

    public int $porPagina = 10;

    public function mount(): void
    {
        $this->categorias = CategoriaEvento::orderBy('nombre')->get();
        $this->tipos = TipoReconocimiento::activos()->orderBy('orden')->get()->toArray();
        $this->cargarContextos();
        $this->cargarEventos();
    }

    public function updatedOrigenTipo(): void
    {
        $this->reset(['categoria_id', 'evento_id', 'contexto_id', 'plantilla_id']);
        $this->plantillas = [];
        $this->cargarContextos();
        $this->cargarEventos();
    }

    public function updatedCategoriaId(): void
    {
        $this->reset(['contexto_id', 'evento_id', 'plantilla_id']);
        $this->plantillas = [];
        $this->cargarContextos();
        $this->cargarEventos();
    }

    public function updatedEventoId(): void
    {
        $this->plantilla_id = null;
        $this->cargarPlantillas();
    }

    public function updatedContextoId(): void
    {
        $this->plantilla_id = null;

        if ($this->origen_tipo === 'evento') {
            $this->evento_id = null;
            $this->plantillas = [];
            $this->cargarEventos();

            return;
        }

        $this->cargarPlantillas();
    }

    public function updatedTipoReconocimientoId(): void
    {
        $this->plantilla_id = null;
        $this->cargarPlantillas();
    }

    private function alcance(): string
    {
        return $this->origen_tipo === 'contexto' ? 'contexto' : 'evento';
    }

    /**
     * Contextos disponibles: al emitir por evento se acotan a la categoría elegida.
     */
    private function cargarContextos(): void
    {
        if ($this->origen_tipo === 'evento') {
            $this->contextos = $this->categoria_id
                ? Contexto::where('categoria_id', $this->categoria_id)->orderBy('nombre')->get()
                : collect();

            return;
        }

        $this->contextos = Contexto::orderBy('nombre')->get();
    }

    /**
     * Eventos finalizados del contexto seleccionado.
     */
    private function cargarEventos(): void
    {
        $this->eventos = $this->origen_tipo === 'evento' && $this->contexto_id
            ? Evento::where('estado', 'Finalizado')
                ->where('contexto_id', $this->contexto_id)
                ->orderByDesc('fecha_inicio')
                ->get()
            : collect();
    }

    private function cargarPlantillas(): void
    {
        $contextoOrigen = $this->contextoOrigen();

        if (! $this->tipo_reconocimiento_id || ! $contextoOrigen) {
            $this->plantillas = [];
            $this->plantilla_id = null;

            return;
        }

        $this->plantillas = PlantillaCertificado::query()
            ->where('tipo_reconocimiento_id', $this->tipo_reconocimiento_id)
            ->where('contexto_id', $contextoOrigen->contexto_id)
            ->whereNotNull('layout')
            ->orderByDesc('por_defecto')
            ->get()
            ->toArray();

        $porDefecto = collect($this->plantillas)->firstWhere('por_defecto', true) ?? $this->plantillas[0] ?? null;
        $this->plantilla_id = $porDefecto['plantilla_id'] ?? null;
    }

    private function contextoOrigen(): ?Contexto
    {
        if ($this->origen_tipo === 'contexto') {
            return $this->contexto_id ? Contexto::find($this->contexto_id) : null;
        }

        if ($this->evento_id) {
            return Evento::find($this->evento_id)?->contexto;
        }

        return null;
    }

    private function origen(): ?object
    {
        if ($this->origen_tipo === 'contexto') {
            return $this->contexto_id ? Contexto::find($this->contexto_id) : null;
        }

        return $this->evento_id ? Evento::find($this->evento_id) : null;
    }

    public function buscarParticipante(): void
    {
        if ($this->dni) {
            $this->participanteExistente = Participante::where('dni', $this->dni)->first()?->toArray();

            if ($this->participanteExistente) {
                $this->nombre = $this->participanteExistente['nombre'];
                $this->apellido = $this->participanteExistente['apellido'];
                $this->telefono = $this->participanteExistente['telefono'];
                $this->mail = $this->participanteExistente['mail'];
            } else {
                $this->reset('nombre', 'apellido', 'telefono', 'mail');
            }
        }
    }

    public function detectarSimilar(): void
    {
        $this->similar = null;
        $this->decision_similar = null;

        $candidato = app(BuscarParticipanteSimilar::class)->buscar(
            apellido: (string) $this->apellido,
            nombre: (string) $this->nombre,
            telefono: (string) $this->telefono,
            mail: (string) $this->mail,
            dniExcluir: $this->dni !== null && $this->dni !== '' ? (int) $this->dni : null,
        );

        if ($candidato) {
            $this->similar = [
                'participante_id' => $candidato->participante_id,
                'nombre' => $candidato->nombre,
                'apellido' => $candidato->apellido,
                'dni' => $candidato->dni,
                'mail' => $candidato->mail,
                'telefono' => $candidato->telefono,
            ];
        }
    }

    public function usarSimilar(): void
    {
        $this->decision_similar = 'usar';
    }

    public function crearNuevo(): void
    {
        $this->decision_similar = 'nuevo';
    }

    public function emitir(): void
    {
        $this->validate([
            'dni' => 'required|string|max:15',
            'nombre' => 'required|string|max:100',
            'apellido' => ['required', 'string', 'max:100', new LargoNombreCertificado($this->nombre)],
            'telefono' => 'required|string|min:6|max:15',
            'mail' => 'required|email|max:100',
            'tipo_reconocimiento_id' => 'required|exists:tipo_reconocimiento,tipo_reconocimiento_id',
            'plantilla_id' => 'required|exists:plantilla_certificado,plantilla_id',
        ]);

        $origen = $this->origen();

        if (! $origen) {
            $this->dispatch('oops', message: 'Seleccioná un evento o contexto de origen.');

            return;
        }

        if ($this->similar === null) {
            $this->detectarSimilar();
        }

        if ($this->similar && $this->decision_similar === null) {
            $this->dispatch('oops', message: 'Encontramos un participante con datos muy similares. Confirmá si es la misma persona para continuar.');

            return;
        }

        DB::beginTransaction();

        try {
            $this->nombre = NormalizadorIdentidad::titulo($this->nombre);
            $this->apellido = NormalizadorIdentidad::titulo($this->apellido);

            $participante = $this->resolverParticipante();

            if (! $participante) {
                DB::rollBack();

                return;
            }

            $tipo = TipoReconocimiento::findOrFail($this->tipo_reconocimiento_id);
            $plantilla = PlantillaCertificado::findOrFail($this->plantilla_id);

            $emision = app(GenerarCertificadoEmision::class)->emitir(
                participante: $participante,
                tipo: $tipo,
                plantilla: $plantilla,
                origen: $origen,
                alcance: $this->alcance(),
                emitidoPor: auth()->id(),
            );

            DB::commit();

            $this->dispatch('alert', message: $emision->wasRecentlyCreated
                ? 'Certificado emitido correctamente.'
                : 'Certificado reemitido correctamente (se reemplazó el anterior).');
            $this->reset(['evento_id', 'contexto_id', 'tipo_reconocimiento_id', 'plantilla_id', 'nombre', 'apellido', 'dni', 'telefono', 'mail', 'participanteExistente', 'similar', 'decision_similar', 'plantillas']);
            $this->cargarEventos();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error: '.$e->getMessage());
        }
    }

    private function resolverParticipante(): ?Participante
    {
        if ($this->similar && $this->decision_similar === 'usar') {
            $participante = Participante::find($this->similar['participante_id']);

            if (! $participante) {
                $this->dispatch('oops', message: 'El participante similar ya no está disponible.');

                return null;
            }

            $participante->update([
                'nombre' => $this->nombre,
                'apellido' => $this->apellido,
                'telefono' => $this->telefono,
            ]);

            DuplicadoRevision::create([
                'participante_id' => $participante->participante_id,
                'candidato_id' => $participante->participante_id,
                'origen' => 'emision',
                'decision' => 'misma_persona',
            ]);

            return $participante;
        }

        $participante = Participante::where('dni', $this->dni)->first();

        if ($participante) {
            $participante->update([
                'nombre' => $this->nombre,
                'apellido' => $this->apellido,
                'telefono' => $this->telefono,
                'mail' => $this->mail,
            ]);

            return $participante;
        }

        $participante = Participante::create([
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'dni' => $this->dni,
            'telefono' => $this->telefono,
            'mail' => $this->mail,
        ]);

        if ($this->similar && $this->decision_similar === 'nuevo') {
            DuplicadoRevision::create([
                'participante_id' => $participante->participante_id,
                'candidato_id' => $this->similar['participante_id'],
                'origen' => 'emision',
                'decision' => 'otra_persona',
            ]);
        }

        return $participante;
    }

    public function render()
    {
        $emisiones = Emision::query()
            ->with(['participante', 'tipoReconocimiento'])
            ->orderByDesc('created_at')
            ->paginate($this->porPagina);

        return view('livewire.emisor-certificados', compact('emisiones'));
    }
}
