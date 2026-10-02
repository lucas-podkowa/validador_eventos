<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Categorías de Eventos</h2>
        <button wire:click="abrirCrear"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 text-xs font-semibold">
            + Nueva Categoría
        </button>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar categoría...">
    </div>

    {{-- Tabla de categorías --}}
    @if ($categorias->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Nombre</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Descripción</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Contextos</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Eventos</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($categorias as $cat)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $cat->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $cat->descripcion ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-indigo-100 text-indigo-700 text-xs px-2 py-1 rounded-full">
                                    {{ $cat->contextos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                    {{ $cat->eventos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="editar({{ $cat->categoria_id }})"
                                    class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button
                                    wire:click="eliminar({{ $cat->categoria_id }})"
                                    wire:confirm="¿Eliminar la categoría '{{ $cat->nombre }}'?"
                                    class="btn-action-delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $categorias->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron categorías.</div>
    @endif

    {{-- Modal crear / editar categoría --}}
    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Categoría' : 'Nueva Categoría' }}
            </x-slot>

            <x-slot name="content">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input wire:model="nombre" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Ej: JIDETeV, RPIC...">
                    @error('nombre')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                    <input wire:model="descripcion" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Breve descripción de la categoría">
                    @error('descripcion')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <label class="mt-4 inline-flex items-center text-sm text-gray-700">
                    <input type="checkbox" wire:model="disponible_para_eventos" class="mr-2">
                    Disponible para crear eventos clásicos
                </label>
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
