<?php

namespace App\Livewire;

use App\Models\CategoriaEvento;
use App\Models\Destinatario;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\InscripcionParticipante;
use App\Models\PlanillaInscripcion;
use App\Models\Responsable;
use App\Models\TipoEvento;
use App\Models\TipoIndicador;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearEvento extends Component
{
    use WithFileUploads;
    public $evento_id = null; // Para saber si es edición o creación

    public $categoria_id = null;

    public $tipo_evento_id = null;

    public bool $por_aprobacion = false;

    public bool $arancel = false;

    public ?string $link_pago = null;

    public $nombre_evento = null;

    public $fecha_inicio = null;

    public $lugar_evento = null;

    public $cupo = null;

    public $estado_evento = null;

    public $categorias = [];

    public $tiposEventos = [];

    public $tiposIndicadores = [];

    public $indicadoresSeleccionados = [];

    public $destinatarios = [];

    public array $destinatarioSeleccionado = [];

    public array $destinatarioPrecio = [];

    // Métodos de pago flexibles (url, cbu, alias, qr_image path, etc.)
    public array $metodosPago = [];

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
        if (! auth()->user()->hasRole('Administrador') && ! $evento_id) {
            abort(403, 'No tenés permiso para crear eventos.');
        }

        $this->categorias = CategoriaEvento::orderBy('nombre')->get();
        $this->tiposEventos = TipoEvento::orderBy('nombre')->get();
        $this->tiposIndicadores = TipoIndicador::all();

        if ($evento_id) {
            $evento = Evento::with('destinatarios')->find($evento_id);
            $seleccionadosIds = $evento ? $evento->destinatarios->pluck('destinatario_id')->toArray() : [];
            $this->destinatarios = Destinatario::where('activo', true)
                ->orWhereIn('destinatario_id', $seleccionadosIds)
                ->orderBy('nombre')
                ->get();
        } else {
            $this->destinatarios = Destinatario::where('activo', true)->orderBy('nombre')->get();
        }

        if ($evento_id) {
            $this->evento_id = $evento_id;
            $evento = Evento::with('tipoIndicadores')->find($evento_id);

            if ($evento) {
                $this->categoria_id = $evento->categoria_id;
                $this->tipo_evento_id = $evento->tipo_evento_id;
                $this->nombre_evento = $evento->nombre;
                $this->fecha_inicio = $evento->fecha_inicio;
                $this->lugar_evento = $evento->lugar;
                $this->cupo = $evento->cupo;
                $this->estado_evento = $evento->estado;
                $this->por_aprobacion = (bool) $evento->por_aprobacion;
                $this->arancel = (bool) $evento->arancel;
                $this->link_pago = $evento->link_pago;
                // Hidratar métodos de pago si existen
                $this->metodosPago = is_array($evento->metodos_pago) ? $evento->metodos_pago : ($evento->metodos_pago ? json_decode($evento->metodos_pago, true) : []);
                if (empty($this->metodosPago) && ! empty($this->link_pago)) {
                    $this->metodosPago = [[
                        'tipo' => 'url',
                        'valor' => $this->link_pago,
                        'principal' => true,
                        'activo' => true,
                    ]];
                }
                $this->indicadoresSeleccionados = $evento->tipoIndicadores()->pluck('tipo_indicador.tipo_indicador_id')->toArray();

                foreach ($evento->destinatarios as $destinatario) {
                    $this->destinatarioSeleccionado[] = (string) $destinatario->destinatario_id;
                    $this->destinatarioPrecio[$destinatario->destinatario_id] = $destinatario->pivot->precio;
                }

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

    // ----------------------------------------------------------------
    // -----  Crear o Actualizar Evento -------------------------------
    // ----------------------------------------------------------------

    public function save()
    {
        $reglas = [
            'categoria_id' => 'required|exists:categoria_evento,categoria_id',
            'tipo_evento_id' => 'required|exists:tipo_evento,tipo_evento_id',
            'nombre_evento' => 'required|string|min:3|max:255',
            'lugar_evento' => 'required|string|min:2|max:255',
            'cupo' => 'nullable|integer|min:0',
            'responsable_id' => 'required',
            'arancel' => 'boolean',
            'link_pago' => 'nullable|url|max:500',
        ];

        $mensajes = [
            'metodosPago.*.valor.required' => 'Complete el valor del método de pago.',
            'metodosPago.*.valor.url' => 'La URL ingresada no es válida.',
            'metodosPago.*.valor_file.required' => 'Adjunte una imagen para el método QR.',
        ];

        // Validar la fecha siempre que esté presente
        $reglas['fecha_inicio'] = 'required|date';

        if ($this->arancel) {
            $reglas['destinatarioSeleccionado'] = 'required|array|min:1';
            $reglas['destinatarioSeleccionado.*'] = 'exists:destinatarios,destinatario_id';

            foreach ($this->destinatarioSeleccionado as $id) {
                $reglas["destinatarioPrecio.$id"] = 'required|numeric|min:0';
            }

            foreach ($this->metodosPago as $index => $metodo) {
                if (! ($metodo['activo'] ?? true)) {
                    continue;
                }

                $tipo = $metodo['tipo'] ?? 'url';

                if ($tipo === 'qr_image') {
                    $reglas["metodosPago.$index.valor_file"] = 'required';
                } else {
                    $reglas["metodosPago.$index.valor"] = ['required', 'string', 'min:1'];

                    if ($tipo === 'url') {
                        $reglas["metodosPago.$index.valor"][] = 'url';
                    }
                }
            }

            $tienePrecioPositivo = collect($this->destinatarioSeleccionado)
                ->contains(fn ($id) => (float) ($this->destinatarioPrecio[$id] ?? 0) > 0);

            if ($tienePrecioPositivo) {
                $metodosValidos = collect($this->metodosPago)
                    ->filter(fn ($metodo) => ($metodo['activo'] ?? true) && ! empty(trim((string) ($metodo['valor'] ?? ''))))
                    ->count();

                if ($metodosValidos === 0 && empty(trim((string) $this->link_pago))) {
                    $this->addError('metodosPago', 'Agrega al menos un método de pago válido (URL, CBU/CVU, Alias o QR).');

                    return;
                }
            }
        }

        $this->validate($reglas, $mensajes);

        // Validar que no se desmarquen destinatarios ya usados ni se quite el arancel si hay inscripciones pagas
        if ($this->evento_id) {
            $evento = Evento::find($this->evento_id);
            $planilla = $evento?->planillaInscripcion;

            if ($planilla) {
                if ($this->arancel) {
                    $seleccionados = collect($this->destinatarioSeleccionado)->map(fn ($id) => (int) $id)->all();
                    $enUso = InscripcionParticipante::where('planilla_id', $planilla->planilla_inscripcion_id)
                        ->whereNotIn('destinatario_id', $seleccionados)
                        ->whereNotNull('destinatario_id')
                        ->exists();

                    if ($enUso) {
                        $this->addError('destinatarioSeleccionado', 'No se pueden desmarcar destinatarios que ya tienen inscripciones asociadas.');

                        return;
                    }
                } else {
                    $tieneInscripcionesPagas = InscripcionParticipante::where('planilla_id', $planilla->planilla_inscripcion_id)
                        ->whereNotNull('destinatario_id')
                        ->exists();

                    if ($tieneInscripcionesPagas) {
                        $this->addError('arancel', 'No se puede quitar el arancel porque ya existen inscripciones con destinatarios asociadas.');

                        return;
                    }
                }
            }
        }

        // Validar los datos del formulario
        $this->nombre_evento = mb_strtoupper(trim($this->nombre_evento));

        $metodosPagoPersistidos = $this->normalizarMetodosPago();

        if (! empty($metodosPagoPersistidos)) {
            $this->link_pago = collect($metodosPagoPersistidos)->first(fn ($m) => ($m['tipo'] ?? 'url') === 'url' && ! empty($m['valor']))['valor'] ?? $this->link_pago;
        }

        DB::beginTransaction();
        try {
            $datosEvento = [
                'categoria_id' => $this->categoria_id,
                'tipo_evento_id' => $this->tipo_evento_id,
                'nombre' => $this->nombre_evento,
                'lugar' => $this->lugar_evento,
                'fecha_inicio' => Carbon::parse($this->fecha_inicio),
                'cupo' => $this->cupo,
                'por_aprobacion' => (bool) $this->por_aprobacion,
                'arancel' => (bool) $this->arancel,
                'link_pago' => $this->arancel ? $this->link_pago : null,
                'metodos_pago' => $this->arancel ? $metodosPagoPersistidos : null,
                'responsable_id' => $this->responsable_id,
            ];

            if ($this->evento_id) {
                // Modo edición
                $evento = Evento::findOrFail($this->evento_id);
                $evento->update($datosEvento);
                $evento->tipoIndicadores()->sync($this->indicadoresSeleccionados);
            } else {
                // Modo creación
                $evento = Evento::where('nombre', $datosEvento['nombre'])
                    ->where('lugar', $datosEvento['lugar'])
                    ->where('tipo_evento_id', $datosEvento['tipo_evento_id'])
                    ->where('fecha_inicio', $datosEvento['fecha_inicio'])
                    ->first();

                Log::info('Intentando guardar evento', $datosEvento);

                if (! $evento) {
                    $evento = Evento::create($datosEvento);
                    $evento->estado = 'Pendiente';
                    $evento->save();
                    $evento->tipoIndicadores()->attach($this->indicadoresSeleccionados);
                }
            }

            if ($this->arancel) {
                $sync = [];
                foreach ($this->destinatarioSeleccionado as $id) {
                    $sync[(int) $id] = ['precio' => (float) ($this->destinatarioPrecio[$id] ?? 0)];
                }
                $evento->destinatarios()->sync($sync);
            } else {
                $evento->destinatarios()->detach();
            }

            Log::info('Evento guardado correctamente', ['evento_id' => $evento->evento_id]);

            DB::commit();

            // Resetear los campos después de guardar
            $this->reset([
                'evento_id',
                'categoria_id',
                'tipo_evento_id',
                'nombre_evento',
                'fecha_inicio',
                'lugar_evento',
                'cupo',
                'indicadoresSeleccionados',
                'por_aprobacion',
                'arancel',
                'link_pago',
                'destinatarioSeleccionado',
                'destinatarioPrecio',
                'responsable_id',
                'responsable_dni',
                'responsable_nombre',
                'responsable_apellido',
                'responsable_encontrado',
                'metodosPago',
            ]);

            // Redirigir a la ruta de eventos después de la creación exitosa
            return redirect()->route('eventos');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Hubo un error al procesar los datos: '.$e->getMessage());

            return;
        }
    }

    // ----------------------------------------------------------------
    // -----  Eliminar Evento------------------------------------------
    // ----------------------------------------------------------------

    public function eliminarEvento()
    {
        // Solo el Administrador puede eliminar eventos.
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'No tenés permiso para eliminar eventos.');

        if (! $this->evento_id) {
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
            $this->dispatch('oops', message: 'Error al eliminar el evento: '.$e->getMessage());
        }
    }

    public function cancelarEdicion()
    {
        return redirect()->route('eventos');
    }

    // ----------------------------------------------------------------
    // -----  Responsable del Evento ----------------------------------
    // ----------------------------------------------------------------

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
        if (! $this->modal_responsable_buscado) {
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

    // ----------------------------------------------------------------
    // ----- Métodos de pago (skeleton helpers) ------------------------
    // ----------------------------------------------------------------

    public function addMetodo()
    {
        $this->metodosPago[] = [
            'tipo' => 'url',
            'valor' => '',
            'principal' => empty($this->metodosPago),
            'activo' => true,
        ];
    }

    public function removeMetodo($index)
    {
        if (isset($this->metodosPago[$index])) {
            array_splice($this->metodosPago, $index, 1);
        }
    }

    public function setPrincipalMetodo($index)
    {
        foreach ($this->metodosPago as $k => $m) {
            $this->metodosPago[$k]['principal'] = ($k == $index);
        }
    }

    protected function normalizarMetodosPago(): array
    {
        $normalizados = [];

        foreach ($this->metodosPago as $metodo) {
            $normalizado = [
                'tipo' => $metodo['tipo'] ?? 'url',
                'valor' => trim((string) ($metodo['valor'] ?? '')),
                'principal' => (bool) ($metodo['principal'] ?? false),
                'activo' => (bool) ($metodo['activo'] ?? true),
            ];

            if (($normalizado['tipo'] ?? 'url') === 'qr_image' && ! empty($metodo['valor_file'] ?? null)) {
                $file = $metodo['valor_file'];
                if (is_object($file) && method_exists($file, 'store')) {
                    $normalizado['valor'] = $file->store('pagos/qr', 'public');
                }
            }

            if (! empty($normalizado['valor']) || ($normalizado['tipo'] ?? 'url') === 'qr_image') {
                $normalizados[] = $normalizado;
            }
        }

        $hayPrincipal = false;
        foreach ($normalizados as $i => $metodo) {
            if (! empty($metodo['principal'])) {
                if (! $hayPrincipal) {
                    $hayPrincipal = true;
                } else {
                    $normalizados[$i]['principal'] = false;
                }
            }
        }

        if (! $hayPrincipal && ! empty($normalizados)) {
            $normalizados[0]['principal'] = true;
        }

        return $normalizados;
    }

    public function moveMetodoUp($index)
    {
        if ($index > 0 && isset($this->metodosPago[$index - 1])) {
            $tmp = $this->metodosPago[$index - 1];
            $this->metodosPago[$index - 1] = $this->metodosPago[$index];
            $this->metodosPago[$index] = $tmp;
        }
    }

    public function moveMetodoDown($index)
    {
        $count = count($this->metodosPago);
        if ($index < $count - 1 && isset($this->metodosPago[$index + 1])) {
            $tmp = $this->metodosPago[$index + 1];
            $this->metodosPago[$index + 1] = $this->metodosPago[$index];
            $this->metodosPago[$index] = $tmp;
        }
    }

    // ----------------------------------------------------------------
    // ----------------------------------------------------------------
    // ----------------------------------------------------------------

    public function render()
    {

        return view('livewire.crear-evento', [
            'esEdicion' => $this->evento_id !== null,
        ]);
    }
}
