<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 24px;
        }

        .header {
            background-color: #1e3a5f;
            color: #fff;
            padding: 20px;
            border-radius: 6px 6px 0 0;
        }

        .body {
            background-color: #f9fafb;
            padding: 24px;
            border: 1px solid #e5e7eb;
        }

        .credentials {
            background-color: #fff;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 16px;
            margin: 16px 0;
        }

        .credentials p {
            margin: 4px 0;
            font-size: 15px;
        }

        .credentials strong {
            font-weight: bold;
            color: #1e3a5f;
        }

        .footer {
            font-size: 12px;
            color: #6b7280;
            margin-top: 24px;
        }

        .btn {
            display: inline-block;
            background-color: #1e3a5f;
            color: #fff;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 16px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2 style="margin:0;">Secretaría de Extensión Universitaria</h2>
            <p style="margin:4px 0 0;">Facultad de Ingeniería — UNaM</p>
        </div>
        <div class="body">
            <p>Estimado/a <strong>{{ $apellido }}, {{ $nombre }}</strong>:</p>

            @if ($usuarioNuevo)
                <p>
                    Se ha creado una cuenta de acceso al sistema de gestión de eventos para que puedas
                    desempeñar el rol de <strong>Colaborador</strong> en el evento
                    <strong>{{ $evento->nombre }}</strong>.
                </p>
                <p>A continuación encontrarás tus credenciales de acceso:</p>
                <div class="credentials">
                    <p><strong>Usuario (email):</strong> {{ $email }}</p>
                    <p><strong>Contraseña:</strong> {{ $password }}</p>
                </div>
                <p style="color:#d97706; font-size:13px;">
                    Por razones de seguridad, te recomendamos cambiar tu contraseña en tu primer inicio de sesión.
                </p>
            @else
                <p>
                    Tu cuenta existente ha sido actualizada con el rol de <strong>Colaborador</strong>
                    para el evento <strong>{{ $evento->nombre }}</strong>.
                </p>
                <p>Puedes ingresar al sistema con tu email y contraseña habituales.</p>
            @endif

            <a href="{{ url('/login') }}" class="btn">Ingresar al sistema</a>

            <p class="footer">
                Si no esperabas este mensaje o creés que es un error, por favor comunicáte con la secretaría.<br>
                Atentamente,<br>
                <em>Secretaría de Extensión Universitaria — Facultad de Ingeniería</em>
            </p>
        </div>
    </div>
</body>

</html>
