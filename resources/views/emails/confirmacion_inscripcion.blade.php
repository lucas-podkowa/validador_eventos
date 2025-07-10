<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Confirmación de inscripción</title>
</head>

<body>
    <p>Estimado/a {{ $nombre }} {{ $apellido }},</p>

    <p>Le informamos que su inscripción al evento <strong>{{ $evento->nombre }}</strong> ha sido registrada
        exitosamente.</p>

    <p>Gracias por su interés en participar. Si requiere información adicional, no dude en comunicarse con nosotros.</p>

    <p>Atentamente,<br>
        <em>Secretaría de Extensión Universitaria - Facultad de Ingeniería</em>
    </p>
</body>

</html>
