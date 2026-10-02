<?php

namespace Tests\Feature;

use App\Livewire\Admin\Emisiones;
use App\Models\ApiCliente;
use App\Models\Contexto;
use App\Models\Emision;
use App\Models\Participante;
use App\Models\TipoReconocimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;
use Tests\TestCase;

class AdminEmisionesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected int $tipoTutor;

    protected Contexto $contexto;

    protected function setUp(): void
    {
        parent::setUp();

        PermissionRole::create(['name' => 'Administrador', 'guard_name' => 'web']);
        Permission::create(['name' => 'crear_eventos', 'guard_name' => 'web'])->syncRoles(['Administrador']);

        $this->admin = User::factory()->create(['email_verified_at' => now()]);
        $this->admin->assignRole('Administrador');

        $this->contexto = Contexto::create(['nombre' => 'PPS', 'tipo' => 'programa', 'activo' => true]);
        $this->tipoTutor = TipoReconocimiento::where('slug', 'tutor')->value('tipo_reconocimiento_id');
    }

    public function test_admin_accede_al_listado_de_certificados(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.certificados'))
            ->assertOk()
            ->assertSee('Certificados emitidos');
    }

    public function test_busca_certificados_internos_y_externos_de_una_persona(): void
    {
        $this->actingAs($this->admin);

        $participante = $this->crearParticipante('Podkowa', 'Lucas', '30111222', 'lucas@example.test');
        $cliente = ApiCliente::create(['nombre' => 'PPS', 'activo' => true]);

        $this->crearEmision($participante);

        $this->crearEmision($participante, [
            'alcance' => 'programa',
            'api_cliente_id' => $cliente->api_cliente_id,
            'external_ref' => 'PPS-TUT-1',
            'match_estado' => Emision::MATCH_AUTO,
        ]);

        Livewire::test(Emisiones::class)
            ->set('search', 'Podkowa')
            ->assertSee('Podkowa, Lucas')
            ->assertSee('Interno')
            ->assertSee('API · PPS')
            ->assertViewHas('emisiones', fn ($emisiones) => $emisiones->total() === 2);

        Livewire::test(Emisiones::class)
            ->set('search', 'Podkowa')
            ->set('origen', 'api')
            ->assertViewHas('emisiones', fn ($emisiones) => $emisiones->total() === 1);
    }

    public function test_ordena_por_columna_receptor(): void
    {
        $this->actingAs($this->admin);

        $this->crearEmision($this->crearParticipante('Zapata', 'Ana', '30111000', 'zapata@example.test'));
        $this->crearEmision($this->crearParticipante('Alvarez', 'Beto', '30111001', 'alvarez@example.test'));

        Livewire::test(Emisiones::class)
            ->call('order', 'receptor')
            ->assertViewHas('emisiones', fn ($emisiones) => $emisiones->first()->participante->apellido === 'Alvarez')
            ->call('order', 'receptor')
            ->assertViewHas('emisiones', fn ($emisiones) => $emisiones->first()->participante->apellido === 'Zapata');
    }

    public function test_pagina_los_resultados(): void
    {
        $this->actingAs($this->admin);

        for ($i = 0; $i < 25; $i++) {
            $dni = (string) (30120000 + $i);
            $this->crearEmision($this->crearParticipante('Perez', 'Persona'.$i, $dni, "persona{$i}@example.test"));
        }

        Livewire::test(Emisiones::class)
            ->assertViewHas('emisiones', fn ($emisiones) => $emisiones->total() === 25 && $emisiones->count() === 20);
    }

    private function crearParticipante(string $apellido, string $nombre, string $dni, string $mail): Participante
    {
        return Participante::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'mail' => $mail,
            'telefono' => '3764000000',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function crearEmision(Participante $participante, array $overrides = []): Emision
    {
        return Emision::create(array_merge([
            'participante_id' => $participante->participante_id,
            'tipo_reconocimiento_id' => $this->tipoTutor,
            'origen_type' => Contexto::class,
            'origen_id' => (string) $this->contexto->contexto_id,
            'alcance' => 'contexto',
            'estado' => Emision::ESTADO_EMITIDO,
        ], $overrides));
    }
}
