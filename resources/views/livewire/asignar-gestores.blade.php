<div class="bg-gray-200 bg-opacity-25 grid gap-6 lg:gap-8 p-6 lg:p-8">
    <div class="w-full p-4 bg-white shadow-md rounded-lg">

        @if ($evento)
            <h2 class="text-xl font-bold mb-4">
                Asignar gestores al Evento: {{ $evento->nombre }}
            </h2>
        @endif

        <form wire:submit.prevent="guardar" enctype="multipart/form-data" class="space-y-4">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Seleccionar Gestores</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach ($gestores as $gestor)
                        <label class="flex items-center space-x-2">
                            <input type="checkbox" wire:model="gestoresSeleccionados" value="{{ $gestor->id }}"
                                class="rounded text-indigo-600">
                            <span>{{ $gestor->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end space-x-4 px-6 pt-4">
                <x-secondary-button wire:click="redirectToEventos('pendientes')">
                    Volver
                </x-secondary-button>

                <button type="submit" wire:loading.attr="disabled" style="font-size: 0.75rem; font-weight: 600"
                    class="btn btn-primary rounded-md text-white uppercase py-2 px-4 mx-4">
                    <span wire:loading.remove>Guardar</span>
                    <span wire:loading>Procesando...</span>
                </button>
            </div>
        </form>
    </div>
</div>
