<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="flex gap-4">
        <!-- Tabla de Eventos (Siempre visible) -->
        <div class="max-w-[50%] flex-1">
            <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                <table class="w-full min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Tipo de Evento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Asistencias</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($eventos as $evento)
                            <tr wire:click="seleccionarEvento('{{ $evento->evento_id }}')"
                                class="{{ $evento_selected && $evento_selected->evento_id == $evento->evento_id ? 'bg-blue-600 text-white' : '' }}">
                                <td class="px-6 py-2">{{ $evento->tipoEvento->nombre }}</td>
                                <td class="px-6 py-2">{{ $evento->nombre }}</td>
                                <td class="px-6 py-2">
                                    @if (count($evento->sesiones) > 0)
                                        <a wire:click="descargarAsistencias"
                                            class="block px-4 py-1 text-gray-700 cursor-pointer flex items-center gap-2">
                                            <i class="mr-2 fa-solid fa-file-pdf fa-xl"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de Sesiones (Solo visible si hay un evento seleccionado) -->

        <div class="max-w-[50%] flex-1">

            @if ($evento_selected)
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-bold">Sesiones del evento: {{ $evento_selected->nombre }}</h3>

                    <x-button wire:click="abrirModalSesion" title="Crear Sesión"
                        class="bg-green-500 text-white px-4 py-2 rounded">
                        <i class="fa-solid fa-plus"></i>
                    </x-button>
                </div>

                {{-- <x-table> --}}
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    @if ($sesiones->count())
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Nombre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Desde</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Hasta</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($sesiones as $sesion)
                                    <tr wire:click="seleccionarSesion('{{ $sesion->sesion_evento_id }}')">
                                        <td class="px-6 py-3 text-gray-900">{{ $sesion->nombre }}</td>
                                        <td class="px-6 py-3 text-gray-900">
                                            {{ \Carbon\Carbon::parse($sesion->fecha_hora_inicio)->format('d/m/y H:i') }}
                                        </td>
                                        <td class="px-6 py-3 text-gray-900">
                                            {{ \Carbon\Carbon::parse($sesion->fecha_hora_fin)->format('d/m/y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="px-6 py-4">No existen registros para mostrar</div>
                    @endif
                    {{-- </x-table> --}}
                </div>
            @else
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-lg font-bold">Seleccione algun evento para ver sus Sesiones
                </div>
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <div class="px-6 py-4">No existen registros para mostrar</div>
                </div>
            @endif

        </div>
    </div>

    <!-- Modal para agregar sesión -->
    @if ($mostrarModalSesion)
        <x-dialog-modal wire:model="mostrarModalSesion">
            <x-slot name="title">
                Nueva sesión de asistencias al evento {{ $evento_selected->nombre ?? '' }}
            </x-slot>

            <x-slot name="content">
                <!-- Input para cupo -->
                <div class="w-1/4 ml-4">
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de la
                        Sesión</label>
                    <input type="text" id="nombre" wire:model.live="nombre" placeholder="Sesión 01" min="0"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('nombre')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </div>
                <div class="flex pt-4 px-6 gap-4" x-data="{ inicio: @entangle('fecha_hora_inicio') }">
                    <div class="w-1/2">
                        <label for="fecha_hora_inicio" class="block text-sm font-medium text-gray-700">Fecha y Hora de
                            Inicio</label>
                        <input type="datetime-local" id="fecha_hora_inicio" wire:model.live="fecha_hora_inicio"
                            x-model="inicio"
                            min="{{ \Carbon\Carbon::parse($evento_selected->fecha_inicio)->format('Y-m-d\TH:i') }}"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('fecha_hora_inicio')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="w-1/2">
                        <label for="fecha_hora_fin" class="block text-sm font-medium text-gray-700">Fecha y Hora de
                            Cierre</label>
                        <input type="datetime-local" id="fecha_hora_fin" wire:model.live="fecha_hora_fin"
                            x-bind:min="inicio"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('fecha_hora_fin')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('mostrarModalSesion', false)">
                    Volver
                </x-secondary-button>

                <button type="button" wire:click="crearSesion" style="font-size: 0.75rem; font-weight: 600"
                    class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                    Guardar
                </button>

            </x-slot>
        </x-dialog-modal>
    @endif

    @if ($mostrarModalAsistencia)
        <x-dialog-modal wire:model="mostrarModalAsistencia">
            <x-slot name="title">
                Registro de Asistencias: {{ $sesionSeleccionada->nombre ?? '' }}
            </x-slot>

            <x-slot name="content">
                <!-- Campo de búsqueda -->
                <div class="mb-4">
                    <input type="text" wire:model.live="searchParticipante" placeholder="Buscar por nombre o DNI..."
                        class="w-full border rounded p-2 mb-4" />
                </div>
                <div class="overflow-auto max-h-96">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-2 text-left text-sm font-medium text-gray-700">Participante</th>
                                <th class="px-6 py-2 text-left text-sm font-medium text-gray-700">DNI</th>
                                <th class="px-6 py-2 text-left text-sm font-medium text-gray-700">Asistió</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($asistencias as $index => $asistencia)
                                <tr>
                                    <td class="px-6 py-2">{{ $asistencia['nombre'] }}</td>
                                    <td class="px-6 py-2">{{ $asistencia['dni'] }}</td>
                                    <td class="px-6 py-2 text-center">
                                        <input type="checkbox" wire:model="asistencias.{{ $index }}.asistio"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring focus:ring-blue-200 focus:ring-opacity-50" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('mostrarModalAsistencia', false)">
                    Cancelar
                </x-secondary-button>

                <x-button class="ml-2 bg-blue-600 text-white" wire:click="guardarAsistencia">
                    Guardar Asistencia
                </x-button>
            </x-slot>
        </x-dialog-modal>
    @endif
</div>
