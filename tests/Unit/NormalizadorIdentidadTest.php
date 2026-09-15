<?php

namespace Tests\Unit;

use App\Support\NormalizadorIdentidad;
use PHPUnit\Framework\TestCase;

class NormalizadorIdentidadTest extends TestCase
{
    public function test_normaliza_nombres_sin_acentos_ni_signos(): void
    {
        $this->assertSame('espindola j', NormalizadorIdentidad::nombre('  Espíndola J. '));
        $this->assertSame('ordono felipe', NormalizadorIdentidad::nombre('Ordoño   Felipe'));
        $this->assertSame('maria de los angeles', NormalizadorIdentidad::nombre('María de los Ángeles'));
    }

    public function test_normaliza_telefonos_a_digitos_sin_ceros_iniciales(): void
    {
        $this->assertSame('375515234965', NormalizadorIdentidad::telefono('0375515234965'));
        $this->assertSame('3755719156', NormalizadorIdentidad::telefono(' 3755719156 '));
        $this->assertSame('3771288840', NormalizadorIdentidad::telefono('3771 288840'));
        $this->assertSame('', NormalizadorIdentidad::telefono(null));
    }

    public function test_titula_nombres_preservando_acentos(): void
    {
        $this->assertSame('López Ricci', NormalizadorIdentidad::titulo('LÓPEZ ricci'));
        $this->assertSame('María De Los Ángeles', NormalizadorIdentidad::titulo('  maría de los ángeles '));
        $this->assertSame('Elena V.', NormalizadorIdentidad::titulo('elena v.'));
    }

    public function test_normaliza_mail_a_minusculas(): void
    {
        $this->assertSame('pepito@example.com', NormalizadorIdentidad::mail('  Pepito@Example.COM '));
        $this->assertSame('', NormalizadorIdentidad::mail(null));
    }
}
