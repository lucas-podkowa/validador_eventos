<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\SesionEvento;
use App\Models\AsistenciaParticipante;
use App\Models\InscripcionParticipante;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

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

    // Seleccionar sesión para tomar asistencia
    public function seleccionarSesion($sesionId)
    {
        $this->sesionSeleccionada = SesionEvento::find($sesionId);

        // Obtener participantes inscritos en el evento
        $participantes = InscripcionParticipante::where('planilla_id', $this->evento_selected->planillaInscripcion->planilla_inscripcion_id)
            ->with('participante')
            ->get();

        // $query = InscripcionParticipante::where('planilla_id', $this->evento_selected->planillaInscripcion->planilla_inscripcion_id)
        //     ->with('participante');

        // if (!empty($this->searchParticipante)) {
        //     $query->whereHas('participante', function ($q) {
        //         $q->where('nombre', 'like', "%{$this->searchParticipante}%")
        //             ->orWhere('apellido', 'like', "%{$this->searchParticipante}%");
        //     });
        // }

        // $this->inscriptos = $query->get();


        // Obtener asistencias previas
        $this->asistencias = $participantes->map(function ($inscripcion) {
            $asistencia = AsistenciaParticipante::where('participante_id', $inscripcion->participante_id)
                ->where('sesion_evento_id', $this->sesionSeleccionada->sesion_evento_id)
                ->first();

            return [
                'participante_id' => $inscripcion->participante_id,
                'nombre' => $inscripcion->participante->nombre,
                'asistio' => $asistencia ? $asistencia->asistio : false
            ];
        })->toArray();

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

    public function render()
    {
        return view('livewire.asistencias');
    }
}
