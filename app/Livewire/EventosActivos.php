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
    public $evento_selected = null;
    public $planilla_selected = null;
    public $search = '';
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
    public $eventosEnCurso;


    protected $rules = [
        'apertura' => 'required|date',
        'cierre' => 'required|date|after_or_equal:apertura',
    ];


    public function mount()
    {
        $this->eventosEnCurso = Evento::where('estado', 'en curso')->get();
        $this->tipos_eventos = TipoEvento::all();
        $this->listeners[] = 'toggleAsistencia';
    }


    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->get_inscriptos($this->evento_selected);
    }

    //----------------------------------------------------------------------------
    //------ Metodo disparado por el boton "Finalizar Evento" --------
    //----------------------------------------------------------------------------

    public function finalizarEvento($eventoId)
    {
        $evento_id = $eventoId['evento_id'];
        DB::beginTransaction();
        try {
            $planilla = PlanillaInscripcion::where('evento_id', $evento_id)->first();

            if (!$planilla) {
                throw new \Exception('No se encontró la planilla de inscripción para este evento.');
            }

            $planilla_id = $planilla->planilla_inscripcion_id;


            // Obtener los participantes con asistencia confirmada
            $presentes = DB::table('inscripcion_participante')
                ->where('planilla_id', $planilla_id)
                ->where('asistencia', true)
                ->pluck('participante_id');

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
            foreach ($presentes as $participante_id) {
                $url = route('validar.participante', ['evento_id' => $evento_id, 'participante_id' => $participante_id]); // URL de validación
                $qrcode = $writer->writeString($url); // Generar código QR en formato SVG
                EventoParticipante::create([
                    'evento_id' =>  $eventoId['evento_id'],
                    'participante_id' => $participante_id,
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
    public function cancelarEvento($eventoId)
    {
        $evento_id = $eventoId['evento_id'];

        DB::beginTransaction();
        try {
            // Buscar la planilla asociada al evento
            $planilla = PlanillaInscripcion::where('evento_id', $evento_id)->first();

            if ($planilla) {
                $planilla_id = $planilla->planilla_inscripcion_id;

                // Eliminar todas las inscripciones de la planilla
                InscripcionParticipante::where('planilla_id', $planilla_id)->delete();

                // Eliminar la planilla
                $planilla->delete();
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

    public function showEditModal($eventoId)
    {
        $evento_id = $eventoId['evento_id'];
        $this->resetValidation();
        $evento = Evento::find($evento_id);

        if ($evento && $evento->planillaInscripcion) {
            $this->evento_selected = $evento;
            $this->planilla_selected = $evento->planillaInscripcion;

            // Asigna las fechas actuales de la planilla para que se preseleccionen en el input de fecha
            $this->apertura = Carbon::parse($this->planilla_selected->apertura)->format('Y-m-d');
            $this->cierre = Carbon::parse($this->planilla_selected->cierre)->format('Y-m-d');

            $this->open_edit_modal = true;
        } else {
            $this->dispatch('oops', message: 'No se encontró una planilla de inscripción asociada.');
        }
    }

    public function updateFechas()
    {
        $this->validate();

        if (Carbon::parse($this->cierre)->gt(Carbon::parse($this->evento_selected->fecha_inicio))) {
            $this->dispatch('oops', message: 'La fecha de cierre no puede ser posterior a la fecha del evento.');
            return;
        }

        $this->planilla_selected->update([
            'apertura' => $this->apertura,
            'cierre' => $this->cierre,
        ]);

        $this->open_edit_modal = false;
        $this->dispatch('success', message: 'Fechas actualizadas correctamente.');
    }



    public function get_inscriptos($evento)
    {
        // Obtener el evento con la planilla de inscripción y sus inscritos
        $this->evento_selected = Evento::with(['planillaInscripcion.participantes'])
            ->find($evento['evento_id']);

        if ($this->evento_selected && $this->evento_selected->planillaInscripcion) {

            $query = InscripcionParticipante::where('planilla_id', $this->evento_selected->planillaInscripcion->planilla_inscripcion_id)
                ->with('participante');

            if (!empty($this->searchParticipante)) {
                $query->whereHas('participante', function ($q) {
                    $q->where('nombre', 'like', "%{$this->searchParticipante}%")
                        ->orWhere('apellido', 'like', "%{$this->searchParticipante}%");
                });
            }

            $this->inscriptos = $query->get();
        } else {
            $this->inscriptos = collect(); // No hay participantes inscritos
        }
    }


    //----------------------------------------------------------------------------
    //------ Metodo disparado por la asistencias desde la tabla participantes" ---
    //----------------------------------------------------------------------------

    public function toggleAsistencia($inscripcionID)
    {
        $inscripto = InscripcionParticipante::where('inscripcion_participante_id', $inscripcionID)
            ->whereHas('planilla', function ($query) {
                $query->where('evento_id', $this->evento_selected->evento_id);
            })
            ->first();

        if ($inscripto) {
            $inscripto->asistencia = !$inscripto->asistencia;
            $inscripto->save();

            // Emitir un evento para actualizar la tabla
            $this->dispatch('refreshMainComponent');
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
            ->orderBy($this->sort, $this->direction)
            ->get();

        return view('livewire.eventos-activos', compact('eventos'));
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
