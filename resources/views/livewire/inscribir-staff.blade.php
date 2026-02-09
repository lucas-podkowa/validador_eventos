<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Header con breadcrumb --}}
            <div class="mb-6">
                <nav class="flex items-center text-sm text-gray-500 mb-4">
                    <a href="{{ route('eventos', ['tab' => 'activos']) }}" class="hover:text-blue-600 transition">
                        <i class="fa-solid fa-calendar-alt"></i> Eventos Activos
                    </a>
                    <i class="fa-solid fa-chevron-right mx-2 text-xs"></i>
                    <span class="text-gray-700 font-medium">Inscribir Disertante/Colaborador</span>
                </nav>

                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fa-solid fa-user-tie text-blue-600"></i>
                            Inscribir Disertante o Colaborador
                        </h1>
                        <p class="text-gray-600 mt-1">{{ $evento->nombre }}</p>
                    </div>

                    <button wire:click="volver"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Volver
                    </button>
                </div>
            </div>

            {{-- Card del formulario --}}
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">

                {{-- Formulario --}}
                <form wire:submit.prevent="submit" class="p-6 space-y-6">

                    {{-- Selección de Rol (destacado) --}}
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50  rounded-xl ">
                        <label class="block text-base font-bold text-gray-800 mb-2">
                            <i class="fa-solid fa-user-tag text-blue-600"></i>
                            Seleccione el rol <span class="text-red-500">*</span>
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Opción: Disertante --}}
                            <label
                                class="relative flex items-center p-2 bg-white rounded-lg border-2 cursor-pointer transition
                            {{ $rol_seleccionado === 'Disertante' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-purple-300' }}">
                                <input type="radio" wire:model="rol_seleccionado" value="Disertante"
                                    class="w-5 h-5 text-purple-600 focus:ring-purple-500">
                                <div class="ml-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-chalkboard-teacher text-purple-600 text-xl"></i>
                                        <span class="font-semibold text-gray-800">Disertante</span>
                                    </div>
                                    <span class="text-xs text-gray-600">Expositor del evento</span>
                                </div>
                            </label>

                            {{-- Opción: Colaborador --}}
                            <label
                                class="relative flex items-center p-2 bg-white rounded-lg border-2 cursor-pointer transition
                            {{ $rol_seleccionado === 'Colaborador' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300' }}">
                                <input type="radio" wire:model="rol_seleccionado" value="Colaborador"
                                    class="w-5 h-5 text-green-600 focus:ring-green-500">
                                <div class="ml-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-hands-helping text-green-600 text-xl"></i>
                                        <span class="font-semibold text-gray-800">Colaborador</span>
                                    </div>
                                    <span class="text-xs text-gray-600">Apoyo del evento</span>
                                </div>
                            </label>
                        </div>

                        @error('rol_seleccionado')
                            <p class="text-sm text-red-600 mt-2 flex items-center gap-1">
                                <i class="fa-solid fa-exclamation-circle"></i> {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- DNI con autocompletado --}}
                    <div>
                        <label for="dni" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fa-solid fa-id-card text-gray-500"></i>
                            Número de DNI <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="dni" wire:model.defer="dni"
                                wire:keydown.enter.prevent="buscarParticipante" wire:keydown.tab="buscarParticipante"
                                wire:blur="buscarParticipante"
                                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Ej: 12345678" maxlength="10">

                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fa-solid fa-info-circle"></i>
                            Presione Enter o Tab para buscar automáticamente
                        </p>
                        @error('dni')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Grid: Nombre y Apellido --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Nombre --}}
                        <div>
                            <label for="nombre" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fa-solid fa-user text-gray-500"></i>
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nombre" wire:model="nombre"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Ej: Juan Carlos">
                            @error('nombre')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Apellido --}}
                        <div>
                            <label for="apellido" class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fa-solid fa-user text-gray-500"></i>
                                Apellido <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="apellido" wire:model="apellido"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Ej: García López">
                            @error('apellido')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="mail" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fa-solid fa-envelope text-gray-500"></i>
                            Correo Electrónico <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="email" id="mail" wire:model="mail"
                                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="ejemplo@mail.com">
                        </div>
                        @error('mail')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label for="telefono" class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fa-solid fa-phone text-gray-500"></i>
                            Teléfono <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="tel" id="telefono" wire:model="telefono"
                                class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                placeholder="Ej: 3764123456">
                        </div>
                        @error('telefono')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nota informativa --}}
                    <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-lg">
                        <div class="flex items-start">
                            <i class="fa-solid fa-lightbulb text-amber-600 text-xl mr-3 mt-1"></i>
                            <div class="mt-1">
                                <p class="text-sm text-amber-900 font-semibold">
                                    Si el DNI ya está registrado en el sistema, los datos se autocompletarán
                                    automáticamente.
                                    Los campos pueden editarse y se actualizarán en el sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex justify-end">
                        <x-secondary-button wire:click="volver">
                            Cancelar
                        </x-secondary-button>
                        <button type="submit" style="font-size: 0.75rem; font-weight: 600"
                            class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                            Guardar Inscripción
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
