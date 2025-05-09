<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asistencias - {{ $evento->nombre }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h2>Asistencias - {{ $evento->nombre }}</h2>
    <table>
        <thead>
            <tr>
                <th>Participante</th>
                <th>DNI</th>
                @foreach ($sesiones as $sesion)
                    <th>{{ $sesion->nombre }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($datos as $fila)
                <tr>
                    <td>{{ $fila['nombre'] }}</td>
                    <td>{{ $fila['dni'] }}</td>
                    @foreach ($fila['asistencias'] as $estado)
                        <td>{{ $estado ? 'SI' : 'NO' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
