<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Certificado</title>

    @php
        $fontPath = \App\Support\CertificadoPdfAssets::fontPath();
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
            top: 37.5%;
            left: calc(50% - 80px);
            transform: translateX(-50%);
            width: 55%;
            font-size: 48px;
            display: flex;
            justify-content: center;
            align-items: center;
            white-space: nowrap;
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
            top: 38%;
            left: 77%;
            font-size: 48px;
        }
    </style>
</head>

<body>
    @if ($background)
        <img src="{{ $background }}" class="background">
    @endif

    <div class="ape_nom">{{ $apellido }}, {{ $nombre }}</div>
    <div class="dni">{{ $dni }}</div>
    <div class="qr">
        <img src="{{ $qr }}" width="145" height="145" alt="QR Code" />
    </div>
</body>

</html>
