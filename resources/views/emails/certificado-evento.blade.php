<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Participación</title>
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
            /* Azul oscuro institucional */
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

        .logo {
            max-width: 150px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="email-container">
        <div class="header">
            {{-- Opcional: Si tienes el logo de la facultad, puedes agregarlo aquí --}}
            {{-- <img src="URL_DEL_LOGO" alt="Logo Facultad de Ingeniería" class="logo"> --}}
            <h1>Facultad de Ingeniería</h1>
            <p>Universidad Nacional de Misiones</p>
        </div>
        <div class="content">
            <p>Estimado/a <strong>{{ $participante->nombre }} {{ $participante->apellido }}</strong>,</p>

            <p>
                La Secretaría de Extensión de la Facultad de Ingeniería tiene el agrado de contactarte para extenderte
                nuestras más sinceras felicitaciones por tu valiosa participación en el evento:
            </p>

            <p style="text-align: center; font-size: 18px;">
                <strong>"{{ $evento->nombre }}"</strong>
            </p>

            <p>
                Reconocemos y agradecemos tu interés y dedicación. Como constancia de tu participación, nos complace
                adjuntar a este correo el certificado digital correspondiente en formato PDF.
            </p>

            <p>
                Esperamos que la experiencia haya sido enriquecedora y contamos con tu presencia en futuras actividades
                académicas y de extensión.
            </p>

            <p>Sin otro particular, te saludamos cordialmente.</p>

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
