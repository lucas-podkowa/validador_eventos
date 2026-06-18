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
                    <div class="flex flex-col gap-4">
                        <!-- Campo: DNI -->
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
                        <div class="flex flex-col">
                            <label for="nombre" class="mb-1 lg:mb-0 font-medium">Nombre
                                Completo:</label>
                            <input type="text" id="nombre" wire:model="nombre"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('nombre')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Campo: Apellido -->
                        <div class="flex flex-col">
                            <label for="apellido" class="mb-1 lg:mb-0 font-medium">Apellido:</label>
                            <input type="text" id="apellido" wire:model="apellido"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('apellido')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Campo: Correo Electrónico -->
                        <div class="flex flex-col">
                            <label for="mail" class="mb-1 lg:mb-0 font-medium">Email:</label>
                            <input type="email" id="mail" wire:model="mail"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('mail')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <!-- Campo: Teléfono -->
                        <div class="flex flex-col">
                            <label for="telefono" class="mb-1 lg:mb-0 font-medium">Teléfono:</label>
                            <input type="number" id="telefono" wire:model="telefono"
                                class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                            @error('telefono')
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Destinatario y pago -->
                    @if ($evento->arancel)
                        <div class="space-y-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex flex-col">
                                <label for="destinatario_id" class="mb-1 lg:mb-0 font-medium">
                                    ¿Cuál es tu situación respecto a la institución? <span class="text-red-600">*</span>
                                </label>
                                <select id="destinatario_id" wire:model.live="destinatario_id"
                                    class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                                    <option value="">Seleccione</option>
                                    @foreach ($evento->destinatarios as $dest)
                                        <option value="{{ $dest->destinatario_id }}">
                                            {{ $dest->nombre }}
                                            @if ($dest->pivot->precio > 0)
                                                — ${{ number_format($dest->pivot->precio, 2, ',', '.') }}
                                            @else
                                                — Gratuito
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('destinatario_id')
                                    <span class="text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </div>

                            @if ($montoDestinatario !== null)
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                                    <p class="text-blue-900 font-semibold">
                                        Monto a abonar:
                                        @if ($montoDestinatario > 0)
                                            ${{ number_format($montoDestinatario, 2, ',', '.') }}
                                        @else
                                            <span class="text-green-700">No corresponde abono</span>
                                        @endif
                                    </p>
                                </div>

                                @if ($montoDestinatario > 0)
                                    <div class="flex flex-col">
                                        <label class="mb-1 lg:mb-0 font-medium">Link de pago</label>
                                        <a href="{{ $evento->link_pago }}" target="_blank" rel="noopener noreferrer"
                                            class="text-blue-600 hover:text-blue-800 underline break-all">
                                            {{ $evento->link_pago }}
                                        </a>
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="comprobante" class="mb-1 lg:mb-0 font-medium">
                                            Adjuntar comprobante de pago <span class="text-red-600">*</span>
                                        </label>
                                        <input type="file" id="comprobante" wire:model="comprobante"
                                            accept=".pdf,image/jpeg,image/png"
                                            class="w-full lg:flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring focus:ring-blue-300">
                                        <p class="text-xs text-gray-500 mt-1">PDF, JPG o PNG, máximo 2 MB.</p>
                                        @error('comprobante')
                                            <span class="text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

                    <!-- Indicadores -->
                    <div class="space-y-6">
                        @foreach ($evento->tipoIndicadores as $tipo)
                            <fieldset>
                                <legend class="font-semibold mb-2">{{ $tipo->nombre }}</legend>
                                <div class="flex flex-col space-y-2 ml-4">
                                    @if ($tipo->selector === 'Selección Múltiple')
                                        @foreach ($tipo->indicadores as $indicador)
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" wire:model="indicadoresMultiples"
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

                    {{-- Nota informativa --}}
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                        <div class="flex items-start">
                            <i class="fa-solid fa-lightbulb text-amber-600 text-xl mr-3 mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-amber-900 mb-1">Información importante</h4>
                                <p class="text-sm text-amber-800">
                                    Si el DNI ya está registrado en el sistema, los datos se autocompletarán
                                    automáticamente.
                                    Los campos pueden editarse y se actualizarán en el sistema.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botón -->
                    <button type="submit"
                        class="font-semibold text-white uppercase w-full py-2 px-4 bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring focus:ring-blue-300">
                        Inscribirse
                    </button>
                </form>
            @else
                <div class="text-center text-red-600 font-semibold text-lg">
                    @if ($evento->cupo !== null && $evento->planillaInscripcion->inscripciones->count() >= $evento->cupo)
                        <p>Este curso ya ha cubierto su cupo de {{ $evento->cupo }} participantes.</p>
                    @else
                        <p>El presente formulario de inscripción no se encuentra activo.</p>
                    @endif
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
