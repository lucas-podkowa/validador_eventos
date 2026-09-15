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
