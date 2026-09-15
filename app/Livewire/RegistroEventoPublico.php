<?php

namespace App\Livewire;

use App\Actions\BuscarParticipanteSimilar;
use App\Mail\ConfirmacionInscripcion;
use App\Models\DocumentoPresentado;
use App\Models\DuplicadoRevision;
use App\Models\Evento;
use App\Models\InscripcionParticipante;
use App\Models\Participante;
use App\Models\ParticipanteIndicador;
use App\Models\PlanillaInscripcion;
use App\Models\RequisitoDocumentacion;
use App\Models\Rol;
use App\Rules\LargoNombreCertificado;
use App\Support\NormalizadorIdentidad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class RegistroEventoPublico extends Component
{
    use WithFileUploads;

    public $asunto = 'participante';

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

    // Métodos de pago disponibles para el evento (hidratados desde Evento)
    public array $metodosPago = [];

    public $indicadoresMultiples = []; // para checkboxes

    public $indicadoresUnicos = []; // para radios

    public $rol_participante_id = null; // para cachear el ID del rol

    public $destinatario_id = null;

    public $comprobante = null;

    public ?float $montoDestinatario = null;

    public $requisitosActivos = [];

    public array $documentos = [];

    public ?array $similar = null;

    public ?string $decision_similar = null;

    protected $rules = [
        'apellido' => ['required', 'regex:/^[\pL\s\-\.]+$/u', 'min:2', 'max:50'], // letras, espacios, guiones y puntos
        'nombre' => ['required', 'regex:/^[\pL\s\-\.]+$/u', 'min:2', 'max:50'],
        'dni' => ['required', 'digits_between:6,10', 'numeric'],
        'mail' => ['required', 'email'],
        'telefono' => ['required', 'regex:/^\d+$/', 'min:6', 'max:20'],
    ];

    public function mount($tipoEvento, $eventoId)
    {

        $this->evento_id = $eventoId;
        $this->planilla_inscripcion = PlanillaInscripcion::where('evento_id', $eventoId)->first();

        if (! $this->planilla_inscripcion) {
            dd('No se encontró planilla para evento_id: '.$eventoId);
        } else {
            $this->planilla_id = $this->planilla_inscripcion->planilla_inscripcion_id;
        }

        $this->evento = Evento::with('destinatarios', 'requisitos')->findOrFail($eventoId);

        // Hidratar métodos de pago si existen
        $this->metodosPago = is_array($this->evento->metodos_pago) ? $this->evento->metodos_pago : ($this->evento->metodos_pago ? json_decode($this->evento->metodos_pago, true) : []);

        $rolParticipante = Rol::where('nombre', 'Participante')->first();
        if (! $rolParticipante) {
            dd("Error: No se encontró el rol 'Participante'. Ejecuta el seeder.");
        }
        $this->rol_participante_id = $rolParticipante->rol_id;

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

    public function detectarSimilar(): void
    {
        $this->similar = null;
        $this->decision_similar = null;

        $candidato = app(BuscarParticipanteSimilar::class)->buscar(
            apellido: (string) $this->apellido,
            nombre: (string) $this->nombre,
            telefono: (string) $this->telefono,
            mail: (string) $this->mail,
            dniExcluir: $this->dni !== null && $this->dni !== '' ? (int) $this->dni : null,
        );

        if ($candidato) {
            $this->similar = [
                'participante_id' => $candidato->participante_id,
                'nombre' => $candidato->nombre,
                'apellido' => $candidato->apellido,
                'dni' => $candidato->dni,
                'mail' => $candidato->mail,
                'telefono' => $candidato->telefono,
            ];
        }
    }

    public function usarSimilar(): void
    {
        $this->decision_similar = 'usar';
    }

    public function crearNuevo(): void
    {
        $this->decision_similar = 'nuevo';
    }

    public function updatedDestinatarioId($value)
    {
        $destinatario = $this->evento->destinatarios->firstWhere('destinatario_id', $value);
        $this->montoDestinatario = $destinatario ? (float) $destinatario->pivot->precio : null;

        $this->requisitosActivos = RequisitoDocumentacion::where('evento_id', $this->evento_id)
            ->where('destinatario_id', $value)
            ->orderBy('orden')
            ->get()
            ->toArray();
        $this->documentos = [];
    }

    protected function reglas()
    {
        $reglas = [
            'apellido' => ['required', 'regex:/^[\pL\s\-\.]+$/u', 'min:2', 'max:50', new LargoNombreCertificado($this->nombre)],
            'nombre' => ['required', 'regex:/^[\pL\s\-\.]+$/u', 'min:2', 'max:50'],
            'dni' => ['required', 'digits_between:6,10', 'numeric'],
            'mail' => ['required', 'email'],
            'telefono' => ['required', 'regex:/^\d+$/', 'min:6', 'max:20'],
        ];

        $tieneDestinatarios = $this->evento->destinatarios->isNotEmpty();

        if ($tieneDestinatarios) {
            $reglas['destinatario_id'] = [
                'required',
                Rule::in($this->evento->destinatarios->pluck('destinatario_id')->toArray()),
            ];
        }

        if ($this->evento->arancel && $this->montoDestinatario > 0) {
            $reglas['comprobante'] = 'required|file|mimes:pdf,jpg,png|max:30720';
        }

        foreach ($this->requisitosActivos as $requisito) {
            $reglas["documentos.{$requisito['requisito_id']}"] = 'required|file|mimes:pdf|max:30720';
        }

        return $reglas;
    }

    public function submit()
    {
        $this->validate($this->reglas());

        // Normalizar nombre y apellido para que cada palabra inicie con mayúscula
        $this->nombre = NormalizadorIdentidad::titulo($this->nombre);
        $this->apellido = NormalizadorIdentidad::titulo($this->apellido);

        if ($this->similar === null) {
            $this->detectarSimilar();
        }

        if ($this->similar && $this->decision_similar === null) {
            $this->dispatch('oops', message: 'Encontramos un participante con datos muy similares. Confirmá si es la misma persona para continuar.');

            return;
        }

        DB::beginTransaction();
        try {
            if (! $this->planilla_inscripcion) {
                DB::rollBack();
                $this->dispatch('oops', message: 'Error: No hay una planilla de inscripción asociada a este evento.');

                return;
            }

            $participante = null;

            if ($this->similar && $this->decision_similar === 'usar') {
                // Misma persona: se usa el registro existente y se actualizan solo datos no identitarios.
                $participante = Participante::find($this->similar['participante_id']);

                if (! $participante) {
                    DB::rollBack();
                    $this->dispatch('oops', message: 'El participante similar ya no está disponible. Volvé a intentar.');

                    return;
                }

                $datosActualizados = [];
                if ($participante->nombre !== $this->nombre) {
                    $datosActualizados['nombre'] = $this->nombre;
                }
                if ($participante->apellido !== $this->apellido) {
                    $datosActualizados['apellido'] = $this->apellido;
                }
                if ($participante->telefono !== $this->telefono) {
                    $datosActualizados['telefono'] = $this->telefono;
                }
                if (! empty($datosActualizados)) {
                    $participante->update($datosActualizados);
                }

                DuplicadoRevision::create([
                    'participante_id' => $participante->participante_id,
                    'candidato_id' => $participante->participante_id,
                    'origen' => 'publico',
                    'decision' => 'misma_persona',
                ]);
            } else {
                $participante = Participante::where('dni', $this->dni)->first();

                if (! $participante) {
                    // Verificar si el email ya existe en otro participante
                    $mailExistente = Participante::where('mail', $this->mail)->exists();

                    if ($mailExistente) {
                        DB::rollBack();
                        $this->dispatch('oops', message: 'El correo electrónico ingresado ya está registrado para otro participante.');

                        return;
                    }

                    $participante = Participante::create([
                        'nombre' => $this->nombre,
                        'apellido' => $this->apellido,
                        'dni' => $this->dni,
                        'mail' => $this->mail,
                        'telefono' => $this->telefono,
                    ]);

                    if ($this->similar && $this->decision_similar === 'nuevo') {
                        DuplicadoRevision::create([
                            'participante_id' => $participante->participante_id,
                            'candidato_id' => $this->similar['participante_id'],
                            'origen' => 'publico',
                            'decision' => 'otra_persona',
                        ]);
                    }
                } else {
                    $datosActualizados = [];

                    if ($participante->nombre !== $this->nombre) {
                        $datosActualizados['nombre'] = $this->nombre;
                    }

                    if ($participante->apellido !== $this->apellido) {
                        $datosActualizados['apellido'] = $this->apellido;
                    }

                    if ($participante->mail !== $this->mail) {
                        // Verificar si ese nuevo mail ya lo usa otro participante
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

                    if (! empty($datosActualizados)) {
                        $participante->update($datosActualizados);
                    }
                }
            }

            // Verificar si ya está registrado en la planilla de inscripción
            $yaInscripto = InscripcionParticipante::where('planilla_id', $this->planilla_id)
                ->where('participante_id', $participante->participante_id)
                ->first();

            if ($yaInscripto) {
                DB::rollBack();
                $this->dispatch('oops', message: 'Este participante ya está inscrito en esta planilla.');

                return;
            }

            // Registrar la inscripción con el UUID correcto
            $comprobantePath = null;
            if ($this->evento->arancel && $this->montoDestinatario > 0 && $this->comprobante) {
                $comprobantePath = $this->comprobante->store("comprobantes/{$this->evento_id}", 'private');
            }

            $tieneDestinatarios = $this->evento->destinatarios->isNotEmpty();

            $inscripcion = InscripcionParticipante::create([
                'planilla_id' => $this->planilla_id,
                'participante_id' => $participante->participante_id,
                'rol_id' => $this->rol_participante_id,
                'destinatario_id' => $tieneDestinatarios ? $this->destinatario_id : null,
                'monto' => $tieneDestinatarios ? $this->montoDestinatario : null,
                'comprobante_pago' => $comprobantePath,
                'metodo_pago' => $this->evento->getPrimaryMetodoPago(),
                'fecha_inscripcion' => now(),
                'asistencia' => false,
            ]);

            // Guardar documentos presentados
            foreach ($this->requisitosActivos as $requisito) {
                $file = $this->documentos[$requisito['requisito_id']] ?? null;

                if ($file) {
                    $path = $file->store("documentos/{$this->evento_id}/{$inscripcion->inscripcion_participante_id}", 'private');

                    DocumentoPresentado::create([
                        'inscripcion_participante_id' => $inscripcion->inscripcion_participante_id,
                        'requisito_id' => $requisito['requisito_id'],
                        'path' => $path,
                    ]);
                }
            }

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
            Mail::to($this->mail)->send(new ConfirmacionInscripcion($this->nombre, $this->apellido, $this->evento, $this->asunto));
            $this->dispatch('alert', message: '¡Inscripción completada con éxito!');

            $this->reset(['nombre', 'apellido', 'dni', 'mail', 'telefono', 'indicadoresMultiples', 'indicadoresUnicos', 'destinatario_id', 'comprobante', 'montoDestinatario', 'requisitosActivos', 'documentos', 'similar', 'decision_similar']);
            $this->verificarInscripcionActiva(); // <-- Refresca el estado del formulario

            // return redirect()->route('inscripcion.publica', ['planilla' => $this->planillaId]);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Hubo un error al procesar los datos: '.$e->getMessage());

            return;
        }
    }

    public function render()
    {
        return view('livewire.registro-evento-publico')->layout('layouts.guest', [
            'title' => $this->evento->nombre,
        ]);
    }
}
