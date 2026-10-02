<?php

namespace App\Livewire\Admin;

use App\Models\Contexto;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\TipoEvento;
use App\Models\TipoReconocimiento;
use App\Support\CertificadoLayout;
use App\Support\CertificadoPdfAssets;
use App\Support\CertificadoVariables;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContextoPlantillas extends Component
{
    use WithFileUploads;

    public const CAMPOS = [
        'literal' => 'Texto literal (acepta tokens)',
        'apellido_nombres' => 'Apellido, Nombres',
        'dni' => 'DNI',
        'texto_cuerpo' => 'Texto del cuerpo',
        'fecha_rango' => 'Fecha (rango)',
        'qr' => 'QR',
        'firma_1_imagen' => 'Firma 1 — imagen',
        'firma_1_nombre' => 'Firma 1 — nombre',
        'firma_1_cargo' => 'Firma 1 — cargo',
        'firma_2_imagen' => 'Firma 2 — imagen',
        'firma_2_nombre' => 'Firma 2 — nombre',
        'firma_2_cargo' => 'Firma 2 — cargo',
        'firma_3_imagen' => 'Firma 3 — imagen',
        'firma_3_nombre' => 'Firma 3 — nombre',
        'firma_3_cargo' => 'Firma 3 — cargo',
    ];

    public const TOKENS = [
        '{apellido}', '{nombres}', '{apellido_nombres}', '{dni}',
        '{tipo_evento}', '{formula}', '{nombre_evento}',
        '{contexto}', '{contexto_nombre}', '{contexto_denominacion}', '{institucion}',
        '{resolucion}', '{lugar}', '{fecha_rango}',
        '{firmante_1_nombre}', '{firmante_1_cargo}',
        '{firmante_2_nombre}', '{firmante_2_cargo}',
        '{firmante_3_nombre}', '{firmante_3_cargo}',
    ];

    public int $contexto_id;

    public $plantillas = [];

    public $open_modal = false;

    public $editando_id = null;

    public $clonando_id = null;

    public $nombre = '';

    public ?int $tipo_reconocimiento_id = null;

    public $por_defecto = false;

    public $imagen = null;

    public $imagen_actual = null;

    public $texto = '';

    public $bloques = [];

    public $nuevo_campo = 'literal';

    public ?int $seleccionado = null;

    public string $qr_preview = '';

    /** Datos de ejemplo para la vista previa y el PDF de prueba. */
    public array $mock = [
        'apellido' => 'Almirón',
        'nombres' => 'Franco Abel',
        'dni' => '47424166',
        'tipo_evento' => 'Taller',
        'formula' => 'al',
        'nombre_evento' => 'LABORATORIO DE CÓNICAS: CONSTRUCCIÓN Y ANÁLISIS DE ELIPSE E HIPÉRBOLA',
    ];

    public function mount($contextoId): void
    {
        $this->contexto_id = (int) $contextoId;
        $this->qr_preview = $this->generarQrPreview();
        $this->cargarPlantillas();
    }

    private function cargarPlantillas(): void
    {
        $this->plantillas = PlantillaCertificado::where('contexto_id', $this->contexto_id)
            ->orderBy('tipo_reconocimiento_id')
            ->orderByDesc('por_defecto')
            ->get()
            ->toArray();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'clonando_id', 'nombre', 'imagen', 'imagen_actual', 'seleccionado']);
        $this->tipo_reconocimiento_id = $this->tipoReconocimientoPorDefecto();
        $this->por_defecto = false;
        $this->texto = 'ha asistido {formula} {nombre_evento}, realizado en la {contexto} {institucion}. Según Res. {resolucion}.';
        $this->bloques = $this->layoutPorDefecto();
        $this->nuevo_campo = 'literal';
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function abrirEditar(int $id): void
    {
        $plantilla = PlantillaCertificado::where('contexto_id', $this->contexto_id)->findOrFail($id);

        $this->editando_id = $plantilla->plantilla_id;
        $this->clonando_id = null;
        $this->nombre = $plantilla->nombre;
        $this->tipo_reconocimiento_id = $plantilla->tipo_reconocimiento_id;
        $this->por_defecto = (bool) $plantilla->por_defecto;
        $this->imagen = null;
        $this->imagen_actual = $plantilla->imagen_path;
        $this->texto = $plantilla->texto ?? '';
        $this->bloques = $plantilla->layout ?: $this->layoutPorDefecto();
        $this->nuevo_campo = 'literal';
        $this->seleccionado = null;
        $this->resetValidation();
        $this->open_modal = true;
    }

    /**
     * Abre el editor prellenado con una copia de la plantilla indicada.
     * Al guardar se crea una plantilla nueva (con su propia imagen).
     */
    public function clonar(int $id): void
    {
        $plantilla = PlantillaCertificado::where('contexto_id', $this->contexto_id)->findOrFail($id);

        $this->editando_id = null;
        $this->clonando_id = $plantilla->plantilla_id;
        $this->nombre = 'Copia de '.$plantilla->nombre;
        $this->tipo_reconocimiento_id = $plantilla->tipo_reconocimiento_id;
        $this->por_defecto = false;
        $this->imagen = null;
        $this->imagen_actual = $plantilla->imagen_path;
        $this->texto = $plantilla->texto ?? '';
        $this->bloques = $plantilla->layout ?: $this->layoutPorDefecto();
        $this->nuevo_campo = 'literal';
        $this->seleccionado = null;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function agregarBloque(): void
    {
        $this->bloques[] = [
            'campo' => $this->nuevo_campo,
            'contenido' => '',
            'x' => 35,
            'y' => 50,
            'w' => 30,
            'h' => CertificadoLayout::esImagen($this->nuevo_campo) ? 12 : 0,
            'size' => 16,
            'align' => 'center',
            'color' => '#0A1B3A',
            'bold' => false,
            'italic' => false,
        ];

        $this->seleccionado = count($this->bloques) - 1;
    }

    public function quitarBloque(int $index): void
    {
        if (! isset($this->bloques[$index])) {
            return;
        }

        unset($this->bloques[$index]);
        $this->bloques = array_values($this->bloques);

        if ($this->seleccionado === $index) {
            $this->seleccionado = null;
        } elseif ($this->seleccionado !== null && $this->seleccionado > $index) {
            $this->seleccionado--;
        }
    }

    public function seleccionar(int $index): void
    {
        $this->seleccionado = $index;
    }

    /**
     * Actualiza la posición/tamaño de un bloque tras un arrastre o redimensión.
     */
    public function actualizarPosicion(int $index, float $x, float $y, float $w, ?float $h = null): void
    {
        if (! isset($this->bloques[$index])) {
            return;
        }

        $this->bloques[$index]['x'] = round($x, 2);
        $this->bloques[$index]['y'] = round($y, 2);
        $this->bloques[$index]['w'] = round($w, 2);

        if ($h !== null) {
            $this->bloques[$index]['h'] = round($h, 2);
        }

        $this->seleccionado = $index;
    }

    public function deseleccionar(): void
    {
        $this->seleccionado = null;
    }

    public function insertarToken(string $token): void
    {
        $this->texto = trim(($this->texto ?? '').' '.$token);
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => 'required|string|max:100',
            'tipo_reconocimiento_id' => 'required|exists:tipo_reconocimiento,tipo_reconocimiento_id',
            'imagen' => ($this->editando_id || $this->clonando_id ? 'nullable' : 'required').'|image|mimes:jpeg,png|max:30720',
            'texto' => 'nullable|string',
            'bloques' => 'array',
        ]);

        $layout = $this->layoutNormalizado();
        $tipoReconocimiento = TipoReconocimiento::findOrFail($this->tipo_reconocimiento_id);

        if ($this->por_defecto) {
            PlantillaCertificado::where('contexto_id', $this->contexto_id)
                ->where('tipo_reconocimiento_id', $this->tipo_reconocimiento_id)
                ->update(['por_defecto' => false]);
        }

        if ($this->editando_id) {
            $plantilla = PlantillaCertificado::where('contexto_id', $this->contexto_id)->findOrFail($this->editando_id);

            if ($this->imagen) {
                Storage::disk('public')->delete($plantilla->imagen_path);
                $plantilla->imagen_path = $this->imagen->store("plantillas/contexto/{$this->contexto_id}", 'public');
            }

            $plantilla->nombre = $this->nombre;
            $plantilla->tipo = $tipoReconocimiento->slug;
            $plantilla->tipo_reconocimiento_id = $tipoReconocimiento->tipo_reconocimiento_id;
            $plantilla->alcance = $tipoReconocimiento->alcance_sugerido ?: 'evento';
            $plantilla->por_defecto = (bool) $this->por_defecto;
            $plantilla->texto = $this->texto ?: null;
            $plantilla->layout = $layout;
            $plantilla->save();
        } else {
            $imagenPath = $this->imagen
                ? $this->imagen->store("plantillas/contexto/{$this->contexto_id}", 'public')
                : $this->copiarImagenDePlantilla($this->clonando_id);

            if (! $imagenPath) {
                $this->addError('imagen', 'No se pudo copiar la imagen de la plantilla original. Subí una imagen.');

                return;
            }

            PlantillaCertificado::create([
                'categoria_id' => $this->contextoModel()->categoria_id,
                'contexto_id' => $this->contexto_id,
                'nombre' => $this->nombre,
                'imagen_path' => $imagenPath,
                'layout' => $layout,
                'texto' => $this->texto ?: null,
                'tipo' => $tipoReconocimiento->slug,
                'tipo_reconocimiento_id' => $tipoReconocimiento->tipo_reconocimiento_id,
                'alcance' => $tipoReconocimiento->alcance_sugerido ?: 'evento',
                'por_defecto' => (bool) $this->por_defecto,
            ]);
        }

        $this->dispatch('alert', message: $this->clonando_id ? 'Plantilla clonada correctamente.' : 'Plantilla del contexto guardada correctamente.');
        $this->open_modal = false;
        $this->reset(['editando_id', 'clonando_id', 'nombre', 'imagen', 'imagen_actual', 'texto', 'bloques', 'seleccionado']);
        $this->cargarPlantillas();
    }

    /**
     * Copia física del archivo de imagen de una plantilla existente para que la
     * copia sea independiente (borrar una no afecta a la otra).
     */
    private function copiarImagenDePlantilla(?int $plantillaId): ?string
    {
        if (! $plantillaId) {
            return null;
        }

        $origen = PlantillaCertificado::where('contexto_id', $this->contexto_id)->find($plantillaId);

        if (! $origen?->imagen_path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($origen->imagen_path)) {
            return null;
        }

        $extension = pathinfo($origen->imagen_path, PATHINFO_EXTENSION) ?: 'png';
        $destino = "plantillas/contexto/{$this->contexto_id}/clone-".Str::uuid().'.'.$extension;

        return $disk->copy($origen->imagen_path, $destino) ? $destino : null;
    }

    public function descargarPdfPrueba()
    {
        $firmas = [];

        foreach (CertificadoVariables::firmantesDe($this->contextoModel()) as $firmante) {
            $firmante['imagen'] = CertificadoPdfAssets::resolvePrivatePath($firmante['imagen_path']);
            $firmas[] = $firmante;
        }

        $pdf = Pdf::loadView('certificado', [
            'nombre' => $this->mock['nombres'] ?? '',
            'apellido' => $this->mock['apellido'] ?? '',
            'dni' => $this->mock['dni'] ?? '',
            'qr' => $this->qr_preview,
            'background' => $this->backgroundParaPdf(),
            'layout' => $this->layoutNormalizado(),
            'texto' => $this->texto,
            'variables' => $this->mockVariables(),
            'firmas' => $firmas,
        ])->setPaper('a4', 'landscape');

        $contenido = $pdf->output();

        return response()->streamDownload(function () use ($contenido) {
            echo $contenido;
        }, 'certificado-prueba.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function eliminar(int $id): void
    {
        $plantilla = PlantillaCertificado::where('contexto_id', $this->contexto_id)->findOrFail($id);
        $wasDefault = (bool) $plantilla->por_defecto;
        $tipoReconocimientoId = $plantilla->tipo_reconocimiento_id;

        Storage::disk('public')->delete($plantilla->imagen_path);
        $plantilla->delete();

        if ($wasDefault) {
            $otra = PlantillaCertificado::where('contexto_id', $this->contexto_id)
                ->where('tipo_reconocimiento_id', $tipoReconocimientoId)
                ->orderBy('plantilla_id')
                ->first();

            if ($otra) {
                $otra->update(['por_defecto' => true]);
            }
        }

        $this->dispatch('alert', message: 'Plantilla eliminada.');
        $this->cargarPlantillas();
    }

    private function contextoModel(): Contexto
    {
        return Contexto::with('firmantes')->findOrFail($this->contexto_id);
    }

    /**
     * Variables de ejemplo (mock) para la vista previa y el PDF de prueba.
     */
    private function mockVariables(): array
    {
        $contexto = $this->contextoModel();

        $tipo = new TipoEvento([
            'nombre' => $this->mock['tipo_evento'] ?? '',
            'formula' => $this->mock['formula'] ?? '',
        ]);

        $evento = new Evento(['nombre' => $this->mock['nombre_evento'] ?? '']);
        $evento->setRelation('tipoEvento', $tipo);

        $participante = new Participante([
            'nombre' => $this->mock['nombres'] ?? '',
            'apellido' => $this->mock['apellido'] ?? '',
            'dni' => $this->mock['dni'] ?? '',
        ]);

        return CertificadoVariables::paraEvento(
            $evento,
            $participante,
            $contexto,
            CertificadoVariables::firmantesDe($contexto),
        );
    }

    /**
     * Firmas del contexto con URL de vista previa (marca de agua) para el navegador.
     *
     * @return array<int, array{nombre:string, cargo:string, preview_url:?string}>
     */
    private function mockFirmasPreview(): array
    {
        return $this->contextoModel()->firmantes
            ->map(fn ($firmante) => [
                'nombre' => (string) $firmante->nombre,
                'cargo' => $firmante->pivot?->mostrar_cargo ? (string) $firmante->cargo : '',
                'preview_url' => $firmante->imagen_firma_path
                    ? route('admin.firmantes.imagen', $firmante->firmante_id)
                    : null,
            ])
            ->values()
            ->all();
    }

    private function backgroundParaPdf(): ?string
    {
        if ($this->imagen) {
            $path = method_exists($this->imagen, 'getRealPath') ? $this->imagen->getRealPath() : null;

            if ($path && is_readable($path)) {
                return CertificadoPdfAssets::prepareBackgroundForPdf($path);
            }
        }

        if ($this->imagen_actual) {
            return CertificadoPdfAssets::prepareBackgroundForPdf($this->imagen_actual);
        }

        return null;
    }

    private function layoutNormalizado(): array
    {
        return collect($this->bloques)->map(fn ($bloque) => [
            'campo' => (string) ($bloque['campo'] ?? 'literal'),
            'contenido' => (string) ($bloque['contenido'] ?? ''),
            'x' => (float) ($bloque['x'] ?? 0),
            'y' => (float) ($bloque['y'] ?? 0),
            'w' => (float) ($bloque['w'] ?? 0),
            'h' => (float) ($bloque['h'] ?? 0),
            'size' => (float) ($bloque['size'] ?? 0),
            'align' => (string) ($bloque['align'] ?? 'left'),
            'color' => (string) ($bloque['color'] ?? '#0A1B3A'),
            'bold' => (bool) ($bloque['bold'] ?? false),
            'italic' => (bool) ($bloque['italic'] ?? false),
        ])->all();
    }

    private function generarQrPreview(): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);
        $writer = new Writer($renderer);
        $svg = $writer->writeString(route('welcome'));

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    protected function layoutPorDefecto(): array
    {
        $bloque = fn (string $campo, float $x, float $y, float $w, float $size, string $align, array $extra = []) => array_merge([
            'campo' => $campo,
            'contenido' => '',
            'x' => $x,
            'y' => $y,
            'w' => $w,
            'h' => 0,
            'size' => $size,
            'align' => $align,
            'color' => '#0A1B3A',
            'bold' => false,
            'italic' => false,
        ], $extra);

        return [
            $bloque('literal', 8, 39.5, 14, 18, 'left', ['contenido' => 'Por cuanto', 'bold' => true]),
            $bloque('apellido_nombres', 20, 38, 52, 40, 'center', ['bold' => true]),
            $bloque('literal', 74, 39.5, 8, 20, 'right', ['contenido' => 'DNI', 'bold' => true]),
            $bloque('dni', 82, 38, 14, 40, 'left', ['bold' => true]),
            $bloque('texto_cuerpo', 8, 47, 84, 16, 'left'),
            $bloque('fecha_rango', 50, 63, 42, 14, 'right', ['italic' => true]),
            $bloque('qr', 44, 72, 12, 0, 'center', ['h' => 18]),
            $bloque('firma_1_imagen', 10, 74, 20, 0, 'center', ['h' => 12]),
            $bloque('firma_1_nombre', 8, 87, 24, 12, 'center', ['bold' => true]),
            $bloque('firma_1_cargo', 8, 91, 24, 10, 'center'),
            $bloque('firma_2_imagen', 70, 74, 20, 0, 'center', ['h' => 12]),
            $bloque('firma_2_nombre', 68, 87, 24, 12, 'center', ['bold' => true]),
            $bloque('firma_2_cargo', 68, 91, 24, 10, 'center'),
        ];
    }

    private function tipoReconocimientoPorDefecto(): ?int
    {
        return TipoReconocimiento::where('slug', 'asistencia')->value('tipo_reconocimiento_id')
            ?? TipoReconocimiento::activos()->orderBy('orden')->value('tipo_reconocimiento_id');
    }

    public function render()
    {
        $tiposReconocimiento = TipoReconocimiento::activos()->orderBy('orden')->orderBy('nombre')->get();

        return view('livewire.admin.contexto-plantillas', [
            'campos' => self::CAMPOS,
            'tokens' => self::TOKENS,
            'tiposReconocimiento' => $tiposReconocimiento,
            'contexto' => $this->contextoModel(),
            'mockVariables' => $this->mockVariables(),
            'mockFirmas' => $this->mockFirmasPreview(),
        ]);
    }
}
