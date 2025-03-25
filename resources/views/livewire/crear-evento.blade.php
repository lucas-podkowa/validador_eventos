<div>
    <div class="pt-4 lg:p-8 pb-0 bg-white border-b border-gray-200">
        <p>
            Selecciona el tipo de evento y luego ingresa el nombre del mismo. A continuación, proporciona la fecha y el
            lugar donde se llevará a cabo. No olvides de adjuntar
            un archivo con la lista de participantes. Una vez completados todos los campos, presiona el botón "Procesar"
            para guardar el evento en el sistema.</p>
    </div>

    <div class="bg-gray-200 bg-opacity-25 grid gap-6 lg:gap-8 p-6 lg:p-8">

        <div class="w-full p-4 bg-white shadow-md rounded-lg">
            <form wire:submit.prevent="save" class="space-y-4">
                <!-- Dropdown y Nombre del evento -->
                <div class="flex py-4 px-6">
                    <!-- Dropdown para tipos de eventos -->
                    <div class="flex-2">
                        <label for="tipo_evento" class="block text-sm font-medium text-gray-700">Tipo de Evento</label>
                        <select wire:model.live="tipo_evento" id="tipo_evento"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Seleccione un tipo</option>
                            @foreach ($tiposEventos as $tipo)
                                <option value="{{ $tipo->tipo_evento_id }}">
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_evento')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Input para nombre del evento -->
                    <div class="flex-1 ml-4">
                        <label for="nombre_evento" class="block text-sm font-medium text-gray-700">Nombre del
                            Evento</label>
                        <input type="text" id="nombre_evento" wire:model.live="nombre_evento"
                            placeholder="Ingrese el nombre"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('nombre_evento')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Input para cupo -->
                    <div class="w-1/4 ml-4">
                        <label for="cupo" class="block text-sm font-medium text-gray-700">Cupo</label>
                        <input type="number" id="cupo" wire:model.live="cupo" placeholder="Sin Límite"
                            min="0"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('cupo')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Fecha y Lugar del evento -->
                <div class="flex pt-4 px-6 gap-4">
                    <!-- Fecha del evento -->
                    <div class="w-1/2">
                        <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha de
                            Inicio</label>
                        <input type="date" id="fecha_inicio" wire:model.live="fecha_inicio"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('fecha_inicio')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Lugar del evento -->
                    <div class="w-1/2">
                        <label for="lugar_evento" class="block text-sm font-medium text-gray-700">Lugar del
                            Evento</label>
                        <input type="text" id="lugar_evento" wire:model.live="lugar_evento"
                            placeholder="Ingrese el lugar"
                            class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('lugar_evento')
                            <span class="text-sm text-red-500">{{ $message }}</span>
                        @enderror
                    </div>
                    <!-- Indicadores a incluir -->
                    <div class="w-1/2 px-6">
                        <label for="indicadoresSeleccionados"
                            class="block text-sm font-medium text-gray-700">Indicadores a Incluir</label>
                        <ul class="w-40 text-sm font-medium text-gray-900 bg-white border border-gray-300 rounded-lg">
                            @foreach ($tiposIndicadores as $indicador)
                                <li class="w-full border-b border-gray-200 rounded-t-lg">
                                    <div class="flex items-center ps-3">
                                        <input wire:model="indicadoresSeleccionados" type="checkbox"
                                            value="{{ $indicador->tipo_indicador_id }}"
                                            class="w-4 h-3 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
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
                    <!-- Botón de Eliminar (Izquierda) -->
                    @if ($esEdicion)
                        <button type="button" wire:click="eliminarEvento"
                            class="flex items-center bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">

                            <i class="fa fa-trash pr-2"></i>
                            Eliminar
                        </button>
                    @endif

                    <!-- Botones de Actualizar y Volver (Derecha) -->
                    <div class="flex space-x-4">
                        <button type="button" wire:click="cancelarEdicion"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 mx-4 rounded">
                            Volver
                        </button>

                        <button type="submit" class="btn btn-primary">
                            {{ $esEdicion ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

</div>
