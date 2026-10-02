<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800">Tipos de reconocimiento</h2>
        <button wire:click="abrirCrear"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <i class="fa-solid fa-plus mr-1"></i> Nuevo
        </button>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full max-w-md rounded-md border-gray-300 shadow-sm text-sm"
            placeholder="Buscar por nombre o slug...">
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-left text-xs uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">Orden</th>
                    <th class="px-4 py-3">Nombre</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Alcance sugerido</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tipos as $tipo)
                    <tr>
                        <td class="px-4 py-2">{{ $tipo->orden }}</td>
                        <td class="px-4 py-2 font-medium">
                            {{ $tipo->nombre }}
                            @if ($tipo->es_sistema)
                                <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase text-gray-500">sistema</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-gray-500">{{ $tipo->slug }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $tipo->alcance_sugerido ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $tipo->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $tipo->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right whitespace-nowrap">
                            <button wire:click="editar({{ $tipo->tipo_reconocimiento_id }})" class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-pen"></i>
                            </button>
                            @unless ($tipo->es_sistema)
                                <button wire:click="eliminar({{ $tipo->tipo_reconocimiento_id }})"
                                    wire:confirm="¿Eliminar '{{ $tipo->nombre }}'?" class="ml-2 text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No hay tipos cargados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="py-4">{{ $tipos->links() }}</div>

    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">{{ $editando_id ? 'Editar' : 'Nuevo' }} tipo de reconocimiento</x-slot>
            <x-slot name="content">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                        <input wire:model="nombre" type="text" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                        @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input wire:model="slug" type="text" class="w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="ej: evaluador">
                        @error('slug') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alcance sugerido</label>
                        <select wire:model="alcance_sugerido" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                            <option value="">—</option>
                            @foreach (\App\Models\TipoReconocimiento::ALCANCES as $alcance)
                                <option value="{{ $alcance }}">{{ $alcance }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                        <input wire:model="orden" type="number" class="w-32 rounded-md border-gray-300 shadow-sm text-sm">
                    </div>
                    <label class="inline-flex items-center text-sm">
                        <input type="checkbox" wire:model="activo" class="mr-2"> Activo
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
