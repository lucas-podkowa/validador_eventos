<?php

namespace App\Livewire;

use App\Actions\BuscarParticipanteSimilar;
use App\Models\DuplicadoRevision;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\Rol;
use App\Rules\LargoNombreCertificado;
use App\Services\GenerarCertificadoEvento;
use App\Support\CertificadoPdfAssets;
use App\Support\NormalizadorIdentidad;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EmisorCertificados extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $modal_abierto = false;

    public $evento_id;

    public $rol_id;

    public $nombre;

    public $apellido;

    public $dni;

    public $telefono;

    public $mail;

    public ?array $participanteExistente = null;

    public ?array $similar = null;

    public ?string $decision_similar = null;

    public $reemision = false;

    public $reemision_rol = null;

    public $background_image;

    // Plantillas de categoría
    public $plantilla_id = null;

    public $plantillas_disponibles = [];

    public $certificado_tipo = null;

    public $plantillas_por_tipo = [];

    public $eventoParticipantes = [];

    public $eventos = [];

    public $roles = [];

    protected $rules = [
        'evento_id' => 'required|exists:evento,evento_id',
        'nombre' => 'required|string|max:100',
        'apellido' => 'required|string|max:100',
        'dni' => 'required|string|max:15',
        'telefono' => 'required|string|min:6|max:15',
        'mail' => 'required|email|max:100',
        'rol_id' => 'required|exists:rol,rol_id',
    ];

    public function mount()
    {
        $this->eventos = Evento::where('estado', 'Finalizado')->get();
        $this->roles = Rol::whereIn('nombre', ['Participante', 'Disertante', 'Colaborador'])->get();
        $this->cargarEventoParticipantes();
    }

    private function cargarEventoParticipantes(): void
    {
        $this->eventoParticipantes = EventoParticipante::with(['participante', 'evento'])
            ->where('emision_directa', true)
            ->whereHas('evento', function ($query) {
                $query->where('estado', 'Finalizado');
            })
            ->get();
    }

    public function abrirModal()
    {
        $this->reset(['evento_id', 'nombre', 'apellido', 'dni', 'telefono', 'mail', 'participanteExistente', 'rol_id', 'background_image', 'plantilla_id', 'plantillas_disponibles', 'certificado_tipo', 'plantillas_por_tipo', 'reemision', 'reemision_rol', 'similar', 'decision_similar']);
        $this->modal_abierto = true;
    }

    public function updatedEventoId(): void
    {
        $this->plantilla_id = null;
        $this->plantillas_disponibles = [];
        $this->certificado_tipo = null;
        $this->plantillas_por_tipo = [];

        if ($this->evento_id) {
            $evento = Evento::with(['categoria.plantillas', 'contexto.plantillas'])->find($this->evento_id);
            if ($evento) {
                $plantillas = ($evento->contexto_id && $evento->contexto)
                    ? $evento->contexto->plantillas
                    : ($evento->categoria?->plantillas ?? collect());

                if ($plantillas && $plantillas->count() > 0) {
                    $grouped = $plantillas->groupBy(function ($p) {
                        return $p->tipo ?: 'asistencia';
                    });
                    // Convertir a arrays para Livewire
                    $this->plantillas_por_tipo = $grouped->map(fn ($items) => $items->map->toArray())->toArray();
                }

                // Determinar tipo y plantilla por defecto según rol y si el evento es por aprobación
                $this->determineDefaultTipoAndPlantilla($evento);
            }
        }

        $this->refreshEstadoReemision();
    }

    public function updatedRolId(): void
    {
        // Recalcular selección si ya tenemos un evento seleccionado
        if ($this->evento_id) {
            $evento = Evento::with('categoria.plantillas')->find($this->evento_id);
            if ($evento) {
                $this->determineDefaultTipoAndPlantilla($evento);
            }
        }
    }

    private function determineDefaultTipoAndPlantilla(Evento $evento): void
    {
        $this->certificado_tipo = null;
        $this->plantilla_id = null;

        $availableTypes = array_keys($this->plantillas_por_tipo ?? []);

        // Obtener nombre del rol seleccionado
        $rolNombre = null;
        if ($this->rol_id) {
            $rol = $this->roles->firstWhere('rol_id', $this->rol_id) ?? null;
            $rolNombre = $rol?->nombre ?? null;
        }

        $preferred = null;
        if ($rolNombre === 'Disertante') {
            $preferred = 'disertante';
        } elseif ($rolNombre === 'Colaborador') {
            $preferred = 'colaborador';
        } else {
            // Participante y otros
            if ($evento->esPorAprobacion() && in_array('aprobacion', $availableTypes)) {
                $preferred = 'aprobacion';
            } else {
                $preferred = 'asistencia';
            }
        }

        // Si el tipo preferido está disponible, usarlo; si no, usar el primer disponible
        if ($preferred && in_array($preferred, $availableTypes)) {
            $this->certificado_tipo = $preferred;
        } elseif (! empty($availableTypes)) {
            $this->certificado_tipo = $availableTypes[0];
        }

        // Preseleccionar plantilla por_defecto si existe
        if ($this->certificado_tipo && isset($this->plantillas_por_tipo[$this->certificado_tipo])) {
            $group = $this->plantillas_por_tipo[$this->certificado_tipo];
            $porDefecto = null;
            foreach ($group as $g) {
                if (! empty($g['por_defecto'])) {
                    $porDefecto = $g;
                    break;
                }
            }
            if ($porDefecto) {
                $this->plantilla_id = $porDefecto['plantilla_id'];
            } elseif (! empty($group)) {
                $this->plantilla_id = $group[0]['plantilla_id'];
            }
        }
    }

    public function buscarParticipante()
    {
        if ($this->dni) {
            $this->participanteExistente = Participante::where('dni', $this->dni)->first()?->toArray();

            if ($this->participanteExistente) {
                $this->nombre = $this->participanteExistente['nombre'];
                $this->apellido = $this->participanteExistente['apellido'];
                $this->telefono = $this->participanteExistente['telefono'];
                $this->mail = $this->participanteExistente['mail'];
            } else {
                $this->reset('nombre', 'apellido', 'telefono', 'mail');
            }
        }

        $this->refreshEstadoReemision();
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

    private function refreshEstadoReemision(): void
    {
        $this->reemision = false;
        $this->reemision_rol = null;

        if (! $this->evento_id || ! $this->participanteExistente) {
            return;
        }

        $existing = EventoParticipante::where('evento_id', $this->evento_id)
            ->where('participante_id', $this->participanteExistente['participante_id'])
            ->first();

        if ($existing) {
            $this->reemision = true;
            $this->reemision_rol = $existing->rol_id;
        }
    }

    public function guardar()
    {
        // Validación condicional: plantilla del tipo seleccionado O imagen manual
        $extraRules = [];

        $extraRules['certificado_tipo'] = 'required|string';

        $plantillasForTipo = $this->plantillas_por_tipo[$this->certificado_tipo] ?? [];
        if (empty($plantillasForTipo)) {
            $extraRules['background_image'] = 'required|image|mimes:jpeg,png|max:30720';
        } else {
            $extraRules['plantilla_id'] = 'required|exists:plantilla_certificado,plantilla_id';
        }

        $this->validate(array_merge($this->rules, $extraRules, [
            'apellido' => array_merge((array) $this->rules['apellido'], [new LargoNombreCertificado($this->nombre)]),
        ]));

        if ($this->similar === null) {
            $this->detectarSimilar();
        }

        if ($this->similar && $this->decision_similar === null) {
            $this->dispatch('oops', message: 'Encontramos un participante con datos muy similares. Confirmá si es la misma persona para continuar.');

            return;
        }

        $backgroundPath = null;
        if (! empty($plantillasForTipo) && $this->plantilla_id) {
            $plantilla = PlantillaCertificado::find($this->plantilla_id);
            // Seguridad: comprobar que la plantilla pertenece a la categoría y al tipo seleccionado
            $evento = Evento::with('categoria')->find($this->evento_id);
            if (! $plantilla || ! $evento || $plantilla->categoria_id !== $evento->categoria_id || ($plantilla->tipo ?? 'asistencia') !== $this->certificado_tipo) {
                $this->dispatch('oops', message: 'La plantilla seleccionada no corresponde a la categoría/tipo del evento.');

                return;
            }
            $backgroundPath = $plantilla ? $plantilla->imagen_path : null;
        } elseif ($this->background_image) {
            $backgroundPath = $this->background_image->store('images', 'public');
        }

        DB::beginTransaction();
        try {
            // Normalizar campos
            $this->nombre = NormalizadorIdentidad::titulo($this->nombre);
            $this->apellido = NormalizadorIdentidad::titulo($this->apellido);

            $participante = null;

            if ($this->similar && $this->decision_similar === 'usar') {
                $participante = Participante::find($this->similar['participante_id']);

                if (! $participante) {
                    DB::rollBack();
                    $this->dispatch('oops', message: 'El participante similar ya no está disponible. Volvé a intentar.');

                    return;
                }

                $participante->update([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'telefono' => $this->telefono,
                ]);

                DuplicadoRevision::create([
                    'participante_id' => $participante->participante_id,
                    'candidato_id' => $participante->participante_id,
                    'origen' => 'emision',
                    'decision' => 'misma_persona',
                ]);
            } else {
                $participante = Participante::where('dni', $this->dni)->first();

                if (! $participante) {
                    $participante = Participante::create([
                        'nombre' => $this->nombre,
                        'apellido' => $this->apellido,
                        'dni' => $this->dni,
                        'telefono' => $this->telefono,
                        'mail' => $this->mail,
                    ]);

                    if ($this->similar && $this->decision_similar === 'nuevo') {
                        DuplicadoRevision::create([
                            'participante_id' => $participante->participante_id,
                            'candidato_id' => $this->similar['participante_id'],
                            'origen' => 'emision',
                            'decision' => 'otra_persona',
                        ]);
                    }
                }
            }

            // Evitar duplicados y, si ya existe, tratar como re-emisión
            $yaExiste = EventoParticipante::where('evento_id', $this->evento_id)
                ->where('participante_id', $participante->participante_id)
                ->first();

            if ($yaExiste) {
                if ((int) $yaExiste->rol_id !== (int) $this->rol_id) {
                    DB::rollBack();
                    $this->dispatch('oops', message: 'Este participante ya está registrado en el evento con otro rol.');

                    return;
                }

                // Re-emisión: reusa el vínculo y el QR existentes, solo regenera su certificado
                $participante->update([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'dni' => $this->dni,
                    'telefono' => $this->telefono,
                    'mail' => $this->mail,
                ]);

                $evento = Evento::with('tipoEvento')->find($this->evento_id);
                $this->generarCertificadoIndividual($participante, $evento, $backgroundPath);

                DB::commit();
                $this->cargarEventoParticipantes();
                $this->dispatch('alert', message: 'Certificado reemitido correctamente.');
                $this->modal_abierto = false;

                return;
            }

            // Generar URL y QR
            $url = route('validar.participante', [
                'evento_id' => $this->evento_id,
                'participante_id' => $participante->participante_id,
            ]);

            $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
            $writer = new Writer($renderer);
            $qrcode = $writer->writeString($url);

            EventoParticipante::create([
                'evento_id' => $this->evento_id,
                'participante_id' => $participante->participante_id,
                'rol_id' => $this->rol_id,
                'url' => $url,
                'qrcode' => $qrcode,
                'emision_directa' => true,
            ]);

            // Generar certificado
            $evento = Evento::with('tipoEvento')->find($this->evento_id);
            $this->generarCertificadoIndividual($participante, $evento, $backgroundPath);

            DB::commit();
            $this->cargarEventoParticipantes();
            $this->dispatch('alert', message: 'Participante registrado correctamente.');
            $this->modal_abierto = false;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error: '.$e->getMessage());
        }
    }

    private function generarCertificadoIndividual(Participante $participante, Evento $evento, ?string $backgroundPath)
    {
        $relacion = EventoParticipante::where('evento_id', $evento->evento_id)
            ->where('participante_id', $participante->participante_id)
            ->first();

        if (! $relacion) {
            throw new \Exception('No se encontró el vínculo entre evento y participante.');
        }

        $plantilla = $this->plantilla_id ? PlantillaCertificado::find($this->plantilla_id) : null;

        $override = null;
        if (! $plantilla && $backgroundPath) {
            $override = CertificadoPdfAssets::prepareBackgroundForPdf($backgroundPath);
        }

        $filename = app(GenerarCertificadoEvento::class)->generar($relacion, $plantilla, $override);

        if ($filename) {
            $evento->update(['certificado_path' => dirname($filename)]);
        }
    }

    public function render()
    {
        return view('livewire.emisor-certificados');
    }
}
