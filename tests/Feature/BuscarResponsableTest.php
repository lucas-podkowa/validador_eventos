<?php

namespace Tests\Feature;

use App\Livewire\CrearEvento;
use App\Models\Responsable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BuscarResponsableTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Administrador', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');
    }

    public function test_busca_responsable_por_dni(): void
    {
        $this->actingAs($this->admin);

        $responsable = $this->crearResponsable('30123456', 'María', 'Gómez');

        Livewire::test(CrearEvento::class)
            ->set('modal_responsable_busqueda', '30123456')
            ->call('buscarResponsable')
            ->assertHasNoErrors()
            ->assertSet('modal_responsable_encontrado', true)
            ->assertSet('modal_responsable_id', $responsable->responsable_id)
            ->assertSet('modal_responsable_nombre', 'María')
            ->assertSet('modal_responsable_apellido', 'Gómez');
    }

    public function test_busca_responsable_por_apellido_ignorando_tildes_y_mayusculas(): void
    {
        $this->actingAs($this->admin);

        $responsable = $this->crearResponsable('30123456', 'María', 'Gómez');

        Livewire::test(CrearEvento::class)
            ->set('modal_responsable_busqueda', 'gomez')
            ->call('buscarResponsable')
            ->assertHasNoErrors()
            ->assertSet('modal_responsable_encontrado', true)
            ->assertSet('modal_responsable_id', $responsable->responsable_id);
    }

    public function test_busca_responsable_por_nombre(): void
    {
        $this->actingAs($this->admin);

        $responsable = $this->crearResponsable('30123456', 'María', 'Gómez');

        Livewire::test(CrearEvento::class)
            ->set('modal_responsable_busqueda', 'maria')
            ->call('buscarResponsable')
            ->assertHasNoErrors()
            ->assertSet('modal_responsable_encontrado', true)
            ->assertSet('modal_responsable_id', $responsable->responsable_id);
    }

    public function test_multiples_coincidencias_requieren_seleccion(): void
    {
        $this->actingAs($this->admin);

        $primero = $this->crearResponsable('30123456', 'María', 'Gómez');
        $segundo = $this->crearResponsable('30987654', 'Juan', 'Gómez');

        $componente = Livewire::test(CrearEvento::class)
            ->set('modal_responsable_busqueda', 'gomez')
            ->call('buscarResponsable')
            ->assertHasNoErrors()
            ->assertSet('modal_responsable_encontrado', false)
            ->assertCount('modal_responsable_resultados', 2)
            ->call('seleccionarResponsable')
            ->assertHasErrors(['modal_responsable_busqueda'])
            ->call('seleccionarResultadoResponsable', $segundo->responsable_id)
            ->assertSet('modal_responsable_encontrado', true)
            ->assertSet('modal_responsable_id', $segundo->responsable_id);

        $this->assertNotEquals($primero->responsable_id, $componente->get('modal_responsable_id'));
    }

    public function test_registra_responsable_nuevo_cuando_no_hay_coincidencias(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CrearEvento::class)
            ->set('modal_responsable_busqueda', 'persona inexistente')
            ->call('buscarResponsable')
            ->assertHasNoErrors()
            ->assertSet('modal_responsable_encontrado', false)
            ->assertCount('modal_responsable_resultados', 0)
            ->set('modal_responsable_nombre', 'Carlos')
            ->set('modal_responsable_apellido', 'Pérez')
            ->set('modal_responsable_dni', '31111222')
            ->call('seleccionarResponsable')
            ->assertHasNoErrors()
            ->assertSet('modal_responsable_encontrado', true);

        $this->assertDatabaseHas('responsable', [
            'dni' => 31111222,
            'nombre' => 'CARLOS',
            'apellido' => 'PÉREZ',
        ]);
    }

    private function crearResponsable(string $dni, string $nombre, string $apellido): Responsable
    {
        return Responsable::create([
            'dni' => $dni,
            'nombre' => $nombre,
            'apellido' => $apellido,
        ]);
    }
}
