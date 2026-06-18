<?php

namespace Tests\Feature;

use App\Livewire\Admin\Destinatarios;
use App\Livewire\CrearEvento;
use App\Livewire\HabilitarPlanilla;
use App\Livewire\RegistroEventoPublico;
use App\Models\CategoriaEvento;
use App\Models\Destinatario;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\PlanillaInscripcion;
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

class ArancelesDestinatariosTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $gestor;

    protected Destinatario $destinatarioPago;

    protected Destinatario $destinatarioGratis;

    protected function setUp(): void
    {
        parent::setUp();

        $roleAdmin = Role::create(['name' => 'Administrador', 'guard_name' => 'web']);
        $roleGestor = Role::create(['name' => 'Gestor', 'guard_name' => 'web']);

        $permEventos = Permission::create(['name' => 'eventos', 'guard_name' => 'web']);
        $permCrear = Permission::create(['name' => 'crear_eventos', 'guard_name' => 'web']);

        $roleAdmin->syncPermissions([$permEventos, $permCrear]);
        $roleGestor->syncPermissions([$permEventos]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrador');

        $this->gestor = User::factory()->create();
        $this->gestor->assignRole('Gestor');

        TipoEvento::create(['nombre' => 'Curso']);
        CategoriaEvento::create(['nombre' => 'Categoría Test']);

        $this->destinatarioPago = Destinatario::where('nombre', 'Público General')->first();
        $this->destinatarioGratis = Destinatario::where('nombre', 'Estudiante de la UNaM')->first();

        Rol::create(['nombre' => 'Participante']);
    }

    public function test_admin_puede_crear_destinatario(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Destinatarios::class)
            ->set('nombre', 'Nuevo Destinatario')
            ->set('activo', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('destinatarios', [
            'nombre' => 'NUEVO DESTINATARIO',
            'activo' => true,
        ]);
    }

    public function test_no_se_puede_eliminar_destinatario_en_uso(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoArancelado([$this->destinatarioPago->destinatario_id => 100]);

        Livewire::test(Destinatarios::class)
            ->call('eliminar', $this->destinatarioPago->destinatario_id)
            ->assertDispatched('oops');

        $this->assertDatabaseHas('destinatarios', ['destinatario_id' => $this->destinatarioPago->destinatario_id]);
    }

    public function test_admin_puede_crear_evento_arancelado(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CrearEvento::class)
            ->set('categoria_id', CategoriaEvento::first()->categoria_id)
            ->set('tipo_evento_id', TipoEvento::first()->tipo_evento_id)
            ->set('nombre_evento', 'Curso Arancelado')
            ->set('fecha_inicio', now()->addDay()->format('Y-m-d'))
            ->set('lugar_evento', 'Aula 1')
            ->set('responsable_id', $this->crearResponsable()->responsable_id)
            ->set('arancel', true)
            ->set('link_pago', 'https://pagos.example.com/curso')
            ->set('destinatarioSeleccionado', [(string) $this->destinatarioPago->destinatario_id])
            ->set('destinatarioPrecio.'.$this->destinatarioPago->destinatario_id, '1500.50')
            ->call('save')
            ->assertHasNoErrors();

        $evento = Evento::where('nombre', 'CURSO ARANCELADO')->first();
        $this->assertNotNull($evento);
        $this->assertTrue($evento->arancel);
        $this->assertEquals('https://pagos.example.com/curso', $evento->link_pago);
        $this->assertTrue($evento->destinatarios->contains($this->destinatarioPago));
        $this->assertEquals(1500.50, $evento->destinatarios->first()->pivot->precio);
    }

    public function test_evento_arancelado_requiere_link_si_precio_positivo(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CrearEvento::class)
            ->set('categoria_id', CategoriaEvento::first()->categoria_id)
            ->set('tipo_evento_id', TipoEvento::first()->tipo_evento_id)
            ->set('nombre_evento', 'Curso Sin Link')
            ->set('fecha_inicio', now()->addDay()->format('Y-m-d'))
            ->set('lugar_evento', 'Aula 1')
            ->set('responsable_id', $this->crearResponsable()->responsable_id)
            ->set('arancel', true)
            ->set('link_pago', '')
            ->set('destinatarioSeleccionado', [(string) $this->destinatarioPago->destinatario_id])
            ->set('destinatarioPrecio.'.$this->destinatarioPago->destinatario_id, '100')
            ->call('save')
            ->assertHasErrors(['link_pago']);
    }

    public function test_formulario_publico_evento_gratuito_no_pide_destinatario(): void
    {
        Mail::fake();

        $evento = $this->crearEventoConPlanilla();

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->assertSet('evento.arancel', false)
            ->assertDontSee('Cuál es tu situación respecto a la institución')
            ->set('dni', '12345678')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Pérez')
            ->set('mail', 'juan@example.com')
            ->set('telefono', '123456789')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripcion_participante', [
            'planilla_id' => $evento->planillaInscripcion->planilla_inscripcion_id,
            'destinatario_id' => null,
            'monto' => null,
            'comprobante_pago' => null,
        ]);
    }

    public function test_formulario_publico_arancelado_exige_comprobante(): void
    {
        Mail::fake();
        Storage::fake('private');

        $evento = $this->crearEventoArancelado([$this->destinatarioPago->destinatario_id => 1000], conPlanilla: true);

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
            ->set('destinatario_id', (string) $this->destinatarioPago->destinatario_id)
            ->call('submit')
            ->assertHasErrors(['comprobante']);

        $comprobante = UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf');

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '12345678')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Pérez')
            ->set('mail', 'juan@example.com')
            ->set('telefono', '123456789')
            ->set('destinatario_id', (string) $this->destinatarioPago->destinatario_id)
            ->set('comprobante', $comprobante)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripcion_participante', [
            'planilla_id' => $evento->planillaInscripcion->planilla_inscripcion_id,
            'destinatario_id' => $this->destinatarioPago->destinatario_id,
            'monto' => 1000,
        ]);
    }

    public function test_formulario_publico_destinatario_gratuito_no_exige_comprobante(): void
    {
        Mail::fake();

        $evento = $this->crearEventoArancelado([$this->destinatarioGratis->destinatario_id => 0], conPlanilla: true);

        Livewire::test(RegistroEventoPublico::class, [
            'tipoEvento' => $evento->tipoEvento->nombre,
            'eventoId' => $evento->evento_id,
        ])
            ->set('dni', '12345678')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Pérez')
            ->set('mail', 'juan@example.com')
            ->set('telefono', '123456789')
            ->set('destinatario_id', (string) $this->destinatarioGratis->destinatario_id)
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inscripcion_participante', [
            'planilla_id' => $evento->planillaInscripcion->planilla_inscripcion_id,
            'destinatario_id' => $this->destinatarioGratis->destinatario_id,
            'monto' => 0,
            'comprobante_pago' => null,
        ]);
    }

    public function test_admin_puede_descargar_comprobante(): void
    {
        Storage::fake('private');

        $evento = $this->crearEventoArancelado([$this->destinatarioPago->destinatario_id => 1000], conPlanilla: true);

        $comprobante = UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf');
        $path = $comprobante->store('comprobantes/'.$evento->evento_id, 'private');

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
            'destinatario_id' => $this->destinatarioPago->destinatario_id,
            'monto' => 1000,
            'comprobante_pago' => $path,
            'fecha_inscripcion' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('comprobante.show', $inscripcion))
            ->assertOk();
    }

    public function test_admin_puede_editar_fecha_de_evento_en_curso(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoEnCurso();
        $nuevaFecha = now()->addDays(5)->format('Y-m-d');

        Livewire::test(CrearEvento::class, ['evento_id' => $evento->evento_id])
            ->assertDontSeeHtml('type="date" id="fecha_inicio" disabled')
            ->set('fecha_inicio', $nuevaFecha)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('evento', [
            'evento_id' => $evento->evento_id,
            'fecha_inicio' => $nuevaFecha,
        ]);
    }

    public function test_gestor_no_puede_editar_fecha_de_evento_en_curso(): void
    {
        $this->actingAs($this->gestor);

        $evento = $this->crearEventoEnCurso();

        $componente = Livewire::test(CrearEvento::class, ['evento_id' => $evento->evento_id]);

        $this->assertMatchesRegularExpression(
            '/type="date" id="fecha_inicio"[^>]*disabled/',
            $componente->html()
        );
    }

    public function test_input_de_precio_se_habilita_al_seleccionar_destinatario(): void
    {
        $this->actingAs($this->admin);

        $id = $this->destinatarioPago->destinatario_id;

        $componente = Livewire::test(CrearEvento::class)
            ->set('arancel', true)
            ->set('destinatarioSeleccionado', [(string) $id]);

        $this->assertDoesNotMatchRegularExpression(
            '/wire:model="destinatarioPrecio\.'.$id.'" placeholder="0,00"\s+disabled/',
            $componente->html()
        );
    }

    public function test_evento_puede_tener_fecha_de_inicio_pasada(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CrearEvento::class)
            ->set('categoria_id', CategoriaEvento::first()->categoria_id)
            ->set('tipo_evento_id', TipoEvento::first()->tipo_evento_id)
            ->set('nombre_evento', 'Evento Pasado')
            ->set('fecha_inicio', now()->subDays(5)->format('Y-m-d'))
            ->set('lugar_evento', 'Aula 1')
            ->set('responsable_id', $this->crearResponsable()->responsable_id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('evento', [
            'nombre' => 'EVENTO PASADO',
        ]);
    }

    public function test_planilla_restringe_fecha_cierre_posterior_a_apertura(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConPlanilla();

        Livewire::test(HabilitarPlanilla::class, ['evento_id' => $evento->evento_id])
            ->set('apertura', '2026-06-15 09:00')
            ->assertSeeHtml('min="2026-06-15T09:00"');
    }

    public function test_planilla_restringe_fecha_apertura_anterior_a_cierre(): void
    {
        $this->actingAs($this->admin);

        $evento = $this->crearEventoConPlanilla();

        Livewire::test(HabilitarPlanilla::class, ['evento_id' => $evento->evento_id])
            ->set('cierre', '2026-06-20 18:00')
            ->assertSeeHtml('max="2026-06-20T18:00"');
    }

    protected function crearEventoEnCurso(): Evento
    {
        $tipo = TipoEvento::first();
        $categoria = CategoriaEvento::first();
        $responsable = $this->crearResponsable();

        $evento = Evento::create([
            'nombre' => 'Evento En Curso',
            'lugar' => 'Lugar Test',
            'fecha_inicio' => now()->subDay(),
            'tipo_evento_id' => $tipo->tipo_evento_id,
            'categoria_id' => $categoria->categoria_id,
            'cupo' => null,
            'por_aprobacion' => false,
            'arancel' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);

        $evento->estado = 'En Curso';
        $evento->save();

        return $evento;
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

    protected function crearEventoArancelado(array $precios, bool $conPlanilla = false): Evento
    {
        $evento = $this->crearEventoConPlanilla(arancel: true);

        $sync = [];
        foreach ($precios as $id => $precio) {
            $sync[(int) $id] = ['precio' => (float) $precio];
        }
        $evento->destinatarios()->sync($sync);

        if (! $conPlanilla) {
            $evento->planillaInscripcion->delete();
            $evento->load('planillaInscripcion');
        }

        return $evento->load('destinatarios');
    }
}
