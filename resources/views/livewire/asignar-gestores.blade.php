<div class="bg-gray-200 bg-opacity-25 grid gap-6 lg:gap-8 p-6 lg:p-8">
    <div class="w-full p-4 bg-white shadow-md rounded-lg">

        @if ($evento)
            <h2 class="text-xl font-bold mb-4">
                Asignar gestores al Evento: {{ $evento->nombre }}
            </h2>
        @endif

        <form wire:submit.prevent="guardar" enctype="multipart/form-data" class="space-y-4">

            <div class="mb-4">
                <input type="text" wire:model.live="searchGestor" placeholder="Buscar gestor por nombre..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm mb-4">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-60 overflow-y-auto p-2 border rounded-md">
                    @forelse ($gestores as $gestor)
                        <label class="flex items-center space-x-2" wire:key="gestor-{{ $gestor->id }}">
                            <input type="checkbox" wire:model="gestoresSeleccionados" value="{{ $gestor->id }}"
                                class="rounded text-indigo-600">
                            <span>{{ $gestor->name }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500 col-span-full">No se encontraron gestores con ese nombre.</p>
                    @endforelse
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
