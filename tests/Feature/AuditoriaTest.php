<?php

namespace Tests\Feature;

use App\Livewire\Admin\Auditoria;
use App\Livewire\CrearEvento;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use App\Models\Responsable;
use App\Models\TipoEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gestor;

    protected function setUp(): void
    {
        parent::setUp();

        $rolAdmin = Role::create(['name' => 'Administrador']);
        $rolGestor = Role::create(['name' => 'Gestor']);
        $permiso = Permission::create(['name' => 'ver_auditoria']);

        $rolAdmin->givePermissionTo($permiso);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($rolAdmin);

        $this->gestor = User::factory()->create();
        $this->gestor->assignRole($rolGestor);
    }

    public function test_eliminar_evento_genera_actividad_con_uuid_y_datos_anteriores(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEvento();
        Activity::query()->delete();

        Livewire::test(CrearEvento::class, ['evento_id' => $evento->evento_id])
            ->call('eliminarEvento');

        $this->assertDatabaseMissing('evento', ['evento_id' => $evento->evento_id]);

        $actividad = Activity::query()
            ->where('event', 'deleted')
            ->where('subject_type', Evento::class)
            ->first();

        $this->assertNotNull($actividad);
        $this->assertSame($evento->evento_id, $actividad->subject_id);
        $this->assertSame($this->admin->id, $actividad->causer_id);
        $this->assertSame('evento', $actividad->log_name);
        $this->assertSame('EVENTO DE PRUEBA', $actividad->attribute_changes['old']['nombre']);
    }

    public function test_eliminacion_rechazada_de_evento_queda_registrada(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEvento();

        PlanillaInscripcion::create([
            'evento_id' => $evento->evento_id,
            'apertura' => now(),
            'cierre' => now()->addDay(),
            'estado' => 'activa',
        ]);

        Activity::query()->delete();

        Livewire::test(CrearEvento::class, ['evento_id' => $evento->evento_id])
            ->call('eliminarEvento')
            ->assertDispatched('oops');

        $this->assertDatabaseHas('evento', ['evento_id' => $evento->evento_id]);

        $actividad = Activity::query()
            ->where('event', 'evento.eliminacion_rechazada')
            ->where('subject_id', $evento->evento_id)
            ->first();

        $this->assertNotNull($actividad);
        $this->assertSame($this->admin->id, $actividad->causer_id);
        $this->assertTrue($actividad->getProperty('tiene_planilla'));
    }

    public function test_solo_administrador_puede_acceder_al_panel(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.auditoria'))
            ->assertOk();

        $this->actingAs($this->gestor)
            ->get(route('admin.auditoria'))
            ->assertForbidden();
    }

    public function test_panel_filtra_por_usuario_accion_y_modulo(): void
    {
        activity()->useLog('evento')->causedBy($this->admin)->event('created')->log('Evento auditado');
        activity()->useLog('usuario')->causedBy($this->gestor)->event('updated')->log('Usuario auditado');

        Livewire::actingAs($this->admin)
            ->test(Auditoria::class)
            ->set('usuario_id', (string) $this->gestor->id)
            ->assertSee('Usuario auditado')
            ->assertDontSee('Evento auditado');

        Livewire::actingAs($this->admin)
            ->test(Auditoria::class)
            ->set('accion', 'created')
            ->assertSee('Evento auditado')
            ->assertDontSee('Usuario auditado');

        Livewire::actingAs($this->admin)
            ->test(Auditoria::class)
            ->set('modulo', 'evento')
            ->assertSee('Evento auditado')
            ->assertDontSee('Usuario auditado');
    }

    public function test_usuario_no_registra_contrasenas_en_la_auditoria(): void
    {
        $usuario = User::factory()->create();
        Activity::query()->delete();

        $this->actingAs($this->admin);

        $usuario->name = 'Nombre Actualizado';
        $usuario->password = Hash::make('nueva-clave-segura');
        $usuario->save();

        $actividad = Activity::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $usuario->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($actividad);
        $this->assertArrayNotHasKey('password', $actividad->attribute_changes['attributes']);
        $this->assertArrayNotHasKey('password', $actividad->attribute_changes['old']);
    }

    private function crearEvento(): Evento
    {
        $tipoEvento = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoria de prueba']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => fake()->unique()->numberBetween(10000000, 99999999),
        ]);

        return Evento::create([
            'nombre' => 'EVENTO DE PRUEBA',
            'fecha_inicio' => now()->toDateString(),
            'lugar' => 'Aula 1',
            'tipo_evento_id' => $tipoEvento->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'responsable_id' => $responsable->responsable_id,
        ]);
    }
}
