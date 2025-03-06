<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use App\Models\TipoEvento;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class EventosPendientes extends Component
{
    public $tipos_eventos = [];
    public $evento_selected = null;
    public $search = '';
    public $search_tipo_evento = null;
    public $sort = 'nombre';
    public $direction = 'asc';
    public $open_detail = false;
    public $open_planilla = false;
    public $apertura = null;
    public $cierre = null;
    public $header = null;
    public $footer = null;
    protected $listeners = ['toggleAsistencia'];


    public $eventosPendientes;

    use WithPagination;
    use WithFileUploads;

    protected $rules = [
        'apertura' => 'required|date',
        'cierre' => 'required|date|after:apertura',
        'header' => 'nullable|image|mimes:jpeg,png,jpg|max:4096', // 2MB máximo
        'footer' => 'nullable|image|mimes:jpeg,png,jpg|max:4096',
    ];

    public function mount()
    {
        $this->eventosPendientes = Evento::where('estado', 'pendiente')->get();
        $this->tipos_eventos = TipoEvento::all();
    }

    public $activeTab = 'pendientes'; // Define la primera pestaña como activa por defecto

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getListeners()
    {
        return [
            'refreshMainComponent' => '$refresh',
        ];
    }


    public function render()
    {
        $eventos = Evento::where('estado', 'pendiente')->get();
        return view('livewire.eventos-pendientes', compact('eventos'));
    }



    //-------------------------------------------------------------------------------------------------
    //------ Metodo llamado al precionar el boton "Habilitar Inscripcion" en Eventos Pendientes -------
    //-------------------------------------------------------------------------------------------------
    public function show_dialog_planilla($evento)
    {
        $this->resetValidation();
        $this->reset(['open_planilla']);
        if (is_array($evento)) {
            $this->evento_selected = Evento::find($evento['evento_id']);
        } else {
            $this->evento_selected = Evento::find($evento->evento_id);
        }
        $this->open_planilla = true;
    }

    public function habilitar_planilla()
    {
        $this->validate();

        // Verificar que la fecha de apertura sea menor a la fecha de inicio del evento
        if (Carbon::parse($this->apertura)->gte(Carbon::parse($this->evento_selected->fecha_inicio))) {
            $fechaInicioFormateada = Carbon::parse($this->evento_selected->fecha_inicio)->format('d/m/Y');
            $this->dispatch('oops', message: 'La fecha de apertura debe ser menor a la fecha de inicio del evento (' . $fechaInicioFormateada . ').');
            return;
        }

        DB::beginTransaction();
        try {
            // Validar y cargar las imágenes
            $headerPath = $this->header ? $this->header->store('images', 'public') : null;
            $footerPath = $this->footer ? $this->footer->store('images', 'public') : null;


            // Consulta si ya existe la planilla de inscripción para el evento
            $planilla = PlanillaInscripcion::where('evento_id', $this->evento_selected->evento_id)->first();

            //Crea la planilla de inscripción para el evento
            if (!$planilla) {
                $planilla = PlanillaInscripcion::create([
                    'evento_id' => $this->evento_selected->evento_id,
                    'apertura' => Carbon::parse($this->apertura),
                    'cierre' => Carbon::parse($this->cierre),
                    'header' => $headerPath,
                    'footer' => $footerPath,
                ]);
            }

            // Actualizar el estado del evento a "en curso"
            Evento::where('evento_id', $this->evento_selected->evento_id)->update(['estado' => 'En Curso']);

            DB::commit();

            $this->reset([
                'apertura',
                'cierre',
                'header',
                'footer',
                'evento_selected',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo habilitar la planilla de inscripción: ' . $e->getMessage());
            return;
        }

        // Cerrar el modal de planilla
        $this->open_planilla = false;

        // Disparar evento para refrescar el componente
        $this->dispatch('refreshMainComponent');

        // Recargar los eventos pendientes después de la operación
        $this->mount();
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
}
