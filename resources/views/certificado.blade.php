<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Certificado</title>
    <style>
        @page {
            margin: 0cm;
        }

        body {
            margin: 0cm;
            padding: 0cm;
        }

        .contenedor {
            position: relative;
            width: 100%;
            height: 100vh;
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('{{ storage_path("app/public/{$background}") }}');
            background-size: cover;
            background-position: center;
            z-index: 1;
        }

        .contenido {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            font-size: 32px;
            font-family: Arial, sans-serif;
            z-index: 2;
        }
    </style>
</head>

<body>
    <div class="contenedor">
        <div class="background"></div>
        <div class="contenido">
            <strong>{{ $nombre }} {{ $apellido }}</strong>
        </div>
    </div>
</body>

</html>
