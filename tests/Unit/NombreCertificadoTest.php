<?php

namespace Tests\Unit;

use App\Support\NombreCertificado;
use PHPUnit\Framework\TestCase;

class NombreCertificadoTest extends TestCase
{
    public function test_calcula_la_longitud_del_texto_normalizado(): void
    {
        $this->assertSame(11, NombreCertificado::longitud('Perez', 'Juan'));
        $this->assertSame(11, NombreCertificado::longitud('  Perez   ', '  Juan '));
    }

    public function test_detecta_cuando_supera_el_limite(): void
    {
        $this->assertFalse(NombreCertificado::excede('Perez', 'Juan'));
        $this->assertTrue(NombreCertificado::excede('Cuella', 'Angel Ezequiel Cuello Cardozo'));
    }

    public function test_no_marca_exceso_si_falta_algun_dato(): void
    {
        $this->assertFalse(NombreCertificado::excede('', 'Juan'));
        $this->assertFalse(NombreCertificado::excede('Perez', ''));
    }

    public function test_no_modifica_el_nombre_si_entra_en_el_limite(): void
    {
        $this->assertSame('Perez, Juan', NombreCertificado::paraCertificado('Perez', 'Juan'));
    }

    public function test_abrevia_el_nombre_intermedio_cuando_supera_el_limite(): void
    {
        $resultado = NombreCertificado::paraCertificado('Cuella', 'Angel Ezequiel Cuello Cardozo');

        $this->assertSame('Cuella, Angel Ezequiel C. Cardozo', $resultado);
        $this->assertLessThanOrEqual(NombreCertificado::LIMITE, mb_strlen($resultado));
    }

    public function test_abrevia_hasta_entrar_con_nombres_muy_largos(): void
    {
        $resultado = NombreCertificado::paraCertificado('Fernandez De La Cruz', 'Maria De Los Angeles Milagros');

        $this->assertLessThanOrEqual(NombreCertificado::LIMITE, mb_strlen($resultado));
        $this->assertStringStartsWith('Fernandez', $resultado);
    }

    public function test_recorta_como_ultimo_recurso(): void
    {
        $resultado = NombreCertificado::paraCertificado('ApellidoExtremadamenteLargo', 'NombreIgualDeLargo');

        $this->assertLessThanOrEqual(NombreCertificado::LIMITE, mb_strlen($resultado));
    }
}
