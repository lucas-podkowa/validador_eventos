<?php

namespace Tests\Feature;

use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\EventoParticipante;
use App\Models\Participante;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Services\ReemitirCertificadosParticipante;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NormalizarParticipantesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_comando_rellena_las_columnas_normalizadas(): void
    {
        $participante = Participante::create([
            'nombre' => 'Santiago Luis Daniel',
            'apellido' => 'Becker',
            'dni' => '47889628',
            'mail' => 'becker@example.com',
            'telefono' => '3755719156',
        ]);

        DB::table('participante')->where('participante_id', $participante->participante_id)->update([
            'nombre' => 'SANTIAGO LUIS DANIEL',
            'apellido' => 'BECKER',
            'nombre_norm' => null,
            'apellido_norm' => null,
            'telefono_norm' => null,
            'mail_norm' => null,
        ]);

        $this->artisan('participantes:normalizar')->assertExitCode(0);

        $participante->refresh();
        $this->assertSame('santiago luis daniel', $participante->nombre_norm);
        $this->assertSame('becker', $participante->apellido_norm);
        $this->assertSame('3755719156', $participante->telefono_norm);
        $this->assertSame('becker@example.com', $participante->mail_norm);
        $this->assertSame('Santiago Luis Daniel', $participante->getRawOriginal('nombre'));
        $this->assertSame('Becker', $participante->getRawOriginal('apellido'));
    }

    public function test_el_modelo_guarda_nombres_en_title_case(): void
    {
        $participante = Participante::create([
            'nombre' => 'JUAN CARLOS',
            'apellido' => 'DE LA CRUZ',
            'dni' => '30111229',
            'mail' => 'tc@example.com',
            'telefono' => '3764000000',
        ]);

        $this->assertSame('Juan Carlos', $participante->getRawOriginal('nombre'));
        $this->assertSame('De La Cruz', $participante->getRawOriginal('apellido'));
    }

    public function test_reemite_certificados_por_dni(): void
    {
        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        $this->mock(ReemitirCertificadosParticipante::class, function ($mock) {
            $mock->shouldReceive('participante')->once()->andReturn(['eventos' => 2, 'titulos' => 1, 'omitidos' => 0]);
        });

        $this->artisan('participantes:reemitir-certificados', ['dni' => [$participante->dni]])
            ->expectsOutputToContain('eventos reemitidos 2')
            ->assertExitCode(0);
    }

    public function test_reemision_omite_cuando_no_hay_plantilla(): void
    {
        $tipo = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoría Test']);
        $responsable = Responsable::create(['nombre' => 'Ana', 'apellido' => 'Perez', 'dni' => '11111111']);
        $rol = Rol::create(['nombre' => 'Participante']);

        $evento = Evento::create([
            'nombre' => 'Evento de Prueba',
            'lugar' => 'Aula Magna',
            'fecha_inicio' => now()->addDay(),
            'tipo_evento_id' => $tipo->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'cupo' => null,
            'por_aprobacion' => false,
            'arancel' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);

        $participante = Participante::create([
            'nombre' => 'Pepito',
            'apellido' => 'Perez',
            'dni' => '30111222',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        EventoParticipante::create([
            'evento_id' => $evento->evento_id,
            'participante_id' => $participante->participante_id,
            'rol_id' => $rol->rol_id,
            'certificado_path' => 'certificados/2026/Curso/Evento de Prueba/Perez_Pepito (30111222).pdf',
        ]);

        $resultado = app(ReemitirCertificadosParticipante::class)->participante($participante);

        $this->assertSame(1, $resultado['omitidos']);
        $this->assertSame(0, $resultado['eventos']);
    }
}
