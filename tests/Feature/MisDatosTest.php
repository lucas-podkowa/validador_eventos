<?php

namespace Tests\Feature;

use App\Livewire\MisDatos;
use App\Models\Participante;
use App\Models\SolicitudCorreccionDni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MisDatosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_participante_puede_editar_sus_datos(): void
    {
        [$user, $participante] = $this->crearParticipanteVinculado();

        Livewire::actingAs($user)
            ->test(MisDatos::class)
            ->set('nombre', 'Santiago L. D.')
            ->set('apellido', 'Becker')
            ->set('telefono', '3755719100')
            ->set('mail', 'nuevo@example.com')
            ->call('guardarDatos')
            ->assertHasNoErrors();

        $participante->refresh();
        $this->assertSame('Santiago L. D.', $participante->nombre);
        $this->assertSame('3755719100', $participante->telefono);
        $this->assertSame('nuevo@example.com', $participante->mail);
    }

    public function test_solicita_correccion_de_dni_con_imagen(): void
    {
        Storage::fake('private');
        [$user, $participante] = $this->crearParticipanteVinculado();

        Livewire::actingAs($user)
            ->test(MisDatos::class)
            ->set('nuevo_dni', '47889627')
            ->set('motivo', 'DNI mal tipeado en la inscripción')
            ->set('imagen', UploadedFile::fake()->image('dni.jpg', 400, 250))
            ->call('solicitarCorreccion')
            ->assertHasNoErrors();

        $solicitud = SolicitudCorreccionDni::firstOrFail();
        $this->assertSame($participante->participante_id, $solicitud->participante_id);
        $this->assertSame('47889627', (string) $solicitud->dni_solicitado);
        $this->assertSame(SolicitudCorreccionDni::ESTADO_PENDIENTE, $solicitud->estado);
        Storage::disk('private')->assertExists($solicitud->imagen_path);
    }

    public function test_la_imagen_es_obligatoria(): void
    {
        Storage::fake('private');
        [$user] = $this->crearParticipanteVinculado();

        Livewire::actingAs($user)
            ->test(MisDatos::class)
            ->set('nuevo_dni', '47889627')
            ->call('solicitarCorreccion')
            ->assertHasErrors(['imagen' => 'required']);

        $this->assertSame(0, SolicitudCorreccionDni::count());
    }

    public function test_un_usuario_sin_vinculo_puede_vincularse_con_dni_y_correo(): void
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);

        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::actingAs($user)
            ->test(MisDatos::class)
            ->set('vincular_dni', '30111222')
            ->call('vincularCuenta')
            ->assertHasNoErrors();

        $this->assertSame($user->id, $participante->fresh()->user_id);
        $this->assertSame('30111222', (string) $user->fresh()->dni);
    }

    public function test_no_vincula_si_el_correo_no_coincide(): void
    {
        $user = User::factory()->create(['email' => 'otro@example.com', 'dni' => null]);

        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::actingAs($user)
            ->test(MisDatos::class)
            ->set('vincular_dni', '30111222')
            ->call('vincularCuenta')
            ->assertHasErrors('vincular_dni');

        $this->assertNull($participante->fresh()->user_id);
    }

    public function test_no_roba_un_vinculo_existente(): void
    {
        $duenio = User::factory()->create(['email' => 'duenio@example.com']);

        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
            'user_id' => $duenio->id,
        ]);

        $otro = User::factory()->create(['email' => 'pepito@example.com', 'dni' => null]);

        Livewire::actingAs($otro)
            ->test(MisDatos::class)
            ->set('vincular_dni', '30111222')
            ->call('vincularCuenta')
            ->assertHasErrors('vincular_dni');

        $this->assertSame($duenio->id, $participante->fresh()->user_id);
    }

    protected function crearParticipanteVinculado(): array
    {
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => '47889628']);

        $participante = Participante::create([
            'nombre' => 'Santiago Luis Daniel',
            'apellido' => 'Becker',
            'dni' => '47889628',
            'mail' => 'pepito@example.com',
            'telefono' => '3755719156',
            'user_id' => $user->id,
        ]);

        return [$user, $participante];
    }
}
