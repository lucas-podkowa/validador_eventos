<?php

namespace Tests\Feature;

use App\Livewire\MisCertificados;
use App\Models\Carrera;
use App\Models\CategoriaEvento;
use App\Models\CertificadoEmitido;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\TituloIntermedio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MisCertificadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    public function test_muestra_los_certificados_del_participante_vinculado(): void
    {
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => '11111111']);
        $participante->user_id = $user->id;
        $participante->save();

        $pathEvento = 'certificados/2026/Curso/pepito.pdf';
        Storage::disk('private')->put($pathEvento, 'PDF-EVENTO');

        $this->crearEventoParticipante($participante, $pathEvento, 'Evento de Prueba');

        $carrera = Carrera::create(['nombre' => 'Ingeniería', 'codigo' => 'ING', 'activa' => true]);
        $titulo = TituloIntermedio::create(['carrera_id' => $carrera->id, 'nombre' => 'Título Intermedio', 'activo' => true]);
        $pathTitulo = 'certificados_titulos/2026/ING/1/pepito.pdf';
        Storage::disk('private')->put($pathTitulo, 'PDF-TITULO');

        CertificadoEmitido::create([
            'participante_id' => $participante->participante_id,
            'titulo_intermedio_id' => $titulo->id,
            'certificado_path' => $pathTitulo,
            'anulado' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(MisCertificados::class)
            ->assertSee('Evento de Prueba')
            ->assertSee('Título Intermedio')
            ->assertDontSee('no está vinculada');
    }

    public function test_descarga_su_propio_certificado_de_evento(): void
    {
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');
        $user = User::factory()->create(['email' => 'pepito@example.com', 'dni' => '11111111']);
        $participante->user_id = $user->id;
        $participante->save();

        $path = 'certificados/2026/Curso/pepito.pdf';
        Storage::disk('private')->put($path, 'PDF-EVENTO');
        $ep = $this->crearEventoParticipante($participante, $path, 'Evento de Prueba');

        $this->actingAs($user)
            ->get(route('mis_certificados.evento', $ep))
            ->assertOk();
    }

    public function test_no_puede_descargar_certificados_de_otro_participante(): void
    {
        $participante = $this->crearParticipante('11111111', 'pepito@example.com');
        $otro = User::factory()->create(['email' => 'otro@example.com', 'dni' => '22222222']);

        $path = 'certificados/2026/Curso/pepito.pdf';
        Storage::disk('private')->put($path, 'PDF-EVENTO');
        $ep = $this->crearEventoParticipante($participante, $path, 'Evento de Prueba');

        $this->actingAs($otro)
            ->get(route('mis_certificados.evento', $ep))
            ->assertForbidden();
    }

    public function test_muestra_estado_vacio_si_la_cuenta_no_esta_vinculada(): void
    {
        $user = User::factory()->create(['email' => 'sinvincular@example.com']);

        $this->actingAs($user);

        Livewire::test(MisCertificados::class)
            ->assertSee('no está vinculada');
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

    protected function crearEventoParticipante(Participante $participante, string $path, string $nombreEvento): EventoParticipante
    {
        TipoEvento::firstOrCreate(['nombre' => 'Curso']);
        CategoriaEvento::firstOrCreate(['nombre' => 'Categoría Test']);
        $responsable = Responsable::firstOrCreate(
            ['dni' => '11111111'],
            ['nombre' => 'RESPONSABLE', 'apellido' => 'TEST']
        );
        $rol = Rol::firstOrCreate(['nombre' => 'Participante']);

        $evento = Evento::create([
            'nombre' => $nombreEvento,
            'lugar' => 'Aula Magna',
            'fecha_inicio' => now()->subDays(5),
            'tipo_evento_id' => TipoEvento::firstOrFail()->tipo_evento_id,
            'categoria_id' => CategoriaEvento::firstOrFail()->categoria_id,
            'cupo' => null,
            'por_aprobacion' => false,
            'arancel' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);
        $evento->estado = 'Finalizado';
        $evento->save();

        return EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => $rol->rol_id,
            'certificado_path' => $path,
        ]);
    }
}
