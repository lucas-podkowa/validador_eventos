<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\Localidad;
use App\Models\Pais;
use App\Models\Participante;
use App\Models\TipoEvento;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;


class CrearEvent extends Component
{
    use WithFileUploads;

    public bool $processing = false; // Estado de procesamiento

    public $tipo_evento = null;
    public $nombreEvento = null;
    public $fecha_cierre = null;
    public $lugarEvento = null;
    public $fileInput = null;
    public $estudiante = null;
    public $localidad = false;

    public $tiposEventos = [];

    // Reglas de validación
    protected $rules = [
        'tipo_evento'  => 'required|integer',
        'nombreEvento' => 'required|string|max:255',
        'fecha_cierre' => 'required|date',
        'lugarEvento'  => 'required|string|max:255',
        'fileInput'    => 'required|file' // Límite de 10MB
    ];

    public function mount()
    {
        $this->tiposEventos = TipoEvento::all();
    }

    // Función para procesar el formulario
    public function save()
    {
        // Iniciar el procesamiento
        $this->processing = true;

        // Validar los datos del formulario
        $this->validate();


        DB::beginTransaction();
        try {
            $datosEvento = [
                'nombre' => $this->nombreEvento,
                'lugar' => $this->lugarEvento,
                'fecha_cierre' => Carbon::parse($this->fecha_cierre),
                'cudap' => uniqid(), // Generar un código único
                'tipo_evento_id' => $this->tipo_evento
            ];

            $evento = Evento::firstOrCreate([
                'nombre' => $datosEvento['nombre'],
                'lugar' => $datosEvento['lugar'],
                'tipo_evento_id' => $datosEvento['tipo_evento_id'],
                'fecha_cierre' => $datosEvento['fecha_cierre']
            ], [
                // Si no se encuentra, entonces se utiliza cudap para crear el nuevo evento
                'cudap' => $datosEvento['cudap']
            ]);


            $this->procesarArchivo($evento);

            //Confirmar transacción
            DB::commit();


            //Resetear los campos después de guardar
            $this->reset([
                'tipo_evento',
                'nombreEvento',
                'fecha_cierre',
                'lugarEvento',
                'fileInput'
            ]);
        } catch (\Exception $e) {

            DB::rollBack();
            $this->dispatch('oops', message: 'Hubo un error al procesar los datos: ' . $e->getMessage());
            return;
        } finally {
            // Finalizar el procesamiento
            $this->processing = false;
        }
    }

    public function procesarArchivo($evento)
    {
        $extension = $this->fileInput->getClientOriginalExtension();
        $path = $this->fileInput->store('uploads');

        if (strtolower($extension) === 'csv') {
            $this->procesarCSV($evento, $path);
        } elseif (in_array(strtolower($extension), ['xlsx', 'xls'])) {
            $this->procesarXLSX($evento, $path);
        } else {
            $this->dispatch('oops', message: 'Formato de archivo no soportado. Solo se permiten archivos CSV o XLSX.');
        }

        Storage::delete($path);
    }

