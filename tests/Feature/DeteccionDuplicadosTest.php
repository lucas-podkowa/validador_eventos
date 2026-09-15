<?php

namespace Tests\Feature;

use App\Livewire\ImportarParticipantes;
use App\Livewire\RegistroEventoPublico;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\InscripcionParticipante;
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

class DeteccionDuplicadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Rol::create(['nombre' => 'Participante']);
    }

    public function test_inscripcion_publica_vincula_al_existente_si_es_la_misma_persona(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        $existente = Participante::create([
            'nombre' => 'Santiago Luis Daniel',
            'apellido' => 'Becker',
            'dni' => '47889627',
            'mail' => 'existente@example.com',
            'telefono' => '3755719156',
        ]);

        $component = Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '47889628')
            ->set('nombre', 'Santiago Luis Daniel')
            ->set('apellido', 'Becker')
            ->set('mail', 'nuevo@example.com')
            ->set('telefono', '3755719156')
            ->call('submit')
            ->assertDispatched('oops')
            ->assertSet('similar.participante_id', $existente->participante_id);

        $this->assertDatabaseMissing('participante', ['dni' => '47889628']);

        $component
            ->call('usarSimilar')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('participante', ['dni' => '47889628']);
        $this->assertSame(1, InscripcionParticipante::where('participante_id', $existente->participante_id)->count());
        $this->assertDatabaseHas('duplicado_revision', ['decision' => 'misma_persona', 'origen' => 'publico']);
    }

    public function test_inscripcion_publica_crea_nuevo_y_marca_revision_si_es_otra_persona(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        Participante::create([
            'nombre' => 'Santiago Luis Daniel',
            'apellido' => 'Becker',
            'dni' => '47889627',
            'mail' => 'existente@example.com',
            'telefono' => '3755719156',
        ]);

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '47889628')
            ->set('nombre', 'Santiago Luis Daniel')
            ->set('apellido', 'Becker')
            ->set('mail', 'nuevo@example.com')
            ->set('telefono', '3755719156')
            ->call('submit')
            ->assertDispatched('oops')
            ->call('crearNuevo')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('participante', ['dni' => '47889628']);
        $this->assertDatabaseHas('duplicado_revision', ['decision' => 'otra_persona', 'origen' => 'publico']);
    }

    public function test_importacion_omite_filas_con_posible_duplicado(): void
    {
        $evento = $this->crearEventoConPlanilla();

        Participante::create([
            'nombre' => 'Santiago Luis Daniel',
            'apellido' => 'Becker',
            'dni' => '47889627',
            'mail' => 'existente@example.com',
            'telefono' => '3755719156',
        ]);

        $csv = "dni,apellido,nombre,mail,telefono\n";
        $csv .= "47889628,Becker,Santiago Luis Daniel,nuevo@example.com,3755719156\n";
        $csv .= "30111223,Perez,Juan,corto@example.com,3764000001\n";

        $archivo = UploadedFile::fake()->createWithContent('participantes.csv', $csv);

        Livewire::test(ImportarParticipantes::class, ['evento_id' => $evento->evento_id])
            ->set('archivo', $archivo)
            ->call('importar')
            ->assertSet('errores', 1)
            ->assertSet('exitosos', 1)
            ->assertSee('Posible duplicado de DNI 47889627');

        $this->assertDatabaseMissing('participante', ['dni' => '47889628']);
        $this->assertDatabaseHas('participante', ['dni' => '30111223']);
        $this->assertDatabaseHas('duplicado_revision', ['decision' => 'otra_persona', 'origen' => 'importacion']);
    }

    public function test_inscripcion_publica_detecta_por_mail_aunque_cambien_los_demas_datos(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        $existente = Participante::create([
            'nombre' => 'Otra Persona',
            'apellido' => 'Distinto',
            'dni' => '11111111',
            'mail' => 'pepito@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '22222222')
            ->set('nombre', 'Nuevo Nombre')
            ->set('apellido', 'Nuevo Apellido')
            ->set('mail', 'Pepito@Example.com')
            ->set('telefono', '3764999999')
            ->call('submit')
            ->assertDispatched('oops')
            ->assertSet('similar.participante_id', $existente->participante_id);

        $this->assertDatabaseMissing('participante', ['dni' => '22222222']);
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
}
