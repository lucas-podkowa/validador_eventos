<?php

namespace Tests\Feature;

use App\Livewire\EventosFinalizados;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class EventosFinalizadosCertificadosTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_requiere_plantilla_manual_cuando_no_hay_plantilla_de_categoria(): void
    {
        $this->actingAs($this->admin);
        $evento = $this->crearEventoFinalizado();

        Livewire::test(EventosFinalizados::class)
            ->call('emitir', ['evento_id' => $evento->evento_id])
            ->call('usarPlantillaManual', 'asistencia')
            ->call('emitirCertificados')
            ->assertHasErrors(['background_image' => 'required']);
    }

    public function test_usa_la_plantilla_de_categoria_sin_requerir_upload_manual(): void
    {
        $this->actingAs($this->admin);
        $evento = $this->crearEventoFinalizado(conPlantillaCategoria: true);

        Livewire::test(EventosFinalizados::class)
            ->call('emitir', ['evento_id' => $evento->evento_id])
            ->assertSet('usar_plantilla_categoria.asistencia', true)
            ->call('emitirCertificados')
            ->assertHasNoErrors(['background_image'])
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

    private function crearEventoFinalizado(bool $conPlantillaCategoria = false): Evento
    {
        $tipoEvento = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoria de prueba']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
        ]);

        if ($conPlantillaCategoria) {
            PlantillaCertificado::create([
                'categoria_id' => $categoria->categoria_id,
                'nombre' => 'Plantilla asistencia',
                'imagen_path' => 'images/plantilla-asistencia.png',
                'tipo' => 'asistencia',
                'por_defecto' => true,
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
            'por_aprobacion' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);
    }
}
