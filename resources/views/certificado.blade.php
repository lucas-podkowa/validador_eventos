<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Certificado</title>

    @php
        $fontPath = \App\Support\CertificadoPdfAssets::fontPath();
        $layout = $layout ?? null;
        $texto = $texto ?? '';
        $variables = $variables ?? [];
        $firmas = $firmas ?? [];
        $esDinamica = ! empty($layout);
    @endphp

    <style>
        @font-face {
            font-family: 'Roboto Condensed Local';
            font-style: normal;
            font-weight: 700;
            src: url('{{ $fontPath }}') format('truetype');
        }

        @page {
            margin: 0cm;
        }

        body,
        .contenedor {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100vh;
            position: relative;
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            object-fit: cover;
        }

        .ape_nom,
        .qr,
        .dni {
            position: absolute;
            height: 5.3%;
            font-family: 'Roboto Condensed Local', sans-serif;
            font-weight: 700;
            text-align: center;
            color: #0A1B3A;
        }

        .ape_nom {
            top: 39.75%;
            left: 15%;
            right: 30%;
            width: auto;
            height: auto;
            font-size: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            white-space: nowrap;
            overflow-x: hidden;
            overflow-y: visible;
            box-sizing: border-box;
            line-height: 1.05;
        }

        .qr {
            top: 72%;
            left: 25%;
            right: 25%;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }


.dni {
            top: 38.6%;
            left: 77%;
            font-size: 44px;
        }

        .bloque {
            position: absolute;
            box-sizing: border-box;
            font-family: 'Roboto Condensed Local', sans-serif;
            line-height: 1.3;
            overflow: visible;
        }
    </style>
</head>

<body>
    @if ($background)
        <img src="{{ $background }}" class="background">
    @endif

    @if ($esDinamica)
        @foreach ($layout as $bloque)
            @php
                $campo = $bloque['campo'] ?? 'literal';
                $esImagen = \App\Support\CertificadoLayout::esImagen($campo);
                $style = \App\Support\CertificadoLayout::estilo($bloque);
            @endphp

            @if ($esImagen)
                @php
                    if ($campo === 'qr') {
                        $src = $qr;
                    } else {
                        $slot = \App\Support\CertificadoLayout::slotFirma($campo);
                        $src = $slot ? ($firmas[$slot - 1]['imagen'] ?? null) : null;
                    }
                @endphp
                @if ($src)
                    <img src="{{ $src }}" class="bloque" style="{{ $style }}">
                @endif
            @else
                <div class="bloque" style="{{ $style }}">{!! \App\Support\CertificadoLayout::textoBloque($bloque, $texto, $variables) !!}</div>
            @endif
        @endforeach
    @else
        <div class="ape_nom">{{ \App\Support\NombreCertificado::paraCertificado($apellido, $nombre) }}</div>
        <div class="dni">{{ $dni }}</div>
        <div class="qr">
            <img src="{{ $qr }}" width="145" height="145" alt="QR Code" />
        </div>
    @endif
</body>

</html>
