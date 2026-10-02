<?php

namespace Tests\Feature;

use App\Livewire\EmisionMasiva;
use App\Livewire\EmisorCertificados;
use App\Models\CategoriaEvento;
use App\Models\Contexto;
use App\Models\Emision;
use App\Models\Evento;
use App\Models\Participacion;
use App\Models\Participante;
use App\Models\PlantillaCertificado;
use App\Models\Responsable;
use App\Models\TipoEvento;
use App\Models\TipoReconocimiento;
use App\Services\GenerarCertificadoEmision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EmisionUnificadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
        Storage::fake('public');
    }

    public function test_el_servicio_emite_y_la_validacion_por_codigo_funciona(): void
    {
        $contexto = $this->crearContexto();
        $participante = $this->crearParticipante('30111222', 'tutor@example.test');
        $plantilla = $this->crearPlantilla($contexto, 'tutor');

        $emision = app(GenerarCertificadoEmision::class)->emitir(
            participante: $participante,
            tipo: TipoReconocimiento::where('slug', 'tutor')->firstOrFail(),
            plantilla: $plantilla,
            origen: $contexto,
        );

        $this->assertNotNull($emision->certificado_path);
        $this->assertNotNull($emision->codigo_verificacion);
        Storage::disk('private')->assertExists($emision->certificado_path);

        $this->get(route('verificar', ['codigo' => $emision->codigo_verificacion]))
            ->assertOk()
            ->assertSee('Perez');

        $emision->update(['estado' => Emision::ESTADO_ANULADO]);

        $this->get(route('verificar', ['codigo' => $emision->codigo_verificacion]))
            ->assertOk()
            ->assertSee('anulado');
    }

    public function test_el_emisor_individual_emite_desde_un_contexto(): void
    {
        $contexto = $this->crearContexto();
        $this->crearPlantilla($contexto, 'tutor');
        $tipo = TipoReconocimiento::where('slug', 'tutor')->firstOrFail();

        Livewire::test(EmisorCertificados::class)
            ->set('origen_tipo', 'contexto')
            ->set('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id)
            ->set('contexto_id', $contexto->contexto_id)
            ->set('dni', '30111222')
            ->set('nombre', 'Juan')
            ->set('apellido', 'Perez')
            ->set('mail', 'juan@example.test')
            ->set('telefono', '3764000000')
            ->call('emitir')
            ->assertHasNoErrors();

        $this->assertSame(1, Emision::count());
        $this->assertDatabaseHas('participante', ['dni' => '30111222']);
    }

    public function test_la_emision_masiva_agrega_e_emite_pendientes(): void
    {
        $contexto = $this->crearContexto();
        $plantilla = $this->crearPlantilla($contexto, 'tutor');
        $tipo = TipoReconocimiento::where('slug', 'tutor')->firstOrFail();

        Livewire::test(EmisionMasiva::class)
            ->set('contexto_id', $contexto->contexto_id)
            ->set('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id)
            ->set('nuevo_dni', '30111222')
            ->set('nuevo_apellido', 'Perez')
            ->set('nuevo_nombre', 'Juan')
            ->set('nuevo_mail', 'juan@example.test')
            ->set('nuevo_telefono', '3764000000')
            ->call('agregarParticipacion')
            ->assertHasNoErrors()
            ->set('plantilla_id', $plantilla->plantilla_id)
            ->call('emitirTodas');

        $this->assertSame(1, Participacion::count());
        $this->assertSame(Emision::count(), 1);
        $this->assertSame(Participacion::ESTADO_EMITIDO, Participacion::first()->estado);
    }

    public function test_lote_pequeno_se_emite_en_un_solo_request(): void
    {
        config(['emision.usar_cola' => false, 'emision.umbral_sincronico' => 5, 'emision.tamano_lote' => 5]);

        $contexto = $this->crearContexto();
        $plantilla = $this->crearPlantilla($contexto, 'tutor');
        $tipo = TipoReconocimiento::where('slug', 'tutor')->firstOrFail();

        $componente = Livewire::test(EmisionMasiva::class)
            ->set('contexto_id', $contexto->contexto_id)
            ->set('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id);

        $this->agregarPersonas($componente, 2);

        $componente->set('plantilla_id', $plantilla->plantilla_id)
            ->call('emitirTodas')
            ->assertSet('procesando', false)
            ->assertSet('emitidos', 2);

        $this->assertSame(2, Emision::count());
        $this->assertSame(0, Participacion::where('estado', Participacion::ESTADO_PENDIENTE)->count());
    }

    public function test_lote_grande_se_procesa_por_bloques(): void
    {
        config(['emision.usar_cola' => false, 'emision.umbral_sincronico' => 1, 'emision.tamano_lote' => 1]);

        $contexto = $this->crearContexto();
        $plantilla = $this->crearPlantilla($contexto, 'tutor');
        $tipo = TipoReconocimiento::where('slug', 'tutor')->firstOrFail();

        $componente = Livewire::test(EmisionMasiva::class)
            ->set('contexto_id', $contexto->contexto_id)
            ->set('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id);

        $this->agregarPersonas($componente, 3);

        $componente->set('plantilla_id', $plantilla->plantilla_id)
            ->call('emitirTodas')
            ->assertSet('procesando', true)
            ->assertSet('emitidos', 1)
            ->call('continuarLote')
            ->assertSet('emitidos', 2)
            ->call('continuarLote')
            ->assertSet('procesando', false)
            ->assertSet('emitidos', 3);

        $this->assertSame(3, Emision::count());
        $this->assertSame(0, Participacion::where('estado', Participacion::ESTADO_PENDIENTE)->count());
    }

    public function test_el_emisor_individual_filtra_eventos_por_categoria_y_contexto(): void
    {
        $categoria = CategoriaEvento::create(['nombre' => 'JIDeTEV']);
        $contextoA = Contexto::create(['categoria_id' => $categoria->categoria_id, 'nombre' => 'JIDeTEV VIII', 'activo' => true]);
        $contextoB = Contexto::create(['categoria_id' => $categoria->categoria_id, 'nombre' => 'JIDeTEV XI', 'activo' => true]);

        $tipoEvento = TipoEvento::create(['nombre' => 'Charla']);
        $responsable = Responsable::create([
            'nombre' => 'Ana',
            'apellido' => 'Perez',
            'dni' => '11111111',
        ]);

        $eventoA = $this->crearEventoFinalizado('Seguridad en el Hogar', $contextoA, $tipoEvento, $responsable);
        $eventoB = $this->crearEventoFinalizado('Seguridad en el Hogar', $contextoB, $tipoEvento, $responsable);

        $componente = Livewire::test(EmisorCertificados::class);

        // Sin categoría ni contexto no se lista ningún evento.
        $this->assertCount(0, $componente->get('eventos'));

        $componente->set('categoria_id', $categoria->categoria_id);
        $this->assertCount(2, $componente->get('contextos'));

        $componente->set('contexto_id', $contextoB->contexto_id);

        $ids = collect($componente->get('eventos'))->pluck('evento_id')->all();
        $this->assertSame([$eventoB->evento_id], $ids);
        $this->assertNotContains($eventoA->evento_id, $ids);
    }

    public function test_reemision_reemplaza_el_pdf_anterior_al_cambiar_el_apellido(): void
    {
        $contexto = $this->crearContexto();
        $plantilla = $this->crearPlantilla($contexto, 'tutor');
        $tipo = TipoReconocimiento::where('slug', 'tutor')->firstOrFail();

        $emitir = function (string $apellido) use ($contexto, $tipo, $plantilla) {
            Livewire::test(EmisorCertificados::class)
                ->set('origen_tipo', 'contexto')
                ->set('tipo_reconocimiento_id', $tipo->tipo_reconocimiento_id)
                ->set('contexto_id', $contexto->contexto_id)
                ->set('plantilla_id', $plantilla->plantilla_id)
                ->set('dni', '30111222')
                ->set('nombre', 'Pedro')
                ->set('apellido', $apellido)
                ->set('mail', 'pedro@example.test')
                ->set('telefono', '3764000000')
                ->call('emitir')
                ->assertHasNoErrors();
        };

        $emitir('Podkowa');

        $emision = Emision::firstOrFail();
        $pathViejo = $emision->certificado_path;
        Storage::disk('private')->assertExists($pathViejo);

        $emitir('Suarez');

        $this->assertSame(1, Emision::count());

        $emision->refresh();
        $this->assertSame('Suarez', $emision->participante->apellido);
        $this->assertNotSame($pathViejo, $emision->certificado_path);
        Storage::disk('private')->assertMissing($pathViejo);
        Storage::disk('private')->assertExists($emision->certificado_path);
    }

    private function agregarPersonas($componente, int $cantidad): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            $dni = (string) (30111200 + $i);

            $componente
                ->set('nuevo_dni', $dni)
                ->set('nuevo_apellido', 'Perez')
                ->set('nuevo_nombre', 'Persona'.$i)
                ->set('nuevo_mail', "persona{$i}@example.test")
                ->set('nuevo_telefono', '3764000000')
                ->call('agregarParticipacion')
                ->assertHasNoErrors();
        }
    }

    private function crearContexto(): Contexto
    {
        return Contexto::create([
            'nombre' => 'Prácticas Profesionales Supervisadas',
            'tipo' => 'programa',
            'activo' => true,
        ]);
    }

    private function crearEventoFinalizado(string $nombre, Contexto $contexto, TipoEvento $tipoEvento, Responsable $responsable): Evento
    {
        $evento = Evento::create([
            'nombre' => $nombre,
            'fecha_inicio' => now()->subDay(),
            'cupo' => 100,
            'lugar' => 'Aula 1',
            'tipo_evento_id' => $tipoEvento->tipo_evento_id,
            'categoria_id' => $contexto->categoria_id,
            'contexto_id' => $contexto->contexto_id,
            'por_aprobacion' => false,
            'responsable_id' => $responsable->responsable_id,
        ]);

        // 'estado' no es fillable: se actualiza de forma explícita.
        $evento->estado = 'Finalizado';
        $evento->save();

        return $evento;
    }

    private function crearParticipante(string $dni, string $mail): Participante
    {
        return Participante::create([
            'nombre' => 'Juan',
            'apellido' => 'Perez',
            'dni' => $dni,
            'mail' => $mail,
            'telefono' => '3764000000',
        ]);
    }

    private function crearPlantilla(Contexto $contexto, string $slug): PlantillaCertificado
    {
        $tipo = TipoReconocimiento::where('slug', $slug)->firstOrFail();

        return PlantillaCertificado::create([
            'contexto_id' => $contexto->contexto_id,
            'nombre' => 'Plantilla '.$slug.' '.$contexto->contexto_id,
            'imagen_path' => 'plantillas/test/base.png',
            'tipo' => $slug,
            'tipo_reconocimiento_id' => $tipo->tipo_reconocimiento_id,
            'alcance' => 'programa',
            'por_defecto' => true,
            'texto' => 'ha ejercido como tutor en {contexto}.',
            'layout' => [
                ['campo' => 'apellido_nombres', 'x' => 20, 'y' => 38, 'w' => 52, 'size' => 40, 'align' => 'center', 'color' => '#0A1B3A', 'bold' => true, 'italic' => false],
                ['campo' => 'texto_cuerpo', 'x' => 8, 'y' => 47, 'w' => 84, 'size' => 16, 'align' => 'left', 'color' => '#0A1B3A', 'bold' => false, 'italic' => false],
            ],
        ]);
    }
}
