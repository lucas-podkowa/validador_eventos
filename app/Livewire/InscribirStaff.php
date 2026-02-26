<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use App\Models\Participante;
use App\Models\InscripcionParticipante;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmacionInscripcion;
use App\Mail\CredencialesColaborador;

class InscribirStaff extends Component
{

    public $asunto = 'staff';
    public $evento;
    public $evento_id;
    public $planilla_id;
    public $nombre = '';
    public $apellido = '';
    public $dni = '';
    public $mail = '';
    public $telefono = '';
    public $rol_seleccionado = '';
    public ?array $participante = null;

    protected $rules = [
        'apellido' => ['required', 'regex:/^[\pL\s\-]+$/u', 'min:2', 'max:50'],
        'nombre'   => ['required', 'regex:/^[\pL\s\-]+$/u', 'min:2', 'max:50'],
        'dni'      => ['required', 'digits_between:6,10', 'numeric'],
        'mail'     => ['required', 'email'],
        'telefono' => ['required', 'regex:/^\d+$/', 'min:6', 'max:20'],
        'rol_seleccionado' => ['required', 'in:Disertante,Colaborador'],
    ];

    protected $messages = [
        'apellido.required' => 'El apellido es obligatorio.',
        'apellido.regex' => 'El apellido solo puede contener letras, espacios y guiones.',
        'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
        'apellido.max' => 'El apellido no puede superar los 50 caracteres.',

        'nombre.required' => 'El nombre es obligatorio.',
        'nombre.regex' => 'El nombre solo puede contener letras, espacios y guiones.',
        'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
        'nombre.max' => 'El nombre no puede superar los 50 caracteres.',

        'dni.required' => 'El DNI es obligatorio.',
        'dni.digits_between' => 'El DNI debe tener entre 6 y 10 dígitos.',
        'dni.numeric' => 'El DNI solo puede contener números.',

        'mail.required' => 'El email es obligatorio.',
        'mail.email' => 'Debe ingresar un email válido.',

        'telefono.required' => 'El teléfono es obligatorio.',
        'telefono.regex' => 'El teléfono solo puede contener números.',
        'telefono.min' => 'El teléfono debe tener al menos 6 dígitos.',
        'telefono.max' => 'El teléfono no puede superar los 20 dígitos.',

        'rol_seleccionado.required' => 'Debe seleccionar un rol.',
        'rol_seleccionado.in' => 'El rol debe ser Disertante o Colaborador.',
    ];

