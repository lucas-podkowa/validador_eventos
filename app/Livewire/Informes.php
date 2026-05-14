<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\Rol;
use App\Models\TipoEvento;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class Informes extends Component
{
    public $modo = 'general';
    public $anio = '';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $tipoEventoId = '';
    public $eventoId = '';
    public $tiposEvento = [];
    public $anios = [];
    public $roles = [];

    public function mount(): void
    {
        abort_if(!auth()->user()->hasRole('Administrador'), 403, 'Solo el Administrador puede acceder a Informes.');

        $this->tiposEvento = TipoEvento::orderBy('nombre')->get();
        $this->anios = Evento::query()
            ->selectRaw('YEAR(fecha_inicio) as anio')
            ->whereNotNull('fecha_inicio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->filter()
            ->map(fn($anio) => (string) $anio)
            ->values()
            ->all();
        $this->roles = Rol::whereIn('nombre', ['Participante', 'Disertante', 'Colaborador'])
            ->pluck('rol_id', 'nombre')
            ->toArray();
    }

    protected function rules(): array
    {
        return [
            'fechaDesde' => 'nullable|date',
            'fechaHasta' => 'nullable|date|after_or_equal:fechaDesde',
        ];
    }

    public function updated($property): void
    {
        if (in_array($property, ['fechaDesde', 'fechaHasta'], true)) {
            $this->validateOnly($property);
        }

        if (in_array($property, ['anio', 'fechaDesde', 'fechaHasta', 'tipoEventoId'], true)) {
            $this->syncEventoSeleccionado();
        }
    }

    public function updatedModo($value): void
    {
        if ($value !== 'curso') {
            $this->eventoId = '';
        }
    }

    public function exportarPdfGeneral()
    {
        $this->validate();

        $reporte = $this->buildGeneralReport();

        $pdf = Pdf::loadView('pdf.informes-general', [
            'reporte' => $reporte,
            'filtros' => $this->activeFiltersLabel(),
            'fechaGeneracion' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'informe-general-' . now()->format('Ymd-His') . '.pdf');
    }

    public function exportarPdfCurso()
    {
        $this->validate();

        if ($this->eventoId === '') {
            $this->dispatch('oops', message: 'Seleccione un curso o evento antes de exportar.');
            return;
        }

        $reporte = $this->buildCourseReport($this->eventoId);

        if (!$reporte) {
            $this->dispatch('oops', message: 'No se encontró información para el curso seleccionado.');
            return;
        }

        $pdf = Pdf::loadView('pdf.informes-curso', [
            'reporte' => $reporte,
            'filtros' => $this->activeFiltersLabel(),
            'fechaGeneracion' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'informe-' . Str::slug($reporte['evento']['nombre']) . '-' . now()->format('Ymd-His') . '.pdf');
    }

    public function render()
    {
        $eventosDisponibles = $this->filteredEventosQuery()
            ->with('tipoEvento')
            ->orderByDesc('fecha_inicio')
            ->get();

        return view('livewire.informes', [
            'eventosDisponibles' => $eventosDisponibles,
            'reporteGeneral' => $this->modo === 'general' ? $this->buildGeneralReport() : null,
            'reporteCurso' => $this->modo === 'curso' && $this->eventoId !== '' ? $this->buildCourseReport($this->eventoId) : null,
            'filtrosActivos' => $this->activeFiltersLabel(),
        ]);
    }

    private function filteredEventosQuery()
    {
        $query = Evento::query();

        return $this->applyEventoFilters($query);
    }

    private function applyEventoFilters($query, string $table = 'evento')
    {
        return $query
            ->when($this->anio !== '', function ($innerQuery) use ($table) {
                $innerQuery->whereYear("{$table}.fecha_inicio", $this->anio);
            })
            ->when($this->fechaDesde !== '', function ($innerQuery) use ($table) {
                $innerQuery->whereDate("{$table}.fecha_inicio", '>=', $this->fechaDesde);
            })
            ->when($this->fechaHasta !== '', function ($innerQuery) use ($table) {
                $innerQuery->whereDate("{$table}.fecha_inicio", '<=', $this->fechaHasta);
            })
            ->when($this->tipoEventoId !== '', function ($innerQuery) use ($table) {
                $innerQuery->where("{$table}.tipo_evento_id", $this->tipoEventoId);
            });
    }

    private function buildGeneralReport(): array
    {
        $eventosDetallados = $this->applyEventoFilters(
            DB::table('evento')
                ->leftJoin('tipo_evento', 'evento.tipo_evento_id', '=', 'tipo_evento.tipo_evento_id')
                ->leftJoin('responsable', 'evento.responsable_id', '=', 'responsable.responsable_id')
                ->leftJoin('planilla_inscripcion as planilla', 'evento.evento_id', '=', 'planilla.evento_id')
                ->leftJoin('inscripcion_participante as inscripcion', 'planilla.planilla_inscripcion_id', '=', 'inscripcion.planilla_id')
                ->leftJoin('asistencia_participante as asistencia', 'inscripcion.inscripcion_participante_id', '=', 'asistencia.inscripcion_participante_id')
        )
            ->selectRaw(
                "evento.evento_id,
                evento.nombre,
                evento.fecha_inicio,
                evento.estado,
                evento.por_aprobacion,
                tipo_evento.nombre as tipo_evento,
                TRIM(CONCAT(COALESCE(responsable.apellido, ''), CASE WHEN responsable.apellido IS NOT NULL AND responsable.nombre IS NOT NULL THEN ', ' ELSE '' END, COALESCE(responsable.nombre, ''))) as responsable,
                COUNT(DISTINCT inscripcion.inscripcion_participante_id) as total_inscripciones,
                COUNT(DISTINCT CASE WHEN asistencia.asistio = 1 THEN inscripcion.inscripcion_participante_id END) as total_con_asistencia"
            )
            ->groupBy(
                'evento.evento_id',
                'evento.nombre',
                'evento.fecha_inicio',
                'evento.estado',
                'evento.por_aprobacion',
                'tipo_evento.nombre',
                'responsable.apellido',
                'responsable.nombre'
            )
            ->orderByDesc('evento.fecha_inicio')
            ->get()
            ->map(function ($evento) {
                $totalIncripciones = (int) $evento->total_inscripciones;
                $totalConAsistencia = (int) $evento->total_con_asistencia;

                return [
                    'evento_id' => $evento->evento_id,
                    'nombre' => $evento->nombre,
                    'tipo_evento' => $evento->tipo_evento ?? 'Sin tipo',
                    'fecha_inicio' => $evento->fecha_inicio ? date('d/m/Y', strtotime($evento->fecha_inicio)) : 'Sin fecha',
                    'estado' => $evento->estado,
                    'responsable' => trim((string) $evento->responsable) !== '' ? $evento->responsable : 'Sin asignar',
                    'certificacion' => $evento->por_aprobacion ? 'Por Aprobación' : 'Por Asistencia',
                    'total_inscripciones' => $totalIncripciones,
                    'total_con_asistencia' => $totalConAsistencia,
                    'porcentaje_asistencia' => $totalIncripciones > 0
                        ? round(($totalConAsistencia / $totalIncripciones) * 100, 2)
                        : 0,
                ];
            })
            ->values();

        $totalEventos = $eventosDetallados->count();
        $totalInscriptos = $eventosDetallados->sum('total_inscripciones');
        $totalConAsistencia = $eventosDetallados->sum('total_con_asistencia');

        $eventosPorTipoYAnio = $this->applyEventoFilters(
            DB::table('evento')
                ->join('tipo_evento', 'evento.tipo_evento_id', '=', 'tipo_evento.tipo_evento_id')
        )
            ->selectRaw('YEAR(evento.fecha_inicio) as anio, tipo_evento.nombre as tipo_evento, COUNT(*) as total')
            ->groupBy('anio', 'tipo_evento.nombre')
            ->orderByDesc('anio')
            ->orderBy('tipo_evento.nombre')
            ->get();

        $promediosPorTipo = $eventosDetallados
            ->groupBy('tipo_evento')
            ->map(function (Collection $items, string $tipoEvento) {
                return [
                    'tipo_evento' => $tipoEvento,
                    'total_eventos' => $items->count(),
                    'total_inscripciones' => $items->sum('total_inscripciones'),
                    'promedio_inscripciones' => round($items->avg('total_inscripciones') ?? 0, 2),
                    'promedio_asistencia' => round($items->avg('porcentaje_asistencia') ?? 0, 2),
                ];
            })
            ->values();

        $indicadoresPorTipo = $this->applyIndicatorPercentages(
            $this->applyEventoFilters(
                DB::table('participante_indicador as participante_indicador')
                    ->join('inscripcion_participante as inscripcion', 'participante_indicador.insc_participante_id', '=', 'inscripcion.inscripcion_participante_id')
                    ->join('planilla_inscripcion as planilla', 'inscripcion.planilla_id', '=', 'planilla.planilla_inscripcion_id')
                    ->join('evento', 'planilla.evento_id', '=', 'evento.evento_id')
                    ->join('tipo_evento', 'evento.tipo_evento_id', '=', 'tipo_evento.tipo_evento_id')
                    ->join('indicador', 'participante_indicador.indicador_id', '=', 'indicador.indicador_id')
                    ->join('tipo_indicador', 'indicador.tipo_indicador_id', '=', 'tipo_indicador.tipo_indicador_id')
            )
                ->selectRaw('tipo_evento.nombre as tipo_evento, tipo_indicador.nombre as tipo_indicador, indicador.nombre as indicador, COUNT(*) as total')
                ->groupBy('tipo_evento.nombre', 'tipo_indicador.nombre', 'indicador.nombre')
                ->orderBy('tipo_evento.nombre')
                ->orderBy('tipo_indicador.nombre')
                ->orderByDesc('total')
                ->get(),
            ['tipo_evento', 'tipo_indicador']
        );

        return [
            'resumen' => [
                'total_eventos' => $totalEventos,
                'total_inscriptos' => $totalInscriptos,
                'promedio_inscriptos' => $totalEventos > 0 ? round($totalInscriptos / $totalEventos, 2) : 0,
                'porcentaje_asistencia' => $totalInscriptos > 0 ? round(($totalConAsistencia / $totalInscriptos) * 100, 2) : 0,
            ],
            'eventos_por_tipo_y_anio' => $eventosPorTipoYAnio,
            'promedios_por_tipo' => $promediosPorTipo,
            'indicadores_por_tipo' => $indicadoresPorTipo,
            'eventos_detallados' => $eventosDetallados,
        ];
    }

    private function buildCourseReport(string $eventoId): ?array
    {
        $evento = Evento::with(['tipoEvento', 'responsable', 'planillaInscripcion'])
            ->find($eventoId);

        if (!$evento) {
            return null;
        }

        $inscripcionesBase = DB::table('inscripcion_participante as inscripcion')
            ->join('planilla_inscripcion as planilla', 'inscripcion.planilla_id', '=', 'planilla.planilla_inscripcion_id')
            ->where('planilla.evento_id', $evento->evento_id);

        $totalInscripciones = (clone $inscripcionesBase)->count('inscripcion.inscripcion_participante_id');
        $totalParticipantes = $this->roleId('Participante')
            ? (clone $inscripcionesBase)->where('inscripcion.rol_id', $this->roleId('Participante'))->count('inscripcion.inscripcion_participante_id')
            : 0;
        $totalStaff = (clone $inscripcionesBase)
            ->whereIn('inscripcion.rol_id', array_filter([$this->roleId('Disertante'), $this->roleId('Colaborador')]))
            ->count('inscripcion.inscripcion_participante_id');

        $inscripcionesConAsistencia = DB::table('asistencia_participante as asistencia')
            ->join('inscripcion_participante as inscripcion', 'asistencia.inscripcion_participante_id', '=', 'inscripcion.inscripcion_participante_id')
            ->join('planilla_inscripcion as planilla', 'inscripcion.planilla_id', '=', 'planilla.planilla_inscripcion_id')
            ->where('planilla.evento_id', $evento->evento_id)
            ->where('asistencia.asistio', true)
            ->distinct()
            ->count('inscripcion.inscripcion_participante_id');

        $distribucionInscripciones = (clone $inscripcionesBase)
            ->whereNotNull('inscripcion.fecha_inscripcion')
            ->selectRaw('DATE(inscripcion.fecha_inscripcion) as fecha, COUNT(*) as total')
            ->groupByRaw('DATE(inscripcion.fecha_inscripcion)')
            ->orderBy('fecha')
            ->get()
            ->map(function ($fila) {
                return [
                    'fecha' => date('d/m/Y', strtotime($fila->fecha)),
                    'total' => (int) $fila->total,
                ];
            })
            ->values();

        $roles = (clone $inscripcionesBase)
            ->join('rol', 'inscripcion.rol_id', '=', 'rol.rol_id')
            ->selectRaw('rol.nombre as rol, COUNT(*) as total')
            ->groupBy('rol.nombre')
            ->orderBy('rol.nombre')
            ->get();

        $sesiones = DB::table('sesion_evento as sesion')
            ->leftJoin('asistencia_participante as asistencia', 'sesion.sesion_evento_id', '=', 'asistencia.sesion_evento_id')
            ->where('sesion.evento_id', $evento->evento_id)
            ->selectRaw('sesion.nombre, sesion.fecha_hora_inicio, COUNT(DISTINCT CASE WHEN asistencia.asistio = 1 THEN asistencia.inscripcion_participante_id END) as total_presentes')
            ->groupBy('sesion.sesion_evento_id', 'sesion.nombre', 'sesion.fecha_hora_inicio')
            ->orderBy('sesion.fecha_hora_inicio')
            ->get()
            ->map(function ($sesion) {
                return [
                    'nombre' => $sesion->nombre,
                    'fecha_hora_inicio' => $sesion->fecha_hora_inicio
                        ? date('d/m/Y H:i', strtotime($sesion->fecha_hora_inicio))
                        : 'Sin fecha',
                    'total_presentes' => (int) $sesion->total_presentes,
                ];
            })
            ->values();

        $indicadores = $this->applyIndicatorPercentages(
            DB::table('participante_indicador as participante_indicador')
                ->join('inscripcion_participante as inscripcion', 'participante_indicador.insc_participante_id', '=', 'inscripcion.inscripcion_participante_id')
                ->join('planilla_inscripcion as planilla', 'inscripcion.planilla_id', '=', 'planilla.planilla_inscripcion_id')
                ->join('indicador', 'participante_indicador.indicador_id', '=', 'indicador.indicador_id')
                ->join('tipo_indicador', 'indicador.tipo_indicador_id', '=', 'tipo_indicador.tipo_indicador_id')
                ->where('planilla.evento_id', $evento->evento_id)
                ->selectRaw('tipo_indicador.nombre as tipo_indicador, indicador.nombre as indicador, COUNT(*) as total')
                ->groupBy('tipo_indicador.nombre', 'indicador.nombre')
                ->orderBy('tipo_indicador.nombre')
                ->orderByDesc('total')
                ->get(),
            ['tipo_indicador']
        );

        $aprobacion = null;

        if ($evento->por_aprobacion) {
            $resumenAprobacion = DB::table('evento_participantes')
                ->where('evento_id', $evento->evento_id)
                ->when($this->roleId('Participante'), function ($query) {
                    $query->where('rol_id', $this->roleId('Participante'));
                })
                ->selectRaw('COUNT(*) as total_evaluados, SUM(CASE WHEN aprobado = 1 THEN 1 ELSE 0 END) as aprobados')
                ->first();

            $totalEvaluados = (int) ($resumenAprobacion->total_evaluados ?? 0);
            $aprobados = (int) ($resumenAprobacion->aprobados ?? 0);

            $aprobacion = [
                'total_evaluados' => $totalEvaluados,
                'aprobados' => $aprobados,
                'porcentaje_aprobacion' => $totalEvaluados > 0
                    ? round(($aprobados / $totalEvaluados) * 100, 2)
                    : 0,
            ];
        }

        return [
            'evento' => [
                'evento_id' => $evento->evento_id,
                'nombre' => $evento->nombre,
                'tipo_evento' => $evento->tipoEvento->nombre ?? 'Sin tipo',
                'fecha_inicio' => $evento->fecha_inicio_formatted,
                'estado' => $evento->estado,
                'responsable' => $evento->responsable
                    ? $evento->responsable->apellido . ', ' . $evento->responsable->nombre
                    : 'Sin asignar',
                'certificacion' => $evento->por_aprobacion ? 'Por Aprobación' : 'Por Asistencia',
            ],
            'resumen' => [
                'total_inscripciones' => $totalInscripciones,
                'total_participantes' => $totalParticipantes,
                'total_staff' => $totalStaff,
                'porcentaje_asistencia' => $totalInscripciones > 0
                    ? round(($inscripcionesConAsistencia / $totalInscripciones) * 100, 2)
                    : 0,
            ],
            'aprobacion' => $aprobacion,
            'distribucion_inscripciones' => $distribucionInscripciones,
            'roles' => $roles,
            'sesiones' => $sesiones,
            'indicadores' => $indicadores,
        ];
    }

    private function applyIndicatorPercentages(Collection $rows, array $groupKeys): Collection
    {
        $totals = $rows
            ->groupBy(function ($row) use ($groupKeys) {
                return collect($groupKeys)
                    ->map(fn($key) => $row->{$key})
                    ->implode('|');
            })
            ->map(fn(Collection $items) => $items->sum('total'));

        return $rows
            ->map(function ($row) use ($groupKeys, $totals) {
                $groupKey = collect($groupKeys)
                    ->map(fn($key) => $row->{$key})
                    ->implode('|');

                $result = [];

                foreach ($groupKeys as $key) {
                    $result[$key] = $row->{$key};
                }

                $result['indicador'] = $row->indicador;
                $result['total'] = (int) $row->total;
                $result['porcentaje'] = ($totals[$groupKey] ?? 0) > 0
                    ? round(($row->total / $totals[$groupKey]) * 100, 2)
                    : 0;

                return $result;
            })
            ->values();
    }

    private function syncEventoSeleccionado(): void
    {
        if ($this->eventoId === '') {
            return;
        }

        $eventoExiste = $this->filteredEventosQuery()
            ->where('evento_id', $this->eventoId)
            ->exists();

        if (!$eventoExiste) {
            $this->eventoId = '';
        }
    }

    private function roleId(string $nombre): ?int
    {
        return $this->roles[$nombre] ?? null;
    }

    private function activeFiltersLabel(): string
    {
        $filters = [];

        if ($this->anio !== '') {
            $filters[] = 'Año: ' . $this->anio;
        }

        if ($this->fechaDesde !== '') {
            $filters[] = 'Desde: ' . date('d/m/Y', strtotime($this->fechaDesde));
        }

        if ($this->fechaHasta !== '') {
            $filters[] = 'Hasta: ' . date('d/m/Y', strtotime($this->fechaHasta));
        }

        if ($this->tipoEventoId !== '') {
            $tipoEvento = collect($this->tiposEvento)->firstWhere('tipo_evento_id', (int) $this->tipoEventoId);
            $filters[] = 'Tipo: ' . ($tipoEvento->nombre ?? 'Sin tipo');
        }

        return count($filters) > 0 ? implode(' | ', $filters) : 'Sin filtros adicionales';
    }
}