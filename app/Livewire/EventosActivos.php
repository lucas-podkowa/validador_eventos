<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\InscripcionParticipante;
use App\Models\PlanillaInscripcion;
use App\Models\TipoEvento;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EventosActivos extends Component
{
    use WithPagination;

    public $tipos_eventos = [];
    public $inscriptos = [];
    public $mostrar_inscriptos = false;
    public $evento_selected = null;
    public $planilla_selected = null;
    public $search = '';
    public $header = null;
    public $footer = null;
    public $search_tipo_evento = null;
    public $sort = 'nombre';
    public $direction = 'asc';
    public $searchParticipante = '';
    protected $listeners = [
        'toggleAsistencia',
        'finalizarEvento', // Debe coincidir con el evento emitido
        'cancelarEvento',
    ];

    public $open_edit_modal = false;
    public $apertura;
    public $cierre;
    //public $eventosEnCurso;

    protected $rules = [
        'apertura' => 'required|date_format:Y-m-d H:i',
        'cierre' => 'required|date_format:Y-m-d H:i|after:apertura',
        'header' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        'footer' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
    ];

    public function mount()
    {
        //$this->eventosEnCurso = Evento::where('estado', 'en curso')->orderBy($this->sort, $this->direction)->get();
        $this->tipos_eventos = TipoEvento::all();
        $this->listeners[] = 'toggleAsistencia';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedEventoSelected($value)
    {
        if (is_null($value)) {
            $this->resetPage(); // Resetea la paginación
        }
    }
    public function updatedSearch()
    {
        $this->get_inscriptos($this->evento_selected);
    }

    public function updatedApertura($value)
    {
        $this->apertura = Carbon::parse($value)->format('Y-m-d H:i');
    }
    public function updatedCierre($value)
    {
        $this->cierre = Carbon::parse($value)->format('Y-m-d H:i');
    }


    //----------------------------------------------------------------------------
    //------ Metodo disparado por el boton "Finalizar Evento" --------
    //----------------------------------------------------------------------------

    public function finalizarEvento($evento_id)
    {
        DB::beginTransaction();
        try {
            $evento = Evento::findOrFail($evento_id);
            $planilla = PlanillaInscripcion::where('evento_id', $evento_id)->first();

            if (!$planilla) {
                throw new \Exception('No se encontró la planilla de inscripción para este evento.');
            }

            // Obtener los participantes con asistencia confirmada
            $presentes = $evento->participantesConAsistencia();

            if ($presentes->isEmpty()) {
                throw new \Exception('No hay participantes con asistencia confirmada para este evento.');
            }

            // Instanciar generador de QR
            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);

            // Insertar en evento_participantes
            foreach ($presentes as $participante) {
                $url = route('validar.participante', ['evento_id' => $evento_id, 'participante_id' => $participante['participante_id']]); // URL de validación
                $qrcode = $writer->writeString($url); // Generar código QR en formato SVG
                EventoParticipante::create([
                    'evento_id' =>  $evento_id,
                    'participante_id' => $participante['participante_id'],
                    'url' => $url,
                    'qrcode' => $qrcode,
                ]);
            }

            Evento::where('evento_id', $evento_id)->update(['estado' => 'Finalizado']);
            PlanillaInscripcion::where('evento_id', $evento_id)->update(['cierre' => Carbon::now()]);

            DB::commit();
            $this->reset([
                'evento_selected',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo finalizar el Evento: ' . $e->getMessage());
        }
        // Disparar evento para refrescar el componente
        $this->dispatch('refreshMainComponent');

        // Recargar los eventos pendientes después de la operación
        $this->mount();
    }

    //----------------------------------------------------------------------------
    //------ Metodo disparado por el boton Cancelar Evento" ---
    //----------------------------------------------------------------------------
    public function cancelarEvento($evento_id)
    {
        $this->mostrar_inscriptos = false;

        DB::beginTransaction();
        try {
            // Buscar la planilla asociada al evento
            $planilla = PlanillaInscripcion::where('evento_id', $evento_id)->first();

            if ($planilla) {
                $planilla_id = $planilla->planilla_inscripcion_id;

                // Eliminar todas las inscripciones de la planilla
                InscripcionParticipante::where('planilla_id', $planilla_id)->delete();
                $planilla->delete(); // Eliminar la planilla
            }

            // Actualizar el estado del evento a "pendiente"
            Evento::where('evento_id', $evento_id)->update(['estado' => 'Pendiente']);

            DB::commit();

            $this->dispatch('success', message: 'El evento fue cancelado correctamente.');
            $this->mount(); // Refrescar la lista de eventos activos

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo cancelar el evento: ' . $e->getMessage());
        }
    }

    public function show_dialog_planilla($ev)
    {
        $this->mostrar_inscriptos = false;
        $this->resetValidation();
        $evento = Evento::find($ev['evento_id']);

        if ($evento && $evento->planillaInscripcion) {
            $this->evento_selected = $evento;
            $this->planilla_selected = $evento->planillaInscripcion;
            $this->apertura = Carbon::parse($this->planilla_selected->apertura)->format('Y-m-d H:i');
            $this->cierre = Carbon::parse($this->planilla_selected->cierre)->format('Y-m-d H:i');
            $this->header = $this->planilla_selected->header;
            $this->footer = $this->planilla_selected->footer;

            $this->open_edit_modal = true;
        } else {
            $this->dispatch('oops', message: 'No se encontró una planilla de inscripción asociada.');
        }
    }

    public function updatePlanilla()
    {
        $this->validate();
        // Formatear fechas correctamente antes de la validación
        $apertura = Carbon::createFromFormat('Y-m-d H:i', $this->apertura);
        $cierre = Carbon::createFromFormat('Y-m-d H:i', $this->cierre);

        // Verificar que la fecha de apertura sea menor a la fecha de inicio del evento
        if ($apertura->gte(Carbon::parse($this->evento_selected->fecha_inicio))) {
            $fechaInicioFormateada = Carbon::parse($this->evento_selected->fecha_inicio)->format('d/m/Y H:i');
            $this->dispatch('oops', message: 'La fecha de apertura debe ser menor a la fecha de inicio del evento (' . $fechaInicioFormateada . ').');
            return;
        }
        DB::beginTransaction();
        try {
            // Validar y cargar las imágenes
            $headerPath = $this->header ? $this->header->store('images', 'public') : null;
            $footerPath = $this->footer ? $this->footer->store('images', 'public') : null;

            $this->planilla_selected->update([
                'apertura' => $apertura,
                'cierre' => $cierre,
                'header' => $headerPath,
                'footer' => $footerPath,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo actualizar la planilla de inscripción: ' . $e->getMessage());
        }

        // Cerrar el modal de planilla
        $this->open_edit_modal = false;

        // Disparar evento para refrescar el componente
        $this->dispatch('refreshMainComponent');

        // Recargar los eventos pendientes después de la operación
        $this->mount();
    }


    public function get_inscriptos($evento)
    {
        $this->evento_selected = Evento::find($evento['evento_id']);

        // Obtener el evento con la planilla de inscripción y sus inscritos
        $this->evento_selected = Evento::with(['planillaInscripcion.participantes'])
            ->find($evento['evento_id']);

        if ($this->evento_selected && $this->evento_selected->planillaInscripcion) {

            $query = InscripcionParticipante::where('planilla_id', $this->evento_selected->planillaInscripcion->planilla_inscripcion_id)
                ->with('participante');

            if (!empty($this->searchParticipante)) {
                $query->whereHas('participante', function ($q) {
                    $q->where('nombre', 'like', "%{$this->searchParticipante}%")
                        ->orWhere('apellido', 'like', "%{$this->searchParticipante}%")
                        ->orWhere('dni', 'like', '%' . $this->searchParticipante . '%');
                });
            }
            $this->inscriptos = $query->get();
            $this->mostrar_inscriptos = true;
        } else {
            $this->inscriptos = collect(); // No hay participantes inscritos
        }
    }

    public function render()
    {
        $eventos = Evento::query()
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->when($this->search_tipo_evento, function ($query) {
                $query->where('tipo_evento_id', $this->search_tipo_evento);
            })
            ->where('estado', 'en curso')
            ->orderBy($this->sort, $this->direction)
            ->get();

        return view('livewire.eventos-activos', compact('eventos'));
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
