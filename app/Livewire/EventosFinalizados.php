<?php

namespace App\Livewire;

use App\Mail\CertificadoEventoMail;
use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\User;
use App\Services\GenerarCertificadoEvento;
use App\Support\CertificadoPdfAssets;
use App\Support\Texto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventosFinalizados extends Component
{
    use WithFileUploads;
    use WithPagination;

    public const MODOS = ['a_certificar', 'finalizados'];

    /**
     * 'a_certificar': eventos finalizados sin certificados emitidos.
     * 'finalizados': eventos con certificados ya emitidos.
     */
    public string $modo = 'finalizados';

    public $evento_selected = null;

    public $open_detail = false;

    public $sort = 'nombre';

    public $direction = 'asc';

    public $search = ''; // Búsqueda por evento

    public $searchParticipante = ''; // Búsqueda por participante (DNI)

    public $searchTipoEvento = '';

    public $searchResponsable = '';

    public $searchCategoria = '';

    public $participantes = [];

    public $tiposEvento = [];

    public $categorias = [];

    public $open_emitir = false;

    public $open_enviar_mail = false;

    public $participantes_mail = [];

    public $selected_participantes = [];

    public $hasDisertantes = false;

    public $hasColaboradores = false;

    // Resumen de plantillas del contexto (tipo => [etiqueta, existe, nombre]).
    public $plantillas_contexto = [];

    public $tipos_faltantes = [];

    // Asignación de contexto/categoría desde la instancia de certificación.
    public $categoria_asignada_id = null;

    public $contexto_asignado_id = null;

    public $contextos_asignables = [];

    // Gestión de eventos en la etapa "a certificar"
    public $open_modal_revisor = false;

    public $busqueda_usuario = '';

    public $usuarios_filtrados = [];

    public $usuario_seleccionado_id = null;

    protected $listeners = [
        'devolverAEnCurso',
        'aprobarInstantaneamente',
        'quitarAprobacion',
    ];

    protected $paginationTheme = 'tailwind';

    public function mount(string $modo = 'finalizados')
    {
        $this->modo = in_array($modo, self::MODOS, true) ? $modo : 'finalizados';
        $this->tiposEvento = TipoEvento::orderBy('nombre')->get();
        $this->categorias = CategoriaEvento::disponiblesParaEventos()->orderBy('nombre')->get();
    }

    /**
     * Abre el modal de emisión y resuelve las plantillas del contexto.
     */
    public function emitir($evento)
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede emitir certificados.');

        $this->evento_selected = Evento::with('contexto.firmantes')->find($evento['evento_id']);

        $roles = Rol::whereIn('nombre', ['Disertante', 'Colaborador'])
            ->pluck('rol_id', 'nombre');

        $rolDisertanteId = $roles['Disertante'] ?? null;
        $rolColaboradorId = $roles['Colaborador'] ?? null;

        $this->hasDisertantes = $rolDisertanteId
            ? $this->evento_selected->participantes()->wherePivot('rol_id', $rolDisertanteId)->exists()
            : false;

        $this->hasColaboradores = $rolColaboradorId
            ? $this->evento_selected->participantes()->wherePivot('rol_id', $rolColaboradorId)->exists()
            : false;

        $this->plantillas_contexto = $this->resolverPlantillasDelContexto();
        $this->tipos_faltantes = collect($this->plantillas_contexto)
            ->filter(fn ($info) => ! $info['existe'])
            ->pluck('etiqueta')
            ->values()
            ->all();

        $this->categoria_asignada_id = $this->evento_selected->categoria_id;
        $this->contexto_asignado_id = $this->evento_selected->contexto_id;
        $this->cargarContextosAsignables();

        $this->resetValidation();

        $this->open_emitir = true;
    }

    /**
     * Contextos activos de la categoría elegida en el formulario de asignación.
     */
    private function cargarContextosAsignables(): void
    {
        $this->contextos_asignables = $this->categoria_asignada_id
            ? Contexto::where('categoria_id', $this->categoria_asignada_id)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get()
            : collect();
    }

    public function updatedCategoriaAsignadaId(): void
    {
        $this->contexto_asignado_id = null;
        $this->cargarContextosAsignables();
    }

    /**
     * Asigna categoría y contexto a un evento listo para certificar, sin cambiar
     * su estado ni tocar el QR o las aprobaciones de los participantes.
     */
    public function asignarContexto(): void
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede asignar el contexto.');

        if (! $this->evento_selected) {
            return;
        }

        if (! is_null($this->evento_selected->certificado_path)) {
            $this->dispatch('oops', message: 'El evento ya tiene certificados emitidos; no se puede cambiar el contexto.');

            return;
        }

        $this->validate([
            'categoria_asignada_id' => 'required|exists:categoria_evento,categoria_id',
            'contexto_asignado_id' => 'required|exists:contexto,contexto_id',
        ], [
            'categoria_asignada_id.required' => 'Seleccioná una categoría.',
            'contexto_asignado_id.required' => 'Seleccioná un contexto.',
        ]);

        $contexto = Contexto::findOrFail($this->contexto_asignado_id);

        if ((int) $contexto->categoria_id !== (int) $this->categoria_asignada_id) {
            $this->addError('contexto_asignado_id', 'El contexto seleccionado no pertenece a la categoría.');

            return;
        }

        // categoria_id/contexto_id son fillable; el estado no se toca.
        $this->evento_selected->update([
            'categoria_id' => $this->categoria_asignada_id,
            'contexto_id' => $this->contexto_asignado_id,
        ]);

        $this->evento_selected = Evento::with('contexto.firmantes')->find($this->evento_selected->evento_id);

        $this->plantillas_contexto = $this->resolverPlantillasDelContexto();
        $this->tipos_faltantes = collect($this->plantillas_contexto)
            ->filter(fn ($info) => ! $info['existe'])
            ->pluck('etiqueta')
            ->values()
            ->all();

        $this->resetValidation();
        $this->dispatch('alert', message: 'Contexto asignado al evento correctamente.');
    }

    /**
     * Resuelve, para cada tipo de reconocimiento requerido por el evento,
     * la plantilla existente en su contexto.
     *
     * @return array<string, array{etiqueta:string, existe:bool, nombre:?string}>
     */
    private function resolverPlantillasDelContexto(): array
    {
        if (! $this->evento_selected) {
            return [];
        }

        $requeridos = [
            'asistencia' => $this->evento_selected->por_aprobacion
                ? 'Asistencia (no aprobados)'
                : 'Asistentes',
        ];

        if ($this->evento_selected->por_aprobacion) {
            $requeridos['aprobacion'] = 'Aprobación';
        }

        if ($this->hasDisertantes) {
            $requeridos['disertante'] = 'Disertante';
        }

        if ($this->hasColaboradores) {
            $requeridos['colaborador'] = 'Colaborador';
        }

        $servicio = app(GenerarCertificadoEvento::class);

        $resultado = [];
        foreach ($requeridos as $slug => $etiqueta) {
            $plantilla = $servicio->buscarPlantilla($this->evento_selected, $slug);
            $resultado[$slug] = [
                'etiqueta' => $etiqueta,
                'existe' => (bool) $plantilla,
                'nombre' => $plantilla?->nombre,
            ];
        }

        return $resultado;
    }

    private function assertPdfEnvironmentReady(): void
    {
        $fontPath = CertificadoPdfAssets::fontPath();
        $fontCacheDirectory = CertificadoPdfAssets::fontCacheDirectory();

        if (! is_readable($fontPath)) {
            Log::error('No se encontró la fuente base para certificados.', [
                'font_path' => $fontPath,
                'evento_id' => $this->evento_selected?->evento_id,
            ]);

            throw new \RuntimeException('No se encontró la fuente requerida para generar el certificado.');
        }

        if (! CertificadoPdfAssets::fontCacheIsWritable()) {
            Log::error('El directorio de cache de fuentes de Dompdf no es escribible.', [
                'font_cache_directory' => $fontCacheDirectory,
                'evento_id' => $this->evento_selected?->evento_id,
            ]);

            throw new \RuntimeException('El servidor no tiene permisos para generar las fuentes del certificado. Revise storage/fonts.');
        }
    }

    private function extendExecutionTime(int $participantsCount): void
    {
        $seconds = max(180, $participantsCount * 12);

        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds);
        }
    }

    private function noCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
    }

    private function purgeExistingEmission(string $folderPath): void
    {
        $privateDisk = Storage::disk('private');

        $privateDisk->delete($folderPath.'.zip');
        $privateDisk->deleteDirectory($folderPath);
    }

    /**
     * Emite los certificados usando las plantillas del contexto del evento.
     */
    public function emitirCertificados()
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede emitir certificados.');

        if (! $this->evento_selected) {
            return;
        }

        if (! $this->evento_selected->contexto_id) {
            $this->dispatch('oops', message: 'El evento no tiene un contexto asignado. Asignalo en este mismo panel antes de emitir.');

            return;
        }

        if (! empty($this->tipos_faltantes)) {
            $this->dispatch('oops', message: 'Faltan plantillas en el contexto para: '.implode(', ', $this->tipos_faltantes).'.');

            return;
        }

        $year = now()->year;
        $tipoEvento = $this->evento_selected->tipoEvento->nombre;
        $nombreEvento = $this->evento_selected->nombre;
        $folderPath = "certificados/{$year}/{$tipoEvento}/{$nombreEvento}";

        $participantes = $this->evento_selected->participantes;

        try {
            $this->assertPdfEnvironmentReady();
            $this->extendExecutionTime($participantes->count());

            $this->purgeExistingEmission($folderPath);

            $servicio = app(GenerarCertificadoEvento::class);

            foreach ($participantes as $participante) {
                $relacion = EventoParticipante::where('evento_id', $this->evento_selected->evento_id)
                    ->where('participante_id', $participante->participante_id)
                    ->first();

                if (! $relacion) {
                    continue;
                }

                $plantilla = $servicio->plantillaPara($this->evento_selected, $relacion);

                if (! $plantilla) {
                    Log::warning('No se pudo resolver la plantilla del certificado en el contexto.', [
                        'participante_id' => $participante->participante_id,
                        'evento_id' => $this->evento_selected->evento_id,
                    ]);

                    continue;
                }

                $servicio->generar($relacion, $plantilla);
            }
        } catch (\Throwable $e) {
            Log::error('Error al generar certificados desde eventos finalizados.', [
                'evento_id' => $this->evento_selected?->evento_id,
                'message' => $e->getMessage(),
            ]);

            $this->dispatch('oops', message: $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Error al generar certificados. Revise la plantilla, la fuente Roboto y los permisos de storage/fonts.');

            return;
        }

        $this->evento_selected->update([
            'certificado_path' => $folderPath,
        ]);

        $this->reset([
            'open_emitir',
            'evento_selected',
            'hasDisertantes',
            'hasColaboradores',
            'plantillas_contexto',
            'tipos_faltantes',
            'categoria_asignada_id',
            'contexto_asignado_id',
            'contextos_asignables',
        ]);

        session()->flash('message', $this->modo === 'a_certificar'
            ? 'Certificados generados correctamente. El evento ahora aparece en la pestaña "Eventos Finalizados".'
            : 'Certificados reemitidos correctamente. Los archivos anteriores fueron reemplazados.');
    }

    /**
     * Descarga el archivo PDF de disposición respaldatoria del evento.
     */
    public function descargarDisposicion()
    {
        if (! $this->evento_selected || ! $this->evento_selected->planillaInscripcion) {
            $this->dispatch('oops', message: 'No se encontró la planilla de inscripción.');

            return;
        }

        $disposicion = $this->evento_selected->planillaInscripcion->disposicion;

        if (! $disposicion || ! Storage::disk('private')->exists($disposicion)) {
            $this->dispatch('oops', message: 'No se encontró el archivo de disposición respaldatoria.');

            return;
        }

        return response()->download(Storage::disk('private')->path($disposicion));
    }

    /**
     * Descarga el listado de inscriptos del evento en PDF, con los detalles
     * del evento (responsable, disertantes y colaboradores) en la parte superior.
     */
    public function descargarInscriptos($evento)
    {
        $eventoModel = Evento::with(['responsable', 'tipoEvento', 'categoria', 'planillaInscripcion'])
            ->find($evento['evento_id']);

        if (! $eventoModel || ! $eventoModel->planillaInscripcion) {
            $this->dispatch('oops', message: 'No se encontró la planilla de inscripción del evento.');

            return;
        }

        $planilla = $eventoModel->planillaInscripcion;

        $inscriptos = $planilla->inscripcionesParticipantes()
            ->with('participante')
            ->get();

        $disertantesYColaboradores = $planilla->inscripcionesDisertantesYColaboradores()
            ->with(['participante', 'rol'])
            ->get();

        $pdf = Pdf::setOption(['isPhpEnabled' => true])
            ->loadView('pdf.listado-inscriptos', [
                'evento' => $eventoModel,
                'inscriptos' => $inscriptos,
                'mostrarDetalles' => true,
                'disertantesYColaboradores' => $disertantesYColaboradores,
            ])
            ->setPaper('A4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'inscriptos_'.Str::slug($eventoModel->nombre).'.pdf');
    }

    public function abrirCarpeta($path)
    {
        $privateDisk = Storage::disk('private');
        $directoryPath = $privateDisk->path($path);

        if (! is_dir($directoryPath)) {
            session()->flash('error', 'La carpeta no existe.');

            return;
        }

        $zipFile = "{$path}.zip";
        $zipPath = $privateDisk->path($zipFile);

        $privateDisk->delete($zipFile);

        $files = $privateDisk->files($path);
        $zip = new \ZipArchive;

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            session()->flash('error', 'No se pudo generar el archivo ZIP de certificados.');

            return;
        }

        foreach ($files as $file) {
            $fullFilePath = $privateDisk->path($file);

            if (file_exists($fullFilePath)) {
                $zip->addFile($fullFilePath, basename($file));
            } else {
                Log::error("Archivo no encontrado: {$fullFilePath}");
            }
        }

        $zip->close();

        return response()->download($zipPath, basename($zipPath), $this->noCacheHeaders());
    }

    // ----------------------------------------------------------------------------
    // ------------  MÉTODOS PARA ENVÍO DE CORREOS --------------------------------
    // ----------------------------------------------------------------------------

    public function abrirModalMail($evento)
    {
        $this->evento_selected = Evento::find($evento['evento_id']);
        $this->participantes_mail = $this->evento_selected->participantes()->get();
        $this->selected_participantes = []; // Resetea la selección
        $this->open_enviar_mail = true;
    }

    public function enviarMailsTodos()
    {
        $participantes = $this->evento_selected->participantes;
        foreach ($participantes as $participante) {
            $this->_enviarMailParticipante($participante);
        }

        session()->flash('message', 'Correos enviados a todos los participantes.');
        $this->reset(['open_enviar_mail', 'evento_selected', 'participantes_mail', 'selected_participantes']);
    }

    public function enviarMailsSeleccionados()
    {
        if (empty($this->selected_participantes)) {
            session()->flash('error', 'No ha seleccionado ningún participante.');

            return;
        }

        $participantes = Participante::whereIn('participante_id', $this->selected_participantes)->get();

        foreach ($participantes as $participante) {
            $this->_enviarMailParticipante($participante);
        }

        session()->flash('message', 'Correos enviados a los participantes seleccionados.');
        $this->reset(['open_enviar_mail', 'evento_selected', 'participantes_mail', 'selected_participantes']);
    }

    /**
     * Lógica centralizada para enviar un correo a un participante.
     */
    private function _enviarMailParticipante($participante)
    {
        $relacion = EventoParticipante::where('evento_id', $this->evento_selected->evento_id)
            ->where('participante_id', $participante->participante_id)
            ->first();

        if ($relacion && $relacion->certificado_path && Storage::disk('private')->exists($relacion->certificado_path)) {
            try {
                Mail::to($participante->mail)->send(new CertificadoEventoMail($this->evento_selected, $participante, $relacion->certificado_path));
            } catch (\Exception $e) {
                $this->dispatch('oops', message: $e->getMessage());

                return;
            }
        } else {
            $this->dispatch('oops', "No se encontró certificado para {$participante->nombre} {$participante->apellido} en el evento {$this->evento_selected->nombre}");
        }
    }

    // ----------------------------------------------------------------------------
    // ----------------------------------------------------------------------------

    public function updatingSearchParticipante()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchTipoEvento()
    {
        $this->resetPage();
    }

    public function updatingSearchResponsable()
    {
        $this->resetPage();
    }

    public function updatingSearchCategoria()
    {
        $this->resetPage();
    }

    public function getFiltrosActivosProperty(): int
    {
        return collect([$this->search, $this->searchResponsable, $this->searchParticipante])
            ->filter(fn ($valor) => trim((string) $valor) !== '')
            ->count()
            + (int) ($this->searchTipoEvento !== '')
            + (int) ($this->searchCategoria !== '');
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'searchResponsable', 'searchParticipante', 'searchTipoEvento', 'searchCategoria']);
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $eventosFinalizados = Evento::query()
            ->select('evento.*')
            ->with(['gestores', 'tipoEvento', 'categoria'])
            ->leftJoin('tipo_evento as tipo_evento_orden', 'evento.tipo_evento_id', '=', 'tipo_evento_orden.tipo_evento_id')
            ->leftJoin('categoria_evento as categoria_orden', 'evento.categoria_id', '=', 'categoria_orden.categoria_id')
            ->where('evento.estado', 'finalizado')
            // Separación entre pestañas: un evento con certificado_path ya tiene emisión.
            ->when($this->modo === 'a_certificar', fn ($query) => $query->whereNull('evento.certificado_path'))
            ->when($this->modo === 'finalizados', fn ($query) => $query->whereNotNull('evento.certificado_path'))
            ->when($user->hasRole('Gestor'), function ($query) use ($user) {
                $query->whereHas('gestores', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when(mb_strlen(trim($this->search)) >= 3, function ($query) {
                Texto::aplicarFiltroLike($query, 'evento.nombre', $this->search);
            })
            ->when(mb_strlen(trim($this->searchResponsable)) > 0, function ($query) {
                $query->whereHas('responsable', function ($q) {
                    Texto::aplicarFiltroLike($q, ['responsable.nombre', 'responsable.apellido'], $this->searchResponsable);
                });
            })
            ->when($this->searchParticipante, function ($query) {
                $query->whereHas('participantes', function ($q) {
                    $q->where('dni', 'like', '%'.$this->searchParticipante.'%');
                });
            })
            ->when($this->searchTipoEvento !== '', function ($query) {
                $query->where('evento.tipo_evento_id', $this->searchTipoEvento);
            })
            ->when($this->searchCategoria !== '', function ($query) {
                $query->where('evento.categoria_id', $this->searchCategoria);
            })
            ->orderBy($this->resolveSortColumn(), $this->direction)
            ->paginate(20);

        // La emisión se marca en DB (evento.certificado_path); ya no se consulta el disco por fila.
        foreach ($eventosFinalizados as $evento) {
            $evento->certificados_disponibles = (bool) $evento->certificado_path;
        }

        return view('livewire.eventos-finalizados', [
            'eventosFinalizados' => $eventosFinalizados,
        ]);
    }

    private function resolveSortColumn(): string
    {
        return match ($this->sort) {
            'tipo_evento' => 'tipo_evento_orden.nombre',
            'fecha_inicio' => 'evento.fecha_inicio',
            'categoria' => 'categoria_orden.nombre',
            default => 'evento.nombre',
        };
    }

    public function order($sort)
    {
        if ($this->sort == $sort) { // si estoy en la misma columna me pregunto por la direccion de ordenamiento
            if ($this->direction == 'asc') {
                $this->direction = 'desc';
            } else {
                $this->direction = 'asc';
            }
        } else { // si es una columna nueva, ordeno de forma ascendente
            $this->sort = $sort;
            $this->direction = 'asc';
        }
    }

    // ----------------------------------------------------------------------------
    // ------ Metodo llamado al precionar el boton QR para ver los participantes --
    // ----------------------------------------------------------------------------
    public function detail($evento)
    {
        $this->resetValidation();
        $this->evento_selected = Evento::find($evento['evento_id']);
        //        $this->participantes = $this->evento_selected->participantes()->withPivot('qrcode')->get();
        $this->participantes = $this->evento_selected->participantes()
            ->withPivot('qrcode')
            ->get()
            ->map(function ($participante) {
                $participante->qrcode_base64 = 'data:image/svg+xml;base64,'.base64_encode($participante->pivot->qrcode);

                return $participante;
            });

        $this->open_detail = true;
    }

    // ----------------------------------------------------------------------------
    // ------ Gestión de eventos en la etapa "a certificar" -----------------------
    // ----------------------------------------------------------------------------

    private function redirectToEventos(string $tab)
    {
        $this->redirectRoute('eventos', ['tab' => $tab]);
    }

    /**
     * Devuelve un evento "a certificar" al listado de Eventos en Curso.
     *
     * Limpia los QR y las aprobaciones parciales generadas al finalizar, para
     * permitir registrar nuevas sesiones/asistencias y volver a finalizar.
     * Conserva el revisor asignado para poder editarlo.
     */
    public function devolverAEnCurso($evento_id)
    {
        DB::beginTransaction();

        try {
            $evento = Evento::findOrFail($evento_id);

            if (! is_null($evento->certificado_path)) {
                throw new \Exception('El evento ya tiene certificados emitidos; no es posible devolverlo a "En Curso".');
            }

            // Limpiar los registros creados al finalizar (QR + aprobaciones parciales).
            EventoParticipante::where('evento_id', $evento_id)->delete();

            // 'estado' no es fillable: se actualiza con el query builder.
            Evento::where('evento_id', $evento_id)->update([
                'estado' => 'En Curso',
                'revisado' => false,
            ]);

            DB::commit();

            $this->dispatch('alert', message: 'El evento volvió a "Eventos en Curso". Se limpiaron los QR y las aprobaciones parciales.');

            $this->redirectToEventos('en_curso');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo devolver el evento: '.$e->getMessage());
        }
    }

    /**
     * Abre el modal para asignar o editar el revisor de un evento.
     */
    public function modalRevisor($evento_id)
    {
        $evento = Evento::findOrFail($evento_id);

        if (! $evento->por_aprobacion) {
            $this->dispatch('oops', message: 'Este evento no requiere aprobación.');

            return;
        }

        $this->evento_selected = $evento;
        $this->open_modal_revisor = true;
        $this->busqueda_usuario = '';
        $this->usuarios_filtrados = $evento->revisor ? collect([$evento->revisor]) : [];
        $this->usuario_seleccionado_id = $evento->revisor_id;
    }

    public function updatedBusquedaUsuario()
    {
        $this->usuarios_filtrados = User::role('Revisor')
            ->where(function ($query) {
                $query->where('name', 'like', '%'.$this->busqueda_usuario.'%')
                    ->orWhere('email', 'like', '%'.$this->busqueda_usuario.'%');
            })
            ->limit(10)
            ->get();
    }

    public function seleccionarRevisor($userId)
    {
        $this->usuario_seleccionado_id = $userId;
    }

    public function guardarRevisor()
    {
        if (! $this->evento_selected || ! $this->usuario_seleccionado_id) {
            $this->reset(['open_modal_revisor', 'evento_selected', 'busqueda_usuario', 'usuarios_filtrados', 'usuario_seleccionado_id']);

            return;
        }

        // Validación server-side: el usuario elegido debe tener el rol Revisor.
        if (! User::role('Revisor')->whereKey($this->usuario_seleccionado_id)->exists()) {
            $this->dispatch('oops', message: 'El usuario seleccionado no tiene el rol Revisor.');

            return;
        }

        $cambioRevisor = (int) $this->evento_selected->revisor_id !== (int) $this->usuario_seleccionado_id;

        $this->evento_selected->update([
            'revisor_id' => $this->usuario_seleccionado_id,
            // Si se reasigna a otro revisor, debe volver a emitir su dictamen.
            'revisado' => $cambioRevisor ? false : $this->evento_selected->revisado,
        ]);

        $this->dispatch('alert', message: 'Revisor actualizado correctamente.');

        $this->reset(['open_modal_revisor', 'evento_selected', 'busqueda_usuario', 'usuarios_filtrados', 'usuario_seleccionado_id']);
    }

    /**
     * Aprueba a todos los participantes con asistencia registrada y cierra la revisión.
     */
    public function aprobarInstantaneamente($evento_id)
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede aprobar instantáneamente.');

        DB::beginTransaction();

        try {
            $evento = Evento::findOrFail($evento_id);

            if (! is_null($evento->certificado_path)) {
                throw new \Exception('El evento ya tiene certificados emitidos.');
            }

            if (! $evento->por_aprobacion) {
                throw new \Exception('Este evento no requiere aprobación.');
            }

            $rolParticipanteId = Rol::where('nombre', 'Participante')->value('rol_id');

            EventoParticipante::where('evento_id', $evento_id)
                ->where('rol_id', $rolParticipanteId)
                ->update(['aprobado' => true]);

            $evento->update(['revisado' => true]);

            DB::commit();

            $this->dispatch('alert', message: 'Aprobación instantánea aplicada: todos los asistentes quedaron aprobados.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo aplicar la aprobación instantánea: '.$e->getMessage());
        }
    }

    /**
     * Quita la aprobación del evento, descartando revisor y revisiones parciales.
     */
    public function quitarAprobacion($evento_id)
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede quitar la aprobación.');

        DB::beginTransaction();

        try {
            $evento = Evento::findOrFail($evento_id);

            if (! is_null($evento->certificado_path)) {
                throw new \Exception('El evento ya tiene certificados emitidos.');
            }

            if (! $evento->por_aprobacion) {
                throw new \Exception('Este evento no requiere aprobación.');
            }

            $rolParticipanteId = Rol::where('nombre', 'Participante')->value('rol_id');

            EventoParticipante::where('evento_id', $evento_id)
                ->where('rol_id', $rolParticipanteId)
                ->update(['aprobado' => null]);

            $evento->update([
                'por_aprobacion' => false,
                'revisor_id' => null,
                'revisado' => false,
            ]);

            DB::commit();

            $this->dispatch('alert', message: 'Se quitó la aprobación. El evento ahora certifica por asistencia.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'No se pudo quitar la aprobación: '.$e->getMessage());
        }
    }
}
