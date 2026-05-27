<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">


    <div class="py-2 flex justify-between items-center">
        <h2 class="text-xl font-bold mb-4">Inscripción a Eventos y emisión directa de Certificados</h2>
        <button wire:click="abrirModal" style="font-size: 0.75rem; font-weight: 600"
            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
            Nueva Emisión
        </button>
    </div>
    <div>
        <x-table>
            <table class="w-full min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Participante</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Curso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Certificado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">

                    @foreach ($eventoParticipantes as $ep)
                        <tr>
                            <td class="px-6 py-3">{{ $ep->participante->nombre }}</td>
                            <td class="px-6 py-3">{{ $ep->evento->nombre ?? 'Evento sin nombre' }}</td>
                            <td class="px-6 py-3">
                                @if ($ep->certificado_path)
                                    <a href="{{ route('ver.certificado', $ep) }}" target="_blank"
                                        title="Ver Certificado" class="text-red-600 hover:text-red-800">
                                        <i class="mr-2 fa-solid fa-file-pdf fa-xl text-blue-500"></i>
                                    </a>
                                @else
                                    <span class="text-gray-400" title="Certificado no disponible">
                                        <i class="fa-solid fa-file-pdf fa-xl"></i>
                                    </span>
                                @endif

                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-table>
        <!-- Paginación -->
        {{-- <div class="mt-4">
            {{ $eventoParticipantes->links() }}
        </div> --}}





        <x-dialog-modal wire:model="modal_abierto">
            <x-slot name="title">Emisor de Certificados</x-slot>

            <x-slot name="content">

                {{-- SECCIÓN 1: DATOS DEL EVENTO --}}
                <div class="border border-gray-300 rounded-md p-4 mb-6 bg-gray-50">
                    <h2 class="text-lg font-semibold mb-2 text-gray-700">🗓️ Información del Evento</h2>

                    <div class="mb-4">
                        {{-- <label class="block font-medium text-sm text-gray-700">Evento</label> --}}
                        <select wire:model="evento_id" class="form-select w-full mt-1">
                            <option value="">-- Seleccionar --</option>
                            @foreach ($eventos as $evento)
                                <option value="{{ $evento->evento_id }}">{{ $evento->nombre }}</option>
                            @endforeach
                        </select>
                        @error('evento_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block font-medium text-sm text-gray-700">🏆 Rol del Participante</label>
                        <select wire:model="rol_id" class="form-select w-full mt-1">
                            <option value="">-- Seleccionar Rol --</option>
                            {{-- $roles fue cargado en el mount() del componente --}}
                            @foreach ($roles as $rol)
                                <option value="{{ $rol->rol_id }}">{{ $rol->nombre }}</option>
                            @endforeach
                        </select>
                        @error('rol_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-2">
                        @php
                            $tiposDisponibles = array_keys($plantillas_por_tipo ?? []);
                            $labelForTipo = function ($t) {
                                return $t === 'aprobacion' ? 'Aprobación' : ucfirst($t);
                            };
                        @endphp

                        @if (!empty($tiposDisponibles))
                            <label class="block font-medium text-sm text-gray-700 mb-2">🖼️ Plantilla del certificado</label>

                            @if (count($tiposDisponibles) > 1)
                                <div class="mb-3">
                                    <label class="block text-xs text-gray-500">Tipo de certificado</label>
                                    <select wire:model="certificado_tipo" class="form-select w-full mt-1">
                                        @foreach ($tiposDisponibles as $t)
                                            <option value="{{ $t }}">{{ $labelForTipo($t) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if ($certificado_tipo && isset($plantillas_por_tipo[$certificado_tipo]) && count($plantillas_por_tipo[$certificado_tipo]) > 0)
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    @foreach ($plantillas_por_tipo[$certificado_tipo] as $plt)
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model="plantilla_id" value="{{ $plt['plantilla_id'] }}" class="sr-only peer">
                                            <div class="border-2 rounded-lg overflow-hidden transition-all
                                                    peer-checked:border-indigo-500 peer-checked:ring-2 peer-checked:ring-indigo-300
                                                    hover:border-gray-400">
                                                <img src="{{ asset('storage/' . $plt['imagen_path']) }}" alt="{{ $plt['nombre'] }}" class="w-full h-24 object-cover">
                                                <p class="text-xs text-center py-1 font-medium text-gray-700">{{ $plt['nombre'] }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('plantilla_id')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            @else
                                {{-- Fallback: carga manual si no hay plantillas para el tipo seleccionado --}}
                                <label for="background_image" class="block text-sm font-medium text-gray-700">
                                    🖼️ Plantilla para el certificado
                                    @if ($evento_id)
                                        <span class="text-xs text-gray-400 ml-1">(la categoría del evento no tiene plantillas para este tipo)</span>
                                    @endif
                                </label>
                                <input type="file" id="background_image" wire:model="background_image" accept="image/png, image/jpeg, image/jpg" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('background_image')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            @endif

                        @else
                            {{-- Fallback global: cargar imagen si la categoría no tiene plantillas --}}
                            <label for="background_image" class="block text-sm font-medium text-gray-700">
                                🖼️ Plantilla para el certificado
                                @if ($evento_id)
                                    <span class="text-xs text-gray-400 ml-1">(la categoría del evento no tiene plantillas)</span>
                                @endif
                            </label>
                            <input type="file" id="background_image" wire:model="background_image" accept="image/png, image/jpeg, image/jpg" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('background_image')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        @endif
                    </div>
                </div>

                {{-- SECCIÓN 2: DATOS DEL PARTICIPANTE --}}
                <div class="border border-gray-300 rounded-md p-4 bg-white">
                    <h2 class="text-lg font-semibold mb-4 text-gray-700">👤 Información del Participante</h2>

                    {{-- Línea: DNI --}}
                    <div class="flex flex-col">
                        <label for="dni" class="mb-1 lg:mb-0 font-medium">Número
                            DNI:</label>

                        <input type="text" wire:model.defer="dni" wire:keydown.enter="buscarParticipante"
                            wire:keydown.tab="buscarParticipante" wire:blur="buscarParticipante"
                            class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300" />

                        @error('dni')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Campo: Nombre -->
                    <div class="flex flex-col mt-2">
                        <label for="nombre" class="mb-1 lg:mb-0 font-medium">Nombre
                            Completo:</label>
                        <input type="text" id="nombre" wire:model="nombre"
                            class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                        @error('nombre')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Campo: Apellido -->
                    <div class="flex flex-col mt-2">
                        <label for="apellido" class="mb-1 lg:mb-0 font-medium">Apellido:</label>
                        <input type="text" id="apellido" wire:model="apellido"
                            class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                        @error('apellido')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Campo: Correo Electrónico -->
                    <div class="flex flex-col mt-2">
                        <label for="mail" class="mb-1 lg:mb-0 font-medium">Email:</label>
                        <input type="email" id="mail" wire:model="mail"
                            class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                        @error('mail')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Campo: Teléfono -->
                    <div class="flex flex-col mt-2">
                        <label for="telefono" class="mb-1 lg:mb-0 font-medium">Teléfono:</label>
                        <input type="number" id="telefono" wire:model="telefono"
                            class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                        @error('telefono')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

            </x-slot>

            <x-slot name="footer">
                <div class="py-2 flex justify-end items-center">
                    <!-- Botones de Guardar y Volver (Derecha) -->
                    <div class="flex space-x-4">
                        <x-secondary-button wire:click="$set('modal_abierto', false)">
                            Volver
                        </x-secondary-button>

                        <button wire:click="guardar" style="font-size: 0.75rem; font-weight: 600"
                            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                            Guardar
                        </button>
                    </div>
                </div>
            </x-slot>
        </x-dialog-modal>
    </div>
</div>
