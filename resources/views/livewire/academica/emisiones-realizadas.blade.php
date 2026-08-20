<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="py-2">
        <h2 class="text-xl font-bold mb-4">Emisiones Realizadas</h2>
    </div>

    {{-- Filtros --}}
    <div class="border border-gray-200 rounded-xl bg-white shadow-sm p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Carrera</label>
                <select wire:model.live="carrera_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Todas</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Título intermedio</label>
                <select wire:model.live="titulo_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">Todos</option>
                    @foreach ($titulos as $titulo)
                        <option value="{{ $titulo->id }}">{{ $titulo->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre / Apellido</label>
                <input type="text" wire:model.live.debounce.300ms="buscar_nombre"
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Buscar por nombre...">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">DNI</label>
                <input type="text" wire:model.live.debounce.300ms="buscar_dni"
                    class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Buscar por DNI...">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
                <input type="date" wire:model.live="fecha_desde" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                <input type="date" wire:model.live="fecha_hasta" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
            </div>
        </div>

        <div class="mt-3 flex items-center gap-4">
            <label class="inline-flex items-center text-sm">
                <input type="checkbox" wire:model.live="solo_vigentes" class="mr-2">
                Solo vigentes (no mostrar anulados)
            </label>
            <span class="text-xs text-gray-400">{{ $emisiones->total() }} resultado(s)</span>
        </div>
    </div>

    @if ($emisiones->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Fecha</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Estudiante</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Carrera</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Título intermedio</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Estado</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($emisiones as $emision)
                        <tr class="{{ $emision->anulado ? 'opacity-60' : '' }}">
                            <td class="px-4 py-2 text-sm">{{ $emision->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <span class="font-medium">{{ $emision->participante->apellido }}, {{ $emision->participante->nombre }}</span>
                                <span class="block text-xs text-gray-400">DNI: {{ $emision->participante->dni }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $emision->tituloIntermedio->carrera->nombre }}</td>
                            <td class="px-4 py-2 text-sm">{{ $emision->tituloIntermedio->nombre }}</td>
                            <td class="px-4 py-2 text-center">
                                @if ($emision->anulado)
                                    <span class="inline-block bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full font-semibold">
                                        NO VÁLIDO · ANULADO
                                    </span>
                                @else
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full font-semibold">
                                        VÁLIDO
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <a href="{{ route('academica.ver_certificado', $emision->id) }}" target="_blank"
                                    class="btn-action-edit" title="Ver / Imprimir">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $emisiones->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron emisiones.</div>
    @endif
</div>
