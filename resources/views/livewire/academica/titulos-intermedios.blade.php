<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Títulos Intermedios</h2>
        <button wire:click="abrirCrear"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 text-xs font-semibold">
            + Nuevo Título
        </button>
    </div>

    <div class="mb-4">
        <input type="text" wire:model.live="search"
            class="w-full px-4 py-2 border rounded-md focus:ring focus:ring-blue-300"
            placeholder="Buscar por título o carrera...">
    </div>

    @if ($titulos->count() > 0)
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Carrera</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-left">Título</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Estado</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Plantilla</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($titulos as $titulo)
                        <tr>
                            <td class="px-4 py-2">{{ $titulo->carrera->nombre }}</td>
                            <td class="px-4 py-2 font-medium">{{ $titulo->nombre }}</td>
                            <td class="px-4 py-2 text-center">
                                @if ($titulo->activo)
                                    <span class="inline-block bg-green-100 text-green-700 text-xs px-2 py-1 rounded-full">Activo</span>
                                @else
                                    <span class="inline-block bg-red-100 text-red-700 text-xs px-2 py-1 rounded-full">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <i class="fas fa-image {{ $titulo->imagen_plantilla_path ? 'text-green-600' : 'text-gray-300' }}"></i>
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="editar({{ $titulo->id }})" class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button
                                    wire:click="eliminar({{ $titulo->id }})"
                                    wire:confirm="¿Eliminar el título intermedio '{{ $titulo->nombre }}'? Esta acción no se puede deshacer."
                                    class="btn-action-delete" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <div class="py-4">{{ $titulos->links() }}</div>
    @else
        <div class="text-gray-500 py-4">No se encontraron títulos intermedios.</div>
    @endif

    {{-- Modal crear / editar --}}
    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Título Intermedio' : 'Nuevo Título Intermedio' }}
            </x-slot>

            <x-slot name="content">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carrera</label>
                    <select wire:model="carrera_id" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="">-- Seleccionar carrera --</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                    @error('carrera_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del título intermedio</label>
                    <input wire:model="nombre" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm"
                        placeholder="Ej: Técnico Universitario en Construcciones">
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

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plantilla (opcional, PNG/JPEG)</label>
                    <input type="file" wire:model="plantilla_imagen" accept="image/png,image/jpeg"
                        class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                    <p class="mt-1 text-xs text-gray-500">Si no la cargás acá, podés hacerlo desde el submenú "Plantillas".</p>
                    @error('plantilla_imagen')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
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
