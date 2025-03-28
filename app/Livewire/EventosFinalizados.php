<?php

namespace App\Livewire;

use App\Models\Evento;
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

    protected $paginationTheme = 'tailwind';

    protected $rules = [
        'background_image' => 'required|image|mimes:jpeg,png|max:2048',
    ];

    public function emitir($evento)
    {
        $this->evento_selected = Evento::find($evento['evento_id']);
        $this->open_emitir = true;
    }

    public function emitirCertificados()
    {
        $this->validate();

        //$backgroundPath = $this->background_image->store('certificados');
        $backgroundPath = $this->background_image ? $this->background_image->store('images', 'public') : null;

        $participantes = $this->evento_selected->participantes;

        $year = now()->year;
        $tipoEvento = $this->evento_selected->tipoEvento->nombre;
        $nombreEvento = $this->evento_selected->nombre;

        foreach ($participantes as $participante) {

            $pdf = Pdf::loadView('certificado', [
                'nombre' => $participante->nombre,
                'apellido' => $participante->apellido,
                'dni' => $participante->dni,
                'qr' => 'data:image/svg+xml;base64,' . base64_encode($participante->pivot->qrcode),
                'background' => $backgroundPath
            ])->setPaper('a4', 'landscape');

            $folderPath = "certificados/{$year}/{$tipoEvento}/{$nombreEvento}";
            $filename = "{$folderPath}/{$participante->apellido}_{$participante->nombre} ($participante->dni).pdf";

            Storage::put($filename, $pdf->output());
        }
        $this->evento_selected->update([
            'certificado_path' => $folderPath
        ]);

        $this->reset(['open_emitir', 'background_image', 'evento_selected']);
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
        $eventosFinalizados = Evento::where('estado', 'finalizado')
            ->when(!empty($this->search), function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->when(!empty($this->searchParticipante), function ($query) {
                $query->whereHas('participantes', function ($q) {
                    $q->where('dni', 'like', '%' . $this->searchParticipante . '%');
                });
            })
            ->orderBy($this->sort, $this->direction)
            ->paginate(10);

        // $eventosFinalizados = Evento::where('estado', 'finalizado')
        //     ->when($this->search != '', function ($query) {
        //         $query->where('nombre', 'like', '%' . $this->search . '%');
        //     })
        //     ->orderBy($this->sort, $this->direction)
        //     ->paginate(10);

        return view('livewire.eventos-finalizados', [
            'eventosFinalizados' => $eventosFinalizados
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
