<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\InscripcionParticipante;
use App\Models\PlanillaInscripcion;
use App\Models\TipoEvento;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\PDF;
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

    // para revisor
    public $open_modal_revisor = false;
    public $busqueda_usuario = '';
    public $usuarios_filtrados = [];
    public $usuario_seleccionado_id = null;
    public $apertura;
    public $cierre;

    public $open_modal_detalles = false;
    public $evento_detalles = null;

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
    public function redirectToEventos($tab)
    {
        return redirect()->route('eventos', ['tab' => $tab]);
    }

    //----------------------------------------------------------------------------
    //------ Metodo disparado por el boton "Ver detalle de la columna Detalle" ---
    //----------------------------------------------------------------------------
    public function verDetalles($evento_id)
    {
        $this->evento_detalles = Evento::with(['planillaInscripcion', 'revisor', 'gestores', 'tipoEvento'])
            ->findOrFail($evento_id);
        $this->open_modal_detalles = true;
    }

    public function descargarResumenPDF($evento_id)
    {
        // $evento = Evento::with(['planillaInscripcion', 'revisor', 'gestores', 'tipoEvento'])
        //     ->findOrFail($evento_id);

        // // Lógica para generar PDF (ej: con DomPDF o Laravel Snappy)
        // $pdf = PDF::loadView('pdf.resumen_evento', ['evento' => $evento]);

        // return response()->streamDownload(function () use ($pdf) {
        //     echo $pdf->output();
        // }, 'resumen_evento.pdf');
    }


    //----------------------------------------------------------------------------
    //------ Metodo disparado por el boton "Asignar Revisor" --------
    //----------------------------------------------------------------------------
    public function modalRevisor($evento_id)
    {
        $this->evento_selected = Evento::find($evento_id);
        $this->open_modal_revisor = true;
        $this->busqueda_usuario = '';
        $this->usuarios_filtrados = [];
        $this->usuario_seleccionado_id = null;
    }

    public function updatedBusquedaUsuario()
    {
        $this->usuarios_filtrados = User::role('Revisor') // filtra por rol "Revisor"
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->busqueda_usuario . '%')
                    ->orWhere('email', 'like', '%' . $this->busqueda_usuario . '%');
            })
            ->limit(10)
            ->get();
    }


    public function guardarRevisor()
    {
        if ($this->evento_selected && $this->usuario_seleccionado_id) {
            $this->evento_selected->revisor_id = $this->usuario_seleccionado_id;
            $this->evento_selected->save();

            $this->dispatch('alert', message: 'Revisor asignado correctamente.');
        }

        $this->reset(['open_modal_revisor', 'evento_selected', 'busqueda_usuario', 'usuarios_filtrados', 'usuario_seleccionado_id']);
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

            // // Validar si el evento es por aprobación y no tiene revisores (en el caso que sean multiples)
            // if ($evento->por_aprobacion && $evento->revisores()->count() === 0) {
            //     throw new \Exception('El evento requiere al menos un revisor asignado para poder finalizarse.');
            // }

            // Validación: si el evento es por aprobación, debe tener un revisor asignado
            if ($evento->esPorAprobacion() && is_null($evento->revisor_id)) {
                throw new \Exception('Este evento requiere que se asigne un revisor antes de poder finalizarlo.');
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
            $this->redirectToEventos('finalizados');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo finalizar el Evento: ' . $e->getMessage());
        }
        // Disparar evento para refrescar el componente
        $this->dispatch('refreshMainComponent');
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

            $this->dispatch('alert', message: 'El evento fue cancelado correctamente.');
            $this->mount(); // Refrescar la lista de eventos activos

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo cancelar el evento: ' . $e->getMessage());
        }
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
        $user = auth()->user();

        $eventos = Evento::with(['planillaInscripcion', 'revisor', 'gestores'])
            ->where('estado', 'en curso')
            ->when($user->hasRole('Gestor'), function ($query) use ($user) {
                $query->whereHas('gestores', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
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
