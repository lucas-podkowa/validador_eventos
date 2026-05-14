<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Gestión de Indicadores</h2>
            <p class="text-sm text-gray-600">
                Organiza los indicadores por tipo, con su selector visible y un flujo de edición más claro.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 font-medium text-slate-700">
                {{ $tiposIndicadores->count() }} tipos configurados
            </span>
            @if ($tipoActivo)
                <span class="inline-flex items-center rounded-full bg-teal-100 px-3 py-1 font-medium text-teal-800">
                    {{ $tipoActivo->indicadores_count }} indicadores en {{ $tipoActivo->nombre }}
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <aside class="xl:col-span-4">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Tipos de indicador</h3>
                        <p class="text-sm text-gray-500">Selecciona un contexto de trabajo.</p>
                    </div>

                    <button type="button" wire:click="createTipo" style="font-size: 0.75rem; font-weight: 600"
                        class="btn btn-primary rounded-md text-white uppercase py-2 px-4 whitespace-nowrap">
                        Nuevo tipo
                    </button>
                </div>

                @if ($tiposIndicadores->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <div
                            class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                            <i class="fa-solid fa-layer-group text-xl"></i>
                        </div>
                        <h4 class="text-base font-semibold text-gray-900">Todavía no hay tipos cargados</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Crea el primer tipo para empezar a organizar sus indicadores y su selector.
                        </p>
                    </div>
                @else
                    <div class="space-y-3 p-3">
                        @foreach ($tiposIndicadores as $tipo)
                            @php($activo = (int) $tipoSeleccionadoId === (int) $tipo->tipo_indicador_id)
                            <button type="button" wire:click="selectTipo({{ $tipo->tipo_indicador_id }})"
                                class="w-full rounded-2xl border px-4 py-4 text-left transition {{ $activo ? 'border-teal-500 bg-teal-50 shadow-sm' : 'border-gray-200 bg-white hover:border-teal-200 hover:bg-gray-50' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-base font-semibold {{ $activo ? 'text-teal-900' : 'text-gray-900' }}">
                                            {{ $tipo->nombre }}
                                        </p>
                                        <span
                                            class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->selectorBadgeClass($tipo->selector) }}">
                                            {{ $tipo->selector }}
                                        </span>
                                    </div>
                                    <span
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold {{ $activo ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $tipo->indicadores_count }}
                                    </span>
                                </div>
                                <p class="mt-3 text-xs {{ $activo ? 'text-teal-700' : 'text-gray-500' }}">
                                    {{ $tipo->indicadores_count === 1 ? '1 indicador asociado' : $tipo->indicadores_count . ' indicadores asociados' }}
                                </p>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </aside>

        <section class="space-y-6 xl:col-span-8">
            @if ($tipoActivo || $modoTipo === 'create')
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Configuración del tipo</h3>
                                <p class="text-sm text-gray-500">Nombre y selector que gobiernan el contexto activo.</p>
                            </div>
                            @if ($modoTipo !== 'idle')
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                    {{ $modoTipo === 'create' ? 'Alta' : 'Edición' }}
                                </span>
                            @endif
                        </div>

                        <div class="p-6">
                            @if ($modoTipo === 'idle')
                                <div class="space-y-4">
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre</p>
                                        <p class="mt-2 text-base font-semibold text-gray-900">{{ $tipoActivo?->nombre ?? 'Sin selección' }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de selección</p>
                                        @if ($tipoActivo)
                                            <span
                                                class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->selectorBadgeClass($tipoActivo->selector) }}">
                                                {{ $tipoActivo->selector }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap gap-3">
                                        @if ($tipoActivo)
                                            <button type="button" wire:click="editTipo({{ $tipoActivo->tipo_indicador_id }})"
                                                style="font-size: 0.75rem; font-weight: 600"
                                                class="btn btn-primary rounded-md text-white uppercase py-2 px-4">
                                                Editar tipo
                                            </button>
                                        @endif
                                        <button type="button" wire:click="createTipo" style="font-size: 0.75rem; font-weight: 600"
                                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                                            Nuevo tipo
                                        </button>
                                    </div>
                                </div>
                            @else
                                <form wire:submit.prevent="saveTipo" class="space-y-4">
                                    <div>
                                        <label for="tipo_nombre" class="block text-sm font-medium text-gray-700">Nombre del tipo</label>
                                        <input type="text" id="tipo_nombre" wire:model.defer="tipo_nombre"
                                            placeholder="Ingrese el nombre del tipo"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error('tipo_nombre')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="tipo_selector" class="block text-sm font-medium text-gray-700">Tipo de selección</label>
                                        <select id="tipo_selector" wire:model.defer="tipo_selector"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            @foreach ($this->selectorOptions() as $selector)
                                                <option value="{{ $selector }}">{{ $selector }}</option>
                                            @endforeach
                                        </select>
                                        @error('tipo_selector')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="flex flex-wrap justify-end gap-3 pt-2">
                                        <x-secondary-button type="button" wire:click="cancelTipoForm">
                                            Cancelar
                                        </x-secondary-button>

                                        <button type="submit" style="font-size: 0.75rem; font-weight: 600"
                                            class="btn btn-primary rounded-md text-white uppercase py-2 px-4">
                                            {{ $modoTipo === 'create' ? 'Guardar tipo' : 'Actualizar tipo' }}
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                </div>

                @if ($tipoActivo)
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-gray-200 bg-gray-50 px-6 py-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">Indicadores del tipo</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $tipoActivo->nombre }} · {{ $tipoActivo->indicadores_count === 1 ? '1 indicador' : $tipoActivo->indicadores_count . ' indicadores' }}
                                </p>
                            </div>

                            <button type="button" wire:click="createIndicador" style="font-size: 0.75rem; font-weight: 600"
                                class="btn btn-primary rounded-md text-white uppercase py-2 px-4">
                                Nuevo indicador
                            </button>
                        </div>

                        @if ($indicadores->isNotEmpty())
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th wire:click="sortBy('nombre')"
                                                class="cursor-pointer px-6 py-3 text-left text-xs font-medium text-gray-500">
                                                Indicador
                                                @if ($sortField === 'nombre')
                                                    <i
                                                        class="fas {{ $sortDirection === 'asc' ? 'fa-sort-alpha-up-alt' : 'fa-sort-alpha-down-alt' }} float-right mt-1"></i>
                                                @else
                                                    <i class="fas fa-sort float-right mt-1"></i>
                                                @endif
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Selector heredado</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white">
                                        @foreach ($indicadores as $indicador)
                                            <tr>
                                                <td class="px-6 py-4">
                                                    <div class="font-medium text-gray-900 break-words">{{ $indicador->nombre }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span
                                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->selectorBadgeClass($tipoActivo->selector) }}">
                                                        {{ $tipoActivo->selector }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-right text-sm font-medium">
                                                    <div class="flex justify-end gap-2 whitespace-nowrap">
                                                        <button type="button" wire:click="editIndicador({{ $indicador->indicador_id }})"
                                                            class="inline-flex items-center rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-teal-700 transition hover:bg-teal-100">
                                                            <i class="fa-regular fa-pen-to-square mr-2"></i>
                                                            Editar
                                                        </button>

                                                        <button type="button"
                                                            onclick="confirmDeleteIndicador({{ $indicador->indicador_id }}, @js($indicador->nombre))"
                                                            class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-red-700 transition hover:bg-red-100">
                                                            <i class="fa-solid fa-trash mr-2"></i>
                                                            Eliminar
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="px-6 py-10 text-center">
                                <div
                                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <i class="fa-solid fa-sliders text-xl"></i>
                                </div>
                                <h4 class="text-base font-semibold text-gray-900">Este tipo todavía no tiene indicadores</h4>
                                <p class="mt-2 text-sm text-gray-500">
                                    Crea el primer indicador dentro de {{ $tipoActivo->nombre }} para completar este contexto.
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            @else
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center shadow-sm">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                        <i class="fa-solid fa-diagram-project text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">No hay un tipo activo para trabajar</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">
                        Crea un tipo de indicador para definir su selector y, a partir de ahí, cargar los indicadores que correspondan.
                    </p>
                    <button type="button" wire:click="createTipo" style="font-size: 0.75rem; font-weight: 600"
                        class="btn btn-primary mt-5 rounded-md text-white uppercase py-2 px-4">
                        Crear primer tipo
                    </button>
                </div>
            @endif

            <x-dialog-modal wire:model="showIndicadorModal">
                <x-slot name="title">
                    {{ $modoIndicador === 'edit' ? 'Editar indicador' : 'Nuevo indicador' }}
                </x-slot>

                <x-slot name="content">
                    @if ($tipoActivo)
                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Guardado dentro de</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="text-base font-semibold text-gray-900">{{ $tipoActivo->nombre }}</span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $this->selectorBadgeClass($tipoActivo->selector) }}">
                                    {{ $tipoActivo->selector }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="mt-4">
                        <label for="indicador_nombre_modal" class="block text-sm font-medium text-gray-700">Nombre del indicador</label>
                        <input type="text" id="indicador_nombre_modal" wire:model.defer="indicador_nombre"
                            placeholder="Ingrese el nombre del indicador"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('indicador_nombre')
                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </x-slot>

                <x-slot name="footer">
                    <x-secondary-button type="button" wire:click="cancelIndicadorForm">
                        Cancelar
                    </x-secondary-button>

                    <button type="button" wire:click="saveIndicador" style="font-size: 0.75rem; font-weight: 600"
                        class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                        {{ $modoIndicador === 'edit' ? 'Actualizar indicador' : 'Guardar indicador' }}
                    </button>
                </x-slot>
            </x-dialog-modal>
        </section>
    </div>

    <script>
        function confirmDeleteTipo(tipo_indicador_id, nombre) {
            Swal.fire({
                title: '¿Eliminar el tipo de indicador?',
                text: `Se eliminará ${nombre}. Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.Livewire.dispatch('deleteTipo', {
                        tipo_indicador_id
                    });
                }
            })
        }

        function confirmDeleteIndicador(indicador_id, nombre) {
            Swal.fire({
                title: '¿Eliminar el indicador?',
                text: `Se eliminará ${nombre}. Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.Livewire.dispatch('deleteIndicador', {
                        indicador_id
                    });
                }
            })
        }
    </script>
</div>