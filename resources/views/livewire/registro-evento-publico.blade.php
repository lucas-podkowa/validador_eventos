{{-- <div class="bg-gray-200 bg-opacity-25 gap-6 lg:gap-8 p-6 lg:p-8 justify-center"> --}}
<div class="min-h-screen flex flex-col">

    <header class="w-full flex justify-center bg-gray-200">
        @if (isset($planilla_inscripcion['header']) && $planilla_inscripcion['header'])
            <img src="{{ asset('storage/' . $planilla_inscripcion['header']) }}" alt="Cabecera del formulario"
                class="max-h-[300px] w-auto object-contain">
        @endif
    </header>

    <main class="flex-1 p-4 justify-center">
        <!-- Título del formulario -->
        <h2 class="text-2xl font-semibold text-center">Inscripción al evento: {{ $evento->nombre }}</h2>

        <div class="w-full p-4 bg-white shadow-md rounded-lg">
            @if ($inscripcion_activa)
                <form wire:submit.prevent="submit" class="space-y-6">
                    <!-- Datos principales -->
                    <div class="flex flex-col gap-4">
                        <!-- Campo: DNI -->
                        <div class="flex items-center">
                            <label for="dni" class="text-right pr-4 font-medium mr-2">Número DNI:</label>

                            <input type="text" wire:model.defer="dni" wire:keydown.enter="buscarParticipante"
                                wire:keydown.tab="buscarParticipante" wire:blur="buscarParticipante"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300" />

                            @error('dni')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Campo: Nombre -->
                        <div class="flex items-center">
                            <label for="nombre" class="text-right pr-4 font-medium mr-2">Nombre:</label>
                            <input type="text" id="nombre" wire:model="nombre"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('nombre')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Campo: Apellido -->
                        <div class="flex items-center">
                            <label for="apellido" class="text-right pr-4 font-medium mr-2">Apellido:</label>
                            <input type="text" id="apellido" wire:model="apellido"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('apellido')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Campo: Correo Electrónico -->
                        <div class="flex items-center">
                            <label for="mail" class="text-right pr-4 font-medium mr-2">Email:</label>
                            <input type="email" id="mail" wire:model="mail"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('mail')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Campo: Localidad -->
                        {{-- <div class="flex items-start">
                    <label for="localidad_nombre" class="text-right pr-4 font-medium mr-2">Localidad:</label>
                    <div class="flex-1 relative">
                        <input type="text" id="localidad_nombre" wire:model="localidad_nombre"
                            wire:keyup="buscarLocalidades" placeholder="Escriba el nombre de la localidad"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                        <ul class="bg-white shadow rounded-md mt-2 absolute w-full z-10 max-h-40 overflow-y-auto">
                            @if (!empty($localidadesFiltradas))
                                @foreach ($localidadesFiltradas as $localidad)
                                    <li class="px-4 py-2 cursor-pointer hover:bg-gray-200"
                                        wire:click="seleccionarLocalidad({{ $localidad->localidad_id }}, '{{ $localidad->nombre }}')">
                                        {{ $localidad->nombre }}
                                    </li>
                                @endforeach
                            @endif
                            @if (empty($localidadesFiltradas) && !empty($localidad_nombre))
                                <li class="px-4 py-2 text-gray-500">
                                    Sin coincidencias. "{{ $localidad_nombre }}" será agregada a la base de datos.
                                </li>
                            @endif
                        </ul>
                        @error('localidad_id')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>
                </div> --}}
                    </div>

                    <!-- Indicadores -->
                    <div class="space-y-6">
                        @foreach ($evento->tipoIndicadores as $tipo_indicador)
                            <fieldset>
                                <legend class="font-semibold">{{ $tipo_indicador->nombre }}</legend>
                                <div class="mt-2 space-y-2">
                                    @foreach ($tipo_indicador->indicadores as $indicador)
                                        <div class="flex items-center">
                                            <input type="checkbox" name="indicadores[]"
                                                id="indicador_{{ $indicador->id }}" value="{{ $indicador->id }}"
                                                class="mr-2">

                                            {{-- <input type="checkbox" wire:model="tipos_indicadores_seleccionados"
                                            id="indicador_{{ $indicador->id }}" value="{{ $indicador->id }}"
                                            class="mr-2"> --}}

                                            <label
                                                for="indicador_{{ $indicador->id }}">{{ $indicador->nombre }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </fieldset>
                            <hr class="my-4 border-gray-300">
                        @endforeach
                    </div>

                    <!-- Botón -->
                    <button type="submit"
                        class="w-full py-2 px-4 bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-300">
                        Inscribirse
                    </button>
                </form>
            @else
                <div class="text-center text-red-600 font-semibold text-lg">
                    <p>El presente formulario de inscripción no se encuentra activo.</p>
                </div>
            @endif
        </div>

    </main>

    <footer class="w-full flex justify-center bg-gray-200">
        @if (isset($planilla_inscripcion['footer']) && $planilla_inscripcion['footer'])
            <img src="{{ asset('storage/' . $planilla_inscripcion['footer']) }}" alt="Cabecera del formulario"
                class="max-h-[300px] w-auto object-contain">
        @endif
    </footer>
</div>
