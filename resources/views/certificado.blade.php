<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Certificado</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <style>
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
            position: absolute;
            z-index: -1;
            background-image: url('{{ storage_path("app/public/{$background}") }}');
            background-size: cover;
            background-position: center;
        }

        .ape_nom,
        .qr,
        .dni {
            position: absolute;
            height: 5.3%;
            font-family: 'Roboto', sans-serif;
            font-weight: bold;
            text-align: center;
        }

        .ape_nom {
            top: 39.5%;
            left: 30%;
            right: 25%;
            width: auto;
            font-size: 28px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .qr {
            top: 75%;
            left: 25%;
            right: 25%;
            width: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }


        .dni {
            top: 39.5%;
            left: 82%;
            font-size: 28px;
        }
    </style>
</head>

<body>
    @if ($background)
        <img src="{{ public_path('storage/' . $background) }}" class="background">
    @endif

    <div class="ape_nom">{{ $apellido }} {{ $nombre }}</div>
    <div class="dni">{{ $dni }}</div>
    <div class="qr">
        <img src="{{ $qr }}" width="125" height="125" alt="QR Code" />
    </div>
</body>

</html>
