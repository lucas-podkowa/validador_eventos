<?php

namespace Tests\Feature;

use Tests\TestCase;

class PdfListadoInscriptosTest extends TestCase
{
    public function test_pdf_uses_existing_logo_asset(): void
    {
        $evento = new \stdClass;
        $evento->nombre = 'Evento de prueba';

        $view = view('pdf.listado-inscriptos', [
            'evento' => $evento,
            'inscriptos' => collect(),
        ])->render();

        $this->assertStringContainsString('unam-color.png', $view);
        $this->assertStringNotContainsString('logo-unam-color.png', $view);
    }

    public function test_pdf_muestra_detalles_del_evento_cuando_se_solicita(): void
    {
        $evento = new \stdClass;
        $evento->nombre = 'Evento de prueba';
        $evento->fecha_inicio_formatted = '01/01/2026';
        $evento->lugar = 'Aula 1';
        $evento->por_aprobacion = false;
        $evento->tipoEvento = (object) ['nombre' => 'Curso'];
        $evento->categoria = (object) ['nombre' => 'Categoría'];
        $evento->responsable = (object) ['nombre' => 'Ana', 'apellido' => 'Perez'];

        $view = view('pdf.listado-inscriptos', [
            'evento' => $evento,
            'inscriptos' => collect(),
            'mostrarDetalles' => true,
            'disertantesYColaboradores' => collect(),
        ])->render();

        $this->assertStringContainsString('Detalles del Evento', $view);
        $this->assertStringContainsString('Ana Perez', $view);
        $this->assertStringNotContainsString('Disertantes y Colaboradores', $view);
        $this->assertStringContainsString('Listado de Inscriptos', $view);
        $this->assertStringContainsString('Roboto Condensed', $view);
        $this->assertStringContainsString('class="tabla-inscriptos"', $view);
    }
}
