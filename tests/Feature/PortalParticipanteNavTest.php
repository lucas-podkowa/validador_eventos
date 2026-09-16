<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\NavigationMenu;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class PortalParticipanteNavTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Administrador', 'Gestor', 'Revisor', 'Colaborador', 'Invitado'] as $rol) {
            SpatieRole::create(['name' => $rol, 'guard_name' => 'web']);
        }
    }

    public function test_un_administrador_ve_todas_las_secciones(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrador');

        Livewire::actingAs($user)
            ->test(NavigationMenu::class)
            ->assertSee('Eventos')
            ->assertSee('Administración')
            ->assertSee('Configuración')
            ->assertSee('Participantes')
            ->assertSee('Mis Certificados')
            ->assertSee('Mis Datos');
    }

    public function test_un_gestor_ve_eventos_y_administracion_pero_no_configuracion(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Gestor');

        Livewire::actingAs($user)
            ->test(NavigationMenu::class)
            ->assertSee('Eventos')
            ->assertSee('Administración')
            ->assertDontSee('Configuración')
            ->assertSee('Mis Certificados');
    }

    public function test_un_invitado_solo_ve_la_seccion_participantes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Invitado');

        Livewire::actingAs($user)
            ->test(NavigationMenu::class)
            ->assertSee('Participantes')
            ->assertSee('Mis Certificados')
            ->assertSee('Mis Datos')
            ->assertDontSee('Administración')
            ->assertDontSee('Configuración');
    }
}
