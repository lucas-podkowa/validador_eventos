<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\SesionEvento;
use App\Models\AsistenciaParticipante;
use App\Models\InscripcionParticipante;
use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;

class Asistencias extends Component
{
    public $eventos;
    public $evento_selected;
    public $sesiones;
    public $sesionSeleccionada;
    public $mostrarModalSesion = false;
    public $mostrarModalAsistencia = false;
    public $nombre;
    public $fecha_hora_inicio;
    public $fecha_hora_fin;
    public $asistencias = [];
    public $searchParticipante;

    private $rol_participante_id;

    public function mount()
    {
        $this->eventos = Evento::where('estado', 'en curso')->get();

        // Obtener el ID del rol "Participante" una sola vez
        $rolParticipante = Rol::where('nombre', 'Participante')->first();

        if (!$rolParticipante) {
            session()->flash('error', 'Error crítico: No se encontró el rol "Participante" en el sistema.');
            return;
        }

        $this->rol_participante_id = $rolParticipante->rol_id;
    }

    // Seleccionar un evento para gestionar sesiones
    public function seleccionarEvento($eventoId)
    {
        $this->evento_selected = Evento::with('sesiones')->find($eventoId);
        $this->sesiones = $this->evento_selected->sesiones;
    }

    // Abrir modal para crear sesión
    public function abrirModalSesion()
    {
        $this->mostrarModalSesion = true;
    }

