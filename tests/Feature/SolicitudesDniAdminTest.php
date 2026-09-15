<?php

namespace Tests\Feature;

use App\Livewire\Admin\SolicitudesDni;
use App\Models\Participante;
use App\Models\SolicitudCorreccionDni;
use App\Models\User;
use App\Services\ReemitirCertificadosParticipante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class SolicitudesDniAdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        SpatieRole::create(['name' => 'Administrador', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->mock(ReemitirCertificadosParticipante::class, function ($mock) {
            $mock->shouldReceive('participante')->andReturn(['eventos' => 0, 'titulos' => 0, 'omitidos' => 0]);
        });
    }

    public function test_aprobar_actualiza_el_dni_y_la_solicitud(): void
    {
        $participante = $this->crearParticipante('47889628');
        $solicitud = $this->crearSolicitud($participante, '47889627');

        Livewire::actingAs($this->admin)
            ->test(SolicitudesDni::class)
            ->call('ver', $solicitud->id)
            ->call('aprobar')
            ->assertHasNoErrors();

        $this->assertSame('47889627', (string) $participante->fresh()->dni);
        $this->assertSame(SolicitudCorreccionDni::ESTADO_APROBADA, $solicitud->fresh()->estado);
        $this->assertSame($this->admin->id, $solicitud->fresh()->revisado_por);
    }

    public function test_no_aprueba_si_el_dni_ya_pertenece_a_otro_participante(): void
    {
        $this->crearParticipante('47889627');
        $participante = $this->crearParticipante('47889628');
        $solicitud = $this->crearSolicitud($participante, '47889627');

        Livewire::actingAs($this->admin)
            ->test(SolicitudesDni::class)
            ->call('ver', $solicitud->id)
            ->call('aprobar')
            ->assertHasErrors(['observacion']);

        $this->assertSame('47889628', (string) $participante->fresh()->dni);
        $this->assertSame(SolicitudCorreccionDni::ESTADO_PENDIENTE, $solicitud->fresh()->estado);
    }

    public function test_rechazar_marca_la_solicitud(): void
    {
        $participante = $this->crearParticipante('47889628');
        $solicitud = $this->crearSolicitud($participante, '47889627');

        Livewire::actingAs($this->admin)
            ->test(SolicitudesDni::class)
            ->call('ver', $solicitud->id)
            ->set('observacion', 'La imagen no es legible')
            ->call('rechazar');

        $this->assertSame(SolicitudCorreccionDni::ESTADO_RECHAZADA, $solicitud->fresh()->estado);
        $this->assertSame('47889628', (string) $participante->fresh()->dni);
    }

    protected function crearParticipante(string $dni): Participante
    {
        return Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => $dni,
            'mail' => "p{$dni}@example.com",
            'telefono' => '3764000000',
        ]);
    }

    protected function crearSolicitud(Participante $participante, string $dniSolicitado): SolicitudCorreccionDni
    {
        return SolicitudCorreccionDni::create([
            'participante_id' => $participante->participante_id,
            'dni_actual' => $participante->dni,
            'dni_solicitado' => $dniSolicitado,
            'imagen_path' => 'solicitudes_dni/test.jpg',
            'imagen_mime' => 'image/jpeg',
            'imagen_original' => 'dni.jpg',
            'estado' => SolicitudCorreccionDni::ESTADO_PENDIENTE,
        ]);
    }
}
