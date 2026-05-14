<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe por curso</title>
    <style>
        @page {
            margin: 70px 25px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 8px;
        }

        header {
            position: fixed;
            top: -55px;
            left: 0;
            right: 0;
            text-align: right;
            font-size: 10px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 18px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
        }

        .summary td {
            width: 25%;
        }

        .section {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <header>
        Generado: {{ $fechaGeneracion }}<br>
        Filtros: {{ $filtros }}
    </header>

    <main>
        <h1>Informe por curso</h1>

        <table>
            <tbody>
                <tr>
                    <th>Curso o Evento</th>
                    <td>{{ $reporte['evento']['nombre'] }}</td>
                    <th>Tipo</th>
                    <td>{{ $reporte['evento']['tipo_evento'] }}</td>
                </tr>
                <tr>
                    <th>Fecha</th>
                    <td>{{ $reporte['evento']['fecha_inicio'] }}</td>
                    <th>Estado</th>
                    <td>{{ $reporte['evento']['estado'] }}</td>
                </tr>
                <tr>
                    <th>Responsable</th>
                    <td>{{ $reporte['evento']['responsable'] }}</td>
                    <th>Certificación</th>
                    <td>{{ $reporte['evento']['certificacion'] }}</td>
                </tr>
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <th>Total inscripciones</th>
                <th>Participantes</th>
                <th>Staff</th>
                <th>Asistencia</th>
            </tr>
            <tr>
                <td>{{ $reporte['resumen']['total_inscripciones'] }}</td>
                <td>{{ $reporte['resumen']['total_participantes'] }}</td>
                <td>{{ $reporte['resumen']['total_staff'] }}</td>
                <td>{{ $reporte['resumen']['porcentaje_asistencia'] }}%</td>
            </tr>
        </table>

        @if ($reporte['aprobacion'])
            <div class="section">
                <h2>Aprobaciones</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Total evaluados</th>
                            <th>Aprobados</th>
                            <th>Porcentaje de aprobación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $reporte['aprobacion']['total_evaluados'] }}</td>
                            <td>{{ $reporte['aprobacion']['aprobados'] }}</td>
                            <td>{{ $reporte['aprobacion']['porcentaje_aprobacion'] }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <div class="section">
            <h2>Distribución de inscripciones por fecha</h2>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Inscripciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['distribucion_inscripciones'] as $fila)
                        <tr>
                            <td>{{ $fila['fecha'] }}</td>
                            <td>{{ $fila['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No hay fechas de inscripción registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Inscripciones por rol</h2>
            <table>
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['roles'] as $fila)
                        <tr>
                            <td>{{ $fila->rol }}</td>
                            <td>{{ $fila->total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No hay roles registrados para este evento.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Asistencia por sesión</h2>
            <table>
                <thead>
                    <tr>
                        <th>Sesión</th>
                        <th>Fecha y hora</th>
                        <th>Presentes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['sesiones'] as $sesion)
                        <tr>
                            <td>{{ $sesion['nombre'] }}</td>
                            <td>{{ $sesion['fecha_hora_inicio'] }}</td>
                            <td>{{ $sesion['total_presentes'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">El evento no tiene sesiones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Indicadores del curso</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo de Indicador</th>
                        <th>Indicador</th>
                        <th>Total</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['indicadores'] as $fila)
                        <tr>
                            <td>{{ $fila['tipo_indicador'] }}</td>
                            <td>{{ $fila['indicador'] }}</td>
                            <td>{{ $fila['total'] }}</td>
                            <td>{{ $fila['porcentaje'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No hay indicadores asociados o aún no existen respuestas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>