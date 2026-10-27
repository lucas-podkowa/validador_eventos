<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class Auditoria extends Component
{
    use WithPagination;

    public string $search = '';

    public string $usuario_id = '';

    public string $accion = '';

    public string $modulo = '';

    public string $desde = '';

    public string $hasta = '';

    public ?int $detalle_id = null;

    public bool $open_detalle = false;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'usuario_id' => ['except' => ''],
        'accion' => ['except' => ''],
        'modulo' => ['except' => ''],
        'desde' => ['except' => ''],
        'hasta' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('Administrador'), 403, 'Solo el Administrador puede acceder a la auditoría.');

        $this->desde = now()->subDays(30)->toDateString();
        $this->hasta = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'usuario_id', 'accion', 'modulo', 'desde', 'hasta'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'usuario_id', 'accion', 'modulo', 'desde', 'hasta']);
        $this->resetPage();
    }

    public function verDetalle(int $id): void
    {
        $this->detalle_id = $id;
        $this->open_detalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->open_detalle = false;
        $this->detalle_id = null;
    }

    public function render()
    {
        $actividades = Activity::query()
            ->with('causer')
            ->when(trim($this->search) !== '', function ($query) {
                $termino = '%'.trim($this->search).'%';

                $query->where(function ($subquery) use ($termino) {
                    $subquery->where('description', 'like', $termino)
                        ->orWhere('properties', 'like', $termino)
                        ->orWhere('attribute_changes', 'like', $termino)
                        ->orWhereHas('causer', fn ($causer) => $causer
                            ->where('name', 'like', $termino)
                            ->orWhere('email', 'like', $termino));
                });
            })
            ->when($this->usuario_id !== '', function ($query) {
                $query->where('causer_type', User::class)
                    ->where('causer_id', $this->usuario_id);
            })
            ->when($this->accion !== '', fn ($query) => $query->where('event', $this->accion))
            ->when($this->modulo !== '', fn ($query) => $query->where('log_name', $this->modulo))
            ->when($this->desde !== '', fn ($query) => $query->whereDate('created_at', '>=', $this->desde))
            ->when($this->hasta !== '', fn ($query) => $query->whereDate('created_at', '<=', $this->hasta))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25);

        $usuarios = User::query()
            ->whereIn('id', Activity::query()
                ->select('causer_id')
                ->whereNotNull('causer_id')
                ->distinct())
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $acciones = Activity::query()
            ->whereNotNull('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event');

        $modulos = Activity::query()
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $detalle = $this->detalle_id
            ? Activity::query()->with('causer')->find($this->detalle_id)
            : null;

        return view('livewire.admin.auditoria', compact('actividades', 'usuarios', 'acciones', 'modulos', 'detalle'));
    }

    public function etiquetaAccion(?string $evento): string
    {
        return match ($evento) {
            'created' => 'Creación',
            'updated' => 'Modificación',
            'deleted' => 'Eliminación',
            'evento.eliminacion_rechazada' => 'Eliminación rechazada',
            'evento.finalizado' => 'Evento finalizado',
            'evento.cancelado' => 'Evento cancelado',
            'evento.devuelto_a_en_curso' => 'Devuelto a En Curso',
            'evento.planilla_habilitada' => 'Planilla habilitada',
            'evento.aprobacion_instantanea' => 'Aprobación instantánea',
            'evento.aprobacion_quitada' => 'Aprobación quitada',
            'inscripciones.importadas' => 'Importación de participantes',
            'inscripcion.staff' => 'Inscripción de staff',
            'participaciones.importadas' => 'Importación para emisión',
            'participacion.agregada' => 'Participante agregado para emisión',
            'usuario.roles_actualizados' => 'Roles actualizados',
            'usuario.participante_vinculado' => 'Participante vinculado',
            'usuario.participante_desvinculado' => 'Participante desvinculado',
            'api_cliente.token_generado' => 'Token generado',
            'api_cliente.token_revocado' => 'Token revocado',
            'emisiones.backfill' => 'Backfill de emisiones',
            default => $evento ?: '—',
        };
    }

    public function etiquetaModulo(?string $modulo): string
    {
        return match ($modulo) {
            'evento' => 'Eventos',
            'planilla' => 'Planillas',
            'inscripcion' => 'Inscripciones',
            'evento_participante' => 'Participantes de evento',
            'participante' => 'Participantes',
            'emision' => 'Emisiones',
            'participacion' => 'Participaciones',
            'usuario' => 'Usuarios',
            'api_cliente' => 'Clientes de API',
            default => $modulo ? ucfirst(str_replace('_', ' ', $modulo)) : '—',
        };
    }

    public function claseAccion(?string $evento): string
    {
        return match ($evento) {
            'created' => 'bg-green-100 text-green-700',
            'deleted', 'evento.eliminacion_rechazada' => 'bg-red-100 text-red-700',
            'updated' => 'bg-blue-100 text-blue-700',
            default => 'bg-amber-100 text-amber-700',
        };
    }

    public function formatearValor(mixed $valor): string
    {
        if ($valor === null) {
            return '—';
        }

        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }

        if (is_array($valor)) {
            return json_encode($valor, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—';
        }

        return (string) $valor;
    }
}
