<?php

namespace Tests\Feature;

use App\Mail\CertificadoTutorMail;
use App\Models\ApiCliente;
use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\Emision;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\TipoReconocimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificadoExternoApiTest extends TestCase
{
    use RefreshDatabase;

    private ?int $contextoId = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');
        Mail::fake();
    }

    public function test_requiere_autenticacion(): void
    {
        $this->postJson(route('api.certificados.store'), $this->payload())
            ->assertUnauthorized();
    }

    public function test_requiere_ability_de_emision(): void
    {
        [$cliente, $token] = $this->clienteConToken(['certificados:leer']);
        $this->crearPlantilla();

        $this->withToken($token)
            ->postJson(route('api.certificados.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_emite_certificado_guarda_pdf_y_encola_mail(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $response = $this->withToken($token)
            ->postJson(route('api.certificados.store'), $this->payload());

        $response->assertCreated()
            ->assertHeader('Idempotent-Replay', 'false')
            ->assertJsonPath('data.external_ref', 'PPS-TUT-1')
            ->assertJsonPath('data.estado', Emision::ESTADO_EMITIDO)
            ->assertJsonPath('data.match_estado', Emision::MATCH_AUTO)
            ->assertJsonPath('data.receptor.nombre', 'Perez, Juan Carlos')
            ->assertJsonPath('data.contexto.nombre', 'Prácticas Profesionales Supervisadas')
            ->assertJsonPath('data.plantilla.codigo', 'tutor_academico_default')
            ->assertJsonStructure(['data' => ['id', 'verificacion_url', 'receptor' => ['nombre', 'dni']]]);

        $certificado = Emision::first();
        $this->assertNotNull($certificado);
        $this->assertNotNull($certificado->certificado_path);
        Storage::disk('private')->assertExists($certificado->certificado_path);

        $this->assertDatabaseHas('participante', [
            'dni' => 30111222,
            'mail' => 'juan@example.test',
        ]);

        Mail::assertSent(CertificadoTutorMail::class);
    }

    public function test_idempotencia_por_referencia_externa(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $primera = $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload());
        $primera->assertCreated();

        $segunda = $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload());
        $segunda->assertOk()->assertHeader('Idempotent-Replay', 'true');

        $this->assertSame(1, Emision::count());
        Mail::assertSentCount(1);
    }

    public function test_tier_1_reutiliza_participante_y_vincula_cuenta(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $user = User::factory()->create([
            'email' => 'juan@example.test',
            'dni' => '30111222',
        ]);

        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'juan@example.test',
            'telefono' => '3764123456',
        ]);

        $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.match_estado', 'auto');

        $certificado = Emision::first();
        $this->assertSame($participante->participante_id, $certificado->participante_id);
        $this->assertSame((int) $user->id, (int) $participante->fresh()->user_id);
        $this->assertSame(1, Participante::count());
    }

    public function test_tier_4_marca_revision_cuando_solo_coincide_dni(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'otro@example.test',
            'telefono' => '3764999999',
        ]);

        $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.match_estado', 'revisar');

        $certificado = Emision::first();
        $this->assertSame($participante->participante_id, $certificado->participante_id);
        $this->assertSame(1, Participante::count());
    }

    public function test_tier_7_crea_participante_nuevo(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.match_estado', 'auto');

        $this->assertSame(1, Participante::count());
        $this->assertDatabaseHas('participante', ['dni' => 30111222]);
    }

    public function test_consulta_solo_del_propio_cliente(): void
    {
        [$cliente, $token] = $this->clienteConToken(['certificados:emitir', 'certificados:leer']);
        $this->crearPlantilla();

        $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload())->assertCreated();
        $certificado = Emision::first();

        $this->withToken($token)
            ->getJson(route('api.certificados.show', $certificado))
            ->assertOk()
            ->assertJsonPath('data.external_ref', 'PPS-TUT-1');

        [, $otroToken] = $this->clienteConToken(['certificados:leer']);

        $this->app['auth']->forgetGuards();

        $this->withToken($otroToken)
            ->getJson(route('api.certificados.show', $certificado))
            ->assertNotFound();
    }

    public function test_validacion_publica_por_codigo(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload())->assertCreated();
        $certificado = Emision::first();

        $this->get(route('verificar', ['codigo' => $certificado->codigo_verificacion]))
            ->assertOk()
            ->assertSee('Perez');

        $this->get(route('verificar', ['codigo' => 'inexistente']))
            ->assertOk()
            ->assertSee('no válido');
    }

    public function test_el_tutor_ve_y_descarga_su_certificado(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $user = User::factory()->create([
            'email' => 'juan@example.test',
            'dni' => '30111222',
        ]);

        $this->withToken($token)->postJson(route('api.certificados.store'), $this->payload())->assertCreated();
        $certificado = Emision::first();

        $this->actingAs($user)
            ->get(route('mis_certificados'))
            ->assertOk()
            ->assertSee('Prácticas Profesionales Supervisadas');

        $this->actingAs($user)
            ->get(route('mis_certificados.emision', $certificado))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rate_limit_por_cliente(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        for ($i = 0; $i < 60; $i++) {
            $this->withToken($token)
                ->postJson(route('api.certificados.store'), ['external_ref' => 'x'])
                ->assertStatus(422);
        }

        $this->withToken($token)
            ->postJson(route('api.certificados.store'), ['external_ref' => 'x'])
            ->assertStatus(429);
    }

    /**
     * @return array{0: ApiCliente, 1: string}
     */
    public function test_contexto_id_es_obligatorio(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $this->withToken($token)
            ->postJson(route('api.certificados.store'), $this->payload(['contexto_id' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contexto_id']);
    }

    public function test_no_usa_plantilla_de_otro_contexto(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $otraCategoria = CategoriaEvento::create(['nombre' => 'Otros']);
        $otroContexto = Contexto::create([
            'categoria_id' => $otraCategoria->categoria_id,
            'nombre' => 'Otro programa',
            'activo' => true,
        ]);

        $this->withToken($token)
            ->postJson(route('api.certificados.store'), $this->payload([
                'contexto_id' => $otroContexto->contexto_id,
                'plantilla_codigo' => 'tutor_academico_default',
            ]))
            ->assertStatus(422);

        $this->assertSame(0, Emision::count());
    }

    public function test_exige_plantilla_predeterminada_o_codigo(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $plantilla = $this->crearPlantilla();
        $plantilla->update(['por_defecto' => false]);

        $this->withToken($token)
            ->postJson(route('api.certificados.store'), $this->payload(['plantilla_codigo' => null]))
            ->assertStatus(422);

        $this->assertSame(0, Emision::count());
    }

    public function test_fecha_rango_usa_el_periodo_de_la_practica(): void
    {
        [$cliente, $token] = $this->clienteConToken();
        $this->crearPlantilla();

        $this->withToken($token)
            ->postJson(route('api.certificados.store'), $this->payload())
            ->assertCreated();

        $fechaRango = (string) (Emision::first()->datos['fecha_rango'] ?? '');

        $this->assertStringContainsString('marzo', $fechaRango);
        $this->assertStringContainsString('junio', $fechaRango);
    }

    public function test_lista_contextos_con_plantillas(): void
    {
        [$cliente, $token] = $this->clienteConToken(['certificados:contextos']);
        $this->crearPlantilla();

        $this->withToken($token)
            ->getJson(route('api.contextos.index'))
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Prácticas Profesionales Supervisadas')
            ->assertJsonPath('data.0.plantillas.0.codigo', 'tutor_academico_default')
            ->assertJsonPath('data.0.plantillas.0.por_defecto', true);

        $this->withToken($token)
            ->getJson(route('api.contextos.index', ['tipo' => 'tutor_academico']))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_contextos_requiere_ability(): void
    {
        [$cliente, $token] = $this->clienteConToken(['certificados:emitir']);
        $this->crearPlantilla();

        $this->withToken($token)
            ->getJson(route('api.contextos.index'))
            ->assertForbidden();
    }

    private function clienteConToken(array $abilities = ['certificados:emitir']): array
    {
        $cliente = ApiCliente::create(['nombre' => 'PPS', 'activo' => true]);

        return [$cliente, $cliente->createToken('test', $abilities)->plainTextToken];
    }

    private function crearPlantilla(): PlantillaCertificado
    {
        $categoria = CategoriaEvento::create(['nombre' => 'Prácticas']);
        $contexto = Contexto::create([
            'categoria_id' => $categoria->categoria_id,
            'nombre' => 'Prácticas Profesionales Supervisadas',
            'institucion' => 'Facultad de Ingeniería UNaM',
            'activo' => true,
        ]);

        $this->contextoId = $contexto->contexto_id;

        $tipoTutor = TipoReconocimiento::where('slug', 'tutor')->value('tipo_reconocimiento_id');

        return PlantillaCertificado::create([
            'categoria_id' => $categoria->categoria_id,
            'contexto_id' => $contexto->contexto_id,
            'nombre' => 'tutor_academico_default',
            'imagen_path' => 'plantillas/contexto/tutor.png',
            'tipo' => 'tutor_academico',
            'tipo_reconocimiento_id' => $tipoTutor,
            'alcance' => 'programa',
            'por_defecto' => true,
            'texto' => 'ha cumplido la función de Tutor Académico en {carrera}.',
            'layout' => [
                ['campo' => 'apellido_nombres', 'x' => 20, 'y' => 38, 'w' => 52, 'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false],
                ['campo' => 'texto_cuerpo', 'x' => 8, 'y' => 47, 'w' => 84, 'size' => 16, 'align' => 'left', 'color' => '#0A1B3A', 'bold' => false, 'italic' => false],
                ['campo' => 'qr', 'x' => 44, 'y' => 74, 'w' => 12, 'h' => 16, 'size' => 0, 'align' => 'center', 'color' => '#000', 'bold' => false, 'italic' => false],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'external_ref' => 'PPS-TUT-1',
            'tipo' => 'tutor_academico',
            'plantilla_codigo' => 'tutor_academico_default',
            'contexto_id' => $this->contextoId,
            'tutor' => [
                'apellido' => 'Perez',
                'nombres' => 'Juan Carlos',
                'dni' => '30111222',
                'email' => 'juan@example.test',
                'telefono' => '3764123456',
                'cargo' => 'Tutor Académico',
            ],
            'practica' => [
                'carrera' => 'Ingeniería Civil',
                'estudiante_apellido_nombres' => 'Gomez, Ana',
                'estudiante_dni' => '40123456',
                'institucion' => 'FIO - UNaM',
                'periodo_inicio' => '2026-03-01',
                'periodo_fin' => '2026-06-30',
                'horas' => 120,
                'resolucion' => 'Res. 123/2026',
            ],
        ], $overrides);
    }
}
