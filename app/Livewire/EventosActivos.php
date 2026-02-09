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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class EventosActivos extends Component
{
    use WithPagination;

    public $tipos_eventos = [];
    public $inscriptos = [];
    public $disertantes_colaboradores = [];
    public $mostrar_inscriptos = false;
    public $mostrar_disertantes_colaboradores = false;
    public $evento_selected = null;
    public $planilla_selected = null;
    public $search = '';
    public $header = null;
    public $footer = null;
    public $search_tipo_evento = null;
    public $sort = 'nombre';
    public $direction = 'asc';
    public $searchParticipante = '';
    public $searchDisertante = '';

    protected $listeners = [
        'toggleAsistencia',
        'finalizarEvento',
        'cancelarEvento',
        'confirmDesmatricular',
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
        $this->tipos_eventos = TipoEvento::all();
        $this->listeners[] = 'toggleAsistencia';
        // Verificar si se debe abrir automáticamente la tabla de disertantes
        if (request('mostrar') === 'disertantes' && request('evento_id')) {
            $this->get_staff(['evento_id' => request('evento_id')]);
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatedEventoSelected($value)
    {
        if (is_null($value)) {
            $this->resetPage();
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

    // ----------------------------------------
    // EXPORTAR LISTADO DE INSCRIPTOS A PDF
    // ----------------------------------------
    public function exportarPDF()
    {
        if (!$this->evento_selected) {
            session()->flash('error', 'Debe seleccionar un evento primero.');
            return;
        }

        $inscriptos = $this->inscriptos;

        $pdf = Pdf::setOption(['isPhpEnabled' => true])
            ->loadView('pdf.listado-inscriptos', [
                'evento' => $this->evento_selected,
                'inscriptos' => $inscriptos
            ])
            ->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inscriptos_' . Str::slug($this->evento_selected->nombre) . '.pdf');
    }

    // ----------------------------------------
    // EXPORTAR LISTADO DE INSCRIPTOS A CSV
    // ----------------------------------------
    public function descargarCSV()
    {
        if (!$this->evento_selected) {
            session()->flash('error', 'Debe seleccionar un evento primero.');
            return;
        }

        $inscriptos = $this->inscriptos;

        if (empty($inscriptos)) {
            abort(404, 'No hay inscriptos para exportar.');
        }

        $filename = 'inscriptos_' . Str::slug($this->evento_selected->nombre) . '.csv';

        return response()->streamDownload(function () use ($inscriptos) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Apellido', 'DNI', 'Email', 'Teléfono'], ';');

            foreach ($inscriptos as $inscripto) {
                fputcsv($handle, [
                    $inscripto->participante->nombre ?? '',
                    $inscripto->participante->apellido ?? '',
                    $inscripto->participante->dni ?? '',
                    $inscripto->participante->mail ?? '',
                    $inscripto->participante->telefono ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
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
        // Implementación pendiente
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
        $this->usuarios_filtrados = User::role('Revisor')
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

            if ($evento->esPorAprobacion() && is_null($evento->revisor_id)) {
                throw new \Exception('Este evento requiere que se asigne un revisor antes de poder finalizarlo.');
            }

            $inscripcionesPresentes = $evento->inscripcionesConAsistencia();
            if ($inscripcionesPresentes->isEmpty()) {
                // Se mantiene el mensaje de error para el usuario final
                throw new \Exception('No hay participantes con asistencia confirmada para este evento.');
            }

            $inscripcionesFinales = $evento->inscripcionesFinales();

            $renderer = new ImageRenderer(
                new RendererStyle(200),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);

            foreach ($inscripcionesFinales as $inscripcion) {
                $participanteId = $inscripcion->participante_id;
                $rolId = $inscripcion->rol_id;

                $url = route('validar.participante', ['evento_id' => $evento_id, 'participante_id' => $participanteId]);
                $qrcode = $writer->writeString($url);

                // Usamos el ID de participante obtenido
                EventoParticipante::create([
                    'evento_id' =>  $evento_id,
                    'participante_id' => $participanteId,
                    'rol_id' => $rolId,
                    'url' => $url,
                    'qrcode' => $qrcode,
                ]);
            }

            Evento::where('evento_id', $evento_id)->update(['estado' => 'Finalizado']);
            PlanillaInscripcion::where('evento_id', $evento_id)->update(['cierre' => Carbon::now()]);

            DB::commit();
            $this->reset(['evento_selected']);
            $this->redirectToEventos('finalizados');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo finalizar el Evento: ' . $e->getMessage());
        }
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
            $planilla = PlanillaInscripcion::where('evento_id', $evento_id)->first();

            if ($planilla) {
                $planilla_id = $planilla->planilla_inscripcion_id;
                InscripcionParticipante::where('planilla_id', $planilla_id)->delete();
                $planilla->delete();
            }

            Evento::where('evento_id', $evento_id)->update(['estado' => 'Pendiente']);

            DB::commit();

            $this->dispatch('alert', message: 'El evento fue cancelado correctamente.');
            $this->mount();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo cancelar el evento: ' . $e->getMessage());
        }
    }

    // ----------------------------------------
    // Método para desmatricular
    // ----------------------------------------
    public function desmatricularParticipante($inscripcion_id)
    {
        DB::beginTransaction();
        try {
            $inscripcion = InscripcionParticipante::findOrFail($inscripcion_id);
            $participante_nombre = $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellido;

            $inscripcion->delete();

            DB::commit();

            $this->get_inscriptos(['evento_id' => $this->evento_selected->evento_id]);

            $this->dispatch('alert', message: "El participante {$participante_nombre} ha sido desmatriculado con éxito.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo desmatricular al participante: ' . $e->getMessage());
        }
    }

    // ----------------------------------------
    // Listener para confirmar la desmatriculación
    // ----------------------------------------
    public function confirmDesmatricular($inscripcion_id)
    {
        // Se llama al método real de desmatriculación con el ID que viene de la confirmación JS
        $this->desmatricularParticipante($inscripcion_id);
    }

    // ----------------------------------------
    // Obtener inscriptos con rol "Asistente"
    // ----------------------------------------

    public function get_inscriptos($evento)
    {
        $this->evento_selected = Evento::with(['planillaInscripcion.inscripcionesAsistentes'])
            ->find($evento['evento_id']);

        if ($this->evento_selected && $this->evento_selected->planillaInscripcion) {
            $query = $this->evento_selected
                ->planillaInscripcion
                ->inscripcionesAsistentes(); // Obtiene el Query Builder de la relación

            if (!empty($this->searchParticipante)) {
                $searchTerm = $this->searchParticipante;
                $query->whereHas('participante', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', "%{$searchTerm}%")
                        ->orWhere('apellido', 'like', "%{$searchTerm}%")
                        ->orWhere('dni', 'like', "%{$searchTerm}%");
                });
            }

            $this->inscriptos = $query->get();
            $this->mostrar_inscriptos = true;
            $this->mostrar_disertantes_colaboradores = false;
        } else {
            $this->inscriptos = collect();
        }
    }


    // ----------------------------------------
    // Obtener Disertantes y Colaboradores
    // ----------------------------------------

    public function get_staff($evento)
    {
        $this->evento_selected = Evento::with(['planillaInscripcion.inscripcionesDisertantesYColaboradores'])
            ->find($evento['evento_id']);

        if ($this->evento_selected && $this->evento_selected->planillaInscripcion) {
            $query = $this->evento_selected
                ->planillaInscripcion
                ->inscripcionesDisertantesYColaboradores();

            if (!empty($this->searchDisertante)) {
                $searchTerm = $this->searchDisertante;
                $query->whereHas('participante', function ($q) use ($searchTerm) {
                    $q->where('nombre', 'like', "%{$searchTerm}%")
                        ->orWhere('apellido', 'like', "%{$searchTerm}%")
                        ->orWhere('dni', 'like', "%{$searchTerm}%");
                });
            }

            $this->disertantes_colaboradores = $query->get();
            $this->mostrar_disertantes_colaboradores = true;
            $this->mostrar_inscriptos = false;
        } else {
            $this->disertantes_colaboradores = collect();
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
        if ($this->sort == $field) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }
}
