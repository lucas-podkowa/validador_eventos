<?php

namespace Tests\Feature;

use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class VincularParticipantesUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SpatieRole::create(['name' => 'Invitado', 'guard_name' => 'web']);
    }

    public function test_dry_run_no_modifica_la_base(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');

        $this->artisan('participantes:vincular-usuarios', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull($participante->fresh()->user_id);
        $this->assertNull($user->fresh()->dni);
    }

    public function test_vincula_por_correo_y_completa_dni(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');

        $this->artisan('participantes:vincular-usuarios')
            ->assertExitCode(0);

        $this->assertSame($user->id, $participante->fresh()->user_id);
        $this->assertSame('11111111', (string) $user->fresh()->dni);
    }

    public function test_es_idempotente(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');

        $this->artisan('participantes:vincular-usuarios')->assertExitCode(0);
        $this->artisan('participantes:vincular-usuarios')->assertExitCode(0);

        $this->assertSame($user->id, $participante->fresh()->user_id);
        $this->assertSame(1, Participante::where('user_id', $user->id)->count());
    }

    public function test_omite_usuario_ya_vinculado_a_otro_participante(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);

        $otro = $this->crearParticipante('22222222', 'otro@example.com');
        $otro->user_id = $user->id;
        $otro->save();

        $participante = $this->crearParticipante('11111111', 'pepito@example.com');

        $this->artisan('participantes:vincular-usuarios')->assertExitCode(0);

        $this->assertNull($participante->fresh()->user_id);
    }

    public function test_solo_invitados_filtra_usuarios_sin_rol(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');

        $this->artisan('participantes:vincular-usuarios', ['--solo-invitados' => true])->assertExitCode(0);
        $this->assertNull($participante->fresh()->user_id);

        $user->assignRole('Invitado');

        $this->artisan('participantes:vincular-usuarios', ['--solo-invitados' => true])->assertExitCode(0);
        $this->assertSame($user->id, $participante->fresh()->user_id);
    }

    public function test_omite_cuando_el_dni_no_coincide(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => '99999999']);
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');

        $this->artisan('participantes:vincular-usuarios')->assertExitCode(0);

        $this->assertNull($participante->fresh()->user_id);
        $this->assertSame('99999999', (string) $user->fresh()->dni);
    }

    protected function crearParticipante(string $dni, string $mail): Participante
    {
        return Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => $dni,
            'mail' => $mail,
            'telefono' => '3764000000',
        ]);
    }
}
