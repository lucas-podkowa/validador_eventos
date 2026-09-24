<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Firmantes</h2>
        <button wire:click="abrirCrear"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 text-xs font-semibold">
            + Nuevo Firmante
        </button>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar por nombre o cargo...">
    </div>

    @if ($firmantes->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Firma</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Nombre</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Cargo</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Estado</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($firmantes as $firmante)
                        <tr>
                            <td class="px-4 py-2">
                                @if ($firmante->imagen_firma_path)
                                    <img src="{{ route('admin.firmantes.imagen', $firmante) }}"
                                        alt="Firma de {{ $firmante->nombre }}"
                                        class="h-12 w-28 object-contain bg-gray-50 border rounded select-none pointer-events-none"
                                        draggable="false" oncontextmenu="return false;">
                                @else
                                    <span class="text-xs text-gray-400">Sin imagen</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 font-medium">{{ $firmante->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $firmante->cargo ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                @if ($firmante->activo)
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Activo</span>
                                @else
                                    <span class="inline-block bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="editar({{ $firmante->firmante_id }})"
                                    class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button wire:click="eliminar({{ $firmante->firmante_id }})"
                                    wire:confirm="¿Eliminar el firmante '{{ $firmante->nombre }}'?"
                                    class="btn-action-delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $firmantes->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron firmantes.</div>
    @endif

    <p class="mt-4 text-xs text-gray-500">
        Las firmas se guardan en el almacenamiento privado. Sólo se muestran aquí como vista previa con marca de agua.
    </p>

    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Firmante' : 'Nuevo Firmante' }}
            </x-slot>

            <x-slot name="content">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre y apellido</label>
                        <input wire:model="nombre" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: Mtr. Ing. María C. DEKUN">
                        @error('nombre') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cargo (opcional)</label>
                        <input wire:model="cargo" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Ej: Decana Facultad de Ingeniería UNaM">
                        @error('cargo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Imagen de la firma (PNG/JPEG, máx. 10 MB)</label>
                        <input wire:model="imagen" type="file" accept="image/png,image/jpeg"
                            class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-1 text-xs text-gray-500">Recomendado: PNG con fondo transparente.</p>
                        @if ($imagen)
                            <p class="mt-2 text-xs font-medium text-blue-700">Archivo listo: {{ $imagen->getClientOriginalName() }}</p>
                        @endif
                        @error('imagen') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <label class="inline-flex items-center text-sm">
                        <input type="checkbox" wire:model="activo" class="mr-2">
                        <span>Firmante activo</span>
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
