<?php

namespace Tests\Feature;

use App\Livewire\Eventos;
use App\Livewire\EventosFinalizados;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as PermissionRole;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EventosFinalizadosGestionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gestor;

    protected User $revisor;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Gestor', 'Revisor'] as $rol) {
            PermissionRole::create(['name' => $rol, 'guard_name' => 'web']);
        }

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->gestor = User::factory()->create();
        $this->gestor->assignRole('Gestor');

        $this->revisor = User::factory()->create();
        $this->revisor->assignRole('Revisor');

        foreach (['Participante', 'Disertante', 'Colaborador'] as $rol) {
            Rol::create(['nombre' => $rol]);
        }
    }

    public function test_devolver_evento_a_en_curso_limpia_qr_y_aprobaciones(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);

        $participante = $this->crearParticipante();

        EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'aprobado' => true,
        ]);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('devolverAEnCurso', $evento->evento_id)
            ->assertRedirect(route('eventos', ['tab' => 'en_curso']));

        $evento->refresh();
        $this->assertSame('En Curso', $evento->estado);
        $this->assertFalse((bool) $evento->revisado);
        $this->assertSame($this->revisor->id, $evento->revisor_id);
        $this->assertDatabaseMissing('evento_participantes', ['evento_id' => $evento->evento_id]);
    }

    public function test_devolver_evento_con_certificado_emitido_notifica_error(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoFinalizado();
        $evento->update(['certificado_path' => 'certificados/2026/Curso/EVENTO']);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('devolverAEnCurso', $evento->evento_id)
            ->assertDispatched('oops');

        $this->assertSame('Finalizado', $evento->fresh()->estado);
    }

    public function test_guardar_revisor_actualiza_revisor_y_resetea_revision(): void
    {
        $this->actingAs($this->gestor);

        $evento = $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);
        $evento->update(['revisado' => true]);

        $nuevoRevisor = User::factory()->create();
        $nuevoRevisor->assignRole('Revisor');

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('modalRevisor', $evento->evento_id)
            ->assertSet('open_modal_revisor', true)
            ->set('busqueda_usuario', $nuevoRevisor->name)
            ->assertSee($nuevoRevisor->email)
            ->call('seleccionarRevisor', $nuevoRevisor->id)
            ->assertSet('usuario_seleccionado_id', $nuevoRevisor->id)
            ->call('guardarRevisor');

        $evento->refresh();
        $this->assertSame($nuevoRevisor->id, $evento->revisor_id);
        $this->assertFalse((bool) $evento->revisado);
    }

    public function test_aprobar_instantaneamente_aprueba_solo_participantes(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);

        $participante = $this->crearParticipante();
        $disertante = $this->crearParticipante();

        EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'aprobado' => null,
        ]);

        EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $disertante->participante_id,
            'rol_id' => Rol::where('nombre', 'Disertante')->value('rol_id'),
            'aprobado' => null,
        ]);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('aprobarInstantaneamente', $evento->evento_id)
            ->assertDispatched('alert');

        $this->assertDatabaseHas('evento_participantes', [
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'aprobado' => true,
        ]);

        $this->assertDatabaseHas('evento_participantes', [
            'evento_id' => $evento->evento_id,
            'participante_id' => $disertante->participante_id,
            'aprobado' => null,
        ]);

        $this->assertTrue((bool) $evento->fresh()->revisado);
    }

    public function test_aprobar_instantaneamente_solo_administrador(): void
    {
        $this->actingAs($this->gestor);

        $evento = $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('aprobarInstantaneamente', $evento->evento_id);
    }

    public function test_quitar_aprobacion_deja_evento_por_asistencia(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);
        $evento->update(['revisado' => true]);

        $participante = $this->crearParticipante();

        EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => Rol::where('nombre', 'Participante')->value('rol_id'),
            'aprobado' => true,
        ]);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('quitarAprobacion', $evento->evento_id)
            ->assertDispatched('alert');

        $evento->refresh();
        $this->assertFalse((bool) $evento->por_aprobacion);
        $this->assertNull($evento->revisor_id);
        $this->assertFalse((bool) $evento->revisado);

        $this->assertDatabaseHas('evento_participantes', [
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'aprobado' => null,
        ]);
    }

    public function test_quitar_aprobacion_solo_administrador(): void
    {
        $this->actingAs($this->gestor);

        $evento = $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);

        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->call('quitarAprobacion', $evento->evento_id);
    }

    public function test_la_pantalla_de_eventos_monta_en_la_pestana_a_certificar(): void
    {
        $this->actingAs($this->admin);

        $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);

        Livewire::test(Eventos::class, ['tab' => 'a_certificar'])->assertOk();
    }

    public function test_menu_a_certificar_muestra_acciones_de_gestion_aunque_requiera_aprobacion(): void
    {
        $this->actingAs($this->admin);

        $this->crearEventoFinalizado(porAprobacion: true, revisorId: $this->revisor->id);

        Livewire::test(EventosFinalizados::class, ['modo' => 'a_certificar'])
            ->assertSee('Devolver a Eventos en Curso')
            ->assertSee('Editar Revisor')
            ->assertSee('Aprobación Instantánea')
            ->assertSee('Quitar Aprobación')
            ->assertSee('Requiere aprobación');
    }

    private function crearEventoFinalizado(bool $porAprobacion = false, ?int $revisorId = null): Evento
    {
        $tipoEvento = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoria de prueba']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
        ]);

        $evento = Evento::create([
            'nombre' => 'EVENTO DE PRUEBA',
            'fecha_inicio' => now()->toDateString(),
            'cupo' => 100,
            'lugar' => 'Aula 1',
            'tipo_evento_id' => $tipoEvento->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'por_aprobacion' => $porAprobacion,
            'revisor_id' => $revisorId,
            'responsable_id' => $responsable->responsable_id,
        ]);

        // 'estado' no es fillable; se asigna con el query builder.
        Evento::where('evento_id', $evento->evento_id)->update(['estado' => 'Finalizado']);

        return $evento->refresh();
    }

    private function crearParticipante(): Participante
    {
        return Participante::create([
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'dni' => (string) fake()->unique()->numberBetween(10000000, 99999999),
            'mail' => fake()->unique()->safeEmail(),
            'telefono' => '3764000000',
        ]);
    }
}
