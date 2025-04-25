<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\TipoEvento;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EventosPendientes extends Component
{
    public $sort = 'nombre';
    public $direction = 'asc';
    public $activeTab = 'pendientes'; // Define la primera pestaña como activa por defecto

    use WithPagination;


    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }


    //-------------------------------------------------------------------------------------------------
    //------ Metodo llamado al precionar el boton "Clonar Evento" en Eventos Pendientes -------
    //-------------------------------------------------------------------------------------------------
    public function duplicarEvento($evento)
    {
        DB::beginTransaction();
        try {
            $eventoOriginal = Evento::with('tipoIndicadores')->findOrFail($evento['evento_id']);

            // Crear el nuevo evento con la palabra "(copia)" en el nombre
            $nuevoEvento = Evento::create([
                'tipo_evento_id' => $eventoOriginal->tipo_evento_id,
                'nombre' => $eventoOriginal->nombre . ' (copia)',
                'lugar' => $eventoOriginal->lugar,
                'fecha_inicio' => $eventoOriginal->fecha_inicio,
                'cupo' => $eventoOriginal->cupo,
                'estado' => 'pendiente'
            ]);

            // Copiar los indicadores asociados
            $nuevoEvento->tipoIndicadores()->attach($eventoOriginal->tipoIndicadores->pluck('tipo_indicador_id'));

            DB::commit();
            // Disparar evento para refrescar el componente
            $this->dispatch('refreshMainComponent');

            // Recargar los eventos pendientes después de la operación
            $this->mount();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo duplicar el evento: ' . $e->getMessage());
        }
    }


    public function render()
    {
        $eventos = Evento::where('estado', 'pendiente')
            ->orderBy($this->sort, $this->direction)
            ->get();
        return view('livewire.eventos-pendientes', compact('eventos'));
    }


    public function order($field)
    {
        if ($this->sort == $field) { //si estoy en la misma columna me pregunto por la direccion de ordenamiento
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else { //si es una columna nueva, ordeno de forma ascendente
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }
}
