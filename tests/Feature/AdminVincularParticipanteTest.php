<?php

namespace Tests\Feature;

use App\Livewire\Admin\Usuarios;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class AdminVincularParticipanteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SpatieRole::create(['name' => 'Invitado', 'guard_name' => 'web']);
        SpatieRole::create(['name' => 'Administrador', 'guard_name' => 'web']);
    }

    public function test_admin_puede_vincular_un_participante_a_un_usuario(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);
        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '11111111',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::actingAs($admin)
            ->test(Usuarios::class)
            ->call('editar', $user->id)
            ->assertSet('dni', null)
            ->set('busqueda_participante', '11111111')
            ->call('buscarParticipante')
            ->assertSet('participante_candidato.participante_id', $participante->participante_id)
            ->call('vincularParticipante')
            ->assertSet('participante_vinculado.participante_id', $participante->participante_id);

        $this->assertSame($user->id, $participante->fresh()->user_id);
    }

    public function test_admin_puede_desvincular_un_participante(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => '11111111']);
        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '11111111',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($admin)
            ->test(Usuarios::class)
            ->call('editar', $user->id)
            ->assertSet('participante_vinculado.participante_id', $participante->participante_id)
            ->call('desvincularParticipante')
            ->assertSet('participante_vinculado', null);

        $this->assertNull($participante->fresh()->user_id);
    }
}
