<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

    <div class="py-2">
        <h2 class="text-xl font-bold mb-2">Emisión de Certificados — Títulos Intermedios</h2>
        <p class="text-sm text-gray-500 mb-6">
            Elegí la carrera y el título intermedio, buscá al estudiante por DNI y emití su certificado.
            Si el estudiante aún no está cargado, se creará automáticamente al emitir.
        </p>
    </div>

    <div class="border border-gray-200 rounded-xl bg-white shadow-sm p-6 space-y-5">

        {{-- Selectores en cascada --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Carrera</label>
                <select wire:model.live="carrera_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- Seleccionar carrera --</option>
                    @foreach ($carreras as $carrera)
                        <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                    @endforeach
                </select>
                @error('carrera_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Título intermedio</label>
                <select wire:model.live="titulo_id" class="w-full border-gray-300 rounded-md shadow-sm" @disabled(! $carrera_id)>
                    <option value="">-- Seleccionar título --</option>
                    @foreach ($titulos as $titulo)
                        <option value="{{ $titulo->id }}">{{ $titulo->nombre }}</option>
                    @endforeach
                </select>
                @error('titulo_id')
                    <span class="text-red-500 text-xs">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Datos del estudiante --}}
        <div class="border-t border-gray-100 pt-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Datos del estudiante</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de documento (DNI)</label>
                    <div class="flex gap-2">
                        <input type="number" wire:model="dni" min="0"
                            class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Ej: 30123456">
                        <button wire:click="buscarParticipante" type="button"
                            class="btn btn-secondary whitespace-nowrap text-xs uppercase px-4 py-2 rounded-md">
                            Buscar
                        </button>
                    </div>
                    @if ($busqueda_realizada && $participante_encontrado)
                        <span class="text-green-600 text-xs">Participante encontrado. Los datos se completaron automáticamente.</span>
                    @elseif ($busqueda_realizada)
                        <span class="text-amber-600 text-xs">No existe un participante con ese DNI: se creará al emitir.</span>
                    @endif
                    @error('dni')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input type="text" wire:model="apellido" class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('apellido')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" wire:model="nombre" class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('nombre')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input type="email" wire:model="mail" class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('mail')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" wire:model="telefono" class="w-full border-gray-300 rounded-md shadow-sm">
                    @error('telefono')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
            @if ($certificado_emitido_id)
                <a href="{{ route('academica.ver_certificado', $certificado_emitido_id) }}" target="_blank"
                    class="btn btn-secondary text-xs uppercase px-4 py-2 rounded-md">
                    <i class="fas fa-file-pdf mr-1"></i> Ver certificado
                </a>
            @endif
            <button wire:click="emitir" class="btn btn-primary text-white text-xs uppercase py-3 px-6 rounded-md">
                Emitir Certificado
            </button>
        </div>
    </div>
</div>
