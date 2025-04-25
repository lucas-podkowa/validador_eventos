<div class="bg-gray-200 bg-opacity-25 grid gap-6 lg:gap-8 p-6 lg:p-8">
    <div class="w-full p-4 bg-white shadow-md rounded-lg">

        @if ($evento)
            <h2 class="text-xl font-bold mb-4">Habilitar Inscripciones al Evento: {{ $evento->nombre }}</h2>
        @endif

        <form wire:submit.prevent="habilitar_planilla" enctype="multipart/form-data" class="space-y-4">

            <div class="flex pt-4 px-6 gap-4">
                <div class="mb-4">
                    <label for="apertura" class="block text-gray-700">Fecha y Hora de Apertura</label>
                    <input type="datetime-local" id="apertura" wire:model="apertura"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                </div>

                <div class="mb-4">
                    <label for="cierre" class="block text-gray-700">Fecha y Hora de Cierre</label>
                    <input type="datetime-local" id="cierre" wire:model="cierre"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                </div>
            </div>
            <div class="flex flex-col gap-4 pt-4 px-6">
                <div class="w-full">
                    <label for="header" class="block text-sm font-medium text-gray-700">Imagen de
                        Cabecera</label>
                    <input type="file" id="header" wire:model="header" accept="image/png, image/jpeg, image/jpg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('header')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="footer" class="block text-sm font-medium text-gray-700">Imagen de Pie</label>
                    <input type="file" id="footer" wire:model="footer" accept="image/png, image/jpeg, image/jpg"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('footer')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div class="w-full">
                    <label for="disposicion" class="block text-sm font-medium text-gray-700">Disposición
                        respaldatoria</label>
                    <input type="file" id="disposicion" wire:model="disposicion" accept="application/pdf"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
                    <span wire:loading.remove>Habilitar</span>
                    <span wire:loading>Procesando...</span>
                </button>
            </div>
        </form>

    </div>
</div>
