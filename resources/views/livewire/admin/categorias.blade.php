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
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Plantillas</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Eventos</th>
                        <th class="px-4 py-3 text-xs font-medium border text-gray-500 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($categorias as $cat)
                        <tr class="{{ $categoria_activa_id === $cat->categoria_id ? 'bg-indigo-50' : '' }}">
                            <td class="px-4 py-2 font-medium">{{ $cat->nombre }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $cat->descripcion ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full">
                                    {{ $cat->plantillas_count }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                                    {{ $cat->eventos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <button wire:click="abrirPlantillas({{ $cat->categoria_id }})"
                                    class="btn-action-edit" title="Gestionar plantillas">
                                    <i class="fas fa-images"></i>
                                </button>
                                <button wire:click="editar({{ $cat->categoria_id }})"
                                    class="btn-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button
                                    wire:click="eliminar({{ $cat->categoria_id }})"
                                    wire:confirm="¿Eliminar la categoría '{{ $cat->nombre }}'? Se eliminarán también todas sus plantillas."
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

    {{-- Panel de gestión de plantillas --}}
    @if ($categoria_activa_id)
        <div class="mt-6 border border-indigo-200 rounded-3xl bg-white shadow-sm overflow-hidden">
            <div class="flex justify-between items-center px-5 py-4 bg-indigo-50 border-b border-indigo-200">
                <div>
                    <h3 class="font-semibold text-indigo-800 text-lg">
                        <i class="fas fa-images mr-1"></i>
                        Plantillas de certificados — {{ $categoria_activa_nombre }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">Edita, elimina o carga variantes nuevas desde un panel unificado.</p>
                </div>
                <button wire:click="cerrarPlantillas" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-5 space-y-6">
                {{-- Plantillas existentes --}}
                @if (count($plantillas_de_categoria) > 0)
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        @foreach ($plantillas_de_categoria as $plantilla)
                            <div class="relative categoria-plantilla-card">
                                <div class="categoria-plantilla-toolbar absolute inset-x-0 top-0 z-10 flex items-center justify-end gap-2 p-2">
                                    <button
                                        wire:click="abrirEditarPlantilla({{ $plantilla['plantilla_id'] }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:text-indigo-600"
                                        title="Editar plantilla">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button
                                        wire:click="eliminarPlantilla({{ $plantilla['plantilla_id'] }})"
                                        wire:confirm="¿Eliminar la plantilla '{{ $plantilla['nombre'] }}'?"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-white text-red-500 shadow-sm transition hover:bg-red-50"
                                        title="Eliminar plantilla">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </div>
                                <img src="{{ asset('storage/' . $plantilla['imagen_path']) }}"
                                    alt="{{ $plantilla['nombre'] }}"
                                    class="w-full h-44 object-cover bg-gray-50">
                                <div class="p-4 bg-white">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $plantilla['nombre'] }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ strtoupper($plantilla['tipo'] ?? 'ASISTENCIA') }}</span>
                                        @if(!empty($plantilla['por_defecto']))
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">Predeterminada</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-400 text-sm mb-4">Esta categoría no tiene plantillas aún.</p>
                @endif

                {{-- Formulario para agregar plantilla --}}
                <div class="border-t border-gray-200 pt-6">
                    <div class="categoria-upload-panel p-5">
                        <div class="flex flex-col gap-1 mb-5">
                            <h4 class="text-base font-semibold text-gray-800">Agregar nueva plantilla</h4>
                            <p class="text-sm text-gray-500">Completa los datos de izquierda a derecha: nombre, tipo y archivo. La plantilla predeterminada se usará automáticamente al emitir certificados.</p>
                        </div>
                    <form wire:submit.prevent="agregarPlantilla" class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_220px] gap-4 items-start">
                        <div class="space-y-3">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600">Nombre de la plantilla</label>
                            <input wire:model="nueva_plantilla_nombre" type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Asistente, Aprobado, Disertante...">
                            @error('nueva_plantilla_nombre')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 mb-2">Tipo de plantilla</label>
                                @php $tipos = \App\Models\PlantillaCertificado::TIPOS; @endphp
                                <select wire:model="nueva_plantilla_tipo"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($tipos as $t)
                                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                                @error('nueva_plantilla_tipo')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <label class="inline-flex items-start gap-3 text-sm rounded-xl border border-gray-200 bg-white px-4 py-3">
                                <input type="checkbox" wire:model="nueva_plantilla_por_defecto" class="mt-1">
                                <span>
                                    <span class="block font-medium text-gray-700">Usar como predeterminada</span>
                                    <span class="block text-xs text-gray-500">Se elegirá automáticamente para esta categoría y tipo.</span>
                                </span>
                            </label>

                            <div class="categoria-upload-dropzone p-4">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-600 mb-2">Imagen</label>
                                <input wire:model="nueva_plantilla_imagen" type="file"
                                    accept="image/png,image/jpeg"
                                    class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <p class="mt-2 text-xs text-gray-500">PNG o JPEG, hasta 2 MB.</p>
                                @if ($nueva_plantilla_imagen)
                                    <p class="mt-2 text-xs font-medium text-blue-700">Archivo listo: {{ $nueva_plantilla_imagen->getClientOriginalName() }}</p>
                                @endif
                                @error('nueva_plantilla_imagen')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="flex xl:justify-end xl:items-start pt-0 xl:pt-7">
                            <button type="submit"
                                class="btn btn-primary text-white text-xs font-semibold uppercase py-3 px-5 rounded-xl whitespace-nowrap w-full xl:w-auto justify-center">
                                <div wire:loading wire:target="nueva_plantilla_imagen" class="inline">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>
                                </div>
                                Subir Plantilla
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
                
                {{-- Modal editar plantilla --}}
                <form wire:submit.prevent="guardarPlantillaEditada">
                    <x-dialog-modal wire:model="open_modal_plantilla_edit">
                        <x-slot name="title">Editar Plantilla</x-slot>

                        <x-slot name="content">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre</label>
                                    <input wire:model="editando_plantilla_nombre" type="text"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Nombre de la plantilla">
                                    @error('editando_plantilla_nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                                    @php $tipos = \App\Models\PlantillaCertificado::TIPOS; @endphp
                                    <select wire:model="editando_plantilla_tipo"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach($tipos as $t)
                                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                        @endforeach
                                    </select>
                                    @error('editando_plantilla_tipo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror

                                    <div class="mt-2">
                                        <label class="inline-flex items-center text-sm">
                                            <input type="checkbox" wire:model="editando_plantilla_por_defecto" class="mr-2">
                                            <span>Marcar como plantilla predeterminada</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Reemplazar imagen (opcional, PNG/JPEG máx.2MB)</label>
                                    <input type="file" wire:model="editando_plantilla_imagen" accept="image/png,image/jpeg"
                                        class="w-full text-sm border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @error('editando_plantilla_imagen') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </x-slot>

                        <x-slot name="footer">
                            <div class="flex justify-end gap-3">
                                <x-secondary-button wire:click="$set('open_modal_plantilla_edit', false)">Cancelar</x-secondary-button>
                                <x-button type="submit">Guardar cambios</x-button>
                            </div>
                        </x-slot>
                    </x-dialog-modal>
                </form>
            </div>
        </div>
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
