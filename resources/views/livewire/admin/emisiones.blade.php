<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Certificados emitidos</h2>

        @if ($search || $tipo_reconocimiento_id || $origen || $estado || $desde || $hasta)
            <button type="button" wire:click="limpiarFiltros"
                class="text-sm text-blue-600 hover:underline">
                Limpiar filtros
            </button>
        @endif
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-12">
        <input type="text" wire:model.live.debounce.400ms="search"
            class="w-full px-3 py-2 border rounded-md focus:ring focus:ring-blue-300 sm:col-span-1 lg:col-span-8"
            placeholder="Buscar por nombre, apellido, DNI o referencia...">

        <select wire:model.live="origen" class="w-full px-3 py-2 border rounded-md sm:col-span-1 lg:col-span-4">
            <option value="">Todos los orígenes</option>
            <option value="api">Solo API</option>
            <option value="interno">Solo internos</option>
        </select>

        <select wire:model.live="tipo_reconocimiento_id" class="w-full px-3 py-2 border rounded-md lg:col-span-4">
            <option value="">Todos los tipos</option>
            @foreach ($tipos as $tipo)
                <option value="{{ $tipo->tipo_reconocimiento_id }}">{{ $tipo->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="estado" class="w-full px-3 py-2 border rounded-md lg:col-span-4">
            <option value="">Todos los estados</option>
            <option value="emitido">Emitido</option>
            <option value="anulado">Anulado</option>
        </select>

        <div class="flex gap-2 lg:col-span-4">
            <input type="date" wire:model.live="desde" class="w-full px-3 py-2 border rounded-md" title="Emitidos desde">
            <input type="date" wire:model.live="hasta" class="w-full px-3 py-2 border rounded-md" title="Emitidos hasta">
        </div>
    </div>

    @if ($emisiones->count() > 0)
        <div class="overflow-x-auto bg-white shadow border-b border-gray-200 sm:rounded-lg">
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th wire:click="order('receptor')"
                            class="px-4 py-3 text-xs font-medium border text-gray-500 text-left cursor-pointer select-none">
                            <span>Receptor</span>
                            @if ($sort === 'receptor')
                                <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th wire:click="order('tipo')"
                            class="px-4 py-3 text-xs font-medium border text-gray-500 text-left cursor-pointer select-none">
                            <span>Tipo</span>
                            @if ($sort === 'tipo')
                                <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th wire:click="order('origen')"
                            class="px-4 py-3 text-xs font-medium border text-gray-500 text-left cursor-pointer select-none">
                            <span>Origen</span>
                            @if ($sort === 'origen')
                                <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th wire:click="order('procedencia')"
                            class="px-4 py-3 text-xs font-medium border text-gray-500 text-left cursor-pointer select-none">
                            <span>Procedencia</span>
                            @if ($sort === 'procedencia')
                                <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th wire:click="order('estado')"
                            class="px-4 py-3 text-xs font-medium border text-gray-500 text-center cursor-pointer select-none">
                            <span>Estado</span>
                            @if ($sort === 'estado')
                                <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th wire:click="order('fecha')"
                            class="px-4 py-3 text-xs font-medium border text-gray-500 text-center cursor-pointer select-none">
                            <span>Fecha</span>
                            @if ($sort === 'fecha')
                                <i class="fas {{ $direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down' }} float-right mt-1"></i>
                            @else
                                <i class="fas fa-sort float-right mt-1"></i>
                            @endif
                        </th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($emisiones as $emision)
                        <tr wire:key="emision-{{ $emision->emision_id }}">
                            <td class="px-4 py-2">
                                <p class="font-medium">{{ $emision->participante?->apellido }}, {{ $emision->participante?->nombre }}</p>
                                <p class="text-xs text-gray-500">DNI {{ $emision->participante?->dni }}</p>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $emision->tipoReconocimiento?->nombre ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">{{ $emision->origen?->nombre ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">
                                @if ($emision->api_cliente_id)
                                    <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">
                                        API · {{ $emision->apiCliente?->nombre ?? 'Cliente' }}
                                    </span>
                                @else
                                    <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">Interno</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                @if ($emision->estado === \App\Models\Emision::ESTADO_ANULADO)
                                    <span class="inline-block bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full">Anulado</span>
                                @else
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Emitido</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center text-sm text-gray-500">
                                {{ ($emision->emitida_en ?? $emision->created_at)?->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                @if ($emision->certificado_path)
                                    <a href="{{ route('ver.emision', $emision) }}" target="_blank"
                                        class="btn-action-edit" title="Descargar">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="py-4">{{ $emisiones->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron certificados.</div>
    @endif
</div>
