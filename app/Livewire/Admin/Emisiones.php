<?php

namespace App\Livewire\Admin;

use App\Models\Contexto;
use App\Models\Emision;
use App\Models\Evento;
use App\Models\TipoReconocimiento;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Explorador unificado de certificados emitidos: incluye las emisiones internas
 * (eventos/contextos) y las externas creadas vía API. Permite encontrar todos los
 * certificados de una persona independientemente de su origen.
 *
 * Optimizado para volúmenes grandes: sólo se seleccionan y precargan las columnas
 * que la tabla muestra, y los joins de orden se agregan únicamente cuando hacen falta.
 */
class Emisiones extends Component
{
    use WithPagination;

    public $search = '';

    public $tipo_reconocimiento_id = '';

    public $origen = '';

    public $estado = '';

    public $desde = '';

    public $hasta = '';

    public $sort = 'fecha';

    public $direction = 'desc';

    protected $paginationTheme = 'tailwind';

    /**
     * @var array<string, array{except: string}>
     */
    protected $queryString = [
        'search' => ['except' => ''],
        'tipo_reconocimiento_id' => ['except' => ''],
        'origen' => ['except' => ''],
        'estado' => ['except' => ''],
        'desde' => ['except' => ''],
        'hasta' => ['except' => ''],
    ];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'tipo_reconocimiento_id', 'origen', 'estado', 'desde', 'hasta'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'tipo_reconocimiento_id', 'origen', 'estado', 'desde', 'hasta']);
        $this->resetPage();
    }

    public function order(string $field): void
    {
        if ($this->sort === $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $emisiones = $this->consulta()
            ->orderBy($this->resolveSortColumn(), $this->direction)
            ->paginate(20);

        return view('livewire.admin.emisiones', [
            'emisiones' => $emisiones,
            'tipos' => TipoReconocimiento::orderBy('orden')->orderBy('nombre')
                ->get(['tipo_reconocimiento_id', 'nombre']),
        ]);
    }

    private function consulta()
    {
        return Emision::query()
            ->select('emision.*')
            ->with([
                'participante:participante_id,apellido,nombre,dni',
                'tipoReconocimiento:tipo_reconocimiento_id,nombre',
                'origen',
                'apiCliente:api_cliente_id,nombre',
            ])
            ->when($this->sort === 'receptor', fn ($query) => $query->leftJoin(
                'participante as participante_orden',
                'emision.participante_id',
                '=',
                'participante_orden.participante_id',
            ))
            ->when($this->sort === 'tipo', fn ($query) => $query->leftJoin(
                'tipo_reconocimiento as tipo_orden',
                'emision.tipo_reconocimiento_id',
                '=',
                'tipo_orden.tipo_reconocimiento_id',
            ))
            ->when($this->sort === 'origen', function ($query) {
                $query->leftJoin('contexto as contexto_orden', function ($join) {
                    $join->on('emision.origen_id', '=', 'contexto_orden.contexto_id')
                        ->where('emision.origen_type', '=', Contexto::class);
                })->leftJoin('evento as evento_orden', function ($join) {
                    $join->on('emision.origen_id', '=', 'evento_orden.evento_id')
                        ->where('emision.origen_type', '=', Evento::class);
                });
            })
            ->when($this->search !== '', function ($query) {
                $busqueda = '%'.$this->search.'%';
                $query->where(function ($subquery) use ($busqueda) {
                    $subquery->whereHas('participante', function ($participante) use ($busqueda) {
                        $participante->where('nombre', 'like', $busqueda)
                            ->orWhere('apellido', 'like', $busqueda)
                            ->orWhere('dni', 'like', $busqueda);
                    })->orWhere('emision.external_ref', 'like', $busqueda);
                });
            })
            ->when($this->tipo_reconocimiento_id !== '', fn ($query) => $query->where('emision.tipo_reconocimiento_id', $this->tipo_reconocimiento_id))
            ->when($this->origen === 'api', fn ($query) => $query->whereNotNull('emision.api_cliente_id'))
            ->when($this->origen === 'interno', fn ($query) => $query->whereNull('emision.api_cliente_id'))
            ->when($this->estado !== '', fn ($query) => $query->where('emision.estado', $this->estado))
            ->when($this->desde !== '', fn ($query) => $query->whereDate('emision.emitida_en', '>=', $this->desde))
            ->when($this->hasta !== '', fn ($query) => $query->whereDate('emision.emitida_en', '<=', $this->hasta));
    }

    private function resolveSortColumn(): string|DB\Query\Expression
    {
        return match ($this->sort) {
            'receptor' => 'participante_orden.apellido',
            'tipo' => 'tipo_orden.nombre',
            'origen' => DB::raw('COALESCE(contexto_orden.nombre, evento_orden.nombre)'),
            'procedencia' => 'emision.api_cliente_id',
            'estado' => 'emision.estado',
            default => 'emision.emitida_en',
        };
    }
}
