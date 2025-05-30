{{-- <div class="bg-gray-200 bg-opacity-25 gap-6 lg:gap-8 p-6 lg:p-8 justify-center"> --}}
<div class="min-h-screen flex flex-col">

    <header class="w-full flex justify-center bg-gray-200">
        @if (isset($planilla_inscripcion['header']) && $planilla_inscripcion['header'])
            <img src="{{ asset('storage/' . $planilla_inscripcion['header']) }}" alt="Cabecera del formulario"
                class="w-full h-auto object-cover">
        @endif
    </header>

    <main class="flex-1 p-4 justify-center max-w-4xl mx-auto">
        <!-- Título del formulario -->
        <h2 class="text-2xl font-semibold text-center">Inscripción al evento: {{ $evento->nombre }}</h2>

        <div class="w-full p-4 bg-white shadow-md rounded-lg">
            @if ($inscripcion_activa)
                <form wire:submit.prevent="submit" class="space-y-6">
                    <!-- Datos principales -->
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                        <!-- Campo: DNI -->
                        <div class="flex flex-col lg:flex-row lg:items-center">
                            <label for="dni" class="mb-1 lg:mb-0 lg:text-right lg:pr-4 font-medium">Número
                                DNI:</label>

                            <input type="text" wire:model.defer="dni" wire:keydown.enter="buscarParticipante"
                                wire:keydown.tab="buscarParticipante" wire:blur="buscarParticipante"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300" />

                            @error('dni')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Campo: Nombre -->
                        <div class="flex flex-col lg:flex-row lg:items-center">
                            <label for="nombre" class="mb-1 lg:mb-0 lg:text-right lg:pr-4 font-medium">Nombre
                                Completo:</label>
                            <input type="text" id="nombre" wire:model="nombre"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('nombre')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Campo: Apellido -->
                        <div class="flex flex-col lg:flex-row lg:items-center">
                            <label for="apellido"
                                class="mb-1 lg:mb-0 lg:text-right lg:pr-4 font-medium">Apellido:</label>
                            <input type="text" id="apellido" wire:model="apellido"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('apellido')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Campo: Correo Electrónico -->
                        <div class="flex flex-col lg:flex-row lg:items-center">
                            <label for="mail" class="mb-1 lg:mb-0 lg:text-right lg:pr-4 font-medium">Email:</label>
                            <input type="email" id="mail" wire:model="mail"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('mail')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Campo: Correo Electrónico -->
                        <div class="flex flex-col lg:flex-row lg:items-center">
                            <label for="telefono"
                                class="mb-1 lg:mb-0 lg:text-right lg:pr-4 font-medium">Teléfono:</label>
                            <input type="number" id="mail" wire:model="telefono"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('telefono')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Indicadores -->
                    <div class="space-y-6">
                        @foreach ($evento->tipoIndicadores as $tipo)
                            <fieldset>
                                <legend class="font-semibold mb-2">{{ $tipo->nombre }}</legend>
                                <div class="flex flex-col space-y-2 ml-4">
                                    @if ($tipo->selector === 'Selección Múltiple')
                                        @foreach ($tipo->indicadores as $indicador)
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" wire:model="indicadoresMutiples"
                                                    value="{{ $indicador->indicador_id }}" class="mr-1">
                                                {{ $indicador->nombre }}
                                            </label>
                                        @endforeach
                                    @else
                                        @foreach ($tipo->indicadores as $indicador)
                                            <label class="inline-flex items-center mr-4">
                                                <input type="radio"
                                                    name="indicadoresUnicos_{{ $tipo->tipo_indicador_id }}"
                                                    wire:model="indicadoresUnicos.{{ $tipo->tipo_indicador_id }}"
                                                    value="{{ $indicador->indicador_id }}" class="mr-1">
                                                {{ $indicador->nombre }}
                                            </label>
                                        @endforeach
                                    @endif
                                </div>
                            </fieldset>
                        @endforeach
                    </div>


                    <!-- Botón -->
                    <button type="submit"
                        class="font-semibold text-white uppercase w-full py-2 px-4 bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-300">
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
                class="w-full h-auto object-cover">
        @endif
    </footer>
</div>