    // Guardar nueva sesión del evento
    public function crearSesion()
    {
        $this->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[\pL\pN\s]+$/u',
            ],
            'fecha_hora_inicio' => 'required',
            'fecha_hora_fin' => 'required|after:fecha_hora_inicio',
        ]);

        SesionEvento::create([
            'evento_id' => $this->evento_selected->evento_id,
            'nombre' => $this->nombre,
            'fecha_hora_inicio' => $this->fecha_hora_inicio,
            'fecha_hora_fin' => $this->fecha_hora_fin
        ]);

        $this->sesiones = $this->evento_selected->sesiones()->get();
        $this->mostrarModalSesion = false;
        $this->reset('nombre', 'fecha_hora_inicio', 'fecha_hora_fin');

        $this->dispatch('alert', message: 'Sesión creada exitosamente.');
    }




    //----------------------------------------------------------------------------
    //------ Cargar asistencias SOLO de participantes con rol "Asistente" --------
    //----------------------------------------------------------------------------

    public function cargarAsistencias()
    {
        if (!$this->evento_selected || !$this->sesionSeleccionada) return;

        $query = $this->evento_selected
            ->planillaInscripcion
            ->inscripcionesParticipantes(); // Filtra por rol "Participante" y hace with('participante')

        // Filtro de búsqueda por nombre, apellido o DNI
        if (!empty($this->searchParticipante)) {
            $searchTerm = $this->searchParticipante;
            $query->whereHas('participante', function ($q) use ($searchTerm) {
                $q->where('nombre', 'like', '%' . $searchTerm . '%')
                    ->orWhere('apellido', 'like', '%' . $searchTerm . '%')
                    ->orWhere('dni', 'like', '%' . $searchTerm . '%');
            });
        }

        $participantesInscripciones = $query->get();

        // Obtener todas las asistencias existentes para esta sesión para evitar N+1
        $asistenciasSesion = AsistenciaParticipante::where('sesion_evento_id', $this->sesionSeleccionada->sesion_evento_id)
            ->whereIn('inscripcion_participante_id', $participantesInscripciones->pluck('inscripcion_participante_id'))
            ->pluck('asistio', 'inscripcion_participante_id'); // Mapear a [inscripcion_id => asistio]

        // Mapear con información de asistencia existente
        $this->asistencias = $participantesInscripciones->map(function ($inscripcion) use ($asistenciasSesion) {
            $inscripcionId = $inscripcion->inscripcion_participante_id;
            $asistio = $asistenciasSesion->get($inscripcionId, false); // Obtener asistencia, si no existe, es false

            return [
                'inscripcion_participante_id' => $inscripcionId,
                'nombre' => $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellido,
                'dni' => $inscripcion->participante->dni,
                'asistio' => (bool) $asistio,
            ];
        })->toArray();
    }


    public function updatedSearchParticipante()
    {
        $this->cargarAsistencias();
    }

    public function seleccionarSesion($sesionId)
    {
        $this->sesionSeleccionada = SesionEvento::find($sesionId);
        $this->searchParticipante = '';
        $this->cargarAsistencias();
        $this->mostrarModalAsistencia = true;
    }


    //----------------------------------------------------------------------------
    //------ Guardar asistencia --------
    //----------------------------------------------------------------------------

    public function guardarAsistencia()
    {
        DB::transaction(function () {
            foreach ($this->asistencias as $asistencia) {
                AsistenciaParticipante::updateOrCreate(
                    [
                        'inscripcion_participante_id' => $asistencia['inscripcion_participante_id'],
                        'sesion_evento_id' => $this->sesionSeleccionada->sesion_evento_id
                    ],
                    ['asistio' => $asistencia['asistio']]
                );
            }
        });

        $this->mostrarModalAsistencia = false;
        $this->dispatch('alert', message: 'Asistencias guardadas correctamente.');
    }

    /**
     * Marcar todos los asistentes como presentes
     */
    public function marcarTodosPresentes()
    {
        foreach ($this->asistencias as $index => $asistencia) {
            $this->asistencias[$index]['asistio'] = true;
        }

        $this->dispatch('alert', message: 'Todos los asistentes marcados como presentes.');
    }

    /**
     * Marcar todos los asistentes como ausentes
     */
    public function marcarTodosAusentes()
    {
        foreach ($this->asistencias as $index => $asistencia) {
            $this->asistencias[$index]['asistio'] = false;
        }

        $this->dispatch('alert', message: 'Todos los asistentes marcados como ausentes.');
    }



    //----------------------------------------------------------------------------
    //------ Descargar PDF de asistencias SOLO del ROL Asistentes --------
    //----------------------------------------------------------------------------

    public function descargarAsistencias()
    {
        if (!$this->evento_selected) {
            $this->dispatch('oops', message: 'Debe seleccionar un evento primero.');
            return;
        }

        $evento = Evento::with(['sesiones', 'planillaInscripcion.inscripcionesParticipantes'])
            ->find($this->evento_selected->evento_id);

        $sesiones = $evento->sesiones;
        $inscripciones = $evento->planillaInscripcion->inscripcionesParticipantes;



        // Si no hay participantes registrados
        if ($inscripciones->isEmpty()) {
            $this->dispatch('oops', message: 'No hay participantes registrados en este evento.');
            return;
        }

        // 1. Pre-cargar todas las asistencias para todas las inscripciones y todas las sesiones de una vez
        $asistenciasData = AsistenciaParticipante::whereIn('inscripcion_participante_id', $inscripciones->pluck('inscripcion_participante_id'))
            ->whereIn('sesion_evento_id', $sesiones->pluck('sesion_evento_id'))
            ->get()
            ->keyBy(function ($item) {
                return $item->inscripcion_participante_id . '-' . $item->sesion_evento_id;
            });

        $datos = $inscripciones->map(function ($inscripcion) use ($sesiones, $asistenciasData) {
            $asistencias = [];

            foreach ($sesiones as $sesion) {
                $key = $inscripcion->inscripcion_participante_id . '-' . $sesion->sesion_evento_id;

                // Buscar la asistencia en el mapa de datos pre-cargados
                $asistio = $asistenciasData->get($key)->asistio ?? false;

                $asistencias[$sesion->nombre] = $asistio;
            }

            return [
                'nombre' => $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellido,
                'dni' => $inscripcion->participante->dni,
                'asistencias' => $asistencias,
            ];
        });
        $pdf = Pdf::loadView('livewire.pdf-asistencias', [
            'evento' => $evento,
            'sesiones' => $sesiones,
            'datos' => $datos,
        ]);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'asistencias_' . \Str::slug($evento->nombre) . '.pdf'
        );
    }


    /**
     * Método auxiliar para obtener estadísticas (opcional)
     */
    public function obtenerEstadisticasSesion($sesionId)
    {
        if (!$this->evento_selected || !$this->evento_selected->planillaInscripcion) return null;

        $planillaId = $this->evento_selected->planillaInscripcion->planilla_inscripcion_id;

        // 1. Total de Asistentes Inscritos (filtrados por rol "Asistente" y por la planilla del evento)
        $totalAsistentes = InscripcionParticipante::where('planilla_id', $planillaId)
            ->asistentes()
            ->count();

        // 2. Total de Presentes: Contamos las asistencias que corresponden a una inscripción con rol "Asistente"
        $presentes = AsistenciaParticipante::where('sesion_evento_id', $sesionId)
            ->where('asistio', true)
            // Solo contamos asistencias cuya inscripción pertenezca al total de asistentes de este evento/planilla
            ->whereIn('inscripcion_participante_id', function ($query) use ($planillaId) {
                $query->select('inscripcion_participante_id')
                    ->from('inscripcion_participante')
                    ->where('planilla_id', $planillaId)
                    ->asistentes(); // Usamos el scope de asistentes aquí también
            })
            ->count();


        return [
            'total' => $totalAsistentes,
            'presentes' => $presentes,
            'ausentes' => $totalAsistentes - $presentes,
            'porcentaje_asistencia' => $totalAsistentes > 0
                ? round(($presentes / $totalAsistentes) * 100, 2)
                : 0
        ];
    }

    public function render()
    {
        return view('livewire.asistencias');
    }
}