    public function mount($evento_id)
    {
        $this->evento_id = $evento_id;
        $this->evento = Evento::with('planillaInscripcion')->findOrFail($evento_id);

        if (!$this->evento->planillaInscripcion) {
            session()->flash('error', 'No se encontró la planilla de inscripción para este evento.');
            return redirect()->route('eventos', ['tab' => 'activos']);
        }

        $this->planilla_id = $this->evento->planillaInscripcion->planilla_inscripcion_id;
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
                $this->reset(['nombre', 'apellido', 'mail', 'telefono']);
            }
        }
    }

    public function submit()
    {
        $this->validate();

        // Normalizar nombre y apellido
        $this->nombre = mb_convert_case(mb_strtolower(trim($this->nombre)), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(mb_strtolower(trim($this->apellido)), MB_CASE_TITLE, "UTF-8");

        DB::beginTransaction();
        try {
            // Obtener el rol seleccionado
            $rol = Rol::where('nombre', $this->rol_seleccionado)->first();

            if (!$rol) {
                DB::rollBack();
                $this->dispatch('oops', message: "Error: No se encontró el rol '{$this->rol_seleccionado}'.");
                return;
            }

            // Buscar o crear participante
            $participante = Participante::where('dni', $this->dni)->first();

            if (!$participante) {
                // Verificar que el email no exista
                $mailExistente = Participante::where('mail', $this->mail)->exists();

                if ($mailExistente) {
                    DB::rollBack();
                    $this->dispatch('oops', message: 'El correo electrónico ingresado ya está registrado para otro participante.');
                    return;
                }

                // Crear nuevo participante
                $participante = Participante::create([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'dni' => $this->dni,
                    'mail' => $this->mail,
                    'telefono' => $this->telefono,
                ]);
            } else {
                // Actualizar datos si cambiaron
                $datosActualizados = [];

                if ($participante->nombre !== $this->nombre) {
                    $datosActualizados['nombre'] = $this->nombre;
                }

                if ($participante->apellido !== $this->apellido) {
                    $datosActualizados['apellido'] = $this->apellido;
                }

                if ($participante->mail !== $this->mail) {
                    $mailUsadoPorOtro = Participante::where('mail', $this->mail)
                        ->where('participante_id', '!=', $participante->participante_id)
                        ->exists();

                    if ($mailUsadoPorOtro) {
                        DB::rollBack();
                        $this->dispatch('oops', message: 'El correo ingresado ya está siendo utilizado por otro participante.');
                        return;
                    }

                    $datosActualizados['mail'] = $this->mail;
                }

                if ($participante->telefono !== $this->telefono) {
                    $datosActualizados['telefono'] = $this->telefono;
                }

                if (!empty($datosActualizados)) {
                    $participante->update($datosActualizados);
                }
            }

            // Verificar si ya está inscrito
            $yaInscripto = InscripcionParticipante::where('planilla_id', $this->planilla_id)
                ->where('participante_id', $participante->participante_id)
                ->exists();

            if ($yaInscripto) {
                DB::rollBack();
                $this->dispatch('oops', message: 'Este participante ya está inscrito en este evento.');
                return;
            }

            // Crear inscripción
            InscripcionParticipante::create([
                'planilla_id' => $this->planilla_id,
                'participante_id' => $participante->participante_id,
                'rol_id' => $rol->rol_id,
                'fecha_inscripcion' => now(),
                'asistencia' => false,
            ]);

            // Si el rol es Colaborador, crear o asignar usuario en el sistema
            $usuarioNuevo = false;
            $passwordPlano = null;

            if ($this->rol_seleccionado === 'Colaborador') {
                $user = User::where('email', $this->mail)->first();

                if (!$user) {
                    // Crear nuevo usuario con el DNI como contraseña inicial
                    $passwordPlano = $this->dni;
                    $user = User::create([
                        'name'     => "{$this->nombre} {$this->apellido}",
                        'email'    => $this->mail,
                        'password' => Hash::make($passwordPlano),
                    ]);
                    $usuarioNuevo = true;
                }

                // Asignar rol Spatie "Colaborador" (si ya lo tiene, Spatie lo ignora)
                $user->assignRole('Colaborador');
            }

            DB::commit();

            // Enviar correo de confirmación de inscripción
            try {
                Mail::to($this->mail)->send(new ConfirmacionInscripcion(
                    $this->nombre,
                    $this->apellido,
                    $this->evento,
                    $this->asunto,
                ));
            } catch (\Exception $mailException) {
                $this->dispatch('oops', message: 'Error enviando correo de confirmación: ' . $mailException->getMessage());
            }

            // Enviar credenciales si se creó o asignó usuario Colaborador
            if ($this->rol_seleccionado === 'Colaborador') {
                try {
                    Mail::to($this->mail)->send(new CredencialesColaborador(
                        $this->nombre,
                        $this->apellido,
                        $this->mail,
                        $passwordPlano ?? '',
                        $this->evento,
                        $usuarioNuevo,
                    ));
                } catch (\Exception $mailException) {
                    $this->dispatch('oops', message: 'Error enviando credenciales de acceso: ' . $mailException->getMessage());
                }
            }

            // Redirigir de vuelta a eventos activos con la tabla abierta
            return redirect()->route('eventos', [
                'tab' => 'en_curso',
                'evento_id' => $this->evento_id,
                'mostrar' => 'disertantes'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error al procesar la inscripción: ' . $e->getMessage());
        }
    }

    public function volver()
    {
        return redirect()->route('eventos', [
            'tab' => 'activos',
            'evento_id' => $this->evento_id,
            'mostrar' => 'disertantes'
        ]);
    }


    public function render()
    {
        return view('livewire.inscribir-staff');
    }
}
