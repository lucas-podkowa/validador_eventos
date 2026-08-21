<?php

namespace Tests\Feature;

use App\Livewire\InscribirStaff;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\InscripcionParticipante;
use App\Models\Participante;
use App\Models\PlanillaInscripcion;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class InscribirStaffTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = SpatieRole::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $permissionEventos = Permission::create(['name' => 'eventos', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions([$permissionEventos]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        TipoEvento::create(['nombre' => 'Curso']);
        CategoriaEvento::create(['nombre' => 'Categoría Test']);

        Rol::create(['nombre' => 'Disertante']);
        Rol::create(['nombre' => 'Colaborador']);
    }

    public function test_guardar_staff_redirige_a_la_lista_del_mismo_evento(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConPlanillaEnCurso();
        $rolDisertante = Rol::where('nombre', 'Disertante')->firstOrFail();

        Livewire::test(InscribirStaff::class, ['evento_id' => $evento->evento_id])
            ->set('rol_seleccionado', 'Disertante')
            ->set('dni', '12345678')
            ->set('nombre', 'Laura')
            ->set('apellido', 'Gimenez')
            ->set('mail', 'laura@example.com')
            ->set('telefono', '3764123456')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('eventos', [
                'tab' => 'en_curso',
                'evento_id' => $evento->evento_id,
                'mostrar' => 'disertantes',
            ], false));

        $participante = Participante::where('dni', '12345678')->first();

        $this->assertNotNull($participante);
        $this->assertDatabaseHas('inscripcion_participante', [
            'planilla_id' => $evento->planillaInscripcion->planilla_inscripcion_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => $rolDisertante->rol_id,
        ]);
    }

    public function test_volver_redirige_a_la_lista_de_staff_del_mismo_evento(): void
    {
        $this->actingAs($this->admin);
        $evento = $this->crearEventoConPlanillaEnCurso();

        Livewire::test(InscribirStaff::class, ['evento_id' => $evento->evento_id])
            ->call('volver')
            ->assertRedirect(route('eventos', [
                'tab' => 'en_curso',
                'evento_id' => $evento->evento_id,
                'mostrar' => 'disertantes',
            ], false));
    }

    public function test_buscar_participante_reutiliza_registro_existente_por_dni(): void
    {
        Mail::fake();
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConPlanillaEnCurso();
        $rolDisertante = Rol::where('nombre', 'Disertante')->firstOrFail();
        $participante = Participante::create([
            'nombre' => 'JUAN',
            'apellido' => 'PEREZ',
            'dni' => '22333444',
            'mail' => 'juan@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::test(InscribirStaff::class, ['evento_id' => $evento->evento_id])
            ->set('dni', '22333444')
            ->call('buscarParticipante')
            ->assertSet('nombre', 'Juan')
            ->assertSet('apellido', 'Perez')
            ->assertSet('mail', 'juan@example.com')
            ->assertSet('telefono', '3764000000')
            ->set('rol_seleccionado', 'Disertante')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, Participante::where('dni', '22333444')->count());
        $this->assertDatabaseHas('inscripcion_participante', [
            'planilla_id' => $evento->planillaInscripcion->planilla_inscripcion_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => $rolDisertante->rol_id,
        ]);
        $this->assertSame(1, InscripcionParticipante::count());
    }

    protected function crearEventoConPlanillaEnCurso(): Evento
    {
        $tipo = TipoEvento::firstOrFail();
        $categoria = CategoriaEvento::firstOrFail();
        $responsable = $this->crearResponsable();

        $evento = Evento::create([
            'nombre' => 'Evento Staff',
            'lugar' => 'Aula Magna',
            'fecha_inicio' => now()->addDay(),
            'tipo_evento_id' => $tipo->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'cupo' => null,
            'por_aprobacion' => false,
            'arancel' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);

        $evento->estado = 'En Curso';
        $evento->save();

        PlanillaInscripcion::create([
            'apertura' => now()->subDay(),
            'cierre' => now()->addMonth(),
            'evento_id' => $evento->evento_id,
        ]);

        return $evento->load('planillaInscripcion');
    }

    protected function crearResponsable(): Responsable
    {
        return Responsable::create([
            'nombre' => 'RESPONSABLE',
            'apellido' => 'STAFF',
            'dni' => '11111111',
        ]);
    }
}
