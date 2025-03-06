<?php

namespace App\Livewire;

use App\Models\Evento;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;


class EventosFinalizados extends Component
{
    use WithPagination;

    public $evento_selected = null;
    public $open_detail = false;
    public $sort = 'nombre';
    public $direction = 'asc';
    public $search = '';
    public $participantes = [];

    public function mount() {}

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage(); // Resetea a la página 1 cuando cambia la búsqueda
    }

    public function render()
    {
        $eventosFinalizados = Evento::where('estado', 'finalizado')
            ->when($this->search != '', function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sort, $this->direction)
            ->paginate(10);

        return view('livewire.eventos-finalizados', [
            'eventosFinalizados' => $eventosFinalizados
        ]);
    }

    public function order($sort)
    {
        if ($this->sort == $sort) { //si estoy en la misma columna me pregunto por la direccion de ordenamiento
            if ($this->direction == 'asc') {
                $this->direction == 'desc';
            } else {
                $this->direction == 'asc';
            }
        } else { //si es una columna nueva, ordeno de forma ascendente
            $this->sort = $sort;
            $this->direction = 'asc';
        }
    }

    //----------------------------------------------------------------------------
    //------ Metodo llamado al precionar el boton QR para ver los participantes --
    //----------------------------------------------------------------------------
    public function detail($evento)
    {
        $this->resetValidation();
        $this->evento_selected = Evento::find($evento['evento_id']);
        //        $this->participantes = $this->evento_selected->participantes()->withPivot('qrcode')->get();
        $this->participantes = $this->evento_selected->participantes()
            ->withPivot('qrcode')
            ->get()
            ->map(function ($participante) {
                $participante->qrcode_base64 = 'data:image/svg+xml;base64,' . base64_encode($participante->pivot->qrcode);
                return $participante;
            });

        $this->open_detail = true;
    }
}
