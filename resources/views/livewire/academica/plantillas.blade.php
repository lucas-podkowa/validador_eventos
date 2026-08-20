<div class="px-4 sm:px-6 lg:px-8 py-4">

    <div class="py-2">
        <h2 class="text-xl font-bold mb-2">Plantillas de Certificados — Títulos Intermedios</h2>
        <p class="text-sm text-gray-500 mb-6">
            Seleccioná una carrera a la izquierda para ver sus títulos intermedios y sus plantillas a la derecha.
            Cada título intermedio admite una única plantilla.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Columna izquierda: carreras --}}
        <div class="border border-gray-200 rounded-xl bg-white overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">Carreras</h3>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($carreras as $carrera)
                    <li>
                        <button wire:click="$set('carrera_id', {{ $carrera->id }})"
                            class="w-full text-left px-4 py-3 transition {{ $carrera_id === $carrera->id ? 'bg-indigo-50 border-l-4 border-indigo-600' : 'hover:bg-indigo-50' }}">
                            <span class="block text-sm font-medium text-gray-800">{{ $carrera->nombre }}</span>
                            <span class="block text-xs text-gray-400 mt-0.5">
                                {{ $carrera->codigo }} · {{ $carrera->titulosIntermedios()->count() }} título(s)
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-gray-400">No hay carreras cargadas.</li>
                @endforelse
            </ul>
        </div>

        {{-- Columna derecha: títulos de la carrera seleccionada --}}
        <div class="md:col-span-2 border border-gray-200 rounded-xl bg-white overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">
                    Títulos intermedios
                    @if ($carrera_id)
                        — {{ $carreras->firstWhere('id', $carrera_id)?->nombre }}
                    @endif
                </h3>
            </div>

            <div class="p-4">
                @if (! $carrera_id)
                    <p class="text-sm text-gray-400">Seleccioná una carrera para ver sus títulos intermedios.</p>
                @elseif ($titulos->isEmpty())
                    <p class="text-sm text-gray-400">Esta carrera no tiene títulos intermedios cargados.</p>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach ($titulos as $titulo)
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="aspect-[3/2] bg-gray-100 flex items-center justify-center overflow-hidden">
                                    @if ($titulo->imagen_plantilla_path && \Storage::disk('public')->exists($titulo->imagen_plantilla_path))
                                        <img src="{{ asset('storage/' . $titulo->imagen_plantilla_path) }}"
                                            alt="Plantilla de {{ $titulo->nombre }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xs text-gray-400 px-4 text-center">Sin plantilla cargada</span>
                                    @endif
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $titulo->nombre }}</p>
                                            <span class="text-xs {{ $titulo->activo ? 'text-green-600' : 'text-red-500' }}">
                                                {{ $titulo->activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                        <button wire:click="selectTituloParaPlantilla({{ $titulo->id }})"
                                            class="btn btn-primary text-white text-xs uppercase py-2 px-3 rounded-md whitespace-nowrap">
                                            {{ $titulo->imagen_plantilla_path ? 'Reemplazar plantilla' : 'Agregar plantilla' }}
                                        </button>
                                    </div>

                                    @if ($nueva_plantilla_titulo_id === $titulo->id)
                                        <form wire:submit.prevent="guardarPlantilla" class="mt-3 space-y-2 border-t border-gray-100 pt-3">
                                            <input type="file" wire:model="nueva_plantilla_imagen"
                                                accept="image/png,image/jpeg"
                                                class="w-full text-sm border-gray-300 rounded-md shadow-sm">
                                            @error('nueva_plantilla_imagen')
                                                <span class="text-red-500 text-xs">{{ $message }}</span>
                                            @enderror
                                            <div class="flex gap-2 items-center">
                                                <button type="submit"
                                                    class="btn btn-primary text-white text-xs uppercase py-2 px-3 rounded-md">
                                                    <div wire:loading wire:target="nueva_plantilla_imagen" class="inline">
                                                        <i class="fas fa-spinner fa-spin mr-1"></i>
                                                    </div>
                                                    Subir
                                                </button>
                                                <button type="button" wire:click="cancelarSeleccionPlantilla"
                                                    class="text-xs text-gray-500 hover:text-gray-700 px-2">Cancelar</button>
                                            </div>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
