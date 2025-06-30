<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\EventoParticipante;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;


class EventosFinalizados extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $evento_selected = null;
    public $open_detail = false;
    public $sort = 'nombre';
    public $direction = 'asc';
    public $search = ''; // Búsqueda por evento
    public $searchParticipante = ''; // Búsqueda por participante (DNI)
    public $participantes = [];

    public $open_emitir = false;


    public $background_image;
    public $background_image_asistencia;
    public $background_image_aprobacion;


    protected $paginationTheme = 'tailwind';

    protected $rules = [
        'background_image' => 'required|image|mimes:jpeg,png|max:2048',
    ];

    protected function rules()
    {
        return $this->evento_selected && $this->evento_selected->por_aprobacion
            ? [
                'background_image_asistencia' => 'required|image|mimes:jpeg,png|max:10240',
                'background_image_aprobacion' => 'required|image|mimes:jpeg,png|max:10240',
            ]
            : [
                'background_image' => 'required|image|mimes:jpeg,png|max:10240',
            ];
    }


    public function emitir($evento)
    {
        $this->evento_selected = Evento::find($evento['evento_id']);
        $this->open_emitir = true;
    }

    public function emitirCertificados()
    {
        $this->validate();

        $year = now()->year;
        $tipoEvento = $this->evento_selected->tipoEvento->nombre;
        $nombreEvento = $this->evento_selected->nombre;
        $folderPath = "certificados/{$year}/{$tipoEvento}/{$nombreEvento}";

        $participantes = $this->evento_selected->participantes;

        //$backgroundPath = $this->background_image->store('certificados');
        //$backgroundPath = $this->background_image ? $this->background_image->store('images', 'public') : null;


        // Si el evento requiere aprobación, subimos dos plantillas
        if ($this->evento_selected->por_aprobacion) {
            $bgAsistenciaPath = $this->background_image_asistencia->store('images', 'public');
            $bgAprobacionPath = $this->background_image_aprobacion->store('images', 'public');

            foreach ($participantes as $participante) {
                $background = $participante->pivot->aprobado ? $bgAprobacionPath : $bgAsistenciaPath;
                $filename = "{$folderPath}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";

                $pdf = Pdf::loadView('certificado', [
                    'nombre' => $participante->nombre,
                    'apellido' => $participante->apellido,
                    'dni' => $participante->dni,
                    'qr' => 'data:image/svg+xml;base64,' . base64_encode($participante->pivot->qrcode),
                    'background' => $background
                ])->setPaper('a4', 'landscape');

                Storage::put($filename, $pdf->output());

                EventoParticipante::where('evento_id', $this->evento_selected->evento_id)
                    ->where('participante_id', $participante->participante_id)
                    ->update(['certificado_path' => $filename]);
            }
        } else {
            // Evento tradicional (solo un background)
            $backgroundPath = $this->background_image->store('images', 'public');

            foreach ($participantes as $participante) {
                $filename = "{$folderPath}/{$participante->apellido}_{$participante->nombre} ({$participante->dni}).pdf";

                $pdf = Pdf::loadView('certificado', [
                    'nombre' => $participante->nombre,
                    'apellido' => $participante->apellido,
                    'dni' => $participante->dni,
                    'qr' => 'data:image/svg+xml;base64,' . base64_encode($participante->pivot->qrcode),
                    'background' => $backgroundPath
                ])->setPaper('a4', 'landscape');

                Storage::put($filename, $pdf->output());

                EventoParticipante::where('evento_id', $this->evento_selected->evento_id)
                    ->where('participante_id', $participante->participante_id)
                    ->update(['certificado_path' => $filename]);
            }
        }
        $this->evento_selected->update([
            'certificado_path' => $folderPath
        ]);

        $this->reset([
            'open_emitir',
            'background_image',
            'background_image_asistencia',
            'background_image_aprobacion',
            'evento_selected',
        ]);
        session()->flash('message', 'Certificados generados correctamente.');
    }


    public function abrirCarpeta($path)
    {
        if (!Storage::exists($path)) {
            session()->flash('error', 'La carpeta no existe.');
            return;
        }

        $zipFile = "{$path}.zip";
        $zipPath = storage_path("app/private/{$zipFile}");

        // Crear ZIP si no existe aún
        if (!Storage::exists("private/{$zipFile}")) {
            $files = Storage::files($path);
            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
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

    public function updatingSearchParticipante()
    {
        // Si el usuario ingresa un DNI, reseteamos la búsqueda de eventos
        $this->search = '';
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->searchParticipante = '';
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $eventosFinalizados = Evento::with(['gestores', 'participantes'])
            ->where('estado', 'finalizado')
            ->when($user->hasRole('Gestor'), function ($query) use ($user) {
                $query->whereHas('gestores', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            })
            ->when($this->search, function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->when($this->searchParticipante, function ($query) {
                $query->whereHas('participantes', function ($q) {
                    $q->where('dni', 'like', '%' . $this->searchParticipante . '%');
                });
            })
            ->orderBy($this->sort, $this->direction)
            ->paginate(10);

        // Chequeo de certificados (fuera del query)
        foreach ($eventosFinalizados as $evento) {
            $evento->certificados_disponibles = $evento->certificado_path && Storage::exists($evento->certificado_path);
        }

        return view('livewire.eventos-finalizados', [
            'eventosFinalizados' => $eventosFinalizados,
        ]);
    }


    public function order($sort)
    {
        if ($this->sort == $sort) { //si estoy en la misma columna me pregunto por la direccion de ordenamiento
            if ($this->direction == 'asc') {
                $this->direction == 'desc';
            } else {
                $this->direction == 'asc';
            }
        } else { //si es una columna nueva, ordeno de forma ascendente
            $this->sort = $sort;
            $this->direction = 'asc';
        }
    }

    //----------------------------------------------------------------------------
    //------ Metodo llamado al precionar el boton QR para ver los participantes --
    //----------------------------------------------------------------------------
    public function detail($evento)
    {
        $this->resetValidation();
        $this->evento_selected = Evento::find($evento['evento_id']);
        //        $this->participantes = $this->evento_selected->participantes()->withPivot('qrcode')->get();
        $this->participantes = $this->evento_selected->participantes()
            ->withPivot('qrcode')
            ->get()
            ->map(function ($participante) {
                $participante->qrcode_base64 = 'data:image/svg+xml;base64,' . base64_encode($participante->pivot->qrcode);
                return $participante;
            });

        $this->open_detail = true;
    }
}
