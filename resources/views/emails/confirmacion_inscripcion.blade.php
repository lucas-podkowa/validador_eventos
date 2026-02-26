</html>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $asunto }}</title>
</head>

<body>
    <p>Estimado/a {{ $nombre }} {{ $apellido }},</p>

    @if (Str::contains(strtolower($asunto), 'staff'))
        <p>Le informamos que su inscripción como <strong>colaborador/disertante</strong> al evento
            <strong>{{ $evento->nombre }}</strong> ha sido registrada
            exitosamente.
        </p>
        <p>Agradecemos su participación y colaboración. Si requiere información adicional, no dude en comunicarse con
            nosotros.</p>
    @elseif(Str::contains(strtolower($asunto), 'participante'))
        <p>Le informamos que su inscripción al evento <strong>{{ $evento->nombre }}</strong> ha sido registrada
            exitosamente.</p>
        <p>Gracias por su interés en participar. Si requiere información adicional, no dude en comunicarse con nosotros.
        </p>
    @else
        <p>Le informamos que se ha registrado una notificación relacionada al evento
            <strong>{{ $evento->nombre }}</strong>.
        </p>
    @endif

    <p>Atentamente,<br>
        <em>Secretaría de Extensión Universitaria - Facultad de Ingeniería</em>
    </p>
</body>

</html>
