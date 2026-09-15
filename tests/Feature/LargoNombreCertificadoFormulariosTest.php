<?php

namespace Tests\Feature;

use App\Livewire\ImportarParticipantes;
use App\Livewire\Participantes;
use App\Livewire\RegistroEventoPublico;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlanillaInscripcion;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class LargoNombreCertificadoFormulariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Rol::create(['nombre' => 'Participante']);
    }

    public function test_inscripcion_publica_rechaza_nombres_demasiado_largos(): void
    {
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '30111222')
            ->set('nombre', 'Angel Ezequiel Cuello Cardozo')
            ->set('apellido', 'Cuella')
            ->set('mail', 'largo@example.com')
            ->set('telefono', '3764000000')
            ->call('submit')
            ->assertHasErrors(['apellido']);

        $this->assertDatabaseMissing('participante', ['dni' => '30111222']);
    }

    public function test_inscripcion_publica_permite_nombres_dentro_del_limite(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '30111222')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Perez')
            ->set('mail', 'corto@example.com')
            ->set('telefono', '3764000000')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('participante', ['dni' => '30111222']);
    }

    public function test_edicion_admin_rechaza_nombres_demasiado_largos(): void
    {
        $participante = $this->crearParticipante('Perez', 'Juan', '30111222', 'pepito@example.com');

        Livewire::test(Participantes::class)
            ->call('edit', $participante->participante_id)
            ->set('nombre', 'Angel Ezequiel Cuello Cardozo')
            ->set('apellido', 'Cuella')
            ->call('update')
            ->assertHasErrors(['apellido']);

        $this->assertSame('Perez', $participante->fresh()->apellido);
    }

    public function test_importacion_omite_filas_con_nombres_demasiado_largos(): void
    {
        $evento = $this->crearEventoConPlanilla();

        $csv = "dni,apellido,nombre,mail,telefono\n";
        $csv .= "30111222,Cuella,Angel Ezequiel Cuello Cardozo,largo@example.com,3764000000\n";
        $csv .= "30111223,Perez,Juan,corto@example.com,3764000001\n";

        $archivo = UploadedFile::fake()->createWithContent('participantes.csv', $csv);

        Livewire::test(ImportarParticipantes::class, ['evento_id' => $evento->evento_id])
            ->set('archivo', $archivo)
            ->call('importar')
            ->assertSet('errores', 1)
            ->assertSet('exitosos', 1)
            ->assertSee('superan los 36 caracteres');

        $this->assertDatabaseMissing('participante', ['dni' => '30111222']);
        $this->assertDatabaseHas('participante', ['dni' => '30111223']);
    }

    public function test_el_certificado_abrevia_nombres_largos_al_renderizar(): void
    {
        $html = view('certificado', [
            'apellido' => 'Cuella',
            'nombre' => 'Angel Ezequiel Cuello Cardozo',
            'dni' => '30111222',
            'qr' => '',
            'background' => null,
        ])->render();

        $this->assertStringContainsString('Cuella, Angel Ezequiel C. Cardozo', $html);
    }

    public function test_nombre_con_punto_es_valido_en_inscripcion_publica(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '30111224')
            ->set('nombre', 'Elena V.')
            ->set('apellido', 'Gomez')
            ->set('mail', 'elena@example.com')
            ->set('telefono', '3764000002')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('participante', ['dni' => '30111224']);
        $this->assertSame('Elena V.', Participante::where('dni', '30111224')->first()->nombre);
    }

    public function test_nombre_con_punto_es_valido_en_edicion_admin(): void
    {
        $participante = $this->crearParticipante('Gomez', 'Elena', '30111225', 'elena2@example.com');

        Livewire::test(Participantes::class)
            ->call('edit', $participante->participante_id)
            ->set('nombre', 'Elena V.')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Elena V.', $participante->fresh()->nombre);
    }

    public function test_edicion_admin_guarda_en_title_case(): void
    {
        $participante = $this->crearParticipante('GOMEZ', 'ELENA', '30111226', 'tc2@example.com');

        Livewire::test(Participantes::class)
            ->call('edit', $participante->participante_id)
            ->set('nombre', 'ELENA DEL CARMEN')
            ->set('apellido', 'GOMEZ')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('Elena Del Carmen', $participante->fresh()->getRawOriginal('nombre'));
        $this->assertSame('Gomez', $participante->fresh()->getRawOriginal('apellido'));
    }

    protected function crearEventoConPlanilla(): Evento
    {
        $tipo = TipoEvento::create(['nombre' => 'Curso']);
        $categoria = CategoriaEvento::create(['nombre' => 'Categoría Test']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => '11111111',
        ]);

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
        $evento->estado = 'En Curso';
        $evento->save();

        PlanillaInscripcion::create([
            'apertura' => now()->subDay(),
            'cierre' => now()->addMonth(),
            'evento_id' => $evento->evento_id,
        ]);

        return $evento;
    }

    protected function crearParticipante(string $apellido, string $nombre, string $dni, string $mail): Participante
    {
        return Participante::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'mail' => $mail,
            'telefono' => '3764000000',
        ]);
    }
}
