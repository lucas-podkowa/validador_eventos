<?php

namespace Tests\Feature;

use App\Livewire\CrearEvento;
use App\Livewire\RegistroEventoPublico;
use App\Models\CategoriaEvento;
use App\Models\Destinatario;
use App\Models\DocumentoPresentado;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlanillaInscripcion;
use App\Models\RequisitoDocumentacion;
use App\Models\Responsable;
use App\Models\Rol;
use App\Models\TipoEvento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RequisitosDocumentacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Destinatario $destinatario;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $roleGestor = Role::create(['name' => 'Gestor', 'guard_name' => 'web']);

        $permEventos = Permission::create(['name' => 'eventos', 'guard_name' => 'web']);
        $permCrear = Permission::create(['name' => 'crear_eventos', 'guard_name' => 'web']);
        $permVerParticipantes = Permission::create(['name' => 'ver_participantes', 'guard_name' => 'web']);

        $roleAdmin->syncPermissions([$permEventos, $permCrear, $permVerParticipantes]);
        $roleGestor->syncPermissions([$permEventos]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        TipoEvento::create(['nombre' => 'Curso']);
        CategoriaEvento::create(['nombre' => 'Categoría Test']);

        $this->destinatario = Destinatario::where('nombre', 'Público General')->first()
            ?? Destinatario::create(['nombre' => 'Público General', 'activo' => true]);

        Rol::create(['nombre' => 'Participante']);
    }

    public function test_admin_puede_crear_evento_gratuito_con_destinatarios_y_requisitos(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CrearEvento::class)
            ->set('categoria_id', CategoriaEvento::first()->categoria_id)
            ->set('tipo_evento_id', TipoEvento::first()->tipo_evento_id)
            ->set('nombre_evento', 'Evento Gratuito con Requisitos')
            ->set('fecha_inicio', now()->addDay()->format('Y-m-d'))
            ->set('lugar_evento', 'Aula 1')
            ->set('responsable_id', $this->crearResponsable()->responsable_id)
            ->set('arancel', false)
            ->set('destinatarioSeleccionado', [(string) $this->destinatario->destinatario_id])
            ->set('destinatarioRequisitos', [
                $this->destinatario->destinatario_id => [
                    ['requisito_id' => null, 'titulo' => 'Certificado de Convivencia'],
                    ['requisito_id' => null, 'titulo' => 'Boleta de Servicio'],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $evento = Evento::where('nombre', 'EVENTO GRATUITO CON REQUISITOS')->first();
        $this->assertNotNull($evento);
        $this->assertFalse($evento->arancel);
        $this->assertTrue($evento->destinatarios->contains($this->destinatario));

        $requisitos = $evento->requisitos;
        $this->assertCount(2, $requisitos);
        $this->assertEquals('Certificado de Convivencia', $requisitos[0]->titulo);
        $this->assertEquals('Boleta de Servicio', $requisitos[1]->titulo);
        $this->assertEquals($this->destinatario->destinatario_id, $requisitos[0]->destinatario_id);
    }

    public function test_admin_puede_crear_evento_arancelado_con_requisitos(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CrearEvento::class)
            ->set('categoria_id', CategoriaEvento::first()->categoria_id)
            ->set('tipo_evento_id', TipoEvento::first()->tipo_evento_id)
            ->set('nombre_evento', 'Curso Arancelado con Docs')
            ->set('fecha_inicio', now()->addDay()->format('Y-m-d'))
            ->set('lugar_evento', 'Aula 1')
            ->set('responsable_id', $this->crearResponsable()->responsable_id)
            ->set('arancel', true)
            ->set('link_pago', 'https://pagos.example.com/curso')
            ->set('destinatarioSeleccionado', [(string) $this->destinatario->destinatario_id])
            ->set('destinatarioPrecio.'.$this->destinatario->destinatario_id, '2500')
            ->set('destinatarioRequisitos', [
                $this->destinatario->destinatario_id => [
                    ['requisito_id' => null, 'titulo' => 'Constancia de Seguro'],
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $evento = Evento::where('nombre', 'CURSO ARANCELADO CON DOCS')->first();
        $this->assertNotNull($evento);
        $this->assertTrue($evento->arancel);
        $this->assertEquals(2500, $evento->destinatarios->first()->pivot->precio);
        $this->assertCount(1, $evento->requisitos);
        $this->assertEquals('Constancia de Seguro', $evento->requisitos->first()->titulo);
    }

    public function test_inscripcion_publica_exige_un_pdf_por_cada_requisito(): void
    {
        Mail::fake();
        Storage::fake('private');

        $evento = $this->crearEventoConRequisitos();
        $req1Id = $evento->requisitos[0]->requisito_id;
        $req2Id = $evento->requisitos[1]->requisito_id;

        // Falla sin adjuntar los PDFs
        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '12345678')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Pérez')
            ->set('mail', 'juan@example.com')
            ->set('telefono', '123456789')
            ->set('destinatario_id', (string) $this->destinatario->destinatario_id)
            ->call('submit')
            ->assertHasErrors(['documentos.*']);

        // Success con todos los PDFs
        $pdf1 = UploadedFile::fake()->create('doc1.pdf', 100, 'application/pdf');
        $pdf2 = UploadedFile::fake()->create('doc2.pdf', 100, 'application/pdf');

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '12345678')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Pérez')
            ->set('mail', 'juan@example.com')
            ->set('telefono', '123456789')
            ->set('destinatario_id', (string) $this->destinatario->destinatario_id)
            ->set("documentos.{$req1Id}", $pdf1)
            ->set("documentos.{$req2Id}", $pdf2)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripcion_participante', [
            'destinatario_id' => $this->destinatario->destinatario_id,
        ]);

        $this->assertDatabaseCount('documentos_presentados', 2);
    }

    public function test_destinatario_sin_requisitos_no_pide_archivos(): void
    {
        Mail::fake();

        $evento = $this->crearEventoConPlanilla();
        $evento->destinatarios()->sync([$this->destinatario->destinatario_id => ['precio' => 0]]);
        $evento->load('destinatarios');

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->assertSee('Cuál es tu situación respecto a la institución')
            ->set('dni', '12345678')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Pérez')
            ->set('mail', 'juan@example.com')
            ->set('telefono', '123456789')
            ->set('destinatario_id', (string) $this->destinatario->destinatario_id)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripcion_participante', [
            'destinatario_id' => $this->destinatario->destinatario_id,
        ]);
    }

    public function test_evento_sin_destinatarios_no_muestra_selector(): void
    {
        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->assertDontSee('Cuál es tu situación respecto a la institución');
    }

    public function test_admin_puede_descargar_documento_presentado(): void
    {
        Storage::fake('private');

        $evento = $this->crearEventoConRequisitos();
        $requisito = $evento->requisitos->first();

        $rolParticipante = Rol::where('nombre', 'Participante')->first();
        $inscripcion = $evento->planillaInscripcion->inscripciones()->create([
            'participante_id' => Participante::create([
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'dni' => '12345678',
                'mail' => 'juan@example.com',
                'telefono' => '123456789',
            ])->participante_id,
            'rol_id' => $rolParticipante->rol_id,
            'destinatario_id' => $this->destinatario->destinatario_id,
            'monto' => 0,
            'fecha_inscripcion' => now(),
        ]);

        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $path = $pdf->store("documentos/{$evento->evento_id}/{$inscripcion->inscripcion_participante_id}", 'private');

        $documento = DocumentoPresentado::create([
            'inscripcion_participante_id' => $inscripcion->inscripcion_participante_id,
            'requisito_id' => $requisito->requisito_id,
            'path' => $path,
        ]);

        $this->actingAs($this->admin)
            ->get(route('documento.show', $documento))
            ->assertOk();
    }

    public function test_no_se_puede_eliminar_requisito_con_documentos_presentados(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConRequisitos();
        $requisito = $evento->requisitos->first();

        $rolParticipante = Rol::where('nombre', 'Participante')->first();
        $inscripcion = $evento->planillaInscripcion->inscripciones()->create([
            'participante_id' => Participante::create([
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'dni' => '12345678',
                'mail' => 'juan@example.com',
                'telefono' => '123456789',
            ])->participante_id,
            'rol_id' => $rolParticipante->rol_id,
            'destinatario_id' => $this->destinatario->destinatario_id,
            'monto' => 0,
            'fecha_inscripcion' => now(),
        ]);

        $inscripcion->documentos()->create([
            'requisito_id' => $requisito->requisito_id,
            'path' => 'documentos/test/doc.pdf',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $requisito->delete();
    }

    public function test_no_se_puede_desmarcar_destinatario_con_inscripciones_en_evento_gratuito(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConRequisitos();

        $rolParticipante = Rol::where('nombre', 'Participante')->first();
        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '12345678',
            'mail' => 'juan@example.com',
            'telefono' => '123456789',
        ]);

        $evento->planillaInscripcion->inscripciones()->create([
            'participante_id' => $participante->participante_id,
            'rol_id' => $rolParticipante->rol_id,
            'destinatario_id' => $this->destinatario->destinatario_id,
            'monto' => 0,
            'fecha_inscripcion' => now(),
        ]);

        // Try to unselect the destinatario
        Livewire::test(CrearEvento::class, ['evento_id' => $evento->evento_id])
            ->set('destinatarioSeleccionado', [])
            ->call('save')
            ->assertHasErrors('destinatarioSeleccionado');
    }

    public function test_evento_gratuito_con_inscripciones_puede_guardarse_sin_bloquear_el_arancel(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConRequisitos();

        $rolParticipante = Rol::where('nombre', 'Participante')->first();
        $participante = Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Pérez',
            'dni' => '12345679',
            'mail' => 'juan2@example.com',
            'telefono' => '123456790',
        ]);

        $evento->planillaInscripcion->inscripciones()->create([
            'participante_id' => $participante->participante_id,
            'rol_id' => $rolParticipante->rol_id,
            'destinatario_id' => $this->destinatario->destinatario_id,
            'monto' => 0,
            'fecha_inscripcion' => now(),
        ]);

        Livewire::test(CrearEvento::class, ['evento_id' => $evento->evento_id])
            ->set('arancel', false)
            ->set('destinatarioSeleccionado', [(string) $this->destinatario->destinatario_id])
            ->call('save')
            ->assertHasNoErrors();
    }

    protected function crearResponsable(): Responsable
    {
        return Responsable::create([
            'nombre' => 'RESPONSABLE',
            'apellido' => 'PRUEBA',
            'dni' => '11111111',
        ]);
    }

    protected function crearEventoConPlanilla(bool $arancel = false): Evento
    {
        $tipo = TipoEvento::first();
        $categoria = CategoriaEvento::first();
        $responsable = $this->crearResponsable();

        $evento = Evento::create([
            'nombre' => 'Evento Test',
            'lugar' => 'Lugar Test',
            'fecha_inicio' => now()->addDay(),
            'tipo_evento_id' => $tipo->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'cupo' => null,
            'por_aprobacion' => false,
            'arancel' => $arancel,
            'link_pago' => $arancel ? 'https://pagos.example.com' : null,
            'responsable_id' => $responsable->responsable_id,
        ]);

        PlanillaInscripcion::create([
            'apertura' => now()->subDay(),
            'cierre' => now()->addMonth(),
            'evento_id' => $evento->evento_id,
        ]);

        return $evento->load('planillaInscripcion');
    }

    protected function crearEventoConRequisitos(): Evento
    {
        $evento = $this->crearEventoConPlanilla(arancel: false);
        $evento->destinatarios()->sync([$this->destinatario->destinatario_id => ['precio' => 0]]);

        $r1 = RequisitoDocumentacion::create([
            'evento_id' => $evento->evento_id,
            'destinatario_id' => $this->destinatario->destinatario_id,
            'titulo' => 'Certificado de Convivencia',
            'orden' => 0,
        ]);

        $r2 = RequisitoDocumentacion::create([
            'evento_id' => $evento->evento_id,
            'destinatario_id' => $this->destinatario->destinatario_id,
            'titulo' => 'Boleta de Servicio',
            'orden' => 1,
        ]);

        return $evento->fresh()->load('requisitos', 'destinatarios');
    }
}
