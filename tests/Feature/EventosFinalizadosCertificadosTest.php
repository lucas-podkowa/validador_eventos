<?php

namespace Tests\Feature;

use App\Livewire\EventosFinalizados;
use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\InscripcionParticipante;
use App\Models\Participante;
use App\Models\PlanillaInscripcion;
use App\Models\PlantillaCertificado;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\TipoReconocimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class EventosFinalizadosCertificadosTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_1PX = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionRole::create(['name' => 'Administrador', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        foreach (['Participante', 'Disertante', 'Colaborador'] as $rol) {
            Rol::create(['nombre' => $rol]);
        }
    }

    public function test_no_emite_si_el_evento_no_tiene_contexto(): void
    {
        $this->actingAs($this->admin);
        $evento = $this->crearEventoFinalizado();

        Livewire::test(EventosFinalizados::class)
            ->call('emitir', ['evento_id' => $evento->evento_id])
            ->call('emitirCertificados')
            ->assertDispatched('oops');

        $this->assertNull($evento->fresh()->certificado_path);
    }

    public function test_emite_unicamente_con_la_plantilla_del_contexto(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $this->actingAs($this->admin);

        $evento = $this->crearEventoFinalizado(conPlantillaContexto: true);

        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => fake()->unique()->numberBetween(10000000, 99999999),
            'mail' => fake()->unique()->safeEmail(),
            'telefono' => '3764000000',
        ]);

        EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
        ]);

        Livewire::test(EventosFinalizados::class)
            ->call('emitir', ['evento_id' => $evento->evento_id])
            ->assertSet('plantillas_contexto.asistencia.existe', true)
            ->assertSet('tipos_faltantes', [])
            ->call('emitirCertificados')
            ->assertSet('open_emitir', false);

        $this->assertDatabaseHas('evento', [
            'evento_id' => $evento->evento_id,
            'certificado_path' => 'certificados/'.now()->year.'/Curso/EVENTO DE PRUEBA',
        ]);
    }

    public function test_la_ruta_de_ver_certificado_envia_headers_sin_cache(): void
    {
        $evento = $this->crearEventoFinalizado();
        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => fake()->unique()->numberBetween(10000000, 99999999),
            'mail' => fake()->unique()->safeEmail(),
            'telefono' => '3764000000',
        ]);

        $certificadoPath = 'certificados/'.now()->year.'/Curso/EVENTO DE PRUEBA/Perez_Juan.pdf';
        Storage::disk('private')->put($certificadoPath, 'pdf de prueba');

        $eventoParticipante = EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'certificado_path' => $certificadoPath,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('ver.certificado', $eventoParticipante));

        $response->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringNotContainsString('public', $cacheControl);

        $response->assertHeader('Pragma', 'no-cache');
        $this->assertNotNull($response->headers->get('Expires'));
    }

    public function test_descarga_listado_de_inscriptos_con_detalles_del_evento(): void
    {
        $this->actingAs($this->admin);
        $evento = $this->crearEventoFinalizado();

        $planilla = PlanillaInscripcion::create([
            'apertura' => now()->subDay(),
            'cierre' => now(),
            'evento_id' => $evento->evento_id,
        ]);

        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => fake()->unique()->numberBetween(10000000, 99999999),
            'mail' => fake()->unique()->safeEmail(),
            'telefono' => '3764000000',
        ]);

        InscripcionParticipante::create([
            'planilla_id' => $planilla->planilla_inscripcion_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'fecha_inscripcion' => now(),
        ]);

        Livewire::test(EventosFinalizados::class)
            ->call('descargarInscriptos', ['evento_id' => $evento->evento_id])
            ->assertFileDownloaded('inscriptos_evento-de-prueba.pdf');
    }

    public function test_descargar_inscriptos_sin_planilla_notifica_error(): void
    {
        $this->actingAs($this->admin);
        $evento = $this->crearEventoFinalizado();

        Livewire::test(EventosFinalizados::class)
            ->call('descargarInscriptos', ['evento_id' => $evento->evento_id])
            ->assertNoFileDownloaded()
            ->assertDispatched('oops');
    }

    public function test_asigna_contexto_desde_certificacion_sin_perder_aprobaciones(): void
    {
        $this->actingAs($this->admin);

        $tipoEvento = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoria Test']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => '11111111',
        ]);

        $evento = Evento::create([
            'nombre' => 'EVENTO LEGADO',
            'fecha_inicio' => now()->toDateString(),
            'cupo' => 100,
            'lugar' => 'Aula 1',
            'tipo_evento_id' => $tipoEvento->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'contexto_id' => null,
            'por_aprobacion' => true,
            'responsable_id' => $responsable->responsable_id,
        ]);
        $evento->estado = 'Finalizado';
        $evento->save();

        $contexto = Contexto::create([
            'categoria_id' => $categoria->categoria_id,
            'nombre' => 'Contexto Nuevo',
            'activo' => true,
        ]);

        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => fake()->unique()->numberBetween(10000000, 99999999),
            'mail' => fake()->unique()->safeEmail(),
            'telefono' => '3764000000',
        ]);

        $relacion = EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'aprobado' => true,
            'qrcode' => '<svg>legacy</svg>',
        ]);

        Livewire::test(EventosFinalizados::class)
            ->call('emitir', ['evento_id' => $evento->evento_id])
            ->set('categoria_asignada_id', $categoria->categoria_id)
            ->set('contexto_asignado_id', $contexto->contexto_id)
            ->call('asignarContexto')
            ->assertHasNoErrors();

        $evento->refresh();
        $this->assertEquals($contexto->contexto_id, $evento->contexto_id);
        $this->assertEquals($categoria->categoria_id, $evento->categoria_id);
        $this->assertSame('Finalizado', $evento->estado);

        $relacion->refresh();
        $this->assertTrue((bool) $relacion->aprobado);
        $this->assertSame('<svg>legacy</svg>', $relacion->qrcode);
    }

    private function crearEventoFinalizado(bool $conPlantillaContexto = false): Evento
    {
        $tipoEvento = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoria de prueba']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
        ]);

        $contextoId = null;

        if ($conPlantillaContexto) {
            $contexto = Contexto::create([
                'categoria_id' => $categoria->categoria_id,
                'nombre' => 'Contexto de prueba',
                'activo' => true,
            ]);
            $contextoId = $contexto->contexto_id;

            Storage::disk('public')->put('plantillas/contexto/base.png', base64_decode(self::PNG_1PX));

            PlantillaCertificado::create([
                'categoria_id' => $categoria->categoria_id,
                'contexto_id' => $contexto->contexto_id,
                'nombre' => 'Plantilla asistencia',
                'imagen_path' => 'plantillas/contexto/base.png',
                'tipo' => 'asistencia',
                'tipo_reconocimiento_id' => TipoReconocimiento::where('slug', 'asistencia')->value('tipo_reconocimiento_id'),
                'por_defecto' => true,
                'texto' => 'ha asistido {formula} {nombre_evento}',
                'layout' => [
                    ['campo' => 'apellido_nombres', 'x' => 20, 'y' => 38, 'w' => 52, 'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false],
                ],
            ]);
        }

        return Evento::create([
            'nombre' => 'EVENTO DE PRUEBA',
            'fecha_inicio' => now()->toDateString(),
            'cupo' => 100,
            'lugar' => 'Aula 1',
            'estado' => 'Finalizado',
            'tipo_evento_id' => $tipoEvento->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'contexto_id' => $contextoId,
            'por_aprobacion' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);
    }
}
