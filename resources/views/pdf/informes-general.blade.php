<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe general</title>
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
        <h1>Informe general</h1>

        <table class="summary">
            <tr>
                <th>Total de eventos</th>
                <th>Total de inscriptos</th>
                <th>Promedio inscriptos</th>
                <th>Asistencia global</th>
            </tr>
            <tr>
                <td>{{ $reporte['resumen']['total_eventos'] }}</td>
                <td>{{ $reporte['resumen']['total_inscriptos'] }}</td>
                <td>{{ $reporte['resumen']['promedio_inscriptos'] }}</td>
                <td>{{ $reporte['resumen']['porcentaje_asistencia'] }}%</td>
            </tr>
        </table>

        <div class="section">
            <h2>Eventos por tipo y año</h2>
            <table>
                <thead>
                    <tr>
                        <th>Año</th>
                        <th>Tipo de Evento</th>
                        <th>Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['eventos_por_tipo_y_anio'] as $fila)
                        <tr>
                            <td>{{ $fila->anio }}</td>
                            <td>{{ $fila->tipo_evento }}</td>
                            <td>{{ $fila->total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No hay datos para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Promedios por tipo de evento</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo de Evento</th>
                        <th>Total Eventos</th>
                        <th>Total Inscriptos</th>
                        <th>Promedio Inscriptos</th>
                        <th>Promedio Asistencia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['promedios_por_tipo'] as $fila)
                        <tr>
                            <td>{{ $fila['tipo_evento'] }}</td>
                            <td>{{ $fila['total_eventos'] }}</td>
                            <td>{{ $fila['total_inscripciones'] }}</td>
                            <td>{{ $fila['promedio_inscripciones'] }}</td>
                            <td>{{ $fila['promedio_asistencia'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay datos para calcular promedios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Indicadores por tipo de curso</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo de Evento</th>
                        <th>Tipo de Indicador</th>
                        <th>Indicador</th>
                        <th>Total</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['indicadores_por_tipo'] as $fila)
                        <tr>
                            <td>{{ $fila['tipo_evento'] }}</td>
                            <td>{{ $fila['tipo_indicador'] }}</td>
                            <td>{{ $fila['indicador'] }}</td>
                            <td>{{ $fila['total'] }}</td>
                            <td>{{ $fila['porcentaje'] }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No hay indicadores disponibles para este rango.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="section">
            <h2>Detalle general de eventos</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Responsable</th>
                        <th>Inscriptos</th>
                        <th>Asistencia</th>
                        <th>Certificación</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reporte['eventos_detallados'] as $evento)
                        <tr>
                            <td>{{ $evento['nombre'] }}</td>
                            <td>{{ $evento['tipo_evento'] }}</td>
                            <td>{{ $evento['fecha_inicio'] }}</td>
                            <td>{{ $evento['estado'] }}</td>
                            <td>{{ $evento['responsable'] }}</td>
                            <td>{{ $evento['total_inscripciones'] }}</td>
                            <td>{{ $evento['porcentaje_asistencia'] }}%</td>
                            <td>{{ $evento['certificacion'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">No hay eventos para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>