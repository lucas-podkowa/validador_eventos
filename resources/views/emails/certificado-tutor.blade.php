<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Tutoría Académica</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .email-container {
            max-width: 680px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #dddddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background-color: #003366;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px;
        }

        .content p {
            font-size: 16px;
            margin: 0 0 1em;
        }

        .content strong {
            color: #003366;
        }

        .footer {
            background-color: #f4f4f4;
            color: #777777;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }

        .footer p {
            margin: 0;
        }
    </style>
</head>

@php
    $datos = $certificado->datos ?? [];
    $tutor = $datos['tutor'] ?? [];
    $practica = $datos['practica'] ?? [];
    $nombreTutor = trim(($tutor['nombres'] ?? ($tutor['nombre'] ?? '')).' '.($tutor['apellido'] ?? ''));
@endphp

<body>
    <div class="email-container">
        <div class="header">
            <h1>Facultad de Ingeniería</h1>
            <p>Universidad Nacional de Misiones</p>
        </div>
        <div class="content">
            <p>Estimado/a <strong>{{ $nombreTutor }}</strong>,</p>

            <p>
                Nos dirigimos a usted para agradecerle su valiosa labor como Tutor Académico en el marco de las
                Prácticas Profesionales Supervisadas.
            </p>

            @if (! empty($practica['carrera']) || ! empty($practica['periodo_inicio']))
                <p>
                    Su acompañamiento se desarrolló en
                    @if (! empty($practica['carrera']))la carrera <strong>{{ $practica['carrera'] }}</strong>@endif
                    @if (! empty($practica['periodo_inicio']))
                        durante el período {{ $practica['periodo_inicio'] }} - {{ $practica['periodo_fin'] ?? '' }}
                    @endif.
                </p>
            @endif

            <p>
                Como constancia de su desempeño, adjuntamos a este correo el certificado digital correspondiente en
                formato PDF.
            </p>

            <p>Sin otro particular, le saludamos cordialmente.</p>

            <br>
            <p><strong>Secretaría de Extensión</strong><br>
                Facultad de Ingeniería<br>
                Universidad Nacional de Misiones (UNaM)</p>
        </div>
        <div class="footer">
            <p>Por favor, no respondas a este correo electrónico. Es una notificación generada automáticamente.</p>
            <p>&copy; {{ date('Y') }} Facultad de Ingeniería - UNaM</p>
        </div>
    </div>
</body>

</html>
