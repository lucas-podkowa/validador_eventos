<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\PlanillaInscripcion;
use App\Models\Responsable;
use App\Models\TipoEvento;
use App\Models\TipoIndicador;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

//use Livewire\WithFileUploads;

class CrearEvento extends Component
{
    //use WithFileUploads;
    public $evento_id = null; // Para saber si es edición o creación
    public $tipo_evento_id = null;

    public bool $por_aprobacion = false;

    public $nombre_evento = null;
    public $fecha_inicio = null;
    public $lugar_evento = null;
    public $cupo = null;
    public $estado_evento = null;

    public $tiposEventos = [];
    public $tiposIndicadores = [];
    public $indicadoresSeleccionados = [];

    // Responsable
    public $responsable_id = null;
    public $responsable_dni = null;
    public $responsable_nombre = null;
    public $responsable_apellido = null;
    public $responsable_encontrado = false;
    public $open_responsable = false;
    public $modal_responsable_id = null;
    public $modal_responsable_dni = null;
    public $modal_responsable_nombre = null;
    public $modal_responsable_apellido = null;
    public bool $modal_responsable_encontrado = false;
    public bool $modal_responsable_buscado = false;

    public function mount($evento_id = null)
    {
        // Gestores solo pueden editar eventos existentes, no crear nuevos.
        if (!auth()->user()->hasRole('Administrador') && !$evento_id) {
            abort(403, 'No tenés permiso para crear eventos.');
        }

        $this->tiposEventos = TipoEvento::all();
        $this->tiposIndicadores = TipoIndicador::all();

        if ($evento_id) {
            $this->evento_id = $evento_id;
            $evento = Evento::with('tipoIndicadores')->find($evento_id);

            if ($evento) {
                $this->tipo_evento_id = $evento->tipo_evento_id;
                $this->nombre_evento = $evento->nombre;
                $this->fecha_inicio = $evento->fecha_inicio;
                $this->lugar_evento = $evento->lugar;
                $this->cupo = $evento->cupo;
                $this->estado_evento = $evento->estado;
                $this->por_aprobacion = (bool) $evento->por_aprobacion;
                $this->indicadoresSeleccionados = $evento->tipoIndicadores()->pluck('tipo_indicador.tipo_indicador_id')->toArray();

                // Cargar responsable si existe
                if ($evento->responsable_id) {
                    $responsable = $evento->responsable;
                    $this->responsable_id = $responsable->responsable_id;
                    $this->responsable_dni = $responsable->dni;
                    $this->responsable_nombre = $responsable->getAttributes()['nombre'];
                    $this->responsable_apellido = $responsable->getAttributes()['apellido'];
                    $this->responsable_encontrado = true;
                }
            }
        }
    }

    //----------------------------------------------------------------
    //-----  Crear o Actualizar Evento -------------------------------
    //----------------------------------------------------------------

    public function save()
    {
        $reglas = [
            'tipo_evento_id' => 'required',
            'nombre_evento' => 'required|string|min:3|max:255',
            'lugar_evento' => 'required|string|min:2|max:255',
            'cupo' => 'nullable|integer|min:0',
            'responsable_id' => 'required',
        ];

        // Solo validar la fecha si estamos creando o el estado es Pendiente
        if (!$this->evento_id || $this->estado_evento === 'Pendiente') {
            $reglas['fecha_inicio'] = 'required|date|after_or_equal:today';
        }

        $this->validate($reglas);


        // Validar los datos del formulario
        $this->nombre_evento = mb_strtoupper(trim($this->nombre_evento));

        DB::beginTransaction();
        try {
            $datosEvento = [
                'tipo_evento_id' => $this->tipo_evento_id,
                'nombre' => $this->nombre_evento,
                'lugar' => $this->lugar_evento,
                'fecha_inicio' => Carbon::parse($this->fecha_inicio),
                'cupo' => $this->cupo,
                'por_aprobacion' => (bool) $this->por_aprobacion,
                'responsable_id' => $this->responsable_id,
            ];


            if ($this->evento_id) {
                // Modo edición
                $evento = Evento::findOrFail($this->evento_id);
                if ($evento) {
                    $evento->update($datosEvento);
                    $evento->tipoIndicadores()->sync($this->indicadoresSeleccionados);
                }
            } else {
                // Modo creación

                $evento = Evento::where('nombre', $datosEvento['nombre'])
                    ->where('lugar', $datosEvento['lugar'])
                    ->where('tipo_evento_id', $datosEvento['tipo_evento_id'])
                    ->where('fecha_inicio', $datosEvento['fecha_inicio'])
                    ->first();

                // Log antes de guardar
                Log::info('Intentando guardar evento', $datosEvento);

                if (!$evento) {
                    $evento = Evento::create($datosEvento);
                    $evento->estado = 'Pendiente';
                    $evento->save();
                    // Guardar los indicadores seleccionados
                    $evento->tipoIndicadores()->attach($this->indicadoresSeleccionados);
                }
            }


            // Log después de guardar
            Log::info('Evento guardado correctamente', ['evento_id' => $evento->evento_id]);

            // // Guardar los indicadores seleccionados
            // foreach ($this->indicadoresSeleccionados as $tipo_indicador_id) {
            //     $evento->tipoIndicadores()->attach($tipo_indicador_id);
            // }

            //Confirmar transacción
            DB::commit();

            //Resetear los campos después de guardar
            $this->reset([
                'evento_id',
                'tipo_evento_id',
                'nombre_evento',
                'fecha_inicio',
                'lugar_evento',
                'cupo',
                'indicadoresSeleccionados',
                'por_aprobacion',
                'responsable_id',
                'responsable_dni',
                'responsable_nombre',
                'responsable_apellido',
                'responsable_encontrado',
            ]);

            // Redirigir a la ruta de eventos después de la creación exitosa
            return redirect()->route('eventos');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Hubo un error al procesar los datos: ' . $e->getMessage());
            return;
        }
    }

