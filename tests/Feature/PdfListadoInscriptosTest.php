<?php

namespace Tests\Feature;

use Tests\TestCase;

class PdfListadoInscriptosTest extends TestCase
{
    public function test_pdf_uses_existing_logo_asset(): void
    {
        $evento = new \stdClass();
        $evento->nombre = 'Evento de prueba';

        $view = view('pdf.listado-inscriptos', [
            'evento' => $evento,
            'inscriptos' => collect(),
        ])->render();

        $this->assertStringContainsString('unam-color.png', $view);
        $this->assertStringNotContainsString('logo-unam-color.png', $view);
    }
}