    // Función para procesar el archivo CSV
    private function procesarCSV($evento, $path)
    {
        try {
            if (($handle = fopen(storage_path('app/private/' . $path), 'r')) === false) {
                throw new Exception('No se pudo abrir el archivo.');
            } else {

                // Saltar la primera línea (cabeceras)
                //fgetcsv($handle, 1000, ',');
                $headers = fgetcsv($handle, 1000, ',');
                $headers = array_map('strtolower', $headers);

                $expectedHeaders = [
                    'ape_nom' => ['nombre', 'Nombre y Apellido', 'Nombre', 'Apellido y Nombre'],
                    'dni' => ['dni', 'documento', 'nro_documento'],
                    'mail' => ['mail', 'email', 'correo electronico'],
                    'localidad' => ['localidad', 'ciudad'],
                    'pais' => ['pais', 'nacionalidad'],
                ];
                $participantes_del_evento = [];


                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    $mappedData = [];

                    foreach ($expectedHeaders as $key => $aliases) {
                        $index = $this->getColumnIndex($headers, $aliases); // Obtener el índice correcto

                        // Si la columna existe en los datos, mapearla
                        if ($index !== null && isset($data[$index])) {
                            $mappedData[$key] = $data[$index];
                        } else {
                            $mappedData[$key] = null; // Si no existe, asignar valor nulo
                        }
                    }

                    // Validar si tiene los datos mínimos (nombre, apellido y dni son obligatorios)
                    if (!$mappedData['ape_nom'] || !$mappedData['dni'] || !$mappedData['mail']) {
                        continue; // Saltar si falta alguno de estos campos esenciales
                    }

                    try {

                        // Buscar o crear el país
                        $paisMinusculas = mb_strtolower($mappedData['pais']);
                        $pais = Pais::whereRaw('LOWER(nombre) = ?', [$paisMinusculas])->first();

                        if (!$pais) {
                            $pais = Pais::create(['nombre' => $paisMinusculas]);
                        }

                        // Buscar o crear la localidad
                        $localidadMin = mb_strtolower($mappedData['localidad']);
                        $localidad = Localidad::whereRaw('LOWER(nombre) = ? AND pais_id = ?', [$localidadMin, $pais->pais_id])->first();

                        if (!$localidad) {
                            $localidad = Localidad::create([
                                'nombre' => $localidadMin,
                                'pais_id' => $pais->pais_id,
                            ]);
                        }


                        $participante = Participante::where('dni', $mappedData['dni'])->first();

                        // Buscar o crear el participante

                        if (!$participante) {
                            $participante = Participante::create([
                                'dni' => $mappedData['dni'],
                                'ape_nom' => $mappedData['ape_nom'],
                                'mail' => $mappedData['mail'],
                                'localidad_id' => $localidad->localidad_id
                            ]);
                        }


                        // Generar la URL
                        $url = "http://localhost:8080/validate/{$evento->evento_id}/{$participante->participante_id}";

                        // Crear el QR en formato SVG usando BaconQrCode
                        $renderer = new ImageRenderer(
                            new RendererStyle(200),
                            new SvgImageBackEnd()
                        );
                        $writer = new Writer($renderer);

                        // Generar el código QR como SVG
                        $qrCodeSvg = $writer->writeString($url);

                        // Agregar datos del participante al arreglo
                        $participantes_del_evento[$participante->participante_id] = [
                            'url' => $url,
                            'qrcode' => $qrCodeSvg,
                        ];
                    } catch (Exception $e) {
                        $this->dispatch('oops', message: $e->getMessage());
                        continue; // Saltar fila corrupta
                    }
                }
                fclose($handle);

                // Asociar todos los participantes al evento en la tabla pivote
                foreach ($participantes_del_evento as $participante_id => $data) {
                    $evento->participantes()->attach($participante_id, $data);
                }

                $this->dispatch('success', message: 'Evento y participantes procesados exitosamente.');
            }
        } catch (Exception $e) {
            $this->dispatch('oops', message: 'Error abriendo archivo: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo abrir el archivo.'], 500);
        }
    }


    //----------------------------------------------------------------
    //----------------------------------------------------------------
    //----------------------------------------------------------------


    private function procesarXLSX($evento, $path)
    {

        try {
            $spreadsheet = IOFactory::load(storage_path('app/private/' . $path));
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $headers = array_map('strtolower', array_shift($rows)); // Convierte las cabeceras a minúsculas

            // nombres esperados y sus alias
            $expectedHeaders = [
                'ape_nom' => ['nombre', 'Nombre y Apellido', 'Nombre', 'Apellido y Nombre'],
                'dni' => ['dni', 'documento', 'nro_documento'],
                'mail' => ['mail', 'email', 'correo electronico', 'Correo Electrónico'],
                'localidad' => ['localidad', 'ciudad'],
                'pais' => ['pais', 'nacionalidad'],
            ];

            $participantes_del_evento = [];

            foreach ($rows as $data) {
                $mappedData = [];

                // Mapear las columnas dinámicas
                foreach ($expectedHeaders as $key => $aliases) {
                    $index = $this->getColumnIndex($headers, $aliases); // Obtener el índice correcto

                    // Si la columna existe en los datos, mapearla
                    if ($index !== null && isset($data[$index])) {
                        $mappedData[$key] = $data[$index];
                    } else {
                        $mappedData[$key] = null; // Si no existe, asignar valor nulo
                    }
                }

                // Validar si tiene los datos mínimos (nombre, apellido y dni son obligatorios)
                if (!$mappedData['ape_nom'] || !$mappedData['dni'] || !$mappedData['mail']) {
                    continue; // Saltar si falta alguno de estos campos esenciales
                }

                // Buscar o crear los registros relacionados (similar a tu lógica anterior)
                try {
                    $localidad = null;

                    if ($this->localidad) {

                        $localidadMin = mb_strtolower($mappedData['localidad']);
                        if ($localidadMin != '') {
                            //$localidad = Localidad::whereRaw('LOWER(nombre) = ? AND pais_id = ?', [$localidadMin, $pais->pais_id])->first();
                            $localidad = Localidad::whereRaw('LOWER(nombre) = ?', [$localidadMin])->first();

                            if (!$localidad) {
                                $localidad = Localidad::create([
                                    'nombre' => $localidadMin
                                    // 'pais_id' => $pais->pais_id,
                                ]);
                            }
                        }
                    }

                    /*
                    // fragmento si es que se utiliza el campo pais
                    
                    $paisMinusculas = mb_strtolower($mappedData['pais']);
                    $pais = Pais::whereRaw('LOWER(nombre) = ?', [$paisMinusculas])->first();

                    if (!$pais) {
                        $pais = Pais::create(['nombre' => $paisMinusculas]);
                    }

                    */

                    // Buscar o crear el participante
                    $participante = Participante::where('dni', $mappedData['dni'])->first();

                    if (!$participante) {
                        $participante = Participante::create([
                            'dni' => $mappedData['dni'],
                            'ape_nom' => $mappedData['ape_nom'],
                            'mail' => $mappedData['mail'],
                            'localidad_id' => $localidad ? $localidad->localidad_id : null
                        ]);
                    }


                    // Generar la URL y el QR
                    $url = "http://localhost:8080/validate/{$evento->evento_id}/{$participante->participante_id}";
                    $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());
                    $writer = new Writer($renderer);
                    $qrCodeSvg = $writer->writeString($url);

                    // Agregar datos del participante al arreglo
                    $participantes_del_evento[$participante->participante_id] = [
                        'url' => $url,
                        'qrcode' => $qrCodeSvg,
                    ];
                } catch (Exception $e) {
                    $this->dispatch('oops', message: 'Error procesando fila: ' . $e->getMessage());
                    continue; // Saltar fila corrupta
                }
            }

            // Asociar todos los participantes al evento en la tabla pivote
            foreach ($participantes_del_evento as $participante_id => $data) {
                $evento->participantes()->attach($participante_id, $data);
            }

            $this->dispatch('alert', message: 'Evento y participantes procesados exitosamente.');
        } catch (Exception $e) {
            $this->dispatch('oops', message: 'Error procesando archivo: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo procesar el archivo.'], 500);
        }
    }

    //----------------------------------------------------------------
    //----------------------------------------------------------------
    //----------------------------------------------------------------

    //  Obtener el índice de la columna basado en sus alias.
    private function getColumnIndex($headers, $aliases)
    {
        foreach ($aliases as $alias) {
            $index = array_search(strtolower($alias), $headers);
            if ($index !== false) {
                return $index;
            }
        }
        return null; // Retornar null si no se encuentra
    }


    public function render()
    {
        return view('livewire.crear-evento');
    }
}
