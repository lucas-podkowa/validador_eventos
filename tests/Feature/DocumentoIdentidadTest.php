<?php

namespace Tests\Feature;

use App\Livewire\ImportarParticipantes;
use App\Livewire\RegistroEventoPublico;
use App\Models\CategoriaEvento;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlanillaInscripcion;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\User;
use App\Support\CertificadoVariables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Laravel\Jetstream\Jetstream;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class DocumentoIdentidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Rol::create(['nombre' => 'Participante']);
        SpatieRole::create(['name' => 'Invitado', 'guard_name' => 'web']);
    }

    public function test_registro_publico_acepta_pasaporte_y_guarda_el_formato_original(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', 'GM155780')
            ->set('nombre', 'John')
            ->set('apellido', 'Smith')
            ->set('mail', 'john@example.com')
            ->set('telefono', '3764000000')
            ->call('submit')
            ->assertHasNoErrors();

        $participante = Participante::where('dni', 'GM155780')->firstOrFail();

        $this->assertSame('GM155780', $participante->dni);
        $this->assertSame('GM155780', $participante->dni_norm);
    }

    public function test_registro_publico_acepta_documento_con_puntuacion(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '140.302.159-71')
            ->set('nombre', 'Joao')
            ->set('apellido', 'Silva')
            ->set('mail', 'joao@example.com')
            ->set('telefono', '3764000000')
            ->call('submit')
            ->assertHasNoErrors();

        $participante = Participante::where('dni', '140.302.159-71')->firstOrFail();

        $this->assertSame('140.302.159-71', $participante->dni);
        $this->assertSame('14030215971', $participante->dni_norm);
    }

    public function test_detecta_duplicado_aunque_cambien_mayusculas_y_puntuacion(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();
        $existente = Participante::create([
            'nombre' => 'John',
            'apellido' => 'Smith',
            'dni' => 'GM155780',
            'mail' => 'john@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', 'gm-155780')
            ->set('nombre', 'Johnny')
            ->set('apellido', 'Smith')
            ->set('mail', 'johnny@example.com')
            ->set('telefono', '3764009999')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, Participante::count());
        $this->assertSame('johnny@example.com', $existente->fresh()->mail);
    }

    public function test_detecta_duplicado_de_cpf_con_y_sin_puntos(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();
        $existente = Participante::create([
            'nombre' => 'Joao',
            'apellido' => 'Silva',
            'dni' => '140.302.159-71',
            'mail' => 'joao@example.com',
            'telefono' => '3764000000',
        ]);

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '14030215971')
            ->set('nombre', 'Joao')
            ->set('apellido', 'Silva')
            ->set('mail', 'joao2@example.com')
            ->set('telefono', '3764001111')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, Participante::count());
        $this->assertSame('14030215971', $existente->fresh()->dni_norm);
    }

    public function test_rechaza_documento_con_espacios_o_muy_corto(): void
    {
        Mail::fake();
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', 'AB')
            ->set('nombre', 'John')
            ->set('apellido', 'Smith')
            ->set('mail', 'john@example.com')
            ->set('telefono', '3764000000')
            ->call('submit')
            ->assertHasErrors(['dni']);

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => 'Curso',
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', 'GM 155 780')
            ->set('nombre', 'John')
            ->set('apellido', 'Smith')
            ->set('mail', 'john@example.com')
            ->set('telefono', '3764000000')
            ->call('submit')
            ->assertHasErrors(['dni']);
    }

    public function test_registro_de_usuario_con_pasaporte_vincula_participante(): void
    {
        $participante = Participante::create([
            'nombre' => 'John',
            'apellido' => 'Smith',
            'dni' => 'GM155780',
            'mail' => 'passport@example.com',
            'telefono' => '3764000000',
        ]);

        $this->post('/register', [
            'name' => 'John Smith',
            'email' => 'passport@example.com',
            'dni' => 'GM155780',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ])->assertRedirect();

        $user = User::where('email', 'passport@example.com')->firstOrFail();

        $this->assertSame('GM155780', $user->dni);
        $this->assertSame('GM155780', $user->dni_norm);
        $this->assertSame($user->id, $participante->fresh()->user_id);
    }

    public function test_las_variables_del_certificado_conservan_el_formato_original(): void
    {
        $evento = $this->crearEventoConPlanilla();
        $participante = Participante::create([
            'nombre' => 'Joao',
            'apellido' => 'Silva',
            'dni' => '140.302.159-71',
            'mail' => 'joao@example.com',
            'telefono' => '3764000000',
        ]);

        $variables = CertificadoVariables::paraEvento($evento, $participante);

        $this->assertSame('140.302.159-71', $variables['dni']);
    }

    public function test_importacion_acepta_encabezado_documento_y_pasaporte(): void
    {
        $evento = $this->crearEventoConPlanilla();

        $csv = "documento,apellido,nombre,mail,telefono\n";
        $csv .= "GM155780,Smith,John,john@example.com,3764000000\n";

        $archivo = UploadedFile::fake()->createWithContent('participantes.csv', $csv);

        Livewire::test(ImportarParticipantes::class, ['evento_id' => $evento->evento_id])
            ->set('archivo', $archivo)
            ->call('importar')
            ->assertSet('errores', 0)
            ->assertSet('exitosos', 1);

        $this->assertDatabaseHas('participante', [
            'dni' => 'GM155780',
            'dni_norm' => 'GM155780',
        ]);
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
