<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\SesionEvento;
use App\Models\AsistenciaParticipante;
use App\Models\InscripcionParticipante;
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

    public function mount()
    {
        $this->eventos = Evento::where('estado', 'en curso')->get();
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
            'nombre' => 'required',
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
    }

    public function cargarAsistencias()
    {
        if (!$this->evento_selected || !$this->sesionSeleccionada) return;

        $query = InscripcionParticipante::where('planilla_id', $this->evento_selected->planillaInscripcion->planilla_inscripcion_id)
            ->with('participante');

        if (!empty($this->searchParticipante)) {
            $query->whereHas('participante', function ($q) {
                $q->where('nombre', 'like', '%' . $this->searchParticipante . '%')
                    ->orWhere('apellido', 'like', '%' . $this->searchParticipante . '%')
                    ->orWhere('dni', 'like', '%' . $this->searchParticipante . '%');
            });
        }

        $participantes = $query->get();

        $this->asistencias = $participantes->map(function ($inscripcion) {
            $asistencia = AsistenciaParticipante::where('participante_id', $inscripcion->participante_id)
                ->where('sesion_evento_id', $this->sesionSeleccionada->sesion_evento_id)
                ->first();

            return [
                'participante_id' => $inscripcion->participante_id,
                'nombre' => $inscripcion->participante->nombre . ' ' . $inscripcion->participante->apellido,
                'dni' => $inscripcion->participante->dni,
                'asistio' => (bool) ($asistencia ? $asistencia->asistio : false),
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

    // Guardar asistencia
    public function guardarAsistencia()
    {
        DB::transaction(function () {
            foreach ($this->asistencias as $asistencia) {
                AsistenciaParticipante::updateOrCreate(
                    [
                        'participante_id' => $asistencia['participante_id'],
                        'sesion_evento_id' => $this->sesionSeleccionada->sesion_evento_id
                    ],
                    ['asistio' => $asistencia['asistio']]
                );
            }
        });

        $this->mostrarModalAsistencia = false;
    }

    public function descargarAsistencias()
    {
        if (!$this->evento_selected) return;

        $evento = Evento::with('sesiones', 'planillaInscripcion.inscripciones.participante')->find($this->evento_selected->evento_id);

        $sesiones = $evento->sesiones;

        $inscripciones = $evento->planillaInscripcion->inscripciones;

        $datos = $inscripciones->map(function ($inscripcion) use ($sesiones) {
            $asistencias = [];

            foreach ($sesiones as $sesion) {
                $asistio = AsistenciaParticipante::where('participante_id', $inscripcion->participante_id)
                    ->where('sesion_evento_id', $sesion->sesion_evento_id)
                    ->value('asistio') ?? false;

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

        return response()->streamDownload(fn() => print($pdf->output()), 'asistencias_evento.pdf');
    }

    public function render()
    {
        return view('livewire.asistencias');
    }
}
