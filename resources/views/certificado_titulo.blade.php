<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Certificado de Título Intermedio</title>
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
            z-index: -1;
            background-image: url('{{ $background }}');
            background-size: cover;
            background-position: center;
        }

        .texto {
            position: absolute;
            width: 100%;
            left: 0;
            text-align: center;
            font-family: 'Roboto', sans-serif;
        }

        .ape_nom {
            top: 39%;
            font-size: 30px;
            font-weight: bold;
        }

        .titulo {
            top: 46%;
            font-size: 26px;
            font-weight: bold;
        }

        .carrera {
            top: 52%;
            font-size: 22px;
            font-weight: normal;
        }

        .dni {
            top: 58%;
            font-size: 22px;
            font-weight: bold;
        }

        .fecha {
            top: 64%;
            font-size: 18px;
            font-weight: normal;
        }
    </style>
</head>

<body>
    @if ($background)
        <img src="{{ $background }}" class="background">
    @endif

    <div class="texto ape_nom">{{ $apellido }} {{ $nombre }}</div>
    <div class="texto titulo">{{ $titulo }}</div>
    <div class="texto carrera">{{ $carrera }}</div>
    <div class="texto dni">DNI: {{ $dni }}</div>
    <div class="texto fecha">{{ $fecha }}</div>
</body>

</html>