    //----------------------------------------------------------------
    //-----  Eliminar Evento------------------------------------------
    //----------------------------------------------------------------

    public function eliminarEvento()
    {
        // Solo el Administrador puede eliminar eventos.
        abort_if(!auth()->user()->hasRole('Administrador'), 403, 'No tenés permiso para eliminar eventos.');

        if (!$this->evento_id) {
            return;
        }

        DB::beginTransaction();
        try {

            $tienePlanilla = PlanillaInscripcion::where('evento_id', $this->evento_id)->exists();
            $tieneParticipantes = EventoParticipante::where('evento_id', $this->evento_id)->exists();

            if ($tienePlanilla || $tieneParticipantes) {
                $this->dispatch('oops', message: 'No se puede eliminar. Existen inscripciones activas o planillas asociadas.');
                return;
            }

            // Eliminar el evento
            Evento::findOrFail($this->evento_id)->delete();

            DB::commit();
            $this->dispatch('alert', message: 'El evento fue eliminado correctamente.');
            return redirect()->route('eventos');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error al eliminar el evento: ' . $e->getMessage());
        }
    }

    public function cancelarEdicion()
    {
        return redirect()->route('eventos');
    }

    //----------------------------------------------------------------
    //-----  Responsable del Evento ----------------------------------
    //----------------------------------------------------------------

    public function abrirSelectorResponsable()
    {
        $this->resetValidation();
        $this->modal_responsable_id = $this->responsable_id;
        $this->modal_responsable_dni = $this->responsable_dni;
        $this->modal_responsable_nombre = $this->responsable_nombre;
        $this->modal_responsable_apellido = $this->responsable_apellido;
        $this->modal_responsable_encontrado = (bool) $this->responsable_id;
        $this->modal_responsable_buscado = (bool) $this->responsable_id;
        $this->open_responsable = true;
    }

    public function cancelarSelectorResponsable()
    {
        $this->resetValidation();
        $this->open_responsable = false;
    }

    public function updatedModalResponsableDni($value)
    {
        $dniSanitizado = preg_replace('/\D+/', '', (string) $value);

        if ($this->modal_responsable_dni !== $dniSanitizado) {
            $this->modal_responsable_dni = $dniSanitizado;
        }

        $this->modal_responsable_id = null;
        $this->modal_responsable_nombre = null;
        $this->modal_responsable_apellido = null;
        $this->modal_responsable_encontrado = false;
        $this->modal_responsable_buscado = false;
        $this->resetErrorBag([
            'modal_responsable_dni',
            'modal_responsable_nombre',
            'modal_responsable_apellido',
        ]);
    }

    public function buscarResponsable()
    {
        $datos = $this->validate([
            'modal_responsable_dni' => ['bail', 'required', 'regex:/^\d+$/', 'digits_between:7,10'],
        ]);

        $responsable = Responsable::where('dni', $datos['modal_responsable_dni'])->first();

        $this->modal_responsable_buscado = true;

        if ($responsable) {
            $this->modal_responsable_id = $responsable->responsable_id;
            $this->modal_responsable_dni = $responsable->dni;
            $this->modal_responsable_nombre = $responsable->nombre;
            $this->modal_responsable_apellido = $responsable->apellido;
            $this->modal_responsable_encontrado = true;
        } else {
            $this->modal_responsable_id = null;
            $this->modal_responsable_nombre = null;
            $this->modal_responsable_apellido = null;
            $this->modal_responsable_encontrado = false;
        }
    }

    public function seleccionarResponsable()
    {
        if (!$this->modal_responsable_buscado) {
            $this->addError('modal_responsable_dni', 'Busque un DNI válido antes de continuar.');
            return;
        }

        $datos = $this->validate([
            'modal_responsable_dni' => ['bail', 'required', 'regex:/^\d+$/', 'digits_between:7,10'],
            'modal_responsable_nombre' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s]+$/u'],
            'modal_responsable_apellido' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s]+$/u'],
        ]);

        if ($this->modal_responsable_encontrado && $this->modal_responsable_id) {
            // Responsable existente, ya tenemos el ID
        } else {
            // Crear nuevo responsable
            $nuevoResponsable = Responsable::create([
                'nombre' => mb_strtoupper(trim($datos['modal_responsable_nombre'])),
                'apellido' => mb_strtoupper(trim($datos['modal_responsable_apellido'])),
                'dni' => $datos['modal_responsable_dni'],
            ]);
            $this->modal_responsable_id = $nuevoResponsable->responsable_id;
            $this->modal_responsable_nombre = $nuevoResponsable->nombre;
            $this->modal_responsable_apellido = $nuevoResponsable->apellido;
            $this->modal_responsable_encontrado = true;
        }

        $this->responsable_id = $this->modal_responsable_id;
        $this->responsable_dni = $this->modal_responsable_dni;
        $this->responsable_nombre = $this->modal_responsable_nombre;
        $this->responsable_apellido = $this->modal_responsable_apellido;
        $this->responsable_encontrado = true;
        $this->open_responsable = false;
    }


    //----------------------------------------------------------------
    //----------------------------------------------------------------
    //----------------------------------------------------------------


    public function render()
    {

        return view('livewire.crear-evento', [
            'esEdicion' => $this->evento_id !== null,
        ]);
    }
}
