<?php

namespace App\Livewire;

use App\Models\Evento;
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
    public $tipo_evento = null;
    public $nombre_evento = null;
    public $fecha_inicio = null;
    public $lugar_evento = null;

    public $tiposEventos = [];
    public $tiposIndicadores = [];
    public $indicadoresSeleccionados = [];

    // Reglas de validación
    protected $rules = [
        'tipo_evento'  => 'required',
        'nombre_evento' => 'required|string|min:3|max:255',
        'fecha_inicio' => 'required|date|after_or_equal:today',
        'lugar_evento'  => 'required|string|min:2|max:255',
    ];

    public function mount($evento_id = null)
    {
        $this->tiposEventos = TipoEvento::all();
        $this->tiposIndicadores = TipoIndicador::all();

        if ($evento_id) {
            $this->evento_id = $evento_id;
            $evento = Evento::with('tipoIndicadores')->find($evento_id);

            if ($evento) {
                $this->tipo_evento = $evento->tipo_evento_id;
                $this->nombre_evento = $evento->nombre;
                $this->fecha_inicio = $evento->fecha_inicio;
                $this->lugar_evento = $evento->lugar;
                $this->indicadoresSeleccionados = $evento->tipoIndicadores()->pluck('tipo_indicador.tipo_indicador_id')->toArray();
            }
        }
    }

    // // Método para cargar datos cuando se edita un evento
    // public function edit($evento_id)
    // {
    //     $evento = Evento::findOrFail($evento_id);

    //     $this->evento_id = $evento->id;
    //     $this->tipo_evento = $evento->tipo_evento_id;
    //     $this->nombre_evento = $evento->nombre;
    //     $this->fecha_inicio = Carbon::parse($evento->fecha_inicio)->format('Y-m-d');
    //     $this->lugar_evento = $evento->lugar;
    //     $this->indicadoresSeleccionados = $evento->tipoIndicadores()->pluck('id')->toArray();
    // }

    // Función para procesar el formulario
    public function save()
    {
        // Validar los datos del formulario
        $this->validate();

        DB::beginTransaction();
        try {
            $datosEvento = [
                'tipo_evento_id' => $this->tipo_evento,
                'nombre' => $this->nombre_evento,
                'lugar' => $this->lugar_evento,
                'fecha_inicio' => Carbon::parse($this->fecha_inicio)
                //'cudap' => uniqid() // Generar un código único
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
                'tipo_evento',
                'nombre_evento',
                'fecha_inicio',
                'lugar_evento',
                'indicadoresSeleccionados'
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
    //----------------------------------------------------------------
    //----------------------------------------------------------------


    public function render()
    {
        //return view('livewire.crear-evento');

        return view('livewire.crear-evento', [
            'esEdicion' => $this->evento_id !== null,
        ]);
    }
}
