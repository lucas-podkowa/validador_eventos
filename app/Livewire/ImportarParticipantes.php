<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use App\Models\Participante;
use App\Models\InscripcionParticipante;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmacionInscripcion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportarParticipantes extends Component
{
    use WithFileUploads;

    public $evento;
    public $evento_id;
    public $planilla;
    public $archivo;
    public $resultados = [];
    public $total = 0;
    public $exitosos = 0;
    public $errores = 0;

    protected $rules = [
        'archivo' => 'required|mimes:xlsx,xls,csv|max:10240', // hasta 10MB
    ];

    public function mount($evento_id)
    {
        $this->evento = Evento::findOrFail($evento_id);
        $this->planilla = PlanillaInscripcion::where('evento_id', $evento_id)->firstOrFail();
        $this->evento_id = $evento_id;
    }

    public function importar()
    {
        $this->validate();

        try {
            $ext = $this->archivo->getClientOriginalExtension();

            // Guarda explícitamente en el disco "private"
            $path = $this->archivo->storeAs('temp', uniqid() . '.' . $ext, 'private');

            // Ruta absoluta al archivo real
            $fullPath = storage_path('app/private/' . $path);

            if ($ext === 'csv') {
                $data = $this->leerCSV($fullPath);
            } else {
                $data = $this->leerXLSX($fullPath);
            }

            $this->procesarImportacion($data);

            // Eliminar el archivo temporal
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        } catch (\Exception $e) {
            $this->dispatch('oops', message: 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    private function leerCSV($path): Collection
    {
        $rows = collect();

        if (($handle = fopen($path, 'r')) !== false) {
            $header = null;

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (!$header) {
                    $header = array_map('strtolower', $row);
                    continue;
                }
                $rows->push(array_combine($header, $row));
            }

            fclose($handle);
        }

        return $rows;
    }

    private function leerXLSX($path): Collection
    {
        try {
            // FastExcel lee automáticamente la primera hoja
            // y usa la primera fila como encabezado
            $data = (new FastExcel)->import($path);

            // Normalizar los nombres de las columnas y limpiar datos
            return collect($data)->map(function ($row) {
                // Convertir las claves a minúsculas para uniformidad
                $row = array_change_key_case((array) $row, CASE_LOWER);

                return [
                    'dni' => trim($row['dni'] ?? ''),
                    'apellido' => trim($row['apellido'] ?? ''),
                    'nombre' => trim($row['nombre'] ?? ''),
                    'mail' => trim($row['mail'] ?? $row['email'] ?? ''),
                    'telefono' => trim($row['telefono'] ?? $row['teléfono'] ?? ''),
                ];
            });
        } catch (\Exception $e) {
            throw new \Exception('Error al leer el archivo XLSX: ' . $e->getMessage());
        }
    }

    private function procesarImportacion(Collection $data)
    {
        $this->resultados = [];
        $this->total = $data->count();
        $this->exitosos = 0;
        $this->errores = 0;

        DB::beginTransaction();

        try {
            foreach ($data as $fila) {
                try {
                    // Validar datos requeridos
                    if (!$fila['dni'] || !$fila['nombre'] || !$fila['apellido'] || !$fila['mail']) {
                        $this->errores++;
                        $this->resultados[] = [
                            'dni' => $fila['dni'] ?: 'N/A',
                            'estado' => 'Error: Datos incompletos'
                        ];
                        continue;
                    }

                    // Buscar o crear participante
                    $participante = Participante::firstOrCreate(
                        ['dni' => $fila['dni']],
                        [
                            'nombre' => mb_convert_case(mb_strtolower(trim($fila['nombre'])), MB_CASE_TITLE, "UTF-8"),
                            'apellido' => mb_convert_case(mb_strtolower(trim($fila['apellido'])), MB_CASE_TITLE, "UTF-8"),
                            'mail' => $fila['mail'],
                            'telefono' => $fila['telefono'] ?? ''
                        ]
                    );

                    // Verificar si ya está inscripto
                    $yaInscripto = InscripcionParticipante::where('planilla_id', $this->planilla->planilla_inscripcion_id)
                        ->where('participante_id', $participante->participante_id)
                        ->exists();

                    if ($yaInscripto) {
                        $this->errores++;
                        $this->resultados[] = [
                            'dni' => $fila['dni'],
                            'estado' => 'Ya inscripto'
                        ];
                        continue;
                    }

                    // Crear inscripción
                    InscripcionParticipante::create([
                        'planilla_id' => $this->planilla->planilla_inscripcion_id,
                        'participante_id' => $participante->participante_id,
                        'fecha_inscripcion' => now(),
                        'asistencia' => false,
                        'rol_id' => 1,
                    ]);

                    $this->exitosos++;
                    $this->resultados[] = [
                        'dni' => $fila['dni'],
                        'estado' => 'Inscripto correctamente'
                    ];
                } catch (\Throwable $e) {
                    $this->errores++;
                    $this->resultados[] = [
                        'dni' => $fila['dni'] ?? 'Desconocido',
                        'estado' => 'Error: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();
            $this->dispatch('alert', message: "Importación finalizada: {$this->exitosos}/{$this->total} inscriptos correctamente.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('oops', message: 'Error general: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.importar-participantes');
    }
}
