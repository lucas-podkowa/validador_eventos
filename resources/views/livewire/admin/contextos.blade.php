<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-bold">Contextos de Certificados</h2>
            <p class="text-sm text-gray-500">Una edición o diseño de certificado dentro de una categoría.</p>
        </div>
        <button wire:click="abrirCrear"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 text-xs font-semibold">
            + Nuevo Contexto
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <input type="text" wire:model.live="search"
            class="sm:col-span-2 px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar contexto...">
        <select wire:model.live="searchCategoria"
            class="px-4 py-2 border rounded-md focus:ring focus:ring-blue-300">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat->categoria_id }}">{{ $cat->nombre }}</option>
            @endforeach
        </select>
    </div>

    @if ($contextos->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Contexto</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Categoría</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Período</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Firmas</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Plantillas</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Eventos</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($contextos as $contexto)
                        <tr class="{{ $contexto_activo_id === $contexto->contexto_id ? 'bg-indigo-50' : '' }}">
                            <td class="px-4 py-2">
                                <div class="font-medium text-gray-800">{{ $contexto->nombre }}</div>
                                @if ($contexto->denominacion)
                                    <div class="text-xs text-gray-500 truncate max-w-xs">{{ $contexto->denominacion }}</div>
                                @endif
                                @unless ($contexto->activo)
                                    <span class="inline-block mt-1 bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">Inactivo</span>
                                @endunless
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600">{{ $contexto->categoria->nombre ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-600">
                                @if ($contexto->fecha_inicio)
                                    {{ $contexto->fecha_inicio->format('d/m/Y') }}
                                    @if ($contexto->fecha_fin && ! $contexto->fecha_fin->isSameDay($contexto->fecha_inicio))
                                        – {{ $contexto->fecha_fin->format('d/m/Y') }}
                                    @endif
                                @elseif ($contexto->anio)
                                    {{ $contexto->anio }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full">{{ $contexto->firmantes_count }}</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">{{ $contexto->plantillas_count }}</span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $contexto->eventos_count }}</span>
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="abrirContexto({{ $contexto->contexto_id }})"
                                    class="btn-action-edit" title="Gestionar contexto">
                                    <i class="fas fa-sliders-h"></i>
                                </button>
                                <button wire:click="editar({{ $contexto->contexto_id }})"
                                    class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="eliminar({{ $contexto->contexto_id }})"
                                    wire:confirm="¿Eliminar el contexto '{{ $contexto->nombre }}'? Se eliminarán sus plantillas."
                                    class="btn-action-delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $contextos->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron contextos.</div>
    @endif

    {{-- Panel del contexto activo --}}
    @if ($contexto_activo_id)
        <div class="mt-6 border border-indigo-200 rounded-3xl bg-white shadow-sm overflow-hidden">
            <div class="flex justify-between items-center px-5 py-4 bg-indigo-50 border-b border-indigo-200">
                <div>
                    <h3 class="font-semibold text-indigo-800 text-lg">
                        <i class="fas fa-sliders-h mr-1"></i>
                        Configuración — {{ $contexto_activo_nombre }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Asigná firmantes y administrá las plantillas dinámicas del contexto.</p>
                </div>
                <button wire:click="cerrarContexto" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 space-y-6">
                {{-- Firmantes --}}
                <div>
                    <h4 class="text-base font-semibold text-gray-800 mb-3">Firmantes</h4>

                    <div class="flex flex-col sm:flex-row gap-3 mb-4">
                        <select wire:model="nuevo_firmante_id"
                            class="flex-1 border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Seleccionar firmante...</option>
                            @foreach ($firmantes as $firmante)
                                <option value="{{ $firmante->firmante_id }}">
                                    {{ $firmante->nombre }}@if($firmante->cargo) — {{ $firmante->cargo }}@endif
                                </option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="agregarFirmante"
                            class="btn btn-primary text-white text-xs font-semibold uppercase py-2 px-4 rounded-md whitespace-nowrap">
                            + Agregar
                        </button>
                    </div>
                    @error('nuevo_firmante_id') <span class="block text-red-500 text-xs -mt-3 mb-3">{{ $message }}</span> @enderror

                    @if (count($firmantes_asignados) > 0)
                        <ul class="space-y-2">
                            @foreach ($firmantes_asignados as $i => $asignado)
                                @php $f = $firmantes->firstWhere('firmante_id', $asignado['firmante_id']); @endphp
                                <li class="flex items-center gap-3 border border-gray-200 rounded-xl px-4 py-2 bg-white">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold">
                                        {{ $i + 1 }}
                                    </span>
                                    @if ($f && $f->imagen_firma_path)
                                        <img src="{{ route('admin.firmantes.imagen', $f) }}" alt="Firma"
                                            class="h-10 w-20 object-contain select-none pointer-events-none"
                                            draggable="false" oncontextmenu="return false;">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $f->nombre ?? 'Firmante eliminado' }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $f->cargo ?? '—' }}</p>
                                    </div>
                                    <label class="inline-flex items-center text-xs text-gray-600 whitespace-nowrap">
                                        <input type="checkbox" wire:model="firmantes_asignados.{{ $i }}.mostrar_cargo"
                                            wire:click="actualizarMostrarCargo({{ $i }})" class="mr-1">
                                        Mostrar cargo
                                    </label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" wire:click="moverFirmante({{ $i }}, -1)"
                                            class="btn-action-edit" title="Subir" @disabled($i === 0)>
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                        <button type="button" wire:click="moverFirmante({{ $i }}, 1)"
                                            class="btn-action-edit" title="Bajar" @disabled($i === count($firmantes_asignados) - 1)>
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                        <button type="button" wire:click="quitarFirmante({{ $i }})"
                                            class="btn-action-delete" title="Quitar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-400">Este contexto todavía no tiene firmantes asignados.</p>
                    @endif
                </div>

                {{-- Plantillas dinámicas --}}
                <div class="border-t border-gray-200 pt-6">
                    <h4 class="text-base font-semibold text-gray-800 mb-3">Plantillas del contexto</h4>
                    <livewire:admin.contexto-plantillas :contexto-id="$contexto_activo_id" :key="'plantillas-'.$contexto_activo_id" />
                </div>
            </div>
        </div>
    @endif

    {{-- Modal crear / editar contexto --}}
    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Contexto' : 'Nuevo Contexto' }}
            </x-slot>

            <x-slot name="content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                        <select wire:model="categoria_id"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Seleccionar categoría...</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->categoria_id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                        @error('categoria_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre corto</label>
                        <input wire:model="nombre" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: XVI JIDeTEV">
                        @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Denominación larga (opcional)</label>
                        <input wire:model="denominacion" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: Jornadas de Investigación, Desarrollo Tecnológico, Extensión y Vinculación">
                        @error('denominacion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Institución (opcional)</label>
                        <input wire:model="institucion" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: Facultad de Ingeniería UNaM">
                        @error('institucion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Año (opcional)</label>
                        <input wire:model="anio" type="number" min="1900" max="2100"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: 2026">
                        @error('anio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lugar (opcional)</label>
                        <input wire:model="lugar" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: Oberá, Misiones">
                        @error('lugar') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio (opcional)</label>
                        <input wire:model="fecha_inicio" type="date"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('fecha_inicio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin (opcional)</label>
                        <input wire:model="fecha_fin" type="date"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('fecha_fin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resolución (opcional)</label>
                        <input wire:model="resolucion" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: CD 089/26">
                        @error('resolucion') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <label class="md:col-span-2 inline-flex items-center text-sm">
                        <input type="checkbox" wire:model="activo" class="mr-2">
                        <span>Contexto activo</span>
                    </label>
                </div>
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-end gap-3">
                    <x-secondary-button wire:click="$set('open_modal', false)">Cancelar</x-secondary-button>
                    <x-button type="submit">Guardar</x-button>
                </div>
            </x-slot>
        </x-dialog-modal>
    </form>
</div>
