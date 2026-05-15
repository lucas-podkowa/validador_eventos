@extends('pdf.layouts.informe')

@section('document-title', 'Informe general')

@section('report-title', 'Informe general')

@section('content')
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
@endsection