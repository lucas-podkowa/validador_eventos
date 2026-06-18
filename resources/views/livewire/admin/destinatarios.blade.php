<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Destinatarios</h2>
        <button wire:click="abrirCrear"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 text-xs font-semibold">
            + Nuevo Destinatario
        </button>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar destinatario...">
    </div>

    @if ($destinatarios->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Nombre</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Activo</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Eventos</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($destinatarios as $dest)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $dest->nombre }}</td>
                            <td class="px-4 py-2 text-center">
                                <button wire:click="toggleActivo({{ $dest->destinatario_id }})"
                                    class="inline-block px-2 py-1 text-xs rounded-full {{ $dest->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $dest->activo ? 'Sí' : 'No' }}
                                </button>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full">
                                    {{ $dest->eventos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="editar({{ $dest->destinatario_id }})"
                                    class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="eliminar({{ $dest->destinatario_id }})"
                                    wire:confirm="¿Eliminar el destinatario '{{ $dest->nombre }}'?"
                                    class="btn-action-delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $destinatarios->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron destinatarios.</div>
    @endif

    {{-- Modal crear / editar destinatario --}}
    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Destinatario' : 'Nuevo Destinatario' }}
            </x-slot>

            <x-slot name="content">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input wire:model="nombre" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Ej: Estudiante de la UNaM">
                    @error('nombre')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="inline-flex items-center text-sm">
                        <input type="checkbox" wire:model="activo" class="mr-2">
                        <span>Activo</span>
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
