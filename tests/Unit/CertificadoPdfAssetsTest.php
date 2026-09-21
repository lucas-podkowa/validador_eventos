<?php

namespace Tests\Unit;

use App\Support\CertificadoPdfAssets;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificadoPdfAssetsTest extends TestCase
{
    public function test_resuelve_una_ruta_relativa_del_disco_publico(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/certificado.png', 'contenido');

        $resolvedPath = CertificadoPdfAssets::resolveBackgroundPath('images/certificado.png');

        $this->assertSame(Storage::disk('public')->path('images/certificado.png'), $resolvedPath);
    }

    public function test_acepta_una_ruta_absoluta_ya_resuelta(): void
    {
        $absolutePath = tempnam(sys_get_temp_dir(), 'certificado-');
        file_put_contents($absolutePath, 'contenido');

        $resolvedPath = CertificadoPdfAssets::resolveBackgroundPath($absolutePath);

        $this->assertSame($absolutePath, $resolvedPath);

        unlink($absolutePath);
    }
}
