<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <style>
        .report-chart-canvas {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 0.75rem;
            min-height: 18rem;
            padding: 1rem;
            border-radius: 0.75rem;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #e5e7eb;
        }

        .report-chart-column {
            display: flex;
            flex: 1 1 0%;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            gap: 0.75rem;
            min-width: 0;
        }

        .report-chart-track {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            width: 100%;
            height: 190px;
            padding: 0 0.5rem;
            border-radius: 1rem 1rem 0 0;
            background-color: #e2e8f0;
        }

        .report-chart-bar {
            width: 100%;
            min-height: 12px;
            border-radius: 1rem 1rem 0 0;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
        }

        .report-bar-blue {
            background: linear-gradient(180deg, #38bdf8 0%, #2563eb 100%);
        }

        .report-bar-green {
            background: linear-gradient(180deg, #2dd4bf 0%, #059669 100%);
        }

        .report-bar-violet {
            background: linear-gradient(180deg, #c084fc 0%, #7c3aed 100%);
        }

        .report-progress-track {
            height: 0.75rem;
            overflow: hidden;
            border-radius: 9999px;
            background-color: #e2e8f0;
        }

        .report-progress-track-sm {
            height: 0.625rem;
        }

        .report-progress-fill {
            height: 100%;
            border-radius: 9999px;
        }

        .report-fill-blue {
            background: linear-gradient(90deg, #2563eb 0%, #38bdf8 100%);
        }

        .report-fill-green {
            background: linear-gradient(90deg, #059669 0%, #84cc16 100%);
        }

        .report-fill-pink {
            background: linear-gradient(90deg, #d946ef 0%, #fb7185 100%);
        }

        .report-fill-orange {
            background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
        }

        .report-fill-rose {
            background: linear-gradient(90deg, #f43f5e 0%, #fb923c 100%);
        }

        @media print {

            nav,
            .print-hidden {
                display: none !important;
            }

            main {
                padding: 0 !important;
            }

            .print-section {
                break-inside: avoid;
            }
        }
    </style>

    <div class="mb-4 flex items-center justify-between print-hidden">
        <h2 class="text-xl font-bold">Informes</h2>
        <div class="flex gap-2">
            @if ($modo === 'general')
                <button type="button" wire:click="exportarPdfGeneral"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Exportar PDF
                </button>
            @else
                <button type="button" wire:click="exportarPdfCurso"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled($eventoId === '')>
                    Exportar PDF
                </button>
            @endif
        </div>
    </div>

    <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-hidden">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Modo</label>
                <select wire:model.live="modo" class="w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="general">General</option>
                    <option value="curso">Por curso</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Año</label>
                <select wire:model.live="anio" class="w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">Todos</option>
                    @foreach ($anios as $anioDisponible)
                        <option value="{{ $anioDisponible }}">{{ $anioDisponible }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Tipo de Evento</label>
                <select wire:model.live="tipoEventoId" class="w-full rounded-md border border-gray-300 px-3 py-2">
                    <option value="">Todos</option>
                    @foreach ($tiposEvento as $tipoEvento)
                        <option value="{{ $tipoEvento->tipo_evento_id }}">{{ $tipoEvento->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" wire:model.live="fechaDesde"
                    class="w-full rounded-md border border-gray-300 px-3 py-2">
                @error('fechaDesde')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" wire:model.live="fechaHasta"
                    class="w-full rounded-md border border-gray-300 px-3 py-2">
                @error('fechaHasta')
                    <span class="text-sm text-red-500">{{ $message }}</span>
                @enderror
            </div>

            @if ($modo === 'curso')
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Curso o Evento</label>
                    <select wire:model.live="eventoId" class="w-full rounded-md border border-gray-300 px-3 py-2">
                        <option value="">Seleccione un evento</option>
                        @foreach ($eventosDisponibles as $eventoDisponible)
                            <option value="{{ $eventoDisponible->evento_id }}">
                                {{ $eventoDisponible->nombre }} - {{ $eventoDisponible->tipoEvento->nombre ?? 'Sin tipo' }}
                                ({{ $eventoDisponible->fecha_inicio_formatted }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="mb-4 text-sm text-gray-600">
        <span class="font-semibold">Filtros aplicados:</span> {{ $filtrosActivos }}
    </div>

    @if ($modo === 'general' && $reporteGeneral)
        @php
            $eventosPorAnioChart = $reporteGeneral['eventos_por_tipo_y_anio']
                ->groupBy('anio')
                ->map(fn($items) => $items->sum('total'))
                ->sortKeys();
            $maxEventosPorAnio = max(1, (int) ($eventosPorAnioChart->max() ?? 0));

            $promediosPorTipoChart = collect($reporteGeneral['promedios_por_tipo']);
            $maxPromedioInscriptos = max(1, (float) ($promediosPorTipoChart->max('promedio_inscripciones') ?? 0));
            $maxTotalEventosTipo = max(1, (int) ($promediosPorTipoChart->max('total_eventos') ?? 0));
            $maxAsistenciaPromedio = max(1, (float) ($promediosPorTipoChart->max('promedio_asistencia') ?? 0));
        @endphp

        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <p class="text-sm text-gray-500">Total de eventos</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteGeneral['resumen']['total_eventos'] }}</p>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <p class="text-sm text-gray-500">Total de inscriptos</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteGeneral['resumen']['total_inscriptos'] }}</p>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <p class="text-sm text-gray-500">Promedio de inscriptos por evento</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteGeneral['resumen']['promedio_inscriptos'] }}</p>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <p class="text-sm text-gray-500">Porcentaje global de asistencia</p>
                <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteGeneral['resumen']['porcentaje_asistencia'] }}%
                </p>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Eventos por año</h3>
                    <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                </div>

                @if ($eventosPorAnioChart->isEmpty())
                    <p class="text-sm text-gray-500">No hay datos suficientes para mostrar este gráfico.</p>
                @else
                    <div class="report-chart-canvas">
                        @foreach ($eventosPorAnioChart as $anio => $total)
                            @php
                                $barHeight = max(14, round(($total / $maxEventosPorAnio) * 100));
                            @endphp
                            <div class="report-chart-column">
                                <span class="text-xs font-semibold text-slate-500">{{ $total }}</span>
                                <div class="report-chart-track">
                                    <div class="report-chart-bar report-bar-blue" style="height: {{ $barHeight }}%;"></div>
                                </div>
                                <span class="text-xs font-medium text-slate-600">{{ $anio }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Volumen por tipo de evento</h3>
                    <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                </div>

                @if ($promediosPorTipoChart->isEmpty())
                    <p class="text-sm text-gray-500">No hay tipos de evento para graficar.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($promediosPorTipoChart as $fila)
                            @php
                                $eventosWidth = max(6, round(($fila['total_eventos'] / $maxTotalEventosTipo) * 100));
                                $promedioWidth = max(6, round(($fila['promedio_inscripciones'] / $maxPromedioInscriptos) * 100));
                            @endphp
                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <div class="mb-2 flex items-center justify-between text-sm font-semibold text-slate-700">
                                    <span>{{ $fila['tipo_evento'] }}</span>
                                    <span>{{ $fila['total_eventos'] }} eventos</span>
                                </div>
                                <div class="report-progress-track mb-3">
                                    <div class="report-progress-fill report-fill-blue" style="width: {{ $eventosWidth }}%;"></div>
                                </div>
                                <div
                                    class="mb-2 flex items-center justify-between text-xs font-medium uppercase tracking-wide text-slate-500">
                                    <span>Promedio de inscriptos</span>
                                    <span>{{ $fila['promedio_inscripciones'] }}</span>
                                </div>
                                <div class="report-progress-track report-progress-track-sm">
                                    <div class="report-progress-fill report-fill-green" style="width: {{ $promedioWidth }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Asistencia promedio por tipo</h3>
                    <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                </div>

                @if ($promediosPorTipoChart->isEmpty())
                    <p class="text-sm text-gray-500">No hay información de asistencia para mostrar.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($promediosPorTipoChart as $fila)
                            @php
                                $asistenciaWidth = max(4, round(($fila['promedio_asistencia'] / $maxAsistenciaPromedio) * 100));
                            @endphp
                            <div>
                                <div class="mb-2 flex items-center justify-between text-sm font-medium text-slate-700">
                                    <span>{{ $fila['tipo_evento'] }}</span>
                                    <span>{{ $fila['promedio_asistencia'] }}%</span>
                                </div>
                                <div class="report-progress-track">
                                    <div class="report-progress-fill report-fill-pink" style="width: {{ $asistenciaWidth }}%;">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Distribución de indicadores</h3>
                    <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                </div>

                @if ($reporteGeneral['indicadores_por_tipo']->isEmpty())
                    <p class="text-sm text-gray-500">No hay indicadores registrados para visualizar.</p>
                @else
                    @php
                        $indicadoresPorTipoEvento = collect($reporteGeneral['indicadores_por_tipo'])
                            ->groupBy('tipo_evento');
                    @endphp
                    <div class="space-y-5">
                        @foreach ($indicadoresPorTipoEvento as $tipoEvento => $indicadores)
                            <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                <p class="mb-3 text-sm font-semibold text-slate-700">{{ $tipoEvento }}</p>
                                <div class="space-y-3">
                                    @foreach ($indicadores->take(4) as $fila)
                                        <div>
                                            <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
                                                <span>{{ $fila['indicador'] }}</span>
                                                <span>{{ $fila['porcentaje'] }}%</span>
                                            </div>
                                            <div class="report-progress-track report-progress-track-sm">
                                                <div class="report-progress-fill report-fill-orange"
                                                    style="width: {{ max(4, round($fila['porcentaje'])) }}%;"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
            <h3 class="mb-4 text-lg font-semibold">Cantidad de eventos por tipo y año</h3>
            @if ($reporteGeneral['eventos_por_tipo_y_anio']->isEmpty())
                <p class="text-sm text-gray-500">No hay datos para los filtros seleccionados.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Año</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo de Evento</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($reporteGeneral['eventos_por_tipo_y_anio'] as $fila)
                                <tr>
                                    <td class="px-4 py-2">{{ $fila->anio }}</td>
                                    <td class="px-4 py-2">{{ $fila->tipo_evento }}</td>
                                    <td class="px-4 py-2">{{ $fila->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
            <h3 class="mb-4 text-lg font-semibold">Promedios por tipo de evento</h3>
            @if ($reporteGeneral['promedios_por_tipo']->isEmpty())
                <p class="text-sm text-gray-500">No hay datos para calcular promedios.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo de Evento</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total Eventos</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total Inscriptos</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Promedio Inscriptos</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Promedio Asistencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($reporteGeneral['promedios_por_tipo'] as $fila)
                                <tr>
                                    <td class="px-4 py-2">{{ $fila['tipo_evento'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['total_eventos'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['total_inscripciones'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['promedio_inscripciones'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['promedio_asistencia'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
            <h3 class="mb-4 text-lg font-semibold">Indicadores por tipo de curso</h3>
            @if ($reporteGeneral['indicadores_por_tipo']->isEmpty())
                <p class="text-sm text-gray-500">No existen indicadores registrados para los filtros seleccionados.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo de Evento</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo de Indicador</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Indicador</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Porcentaje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($reporteGeneral['indicadores_por_tipo'] as $fila)
                                <tr>
                                    <td class="px-4 py-2">{{ $fila['tipo_evento'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['tipo_indicador'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['indicador'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['total'] }}</td>
                                    <td class="px-4 py-2">{{ $fila['porcentaje'] }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
            <h3 class="mb-4 text-lg font-semibold">Detalle general de eventos</h3>
            @if ($reporteGeneral['eventos_detallados']->isEmpty())
                <p class="text-sm text-gray-500">No hay eventos para mostrar.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Nombre</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Fecha</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Estado</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Responsable</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Inscriptos</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Asistencia</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Certificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($reporteGeneral['eventos_detallados'] as $evento)
                                <tr>
                                    <td class="px-4 py-2">{{ $evento['nombre'] }}</td>
                                    <td class="px-4 py-2">{{ $evento['tipo_evento'] }}</td>
                                    <td class="px-4 py-2">{{ $evento['fecha_inicio'] }}</td>
                                    <td class="px-4 py-2">{{ $evento['estado'] }}</td>
                                    <td class="px-4 py-2">{{ $evento['responsable'] }}</td>
                                    <td class="px-4 py-2">{{ $evento['total_inscripciones'] }}</td>
                                    <td class="px-4 py-2">{{ $evento['porcentaje_asistencia'] }}%</td>
                                    <td class="px-4 py-2">{{ $evento['certificacion'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @elseif ($modo === 'curso')
        @if ($reporteCurso)
            @php
                $distribucionChart = collect($reporteCurso['distribucion_inscripciones']);
                $maxDistribucion = max(1, (int) ($distribucionChart->max('total') ?? 0));
                $rolesChart = collect($reporteCurso['roles']);
                $maxRoles = max(1, (int) ($rolesChart->max('total') ?? 0));
                $sesionesChart = collect($reporteCurso['sesiones']);
                $maxSesiones = max(1, (int) ($sesionesChart->max('total_presentes') ?? 0));
                $indicadoresCursoChart = collect($reporteCurso['indicadores'])->groupBy('tipo_indicador');
            @endphp

            <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500">Curso o Evento</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $reporteCurso['evento']['nombre'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tipo de Evento</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $reporteCurso['evento']['tipo_evento'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Fecha de Inicio</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $reporteCurso['evento']['fecha_inicio'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Estado</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $reporteCurso['evento']['estado'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Responsable</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $reporteCurso['evento']['responsable'] }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Certificación</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $reporteCurso['evento']['certificacion'] }}</p>
                    </div>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <p class="text-sm text-gray-500">Total de inscripciones</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteCurso['resumen']['total_inscripciones'] }}</p>
                </div>
                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <p class="text-sm text-gray-500">Participantes</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteCurso['resumen']['total_participantes'] }}</p>
                </div>
                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <p class="text-sm text-gray-500">Staff</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteCurso['resumen']['total_staff'] }}</p>
                </div>
                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <p class="text-sm text-gray-500">Porcentaje de asistencia</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $reporteCurso['resumen']['porcentaje_asistencia'] }}%
                    </p>
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Inscripciones a lo largo del tiempo</h3>
                        <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                    </div>

                    @if ($distribucionChart->isEmpty())
                        <p class="text-sm text-gray-500">No hay fechas de inscripción registradas para este evento.</p>
                    @else
                        <div class="report-chart-canvas">
                            @foreach ($distribucionChart as $fila)
                                @php
                                    $barHeight = max(12, round(($fila['total'] / $maxDistribucion) * 100));
                                @endphp
                                <div class="report-chart-column">
                                    <span class="text-xs font-semibold text-slate-500">{{ $fila['total'] }}</span>
                                    <div class="report-chart-track">
                                        <div class="report-chart-bar report-bar-green" style="height: {{ $barHeight }}%;"></div>
                                    </div>
                                    <span class="text-center text-[11px] font-medium text-slate-600">{{ $fila['fecha'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Asistencia por sesión</h3>
                        <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                    </div>

                    @if ($sesionesChart->isEmpty())
                        <p class="text-sm text-gray-500">El evento no tiene sesiones registradas.</p>
                    @else
                        <div class="report-chart-canvas">
                            @foreach ($sesionesChart as $sesion)
                                @php
                                    $barHeight = max(12, round(($sesion['total_presentes'] / $maxSesiones) * 100));
                                @endphp
                                <div class="report-chart-column">
                                    <span class="text-xs font-semibold text-slate-500">{{ $sesion['total_presentes'] }}</span>
                                    <div class="report-chart-track">
                                        <div class="report-chart-bar report-bar-violet" style="height: {{ $barHeight }}%;"></div>
                                    </div>
                                    <span class="text-center text-[11px] font-medium text-slate-600">{{ $sesion['nombre'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Distribución por rol</h3>
                        <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                    </div>

                    @if ($rolesChart->isEmpty())
                        <p class="text-sm text-gray-500">No hay roles registrados para este evento.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($rolesChart as $fila)
                                @php
                                    $barWidth = max(6, round(($fila->total / $maxRoles) * 100));
                                @endphp
                                <div>
                                    <div class="mb-2 flex items-center justify-between text-sm font-medium text-slate-700">
                                        <span>{{ $fila->rol }}</span>
                                        <span>{{ $fila->total }}</span>
                                    </div>
                                    <div class="report-progress-track">
                                        <div class="report-progress-fill report-fill-blue" style="width: {{ $barWidth }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Indicadores del curso</h3>
                        <span class="text-xs uppercase tracking-wide text-gray-400">Gráfico</span>
                    </div>

                    @if ($indicadoresCursoChart->isEmpty())
                        <p class="text-sm text-gray-500">Este evento no tiene indicadores asociados o aún no hay respuestas
                            registradas.</p>
                    @else
                        <div class="space-y-5">
                            @foreach ($indicadoresCursoChart as $tipoIndicador => $filas)
                                <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-3">
                                    <p class="mb-3 text-sm font-semibold text-slate-700">{{ $tipoIndicador }}</p>
                                    <div class="space-y-3">
                                        @foreach ($filas as $fila)
                                            <div>
                                                <div class="mb-1 flex items-center justify-between text-xs text-slate-500">
                                                    <span>{{ $fila['indicador'] }}</span>
                                                    <span>{{ $fila['porcentaje'] }}%</span>
                                                </div>
                                                <div class="report-progress-track report-progress-track-sm">
                                                    <div class="report-progress-fill report-fill-rose"
                                                        style="width: {{ max(4, round($fila['porcentaje'])) }}%;"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            @if ($reporteCurso['aprobacion'])
                <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
                    <h3 class="mb-4 text-lg font-semibold">Aprobaciones</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <p class="text-sm text-gray-500">Total evaluados</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $reporteCurso['aprobacion']['total_evaluados'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Aprobados</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $reporteCurso['aprobacion']['aprobados'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Porcentaje de aprobación</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $reporteCurso['aprobacion']['porcentaje_aprobacion'] }}%
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
                <h3 class="mb-4 text-lg font-semibold">Distribución de inscripciones por fecha</h3>
                @if ($reporteCurso['distribucion_inscripciones']->isEmpty())
                    <p class="text-sm text-gray-500">No hay fechas de inscripción registradas para este evento.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Fecha</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Inscripciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($reporteCurso['distribucion_inscripciones'] as $fila)
                                    <tr>
                                        <td class="px-4 py-2">{{ $fila['fecha'] }}</td>
                                        <td class="px-4 py-2">{{ $fila['total'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
                <h3 class="mb-4 text-lg font-semibold">Distribución de inscripciones por rol</h3>
                @if ($reporteCurso['roles']->isEmpty())
                    <p class="text-sm text-gray-500">No hay roles registrados para este evento.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Rol</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($reporteCurso['roles'] as $fila)
                                    <tr>
                                        <td class="px-4 py-2">{{ $fila->rol }}</td>
                                        <td class="px-4 py-2">{{ $fila->total }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mb-6 rounded-lg border bg-white p-4 shadow-sm print-section">
                <h3 class="mb-4 text-lg font-semibold">Asistencia por sesión</h3>
                @if ($reporteCurso['sesiones']->isEmpty())
                    <p class="text-sm text-gray-500">El evento no tiene sesiones registradas.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Sesión</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Fecha y hora</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Presentes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($reporteCurso['sesiones'] as $sesion)
                                    <tr>
                                        <td class="px-4 py-2">{{ $sesion['nombre'] }}</td>
                                        <td class="px-4 py-2">{{ $sesion['fecha_hora_inicio'] }}</td>
                                        <td class="px-4 py-2">{{ $sesion['total_presentes'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-lg border bg-white p-4 shadow-sm print-section">
                <h3 class="mb-4 text-lg font-semibold">Indicadores del curso</h3>
                @if ($reporteCurso['indicadores']->isEmpty())
                    <p class="text-sm text-gray-500">Este evento no tiene indicadores asociados o aún no hay respuestas registradas.
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Tipo de Indicador</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Indicador</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Total</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach ($reporteCurso['indicadores'] as $fila)
                                    <tr>
                                        <td class="px-4 py-2">{{ $fila['tipo_indicador'] }}</td>
                                        <td class="px-4 py-2">{{ $fila['indicador'] }}</td>
                                        <td class="px-4 py-2">{{ $fila['total'] }}</td>
                                        <td class="px-4 py-2">{{ $fila['porcentaje'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-lg border bg-white p-6 text-sm text-gray-500 shadow-sm">
                Seleccione un curso o evento para ver su informe individual.
            </div>
        @endif
    @endif
</div>