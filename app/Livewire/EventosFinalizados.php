<?php

namespace App\Livewire;

use App\Mail\CertificadoEventoMail;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\Rol;
use App\Models\TipoEvento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class EventosFinalizados extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $evento_selected = null;

    public $open_detail = false;

    public $sort = 'nombre';

    public $direction = 'asc';

    public $search = ''; // Búsqueda por evento

    public $searchParticipante = ''; // Búsqueda por participante (DNI)

    public $searchTipoEvento = '';

    public $participantes = [];

    public $tiposEvento = [];

    public $open_emitir = false;

    public $open_enviar_mail = false;

    public $participantes_mail = [];

    public $selected_participantes = [];

    public $background_image; // <- Usada como plantilla genérica (Asistente en evento simple)

    public $background_image_disertante;

    public $background_image_colaborador;

    public $background_image_asistencia;

    public $background_image_aprobacion;

    public $hasDisertantes = false;

    public $hasColaboradores = false;

    public $plantillas_por_tipo = [];

    public $usar_plantilla_categoria = [];

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $this->tiposEvento = TipoEvento::orderBy('nombre')->get();
    }

    protected function rules()
    {
        $rules = [];
        $maxSize = '30720';

        if ($this->evento_selected && $this->evento_selected->por_aprobacion) {
            if (! ($this->usar_plantilla_categoria['asistencia'] ?? false)) {
                $rules['background_image_asistencia'] = "required|image|mimes:jpeg,png|max:{$maxSize}";
            }
            if (! ($this->usar_plantilla_categoria['aprobacion'] ?? false)) {
                $rules['background_image_aprobacion'] = "required|image|mimes:jpeg,png|max:{$maxSize}";
            }
        } else {
            if (! ($this->usar_plantilla_categoria['asistencia'] ?? false)) {
                $rules['background_image'] = "required|image|mimes:jpeg,png|max:{$maxSize}";
            }
        }

        if ($this->evento_selected && $this->hasDisertantes) {
            if (! ($this->usar_plantilla_categoria['disertante'] ?? false)) {
                $rules['background_image_disertante'] = "required|image|mimes:jpeg,png|max:{$maxSize}";
            }
        }

        if ($this->evento_selected && $this->hasColaboradores) {
            if (! ($this->usar_plantilla_categoria['colaborador'] ?? false)) {
                $rules['background_image_colaborador'] = "required|image|mimes:jpeg,png|max:{$maxSize}";
            }
        }

        return $rules;
    }

    /**
     * Abre el modal de emisión y verifica si existen disertantes y colaboradores.
     */
    public function emitir($evento)
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede emitir certificados.');

        $this->evento_selected = Evento::with('categoria.plantillas')->find($evento['evento_id']);

        $this->plantillas_por_tipo = [];
        $this->usar_plantilla_categoria = [];

        if ($this->evento_selected && $this->evento_selected->categoria) {
            $plantillas = $this->evento_selected->categoria->plantillas;
            if ($plantillas->count() > 0) {
                $grouped = $plantillas->groupBy(function ($p) {
                    return $p->tipo ?: 'asistencia';
                });
                $this->plantillas_por_tipo = $grouped->map(fn ($items) => $items->toArray())->toArray();

                foreach (array_keys($this->plantillas_por_tipo) as $tipo) {
                    $this->usar_plantilla_categoria[$tipo] = true;
                }
            }
        }

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

        $this->reset([
            'background_image',
            'background_image_disertante',
            'background_image_colaborador',
            'background_image_asistencia',
            'background_image_aprobacion',
        ]);

        $this->open_emitir = true;
    }

    public function usarPlantillaManual($tipo): void
    {
        $this->usar_plantilla_categoria[$tipo] = false;
    }

    public function usarPlantillaCategoria($tipo): void
    {
        $this->usar_plantilla_categoria[$tipo] = true;
    }

    private function getPlantillaPath($tipo): ?string
    {
        $available = $this->plantillas_por_tipo[$tipo] ?? [];
        if (empty($available)) {
            return null;
        }
        $default = collect($available)->firstWhere('por_defecto', true);
        if (! $default) {
            $default = $available[0];
        }

        return $default['imagen_path'];
    }

    /**
     * Emite los certificados, solo subiendo las plantillas que son necesarias.
     */
    public function emitirCertificados()
    {
        abort_if(! auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede emitir certificados.');

        $rules = $this->rules();
        if (! empty($rules)) {
            $this->validate($rules);
        }

        // 1. OBTENER IDS DE ROLES
        $roles = Rol::whereIn('nombre', ['Participante', 'Disertante', 'Colaborador'])
            ->pluck('rol_id', 'nombre');

        $rolAsistenteId = $roles['Participante'] ?? null;
        $rolDisertanteId = $roles['Disertante'] ?? null;
        $rolColaboradorId = $roles['Colaborador'] ?? null;

        if (! $rolAsistenteId || ! $rolDisertanteId || ! $rolColaboradorId) {
            $this->dispatch('oops', message: 'Faltan IDs de roles esenciales (Participante, Disertante, Colaborador) en la base de datos.');

            return;
        }

        // 2. CONFIGURACIÓN DE RUTAS Y PARTICIPANTES
        $year = now()->year;
        $tipoEvento = $this->evento_selected->tipoEvento->nombre;
        $nombreEvento = $this->evento_selected->nombre;
        $folderPath = "certificados/{$year}/{$tipoEvento}/{$nombreEvento}";

        $participantes = $this->evento_selected->participantes;

        $paths = [];
        $isPorAprobacion = $this->evento_selected->por_aprobacion;

        try {
            if ($isPorAprobacion) {
                if ($this->usar_plantilla_categoria['asistencia'] ?? false) {
                    $paths['asistencia'] = $this->getPlantillaPath('asistencia');
                } else {
                    $paths['asistencia'] = $this->background_image_asistencia->store('images', 'public');
                }
                if ($this->usar_plantilla_categoria['aprobacion'] ?? false) {
                    $paths['aprobacion'] = $this->getPlantillaPath('aprobacion');
                } else {
                    $paths['aprobacion'] = $this->background_image_aprobacion->store('images', 'public');
                }

                if ($this->hasDisertantes) {
                    if ($this->usar_plantilla_categoria['disertante'] ?? false) {
                        $paths['disertante'] = $this->getPlantillaPath('disertante');
                    } elseif ($this->background_image_disertante) {
                        $paths['disertante'] = $this->background_image_disertante->store('images', 'public');
                    }
                }

                if ($this->hasColaboradores) {
                    if ($this->usar_plantilla_categoria['colaborador'] ?? false) {
                        $paths['colaborador'] = $this->getPlantillaPath('colaborador');
                    } elseif ($this->background_image_colaborador) {
                        $paths['colaborador'] = $this->background_image_colaborador->store('images', 'public');
                    }
                }
            } else {
                if ($this->usar_plantilla_categoria['asistencia'] ?? false) {
                    $paths['asistente_generico'] = $this->getPlantillaPath('asistencia');
                } else {
                    $paths['asistente_generico'] = $this->background_image->store('images', 'public');
                }

                if ($this->hasDisertantes) {
                    if ($this->usar_plantilla_categoria['disertante'] ?? false) {
                        $paths['disertante'] = $this->getPlantillaPath('disertante');
                    } elseif ($this->background_image_disertante) {
                        $paths['disertante'] = $this->background_image_disertante->store('images', 'public');
                    }
                }

                if ($this->hasColaboradores) {
                    if ($this->usar_plantilla_categoria['colaborador'] ?? false) {
                        $paths['colaborador'] = $this->getPlantillaPath('colaborador');
                    } elseif ($this->background_image_colaborador) {
                        $paths['colaborador'] = $this->background_image_colaborador->store('images', 'public');
                    }
                }
            }
        } catch (\Exception $e) {
            $this->dispatch('oops', message: 'Error al subir una o más plantillas: '.$e->getMessage());

            return;
        }

        // 3. LÓGICA DE GENERACIÓN DE CERTIFICADOS
        foreach ($participantes as $participante) {
            $rolParticipanteId = $participante->pivot->rol_id;
            $background = null;

            // COMPRUEBA que el rol existe Y que su plantilla fue subida
            if ($rolParticipanteId == $rolDisertanteId && isset($paths['disertante'])) {
                $background = $paths['disertante'];
            } elseif ($rolParticipanteId == $rolColaboradorId && isset($paths['colaborador'])) {
                $background = $paths['colaborador'];
            } elseif ($rolParticipanteId == $rolAsistenteId) {
                if ($isPorAprobacion) {
                    // Si es por aprobación, usamos aprobacion o asistencia (ambos paths son obligatorios en este flujo)
                    $background = $participante->pivot->aprobado ? $paths['aprobacion'] : $paths['asistencia'];
                } else {
                    // Si es genérico, usamos el path genérico (obligatorio en este flujo)
                    $background = $paths['asistente_generico'];
                }
            }

            if (is_null($background)) {
                Log::warning("No se encontró plantilla o la plantilla no fue requerida/subida para el rol ID {$rolParticipanteId} del participante {$participante->participante_id}");

                continue; // Saltar si no se pudo determinar la plantilla (ej. rol no reconocido o plantilla no requerida)
            }

            $filename = "{$folderPath}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";

            $pdf = Pdf::loadView('certificado', [
                'nombre' => $participante->nombre,
                'apellido' => $participante->apellido,
                'dni' => $participante->dni,
                'qr' => 'data:image/svg+xml;base64,'.base64_encode($participante->pivot->qrcode),
                'background' => $background,
            ])->setPaper('a4', 'landscape');

            Storage::put($filename, $pdf->output());

            EventoParticipante::where('evento_id', $this->evento_selected->evento_id)
                ->where('participante_id', $participante->participante_id)
                ->update(['certificado_path' => $filename]);
        }

        $this->evento_selected->update([
            'certificado_path' => $folderPath,
        ]);

        $this->reset([
            'open_emitir',
            'background_image',
            'background_image_disertante',
            'background_image_colaborador',
            'background_image_asistencia',
            'background_image_aprobacion',
            'evento_selected',
            'hasDisertantes',
            'hasColaboradores',
            'plantillas_por_tipo',
            'usar_plantilla_categoria',
        ]);
        session()->flash('message', 'Certificados generados correctamente.');
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

    public function abrirCarpeta($path)
    {
        if (! Storage::exists($path)) {
            session()->flash('error', 'La carpeta no existe.');

            return;
        }

        $zipFile = "{$path}.zip";
        $zipPath = storage_path("app/private/{$zipFile}");

        // Crear ZIP si no existe aún
        if (! Storage::exists("private/{$zipFile}")) {
            $files = Storage::files($path);
            $zip = new \ZipArchive;

            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                foreach ($files as $file) {
                    $fullFilePath = storage_path("app/private/{$file}");
                    if (file_exists($fullFilePath)) {
                        $relativeName = basename($file);
                        $zip->addFile($fullFilePath, $relativeName);
                    } else {
                        Log::error("Archivo no encontrado: {$fullFilePath}");
                    }
                }
                $zip->close();
            }
        }

        return response()->download($zipPath);
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

    public function render()
    {
        $user = auth()->user();

        $eventosFinalizados = Evento::query()
            ->select('evento.*')
            ->with(['gestores', 'participantes', 'tipoEvento', 'categoria'])
            ->leftJoin('tipo_evento as tipo_evento_orden', 'evento.tipo_evento_id', '=', 'tipo_evento_orden.tipo_evento_id')
            ->where('evento.estado', 'finalizado')
            ->when($user->hasRole('Gestor'), function ($query) use ($user) {
                $query->whereHas('gestores', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($this->search, function ($query) {
                $query->where('evento.nombre', 'like', '%'.$this->search.'%');
            })
            ->when($this->searchParticipante, function ($query) {
                $query->whereHas('participantes', function ($q) {
                    $q->where('dni', 'like', '%'.$this->searchParticipante.'%');
                });
            })
            ->when($this->searchTipoEvento !== '', function ($query) {
                $query->where('evento.tipo_evento_id', $this->searchTipoEvento);
            })
            ->orderBy($this->resolveSortColumn(), $this->direction)
            ->paginate(20);

        // Chequeo de certificados (fuera del query)
        foreach ($eventosFinalizados as $evento) {
            $evento->certificados_disponibles = $evento->certificado_path && Storage::exists($evento->certificado_path);
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
}
