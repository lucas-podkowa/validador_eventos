<?php

namespace Tests\Feature;

use App\Livewire\Admin\ContextoPlantillas;
use App\Livewire\Admin\Contextos;
use App\Livewire\Admin\Firmantes;
use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Firmante;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\TipoReconocimiento;
use App\Models\User;
use App\Services\GenerarCertificadoEvento;
use App\Support\CertificadoVariables;
use App\Support\FechaCertificado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class CertificadoContextoTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionRole::create(['name' => 'Administrador', 'guard_name' => 'web']);
        Permission::create(['name' => 'crear_eventos', 'guard_name' => 'web'])->syncRoles(['Administrador']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        foreach (['Participante', 'Disertante', 'Colaborador'] as $rol) {
            Rol::create(['nombre' => $rol]);
        }
    }

    public function test_crea_contexto_y_asigna_firmantes_con_orden(): void
    {
        $this->actingAs($this->admin);

        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $firmante1 = Firmante::create(['nombre' => 'Hugo Reiner', 'cargo' => 'Vicedecano', 'activo' => true]);
        $firmante2 = Firmante::create(['nombre' => 'María Dekun', 'cargo' => 'Decana', 'activo' => true]);

        Livewire::test(Contextos::class)
            ->call('abrirCrear')
            ->set('categoria_id', $categoria->categoria_id)
            ->set('nombre', 'XVI JIDeTEV')
            ->set('denominacion', 'Jornadas de Investigación')
            ->set('institucion', 'Facultad de Ingeniería UNaM')
            ->set('anio', 2026)
            ->set('fecha_inicio', '2026-08-25')
            ->set('fecha_fin', '2026-08-28')
            ->call('guardar')
            ->assertHasNoErrors();

        $contexto = Contexto::where('nombre', 'XVI JIDeTEV')->first();
        $this->assertNotNull($contexto);

        Livewire::test(Contextos::class)
            ->call('abrirContexto', $contexto->contexto_id)
            ->set('nuevo_firmante_id', $firmante1->firmante_id)
            ->call('agregarFirmante')
            ->set('nuevo_firmante_id', $firmante2->firmante_id)
            ->call('agregarFirmante');

        $this->assertDatabaseHas('contexto_firmante', [
            'contexto_id' => $contexto->contexto_id,
            'firmante_id' => $firmante1->firmante_id,
            'orden' => 1,
        ]);
        $this->assertDatabaseHas('contexto_firmante', [
            'contexto_id' => $contexto->contexto_id,
            'firmante_id' => $firmante2->firmante_id,
            'orden' => 2,
        ]);
    }

    public function test_editor_de_plantilla_contexto_guarda_layout_y_texto(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $contexto = Contexto::create(['categoria_id' => $categoria->categoria_id, 'nombre' => 'XVI JIDeTEV', 'activo' => true]);

        Livewire::test(ContextoPlantillas::class, ['contextoId' => $contexto->contexto_id])
            ->call('abrirCrear')
            ->set('nombre', 'Asistente')
            ->set('tipo_reconocimiento_id', TipoReconocimiento::where('slug', 'asistencia')->value('tipo_reconocimiento_id'))
            ->set('texto', 'ha asistido {formula} {nombre_evento}')
            ->set('imagen', \Illuminate\Http\UploadedFile::fake()->image('base.png', 1600, 1100))
            ->call('guardar')
            ->assertHasNoErrors();

        $plantilla = PlantillaCertificado::where('contexto_id', $contexto->contexto_id)->first();

        $this->assertNotNull($plantilla);
        $this->assertSame('asistencia', $plantilla->tipo);
        $this->assertNotNull($plantilla->tipo_reconocimiento_id);
        $this->assertNotEmpty($plantilla->layout);
        $this->assertTrue($plantilla->esDinamica());
        $this->assertSame('ha asistido {formula} {nombre_evento}', $plantilla->texto);
    }

    public function test_clona_plantilla_del_contexto_copiando_su_imagen(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);

        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $contexto = Contexto::create(['categoria_id' => $categoria->categoria_id, 'nombre' => 'XVI JIDeTEV', 'activo' => true]);

        $asistenciaId = TipoReconocimiento::where('slug', 'asistencia')->value('tipo_reconocimiento_id');
        $disertanteId = TipoReconocimiento::where('slug', 'disertante')->value('tipo_reconocimiento_id');

        $origen = PlantillaCertificado::create([
            'categoria_id' => $categoria->categoria_id,
            'contexto_id' => $contexto->contexto_id,
            'nombre' => 'Asistente',
            'imagen_path' => 'plantillas/contexto/base.png',
            'tipo' => 'asistencia',
            'tipo_reconocimiento_id' => $asistenciaId,
            'por_defecto' => true,
            'texto' => 'ha asistido {formula} {nombre_evento}',
            'layout' => [
                ['campo' => 'apellido_nombres', 'x' => 20, 'y' => 38, 'w' => 52, 'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false],
            ],
        ]);
        Storage::disk('public')->put('plantillas/contexto/base.png', base64_decode(self::PNG_1PX));

        Livewire::test(ContextoPlantillas::class, ['contextoId' => $contexto->contexto_id])
            ->call('clonar', $origen->plantilla_id)
            ->assertSet('clonando_id', $origen->plantilla_id)
            ->assertSet('editando_id', null)
            ->assertSet('nombre', 'Copia de Asistente')
            ->set('nombre', 'Disertante copia')
            ->set('tipo_reconocimiento_id', $disertanteId)
            ->call('guardar')
            ->assertHasNoErrors();

        $copia = PlantillaCertificado::where('contexto_id', $contexto->contexto_id)
            ->where('nombre', 'Disertante copia')
            ->first();

        $this->assertNotNull($copia);
        $this->assertNotSame($origen->plantilla_id, $copia->plantilla_id);
        $this->assertEquals($disertanteId, $copia->tipo_reconocimiento_id);
        $this->assertSame('ha asistido {formula} {nombre_evento}', $copia->texto);
        $this->assertNotEmpty($copia->layout);
        $this->assertFalse($copia->por_defecto);
        $this->assertNotSame($origen->imagen_path, $copia->imagen_path);
        Storage::disk('public')->assertExists($copia->imagen_path);
        Storage::disk('public')->assertExists($origen->imagen_path);
    }

    public function test_fecha_certificado_formatea_rango(): void
    {
        $this->assertSame(
            'del 25 al 28 de agosto de 2026',
            FechaCertificado::rango('2026-08-25', '2026-08-28')
        );

        $this->assertSame(
            'del 25 de agosto de 2026',
            FechaCertificado::rango('2026-08-25', null)
        );

        $this->assertSame(
            'del 25 de agosto al 2 de septiembre de 2026',
            FechaCertificado::rango('2026-08-25', '2026-09-02')
        );
    }

    public function test_renderiza_certificado_dinamico_con_tokens(): void
    {
        $contexto = $this->contextoDePrueba();
        $evento = $this->eventoDePrueba($contexto);
        $participante = Participante::create([
            'nombre' => 'María Josefina',
            'apellido' => 'Chaves',
            'dni' => '32654222',
            'mail' => 'maria@example.test',
            'telefono' => '3764000000',
        ]);

        $variables = CertificadoVariables::paraEvento($evento, $participante, $contexto, []);

        $html = view('certificado', [
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'qr' => '',
            'background' => null,
            'layout' => [
                ['campo' => 'apellido_nombres', 'x' => 20, 'y' => 38, 'w' => 52, 'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false],
                ['campo' => 'texto_cuerpo', 'x' => 8, 'y' => 47, 'w' => 84, 'size' => 16, 'align' => 'left', 'color' => '#0A1B3A', 'bold' => false, 'italic' => false],
                ['campo' => 'fecha_rango', 'x' => 50, 'y' => 63, 'w' => 42, 'size' => 14, 'align' => 'right', 'color' => '#0A1B3A', 'bold' => false, 'italic' => true],
            ],
            'texto' => 'ha asistido {formula} {nombre_evento}, realizado en la {contexto} {institucion}.',
            'variables' => $variables,
            'firmas' => [],
        ])->render();

        $this->assertStringContainsString('Chaves, María Josefina', $html);
        $this->assertStringContainsString('ha asistido a la ANSIEDAD EN SITUACIÓN DE EXAMEN', $html);
        $this->assertStringContainsString('XVI JIDeTEV (Jornadas de Investigación)', $html);
        $this->assertStringContainsString('del 25 al 28 de agosto de 2026', $html);
    }

    public function test_genera_pdf_dinamico_y_resuelve_firma_desde_disco_privado(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $contexto = $this->contextoDePrueba();
        $firmante = Firmante::create([
            'nombre' => 'María C. Dekun',
            'cargo' => 'Decana',
            'imagen_firma_path' => 'firmas/1/firma.png',
            'activo' => true,
        ]);
        Storage::disk('private')->put('firmas/1/firma.png', base64_decode(self::PNG_1PX));
        $contexto->firmantes()->attach($firmante->firmante_id, ['orden' => 1, 'mostrar_cargo' => true]);

        $evento = $this->eventoDePrueba($contexto);
        $participante = Participante::create([
            'nombre' => 'Franco Abel',
            'apellido' => 'Almirón',
            'dni' => '47424166',
            'mail' => 'franco@example.test',
            'telefono' => '3764000000',
        ]);

        $plantilla = PlantillaCertificado::create([
            'categoria_id' => $contexto->categoria_id,
            'contexto_id' => $contexto->contexto_id,
            'nombre' => 'Asistente',
            'imagen_path' => 'plantillas/contexto/base.png',
            'tipo' => 'asistencia',
            'tipo_reconocimiento_id' => TipoReconocimiento::where('slug', 'asistencia')->value('tipo_reconocimiento_id'),
            'por_defecto' => true,
            'texto' => 'ha asistido {formula} {nombre_evento}',
            'layout' => [
                ['campo' => 'apellido_nombres', 'x' => 20, 'y' => 38, 'w' => 52, 'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false],
                ['campo' => 'firma_1_imagen', 'x' => 10, 'y' => 74, 'w' => 20, 'h' => 12, 'size' => 0, 'align' => 'center', 'color' => '#000', 'bold' => false, 'italic' => false],
            ],
        ]);
        Storage::disk('public')->put('plantillas/contexto/base.png', base64_decode(self::PNG_1PX));

        $relacion = EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'qrcode' => '<svg></svg>',
        ]);

        $filename = app(GenerarCertificadoEvento::class)->generar($relacion);

        $this->assertNotNull($filename);
        $this->assertSame($filename, $relacion->fresh()->certificado_path);
        Storage::disk('private')->assertExists($filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function test_actualiza_posicion_y_selecciona_bloque(): void
    {
        $this->actingAs($this->admin);

        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $contexto = Contexto::create([
            'categoria_id' => $categoria->categoria_id,
            'nombre' => 'XVI JIDeTEV',
            'activo' => true,
        ]);

        Livewire::test(ContextoPlantillas::class, ['contextoId' => $contexto->contexto_id])
            ->call('abrirCrear')
            ->call('actualizarPosicion', 0, 12.5, 34.5, 40.0, 25.5)
            ->assertSet('seleccionado', 0)
            ->assertSet('bloques.0.x', 12.5)
            ->assertSet('bloques.0.y', 34.5)
            ->assertSet('bloques.0.w', 40.0)
            ->assertSet('bloques.0.h', 25.5)
            ->call('seleccionar', 2)
            ->assertSet('seleccionado', 2)
            ->call('deseleccionar')
            ->assertSet('seleccionado', null);
    }

    public function test_estilo_de_bloque_de_texto_incluye_line_height_amplio(): void
    {
        $style = \App\Support\CertificadoLayout::estilo([
            'campo' => 'dni', 'x' => 10, 'y' => 10, 'w' => 20, 'h' => 0,
            'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false,
        ]);

        $this->assertStringContainsString('font-size:40px', $style);
        $this->assertStringContainsString('line-height:1.3', $style);
        $this->assertStringNotContainsString('height:0%', $style);
    }

    public function test_panel_del_bloque_seleccionado_apunta_al_indice_correcto(): void
    {
        $this->actingAs($this->admin);

        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $contexto = Contexto::create([
            'categoria_id' => $categoria->categoria_id,
            'nombre' => 'XVI JIDeTEV',
            'activo' => true,
        ]);

        Livewire::test(ContextoPlantillas::class, ['contextoId' => $contexto->contexto_id])
            ->call('abrirCrear')
            ->call('seleccionar', 2)
            ->assertSet('seleccionado', 2)
            ->assertSee('wire:key="bloque-panel-2"', false)
            ->assertSee('wire:model.live="bloques.2.size"', false)
            ->assertDontSee('wire:model.live="bloques.0.size"', false);
    }

    public function test_descarga_pdf_de_prueba_con_datos_mock(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $this->actingAs($this->admin);

        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $contexto = Contexto::create([
            'categoria_id' => $categoria->categoria_id,
            'nombre' => 'XVI JIDeTEV',
            'institucion' => 'Facultad de Ingeniería UNaM',
            'resolucion' => 'CD 089/26',
            'fecha_inicio' => '2026-08-25',
            'fecha_fin' => '2026-08-28',
            'activo' => true,
        ]);

        Livewire::test(ContextoPlantillas::class, ['contextoId' => $contexto->contexto_id])
            ->call('abrirCrear')
            ->set('nombre', 'Asistente')
            ->set('imagen', \Illuminate\Http\UploadedFile::fake()->image('base.png', 1600, 1100))
            ->call('descargarPdfPrueba')
            ->assertFileDownloaded('certificado-prueba.pdf');
    }

    public function test_abm_de_firmantes_guarda_imagen_en_disco_privado(): void
    {
        Storage::fake('private');
        $this->actingAs($this->admin);

        Livewire::test(Firmantes::class)
            ->call('abrirCrear')
            ->set('nombre', 'Hugo Reiner')
            ->set('cargo', 'Vicedecano')
            ->set('imagen', \Illuminate\Http\UploadedFile::fake()->image('firma.png'))
            ->call('guardar')
            ->assertHasNoErrors();

        $firmante = Firmante::where('nombre', 'Hugo Reiner')->first();

        $this->assertNotNull($firmante);
        $this->assertNotNull($firmante->imagen_firma_path);
        Storage::disk('private')->assertExists($firmante->imagen_firma_path);
    }

    public function test_renderiza_certificado_legacy_sin_layout(): void
    {
        $html = view('certificado', [
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => '12345678',
            'qr' => 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=',
            'background' => null,
            'layout' => null,
        ])->render();

        $this->assertStringContainsString('Perez, Juan', $html);
        $this->assertStringContainsString('12345678', $html);
        $this->assertStringContainsString('class="qr"', $html);
    }

    public function test_ruta_de_firma_requiere_autenticacion(): void
    {
        Storage::fake('private');

        $firmante = Firmante::create([
            'nombre' => 'María C. Dekun',
            'cargo' => 'Decana',
            'imagen_firma_path' => 'firmas/1/firma.png',
            'activo' => true,
        ]);
        Storage::disk('private')->put('firmas/1/firma.png', base64_decode(self::PNG_1PX));

        $this->get(route('admin.firmantes.imagen', $firmante))->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('admin.firmantes.imagen', $firmante))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    private function contextoDePrueba(): Contexto
    {
        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);

        return Contexto::create([
            'categoria_id' => $categoria->categoria_id,
            'nombre' => 'XVI JIDeTEV',
            'denominacion' => 'Jornadas de Investigación',
            'institucion' => 'Facultad de Ingeniería UNaM',
            'anio' => 2026,
            'fecha_inicio' => '2026-08-25',
            'fecha_fin' => '2026-08-28',
            'lugar' => 'Oberá, Misiones',
            'resolucion' => 'CD 089/26',
            'activo' => true,
        ]);
    }

    private function eventoDePrueba(Contexto $contexto): Evento
    {
        $tipoEvento = TipoEvento::create(['nombre' => 'Charla', 'formula' => 'a la']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
        ]);

        return Evento::create([
            'nombre' => 'ANSIEDAD EN SITUACIÓN DE EXAMEN',
            'fecha_inicio' => '2026-08-25',
            'cupo' => 100,
            'lugar' => 'Aula 1',
            'estado' => 'Finalizado',
            'tipo_evento_id' => $tipoEvento->tipo_evento_id,
            'categoria_id' => $contexto->categoria_id,
            'contexto_id' => $contexto->contexto_id,
            'por_aprobacion' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);
    }
}
