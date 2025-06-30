<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\InscripcionParticipante;
use App\Models\Participante;
use App\Models\ParticipanteIndicador;
use App\Models\PlanillaInscripcion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

class RegistroEventoPublico extends Component
{
    public $evento = null;
    public $evento_id = null;
    public $nombre = null;
    public $apellido = null;
    public $dni = null;
    public $mail = null;
    public $telefono = null;
    public $planilla_id = null;
    public $planilla_inscripcion = null;
    public $inscripcion_activa = false;
    public ?array $participante = null;
    public $indicadoresMultiples = []; // para checkboxes
    public $indicadoresUnicos = []; // para radios


    protected $rules = [
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'dni' => 'required|string|max:15',
        'mail' => 'required|email|max:100',
        'telefono' => 'required|string|min:6|max:15',
    ];

    public function mount($tipoEvento, $eventoId)
    {

        $this->evento_id = $eventoId;
        $this->planilla_inscripcion = PlanillaInscripcion::where('evento_id', $eventoId)->first();

        if (!$this->planilla_inscripcion) {
            dd("No se encontró planilla para evento_id: " . $eventoId);
        } else {
            $this->planilla_id = $this->planilla_inscripcion->planilla_inscripcion_id;
        }

        $this->evento = Evento::findOrFail($eventoId);

        $this->verificarInscripcionActiva();
    }

    public function verificarInscripcionActiva()
    {
        $hoy = Carbon::now();
        $apertura = Carbon::parse($this->planilla_inscripcion->apertura)->setTimezone(config('app.timezone'));
        $cierre = Carbon::parse($this->planilla_inscripcion->cierre)->setTimezone(config('app.timezone'));

        if ($apertura <= $hoy && $cierre >= $hoy) {
            if ($this->evento->cupo !== null) {
                $inscriptos = InscripcionParticipante::where('planilla_id', $this->planilla_id)->count();
                $this->inscripcion_activa = $inscriptos < $this->evento->cupo;
            } else {
                $this->inscripcion_activa = true;
            }
        } else {
            $this->inscripcion_activa = false;
        }
    }



    public function buscarParticipante()
    {
        if ($this->dni) {
            $this->participante = Participante::where('dni', $this->dni)->first()?->toArray();

            if ($this->participante) {
                $this->nombre = $this->participante['nombre'];
                $this->apellido = $this->participante['apellido'];
                $this->mail = $this->participante['mail'];
                $this->telefono = $this->participante['telefono'];
            } else {
                $this->reset('nombre', 'apellido', 'mail', 'telefono');
            }
        }
    }

    public function submit()
    {
        $this->validate();

        // Normalizar nombre y apellido antes de guardar o actualizar
        $this->nombre = ucfirst(mb_strtolower(trim($this->nombre)));
        $this->apellido = ucfirst(mb_strtolower(trim($this->apellido)));

        DB::beginTransaction();
        try {
            if (!$this->planilla_inscripcion) {
                DB::rollBack();
                $this->dispatch('oops', ['message' => 'Error: No hay una planilla de inscripción asociada a este evento.']);
                return;
            }

            // Buscar si el participante ya existe por DNI
            $participante = Participante::where('dni', $this->dni)->first();

            if (!$participante) {
                $participante = Participante::create([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'dni' => $this->dni,
                    'mail' => $this->mail,
                    'telefono' => $this->telefono,
                ]);
            } else {
                $datosActualizados = [];

                if ($participante->nombre !== $this->nombre) {
                    $datosActualizados['nombre'] = $this->nombre;
                }
                if ($participante->apellido !== $this->apellido) {
                    $datosActualizados['apellido'] = $this->apellido;
                }
                if ($participante->mail !== $this->mail) {
                    $datosActualizados['mail'] = $this->mail;
                }
                if ($participante->telefono !== $this->telefono) {
                    $datosActualizados['telefono'] = $this->telefono;
                }
                if (!empty($datosActualizados)) {
                    $participante->update($datosActualizados);
                }
            }

            // Verificar si ya está registrado en la planilla de inscripción
            $yaInscripto = InscripcionParticipante::where('planilla_id', $this->planilla_id)
                ->where('participante_id', $participante->participante_id)
                ->first();

            if ($yaInscripto) {
                DB::rollBack();
                $this->dispatch('oops', ['message' => 'Este participante ya está inscrito en esta planilla.']);
                return;
            }

            // Registrar la inscripción con el UUID correcto
            $inscripcion = InscripcionParticipante::create([
                'planilla_id' => $this->planilla_id,
                'participante_id' => $participante->participante_id,
                'fecha_inscripcion' => now(),
                'asistencia' => false,
            ]);

            // Guardar indicadores 

            $ids = collect($this->indicadoresMultiples);

            foreach ($this->indicadoresUnicos as $radioValue) {
                if ($radioValue) {
                    $ids->push($radioValue);
                }
            }

            foreach ($ids->unique() as $id) {
                ParticipanteIndicador::create([
                    'insc_participante_id' => $inscripcion->inscripcion_participante_id,
                    'indicador_id' => $id,
                ]);
            }

            DB::commit();


            // Enviar correo de confirmación al participante
            // Mail::to($this->mail)->send(new ConfirmacionInscripcion(
            //     $this->nombre,
            //     $this->apellido,
            //     $this->evento
            // ));
            $this->dispatch('success', message: '¡Inscripción completada con éxito!');

            $this->reset(['nombre', 'apellido', 'dni', 'mail', 'telefono', 'indicadoresMultiples', 'indicadoresUnicos']);
            $this->verificarInscripcionActiva(); // <-- Refresca el estado del formulario


            // return redirect()->route('inscripcion.publica', ['planilla' => $this->planillaId]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Hubo un error al procesar los datos: ' . $e->getMessage());
            return;
        }
    }


    public function render()
    {
        return view('livewire.registro-evento-publico')->layout('layouts.guest');
    }
}
