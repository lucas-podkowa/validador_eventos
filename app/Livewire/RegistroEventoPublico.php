<?php

namespace App\Livewire;

use App\Mail\ConfirmacionInscripcion;
use App\Models\Evento;
use App\Models\InscripcionParticipante;
use App\Models\Participante;
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
    public $localidad_id = null;
    public $tipos_indicadores_seleccionados = [];
    public $localidadesFiltradas = [];
    public ?array $participante = null;

    protected $rules = [
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'dni' => 'required|string|max:15',
        'mail' => 'required|email|max:100',
        'telefono' => 'required|string|min:6|max:15',
        //'indicadoresSeleccionados' => 'array',
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

        // Verificar si la inscripción está activa
        $hoy = Carbon::now();
        if ($this->planilla_inscripcion->apertura <= $hoy && $this->planilla_inscripcion->cierre >= $hoy) {
            $this->inscripcion_activa = true;
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

        // Crea la localidad si no existe
        // if (!$this->localidad_id) {
        //     $localidad = Localidad::create(['nombre' => $this->localidad_nombre]);
        //     $this->localidad_id = $localidad->localidad_id;
        // }
        DB::beginTransaction();
        try {
            if (!$this->planilla_inscripcion) {
                DB::rollBack();
                //                $this->dispatch('oops', message: 'Error: No hay una planilla de inscripción asociada a este evento.');
                $this->dispatch('oops', ['message' => 'Error: No hay una planilla de inscripción asociada a este evento.']);

                return;
            }

            // Buscar si el participante ya existe por DNI
            $participante = Participante::where('dni', $this->dni)->first();


            if (!$participante) {
                // Si no existe, crearlo
                $participante = Participante::create([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'dni' => $this->dni,
                    'mail' => $this->mail,
                    'telefono' => $this->telefono,
                    //'localidad_id' => $this->localidad_id,
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
            $inscripcionExistente = DB::table('inscripcion_participante')
                ->where('planilla_id', $this->planilla_id)
                ->where('participante_id', $participante->participante_id)
                ->exists();

            if ($inscripcionExistente) {
                DB::rollBack();
                $this->dispatch('oops', message: 'Este participante ya está inscrito en esta planilla.');
                return;
            }



            // Registrar la inscripción con el UUID correcto
            InscripcionParticipante::create([
                'planilla_id' => $this->planilla_id,
                'participante_id' => $participante->participante_id,
                'fecha_inscripcion' => now(),
                'asistencia' => false,
            ]);


            DB::commit();

            //$this->dispatch('alert', '¡Inscripción completada con éxito!');
            // Enviar correo de confirmación al participante

            Mail::to($this->mail)->send(new ConfirmacionInscripcion(
                $this->nombre,
                $this->apellido,
                $this->evento
            ));
            $this->reset(['nombre', 'apellido', 'dni', 'mail', 'telefono']);

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

    // public function buscarLocalidades()
    // {
    //     // Busca localidades que coincidan con el texto ingresado
    //     $this->localidadesFiltradas = Localidad::where('nombre', 'like', '%' . $this->localidad_nombre . '%')->get()->toArray();
    // }

    // public function seleccionarLocalidad($id, $nombre)
    // {
    //     // Asigna la localidad seleccionada
    //     $this->localidad_id = $id;
    //     $this->localidad_nombre = $nombre;
    //     $this->localidadesFiltradas = [];
    // }

    // public function updatedLocalidadNombre($value)
    // {
    //     // Si el nombre es nuevo y no se seleccionó una localidad, se asegura de limpiar localidad_id
    //     // if (!$this->localidadesFiltradas->contains('nombre', $value)) {
    //     //     $this->localidad_id = null;
    //     // }
    //     $nombres = array_column($this->localidadesFiltradas, 'nombre'); // Extrae solo los nombres
    //     if (!in_array($value, $nombres)) {
    //         $this->localidad_id = null;
    //     }
    // }
