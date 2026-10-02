<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Emisión masiva</h2>
        <p class="text-sm text-gray-500">Armá una lista de personas para un contexto/programa y emití los certificados en lote.</p>
    </div>

    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contexto / Programa</label>
                <select wire:model.live="contexto_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($contextos as $contexto)
                        <option value="{{ $contexto->contexto_id }}">{{ $contexto->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de reconocimiento</label>
                <select wire:model.live="tipo_reconocimiento_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo['tipo_reconocimiento_id'] }}">{{ $tipo['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plantilla</label>
                <select wire:model="plantilla_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm" @disabled(empty($plantillas))>
                    <option value="">-- Seleccionar --</option>
                    @foreach ($plantillas as $plantilla)
                        <option value="{{ $plantilla['plantilla_id'] }}">{{ $plantilla['nombre'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold text-gray-700">Agregar persona</h3>
            <div class="grid grid-cols-2 gap-3">
                <input wire:model="nuevo_dni" type="text" placeholder="DNI" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input wire:model="nuevo_apellido" type="text" placeholder="Apellido" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input wire:model="nuevo_nombre" type="text" placeholder="Nombre" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input wire:model="nuevo_mail" type="email" placeholder="Correo" class="rounded-md border-gray-300 shadow-sm text-sm">
                <input wire:model="nuevo_telefono" type="text" placeholder="Teléfono" class="rounded-md border-gray-300 shadow-sm text-sm col-span-2">
            </div>
            <button wire:click="agregarParticipacion"
                class="mt-3 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Agregar
            </button>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 font-semibold text-gray-700">Importar CSV</h3>
            <p class="mb-2 text-xs text-gray-500">Columnas: dni, apellido, nombre, mail, telefono.</p>
            <input type="file" wire:model="csv" class="block w-full text-sm">
            @error('csv') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            <div class="mt-3 flex gap-2">
                <button wire:click="importarCsv"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Importar
                </button>
                <button wire:click="descargarPlantillaCsv"
                    class="rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">
                    Descargar plantilla
                </button>
            </div>
        </div>
    </div>

    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-700">Lista</h3>
        <div class="flex items-center gap-3">
            <input wire:model.live="search" type="text" placeholder="Buscar..." class="rounded-md border-gray-300 shadow-sm text-sm">
            <button wire:click="emitirTodas" @disabled($procesando)
                class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                {{ $procesando ? 'Emitiendo…' : 'Emitir pendientes' }}
            </button>
        </div>
    </div>

    @if ($procesando)
        <div wire:poll.1500ms="continuarLote" class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
            <div class="flex items-center justify-between text-sm font-medium text-indigo-800">
                <span>Emitiendo certificados…</span>
                <span>{{ $emitidos }} / {{ $totalPendientes }}</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded bg-indigo-200">
                <div class="h-2 rounded bg-indigo-600 transition-all"
                    style="width: {{ $totalPendientes > 0 ? round($emitidos / $totalPendientes * 100) : 0 }}%"></div>
            </div>
            <p class="mt-1 text-xs text-indigo-700">
                {{ $emitidos }} emitido(s) · {{ $errores }} error(es). No cierres esta pestaña hasta finalizar.
            </p>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Persona</th>
                    <th class="px-4 py-3">DNI</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($participaciones as $participacion)
                    <tr>
                        <td class="px-4 py-2">{{ $participacion->participante?->apellido }}, {{ $participacion->participante?->nombre }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $participacion->participante?->dni }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $participacion->estaEmitida() ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $participacion->estaEmitida() ? 'Emitido' : 'Pendiente' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            @if ($participacion->estaEmitida() && $participacion->emision)
                                <a href="{{ route('ver.emision', $participacion->emision) }}" target="_blank" class="text-red-600 hover:text-red-800">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            @else
                                <button wire:click="emitirUna('{{ $participacion->participacion_id }}')" class="text-green-600 hover:text-green-800" title="Emitir">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <button wire:click="eliminarParticipacion('{{ $participacion->participacion_id }}')" class="ml-2 text-red-600 hover:text-red-800" title="Quitar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">Sin personas en la lista.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="py-4">{{ $participaciones->links() }}</div>
</div>
