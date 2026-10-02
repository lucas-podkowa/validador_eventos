<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Emisión de certificados</h2>
        <p class="text-sm text-gray-500">Emití un certificado para una persona en un evento o en un contexto/programa.</p>
    </div>

    <form wire:submit.prevent="emitir" class="mb-8 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Origen</label>
                <select wire:model.live="origen_tipo" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="evento">Evento</option>
                    <option value="contexto">Contexto / Programa</option>
                </select>
            </div>

            @if ($origen_tipo === 'evento')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select wire:model.live="categoria_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->categoria_id }}">{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                    @error('categoria_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contexto / Edición</label>
                    <select wire:model.live="contexto_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled(empty($contextos))>
                        <option value="">-- Seleccionar --</option>
                        @foreach ($contextos as $contexto)
                            <option value="{{ $contexto->contexto_id }}">{{ $contexto->nombre }}</option>
                        @endforeach
                    </select>
                    @if (! $categoria_id)
                        <p class="mt-1 text-xs text-gray-400">Elegí una categoría para ver sus contextos.</p>
                    @endif
                    @error('contexto_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Evento</label>
                    <select wire:model.live="evento_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled(empty($eventos))>
                        <option value="">-- Seleccionar --</option>
                        @foreach ($eventos as $evento)
                            <option value="{{ $evento->evento_id }}">{{ $evento->nombre }}@if($evento->fecha_inicio) — {{ \Illuminate\Support\Carbon::parse($evento->fecha_inicio)->format('d/m/Y') }}@endif</option>
                        @endforeach
                    </select>
                    @if ($contexto_id && empty($eventos))
                        <p class="mt-1 text-xs text-amber-600">Este contexto no tiene eventos finalizados.</p>
                    @endif
                    @error('evento_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            @else
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contexto / Programa</label>
                    <select wire:model.live="contexto_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        <option value="">-- Seleccionar --</option>
                        @foreach ($contextos as $contexto)
                            <option value="{{ $contexto->contexto_id }}">{{ $contexto->nombre }}</option>
                        @endforeach
                    </select>
                    @error('contexto_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de reconocimiento</label>
                <select wire:model.live="tipo_reconocimiento_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <option value="">-- Seleccionar --</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo['tipo_reconocimiento_id'] }}">{{ $tipo['nombre'] }}</option>
                    @endforeach
                </select>
                @error('tipo_reconocimiento_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Plantilla</label>
                <select wire:model="plantilla_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm" @disabled(empty($plantillas))>
                    <option value="">-- Seleccionar --</option>
                    @foreach ($plantillas as $plantilla)
                        <option value="{{ $plantilla['plantilla_id'] }}">{{ $plantilla['nombre'] }}</option>
                    @endforeach
                </select>
                @error('plantilla_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <hr class="my-5">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                <div class="flex gap-2">
                    <input wire:model="dni" type="text" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    <button type="button" wire:click="buscarParticipante"
                        class="shrink-0 rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200">Buscar</button>
                </div>
                @error('dni') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                <input wire:model="nombre" type="text" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                <input wire:model="apellido" type="text" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                @error('apellido') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input wire:model="mail" type="email" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                @error('mail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input wire:model="telefono" type="text" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                @error('telefono') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        @if ($similar && $decision_similar === null)
            <div class="mt-4 rounded-md border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-semibold">Ya existe un participante con datos similares:</p>
                <p>{{ $similar['apellido'] }}, {{ $similar['nombre'] }} — DNI {{ $similar['dni'] }}</p>
                <div class="mt-3 flex gap-2">
                    <button type="button" wire:click="usarSimilar"
                        class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white">Es la misma persona</button>
                    <button type="button" wire:click="crearNuevo"
                        class="rounded-md bg-gray-600 px-3 py-1.5 text-xs font-semibold text-white">Es otra persona</button>
                </div>
            </div>
        @endif

        <div class="mt-5 flex justify-end">
            <button type="submit"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Emitir certificado
            </button>
        </div>
    </form>

    <h3 class="mb-3 text-lg font-semibold text-gray-700">Emisiones recientes</h3>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Persona</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Origen</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($emisiones as $emision)
                    <tr>
                        <td class="px-4 py-2">{{ $emision->participante?->apellido }}, {{ $emision->participante?->nombre }}</td>
                        <td class="px-4 py-2">{{ $emision->tipoReconocimiento?->nombre }}</td>
                        <td class="px-4 py-2">{{ $emision->origen_snapshot['nombre'] ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $emision->estaAnulada() ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                {{ $emision->estaAnulada() ? 'Anulado' : 'Emitido' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $emision->emitida_en?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right">
                            @if ($emision->certificado_path)
                                <a href="{{ route('ver.emision', $emision) }}" target="_blank" class="text-red-600 hover:text-red-800">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Todavía no hay emisiones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="py-4">{{ $emisiones->links() }}</div>
</div>
