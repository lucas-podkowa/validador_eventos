<div class="bg-gray-200 bg-opacity-25 grid gap-6 lg:gap-8 p-6 lg:p-8">
    <div class="w-full p-4 bg-white shadow-md rounded-lg">

        @if ($evento)
            {{-- <h2 class="text-xl font-bold mb-4">Habilitar Inscripciones al Evento: {{ $evento->nombre }}</h2> --}}
            <h2 class="text-xl font-bold mb-4">
                {{ $modo === 'crear' ? 'Habilitar' : 'Editar' }} Inscripciones al Evento: {{ $evento->nombre }}
            </h2>
        @endif

        <form wire:submit.prevent="guardar_planilla" enctype="multipart/form-data" class="space-y-4">

            <div class="flex pt-4 px-6 gap-4">
                <div class="mb-4">
                    <label for="apertura" class="block text-gray-700">Fecha y Hora de Apertura</label>
                    <input type="datetime-local" id="apertura" wire:model="apertura"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    @error('apertura')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="cierre" class="block text-gray-700">Fecha y Hora de Cierre</label>
                    <input type="datetime-local" id="cierre" wire:model="cierre"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    @error('cierre')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="flex flex-col gap-4 pt-4 px-6">

                <div class="flex w-full gap-4 ">
                    <div class="w-1/2 bg-gray-100 rounded-xl">
                        <button type="button" wire:click="abrirGaleria('header')"
                            class="btn btn-secondary w-full">Imagen de
                            Cabecera</button>
                        @if ($header)
                            <img src="{{ asset('storage/' . $header) }}" class="max-h-24 w-auto object-contain mx-auto">
                        @endif
                        @error('header')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="w-1/2 bg-gray-100 rounded-xl">
                        <button type="button" wire:click="abrirGaleria('footer')"
                            class="btn btn-secondary w-full">Imagen de
                            Pie</button>
                        @if ($footer)
                            <img src="{{ asset('storage/' . $footer) }}"
                                class="max-h-24 w-auto object-contain mx-auto bg-red-300">
                        @endif
                        @error('footer')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="w-full">
                    <label for="disposicion" class="block text-sm font-medium text-gray-700 mb-1">Disposición
                        respaldatoria</label>
                    <div class="relative">
                        <input id="disposicion" type="file" wire:model="disposicion" accept="application/pdf"
                            class="absolute inset-0 opacity-0 w-full h-full z-10 cursor-pointer" />
                        <div
                            class="flex items-center justify-between border border-gray-300 rounded-md p-2 bg-white shadow-sm">
                            <span class="text-sm text-gray-500 truncate">
                                @if ($disposicion instanceof \Illuminate\Http\UploadedFile)
                                    {{ $disposicion->getClientOriginalName() }}
                                @elseif (is_string($disposicion))
                                    {{ basename($disposicion) }}
                                @else
                                    Seleccioná un archivo PDF
                                @endif
                            </span>
                        </div>
                    </div>
                    @error('disposicion')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-4 px-6 pt-4">
                <x-secondary-button wire:click="redirectToEventos('pendientes')">
                    Volver
                </x-secondary-button>

                <button type="submit" wire:loading.attr="disabled" style="font-size: 0.75rem; font-weight: 600"
                    class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                    <span wire:loading.remove>{{ $modo === 'crear' ? 'Habilitar' : 'Actualizar' }}</span>
                    {{-- <span wire:loading.remove>Habilitar</span> --}}
                    <span wire:loading>Procesando...</span>
                </button>
            </div>
        </form>

    </div>


    <!-- Modal Cabecera -->
    @if ($showHeaderModal)
        <x-modal>
            <x-slot name="title">Seleccionar Cabecera</x-slot>

            {{-- Galería --}}
            <div class="grid grid-cols-3 gap-2 p-2">
                @foreach ($imagenesDisponibles as $imagen)
                    <div class="cursor-pointer" wire:click="seleccionarImagen('{{ $imagen }}', 'header')">
                        <img src="{{ asset('storage/' . $imagen) }}" class="rounded shadow hover:scale-105 transition">
                    </div>
                @endforeach
            </div>

            {{-- Cargar nueva imagen --}}
            <div class="border-t pt-4 mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Subir nueva imagen</label>
                <input type="file" wire:model="nuevaImagen" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                   file:rounded-md file:border-0 file:text-sm file:font-semibold
                   file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />

                @error('nuevaImagen')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror

                <button type="button" wire:click="guardarNuevaImagen"
                    class="mt-2 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">
                    Subir Imagen
                </button>
            </div>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showHeaderModal', false)">Cancelar</x-secondary-button>
            </x-slot>
        </x-modal>
    @endif

    <!-- Modal Pie -->
    @if ($showFooterModal)
        <x-modal>
            <x-slot name="title">Seleccionar Pie de Página</x-slot>

            {{-- Galería --}}
            <div class="grid grid-cols-3 gap-2 p-2">
                @foreach ($imagenesDisponibles as $imagen)
                    <div class="cursor-pointer" wire:click="seleccionarImagen('{{ $imagen }}', 'footer')">
                        <img src="{{ asset('storage/' . $imagen) }}" class="rounded shadow hover:scale-105 transition">
                    </div>
                @endforeach
            </div>

            {{-- Cargar nueva imagen --}}
            <div class="border-t pt-4 mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Subir nueva imagen</label>
                <input type="file" wire:model="nuevaImagen" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                   file:rounded-md file:border-0 file:text-sm file:font-semibold
                   file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />

                @error('nuevaImagen')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror

                <button type="button" wire:click="guardarNuevaImagen"
                    class="mt-2 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 text-sm">
                    Subir Imagen
                </button>
            </div>

            <x-slot name="footer">
                <x-secondary-button wire:click="$set('showFooterModal', false)">Cancelar</x-secondary-button>
            </x-slot>
        </x-modal>
    @endif


</div>
