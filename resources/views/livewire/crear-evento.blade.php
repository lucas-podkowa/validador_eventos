<div>
    

    <div class="bg-gray-200 bg-opacity-25 grid gap-6 lg:gap-8 p-6 lg:p-8">

        <div class="w-full p-4 bg-white shadow-md rounded-lg">
            <form wire:submit.prevent="save" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 px-6">
                    <!-- Categoría -->
                    <div>
                        <label for="categoria_id" class="block text-sm font-medium text-gray-700">Categoría <span class="text-red-500">*</span></label>
                        <select id="categoria_id" wire:model.live="categoria_id"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Seleccione</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->categoria_id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                        @error('categoria_id')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Tipo de Evento -->
                    <div>
                        <label for="tipo_evento_id" class="block text-sm font-medium text-gray-700">Tipo de Evento <span class="text-red-500">*</span></label>
                        <select id="tipo_evento_id" wire:model.live="tipo_evento_id"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Seleccione</option>
                            @foreach ($tiposEventos as $tipo)
                                <option value="{{ $tipo->tipo_evento_id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                        @error('tipo_evento_id')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 px-6">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Evento <span class="text-red-500">*</span></label>
                        <input type="text" id="nombre" wire:model.live="nombre_evento"
                            placeholder="Ingrese el nombre del evento"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('nombre')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>


                    <!-- Fecha -->
                    <div>
                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha de Inicio <span class="text-red-500">*</span></label>
                        <input type="date" id="fecha_inicio" wire:model.live="fecha_inicio"
                            @if ($esEdicion && $estado_evento === 'En Curso' && ! auth()->user()->hasRole('Administrador')) disabled @endif
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('fecha_inicio')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                </div>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 px-6">
                    <!-- Lugar -->
                    <div>
                        <label for="lugar_evento" class="block text-sm font-medium text-gray-700">Lugar del Evento <span class="text-red-500">*</span></label>
                        <input type="text" id="lugar_evento" wire:model.live="lugar_evento"
                            placeholder="Ingrese el lugar"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('lugar_evento')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Cupo Máximo -->
                    <div>
                        <label for="cupo" class="block text-sm font-medium text-gray-700">Cupo Máximo</label>
                        <input type="number" id="cupo" wire:model.live="cupo" placeholder="Dejar vacío si el cupo es ilimitado"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('cupo')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2 px-6">

                    <!-- Requiere Aprobación -->
                    <label class="flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors cursor-pointer">
                        <input type="checkbox" id="por_aprobacion" wire:model="por_aprobacion"
                            class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 cursor-pointer">
                        <span class="text-sm font-medium text-gray-700 select-none">
                            Requiere Aprobación
                        </span>
                    </label>

                    <!-- Responsable del Evento -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Responsable del Evento <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-3">
                            <div class="flex-1 p-2 bg-gray-50 border border-gray-300 rounded-md text-sm text-gray-700">
                                @if ($responsable_id)
                                    <i class="fa-solid fa-user-tie text-green-600 mr-1"></i>
                                    {{ $responsable_nombre }} {{ $responsable_apellido }} — DNI: {{ $responsable_dni }}
                                @else
                                    <span class="text-gray-400">Sin asignar</span>
                                @endif
                            </div>
                            <button type="button" wire:click="abrirSelectorResponsable"
                                class="inline-flex items-center px-3 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i>
                                {{ $responsable_id ? 'Cambiar' : 'Seleccionar' }}
                            </button>
                        </div>
                        @error('responsable_id')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-1 gap-4 pt-2 px-6">

                    <!-- Arancel -->
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                        <label class="flex items-center gap-3 cursor-pointer mb-3">
                            <input type="checkbox" wire:model.live="arancel"
                                class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                            <span class="text-sm font-medium text-gray-700 select-none">Evento arancelado</span>
                        </label>
                        @error('arancel')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror

                        @if ($arancel)
                            <div class="mb-4">
                                <label for="link_pago" class="block text-sm font-medium text-gray-700">
                                    Link de pago <span class="text-red-500">*</span>
                                </label>
                                <input type="url" id="link_pago" wire:model="link_pago"
                                    placeholder="https://..."
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('link_pago')
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Destinatarios y precios <span class="text-red-500">*</span>
                                </label>
                                @error('destinatarioSeleccionado')
                                    <span class="text-sm text-red-500 block mb-2">{{ $message }}</span>
                                @enderror

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach ($destinatarios as $d)
                                        @php
                                            $seleccionado = in_array((string) $d->destinatario_id, array_map('strval', $destinatarioSeleccionado));
                                        @endphp
                                        <div class="flex items-center gap-3 p-2 bg-white border rounded-md {{ $d->activo ? '' : 'opacity-60' }}">
                                            <input type="checkbox" wire:model.live="destinatarioSeleccionado"
                                                value="{{ $d->destinatario_id }}"
                                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-800">{{ $d->nombre }}</p>
                                                @if (! $d->activo)
                                                    <p class="text-xs text-gray-500">Inactivo</p>
                                                @endif
                                            </div>
                                            <div class="w-28">
                                                <input type="number" step="0.01" min="0"
                                                    wire:model="destinatarioPrecio.{{ $d->destinatario_id }}"
                                                    placeholder="0,00"
                                                    @disabled(! $seleccionado)
                                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                @if ($seleccionado)
                                                    @error("destinatarioPrecio.{$d->destinatario_id}")
                                                        <span class="text-xs text-red-500">{{ $message }}</span>
                                                    @enderror
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Indicadores -->
                    <div>
                        <label for="indicadoresSeleccionados"
                            class="block text-sm font-medium text-gray-700">Indicadores a Incluir</label>
                        <ul class="w-full text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg">
                            @foreach ($tiposIndicadores as $indicador)
                                <li class="w-full border-b border-gray-200 rounded-t-lg">
                                    <div class="flex items-center ps-3">
                                        <input wire:model="indicadoresSeleccionados" type="checkbox"
                                            value="{{ $indicador->tipo_indicador_id }}"
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                        <label for="indicador-checkbox-{{ $indicador->tipo_indicador_id }}"
                                            class="w-full py-1 ms-2 text-sm font-medium text-gray-900">
                                            {{ $indicador->nombre }}
                                        </label>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="py-2 flex justify-between items-center">
                    <!-- Botón de Eliminar (Izquierda) — solo Administrador -->
                    @if ($esEdicion && auth()->user()->hasRole('Administrador'))
                        <button type="button" wire:click="eliminarEvento"
                            class="flex items-center bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fa fa-trash pr-2"></i>
                            Eliminar
                        </button>
                    @endif

                    <!-- Botones de Actualizar y Volver (Derecha) -->
                    <div class="flex space-x-4">
                        <x-secondary-button wire:click="cancelarEdicion">
                            Volver
                        </x-secondary-button>

                        <button type="submit" style="font-size: 0.75rem; font-weight: 600"
                            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                            {{ $esEdicion ? 'Actualizar' : 'Crear' }}
                        </button>

                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- ---------------------- MODAL Seleccionar Responsable ---------------------- --}}
    <x-dialog-modal wire:model="open_responsable">
        <x-slot name="title">
            <h4 class="text-md font-semibold text-indigo-600">
                <i class="fa-solid fa-user-tie mr-1"></i> Seleccionar Responsable del Evento
            </h4>
        </x-slot>

        <x-slot name="content">
            {{-- Búsqueda por DNI --}}
            <div class="mb-4">
                <label for="modal_responsable_dni" class="block text-sm font-medium text-gray-700">DNI del Responsable</label>
                <div class="flex gap-2 mt-1">
                    <input type="text" id="modal_responsable_dni" wire:model.live="modal_responsable_dni"
                        inputmode="numeric" maxlength="10" x-on:input="$el.value = $el.value.replace(/\D/g, '')"
                        placeholder="Ingrese el DNI"
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <button type="button" wire:click="buscarResponsable"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none transition">
                        <i class="fa-solid fa-search mr-1"></i> Buscar
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Ingrese solo números.</p>
                @error('modal_responsable_dni')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- Indicador de estado --}}
            @if ($modal_responsable_buscado)
                @if ($modal_responsable_encontrado)
                    <div
                        class="mb-4 p-2 bg-green-50 border border-green-200 rounded-md text-sm text-green-700 flex items-center">
                        <i class="fa-solid fa-circle-check mr-2"></i>
                        Responsable encontrado en el sistema.
                    </div>
                @else
                    <div
                        class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded-md text-sm text-yellow-700 flex items-center">
                        <i class="fa-solid fa-circle-info mr-2"></i>
                        Responsable no encontrado. Complete los datos para registrarlo.
                    </div>
                @endif
            @endif

            {{-- Campos de nombre y apellido --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="modal_responsable_nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                    <input type="text" id="modal_responsable_nombre" wire:model.live="modal_responsable_nombre"
                        x-on:input="$el.value = $el.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '')"
                        placeholder="Nombre del responsable"
                        @if ($modal_responsable_encontrado || !$modal_responsable_buscado) readonly class="block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm sm:text-sm"
                        @else class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" @endif>
                    @error('modal_responsable_nombre')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label for="modal_responsable_apellido" class="block text-sm font-medium text-gray-700">Apellido</label>
                    <input type="text" id="modal_responsable_apellido" wire:model.live="modal_responsable_apellido"
                        x-on:input="$el.value = $el.value.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '')"
                        placeholder="Apellido del responsable"
                        @if ($modal_responsable_encontrado || !$modal_responsable_buscado) readonly class="block w-full bg-gray-100 border-gray-300 rounded-md shadow-sm sm:text-sm"
                        @else class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" @endif>
                    @error('modal_responsable_apellido')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="cancelarSelectorResponsable">
                Cancelar
            </x-secondary-button>

            <button type="button" wire:click="seleccionarResponsable" style="font-size: 0.75rem; font-weight: 600"
                class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4"
                @if (!$modal_responsable_buscado) disabled @endif>
                <i class="fa-solid fa-check mr-1"></i>
                @if (!$modal_responsable_buscado)
                    Buscar primero
                @elseif ($modal_responsable_encontrado)
                    Seleccionar
                @else
                    Registrar responsable
                @endif
            </button>
        </x-slot>
    </x-dialog-modal>

</div>
