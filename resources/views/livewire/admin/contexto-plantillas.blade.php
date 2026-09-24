<div>
    <div class="flex justify-between items-center mb-4">
        <p class="text-sm text-gray-500">
            La imagen es sólo base (cabecera/borde). Arrastrá los bloques sobre la vista previa para ubicarlos.
        </p>
        <button type="button" wire:click="abrirCrear"
            class="btn btn-primary text-white text-xs font-semibold uppercase py-2 px-4 rounded-md whitespace-nowrap">
            + Nueva Plantilla
        </button>
    </div>

    @if (count($plantillas) > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach ($plantillas as $plantilla)
                <div class="relative border border-gray-200 rounded-2xl overflow-hidden bg-white">
                    <div class="absolute inset-x-0 top-0 z-10 flex items-center justify-end gap-2 p-2">
                        <button type="button" wire:click="abrirEditar({{ $plantilla['plantilla_id'] }})"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-700 shadow-sm hover:text-indigo-600"
                            title="Editar plantilla">
                            <i class="fas fa-edit text-xs"></i>
                        </button>
                        <button type="button" wire:click="eliminar({{ $plantilla['plantilla_id'] }})"
                            wire:confirm="¿Eliminar la plantilla '{{ $plantilla['nombre'] }}'?"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-red-200 bg-white text-red-500 shadow-sm hover:bg-red-50"
                            title="Eliminar plantilla">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                    <img src="{{ asset('storage/' . $plantilla['imagen_path']) }}"
                        alt="{{ $plantilla['nombre'] }}"
                        class="w-full h-44 object-cover bg-gray-50">
                    <div class="p-4 bg-white">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $plantilla['nombre'] }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ strtoupper($plantilla['tipo'] ?? 'ASISTENCIA') }}</span>
                            @if (!empty($plantilla['por_defecto']))
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">Predeterminada</span>
                            @endif
                            <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">{{ count($plantilla['layout'] ?? []) }} bloques</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-gray-400">Este contexto todavía no tiene plantillas dinámicas.</p>
    @endif

    <form wire:submit.prevent="guardar">
        <x-dialog-modal wire:model="open_modal" maxWidth="7xl">
            <x-slot name="title">
                {{ $editando_id ? 'Editar Plantilla del Contexto' : 'Nueva Plantilla del Contexto' }}
            </x-slot>

            <x-slot name="content">
                <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6">

                    {{-- ─────────────── Canvas drag & drop ─────────────── --}}
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <select wire:model="nuevo_campo"
                                class="flex-1 min-w-[180px] border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @foreach ($campos as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="agregarBloque"
                                class="btn btn-primary text-white text-xs font-semibold uppercase py-2 px-4 rounded-md whitespace-nowrap">
                                + Agregar bloque
                            </button>
                        </div>

                        <div
                            x-data="{
                                drag: null,
                                iniciar(e, index, modo) {
                                    const el = e.target.closest('[data-bloque]');
                                    if (!el) return;
                                    this.drag = {
                                        index, modo, el,
                                        rect: this.$refs.canvas.getBoundingClientRect(),
                                        startX: e.clientX, startY: e.clientY,
                                        left: el.offsetLeft, top: el.offsetTop,
                                        width: el.offsetWidth, height: el.offsetHeight,
                                        esImagen: el.dataset.imagen === '1',
                                    };
                                },
                                mover(e) {
                                    if (!this.drag) return;
                                    const d = this.drag;
                                    const dx = e.clientX - d.startX;
                                    const dy = e.clientY - d.startY;
                                    if (d.modo === 'move') {
                                        d.el.style.left = (d.left + dx) + 'px';
                                        d.el.style.top = (d.top + dy) + 'px';
                                    } else {
                                        d.el.style.width = Math.max(24, d.width + dx) + 'px';
                                        d.el.style.height = Math.max(20, d.height + dy) + 'px';
                                    }
                                },
                                soltar(e, wire) {
                                    if (!this.drag) return;
                                    const d = this.drag;
                                    this.drag = null;
                                    const r = d.rect;
                                    const x = +(d.el.offsetLeft / r.width * 100).toFixed(2);
                                    const y = +(d.el.offsetTop / r.height * 100).toFixed(2);
                                    const w = +(d.el.offsetWidth / r.width * 100).toFixed(2);
                                    const h = d.modo === 'resize' ? +(d.el.offsetHeight / r.height * 100).toFixed(2) : null;
                                    if (wire) {
                                        wire.actualizarPosicion(d.index, x, y, w, h);
                                    }
                                }
                            }"
                            x-on:pointermove.window="mover($event)"
                            x-on:pointerup.window="soltar($event, $wire)"
                            x-on:pointercancel.window="soltar($event, $wire)"
                        >
                            <div x-ref="canvas"
                                class="relative w-full aspect-[297/210] bg-white border-2 border-dashed border-gray-300 overflow-hidden select-none"
                                x-on:click.self="$wire.deseleccionar()">

                                @if ($imagen)
                                    <img src="{{ $imagen->temporaryUrl() }}" class="absolute inset-0 w-full h-full object-cover pointer-events-none" alt="base">
                                @elseif ($imagen_actual)
                                    <img src="{{ asset('storage/' . $imagen_actual) }}" class="absolute inset-0 w-full h-full object-cover pointer-events-none" alt="base">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center text-gray-300 text-sm pointer-events-none">
                                        Subí una imagen base para verla aquí
                                    </div>
                                @endif

                                @foreach ($bloques as $i => $bloque)
                                    @php
                                        $campo = $bloque['campo'] ?? 'literal';
                                        $esImagen = \App\Support\CertificadoLayout::esImagen($campo);
                                        $slot = \App\Support\CertificadoLayout::slotFirma($campo);
                                        $estilo = \App\Support\CertificadoLayout::estilo($bloque);
                                        $seleccionadoActual = $seleccionado === $i;

                                        if ($esImagen) {
                                            $src = $campo === 'qr'
                                                ? $qr_preview
                                                : ($mockFirmas[$slot - 1]['preview_url'] ?? null);
                                        } else {
                                            $contenido = \App\Support\CertificadoLayout::textoBloque($bloque, $texto, $mockVariables);
                                        }
                                    @endphp

                                    <div
                                        data-bloque
                                        data-imagen="{{ $esImagen ? '1' : '0' }}"
                                        wire:key="bloque-{{ $i }}"
                                        class="absolute cursor-move {{ $seleccionadoActual ? 'ring-2 ring-indigo-500 z-30' : 'ring-1 ring-transparent hover:ring-indigo-300 z-10' }}"
                                        style="{{ $estilo }}"
                                        x-on:pointerdown="iniciar($event, {{ $i }}, 'move')"
                                    >
                                        @if ($esImagen)
                                            @if ($src)
                                                <img src="{{ $src }}" draggable="false"
                                                    class="w-full h-full object-contain pointer-events-none select-none">
                                            @else
                                                <div class="w-full h-full border border-dashed border-indigo-400 bg-indigo-50/60 flex items-center justify-center text-[10px] text-indigo-600 pointer-events-none">
                                                    {{ $campos[$campo] ?? $campo }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="w-full h-full pointer-events-none">
                                                {!! $contenido !!}
                                            </div>
                                        @endif

                                        @if ($seleccionadoActual)
                                            <div class="absolute -bottom-1.5 -right-1.5 h-3.5 w-3.5 rounded-sm bg-indigo-500 border border-white cursor-nwse-resize"
                                                x-on:pointerdown.stop="iniciar($event, {{ $i }}, 'resize')"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <p class="text-xs text-gray-500">
                            <i class="fas fa-info-circle mr-1"></i>
                            Arrastrá para mover; usá el cuadradito de la esquina para redimensionar. Los valores X/Y/Ancho/Alto se guardan en porcentaje.
                        </p>
                    </div>

                    {{-- ─────────────── Panel lateral ─────────────── --}}
                    <div class="space-y-4 max-h-[70vh] overflow-y-auto pr-1">
                        {{-- Bloque seleccionado --}}
                        <div wire:key="bloque-panel-{{ $seleccionado ?? 'none' }}"
                            class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4 space-y-3">
                            <h4 class="text-sm font-semibold text-indigo-800">Bloque seleccionado</h4>

                            @if ($seleccionado !== null && isset($bloques[$seleccionado]))
                                @php $b = $bloques[$seleccionado]; $esImagen = \App\Support\CertificadoLayout::esImagen($b['campo'] ?? 'literal'); @endphp

                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Campo</label>
                                    <select wire:model.live="bloques.{{ $seleccionado }}.campo"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        @foreach ($campos as $valor => $etiqueta)
                                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if (($b['campo'] ?? '') === 'literal')
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Contenido (acepta tokens)</label>
                                        <input wire:model.live.debounce.400ms="bloques.{{ $seleccionado }}.contenido" type="text"
                                            class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">X %</label>
                                        <input wire:model.live="bloques.{{ $seleccionado }}.x" type="number" step="0.1" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Y %</label>
                                        <input wire:model.live="bloques.{{ $seleccionado }}.y" type="number" step="0.1" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Ancho %</label>
                                        <input wire:model.live="bloques.{{ $seleccionado }}.w" type="number" step="0.1" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Alto %</label>
                                        <input wire:model.live="bloques.{{ $seleccionado }}.h" type="number" step="0.1" class="w-full border-gray-300 rounded-md text-sm">
                                        <span class="block text-[10px] text-gray-400 mt-0.5">0 = alto automático</span>
                                    </div>
                                </div>

                                @unless ($esImagen)
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Tamaño (px)</label>
                                            <input wire:model.live="bloques.{{ $seleccionado }}.size" type="number" class="w-full border-gray-300 rounded-md text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Alinear</label>
                                            <select wire:model.live="bloques.{{ $seleccionado }}.align" class="w-full border-gray-300 rounded-md text-sm">
                                                <option value="left">Izquierda</option>
                                                <option value="center">Centro</option>
                                                <option value="right">Derecha</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-medium text-gray-600 mb-1">Color</label>
                                            <input wire:model.live="bloques.{{ $seleccionado }}.color" type="color" class="w-full h-9 border-gray-300 rounded-md">
                                        </div>
                                        <div class="flex items-end gap-3 pb-1">
                                            <label class="inline-flex items-center text-xs text-gray-600">
                                                <input type="checkbox" wire:model.live="bloques.{{ $seleccionado }}.bold" class="mr-1"> Negrita
                                            </label>
                                            <label class="inline-flex items-center text-xs text-gray-600">
                                                <input type="checkbox" wire:model.live="bloques.{{ $seleccionado }}.italic" class="mr-1"> Itálica
                                            </label>
                                        </div>
                                    </div>
                                @endunless

                                <button type="button" wire:click="quitarBloque({{ $seleccionado }})"
                                    class="text-xs text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash mr-1"></i> Quitar bloque
                                </button>
                            @else
                                <p class="text-xs text-gray-500">Hacé clic en un bloque de la vista previa para editar sus propiedades (tamaño, color, alineación, etc.).</p>
                            @endif
                        </div>

                        {{-- Datos de la plantilla --}}
                        <div class="rounded-xl border border-gray-200 p-4 space-y-3">
                            <h4 class="text-sm font-semibold text-gray-800">Datos de la plantilla</h4>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre</label>
                                <input wire:model.live="nombre" type="text"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ej: Asistente, Aprobado...">
                                @error('nombre') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
                                <select wire:model.live="tipo"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach ($tipos as $t)
                                        <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                    @endforeach
                                </select>
                                @error('tipo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <label class="inline-flex items-start gap-3 text-sm rounded-xl border border-gray-200 bg-white px-3 py-2 w-full">
                                <input type="checkbox" wire:model.live="por_defecto" class="mt-1">
                                <span>
                                    <span class="block font-medium text-gray-700 text-xs">Usar como predeterminada</span>
                                    <span class="block text-[11px] text-gray-500">Se elegirá automáticamente para este tipo.</span>
                                </span>
                            </label>

                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Imagen base (PNG/JPEG)</label>
                                <input wire:model="imagen" type="file" accept="image/png,image/jpeg"
                                    class="w-full text-xs border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                @if ($imagen)
                                    <p class="mt-1 text-[11px] font-medium text-blue-700">Archivo listo: {{ $imagen->getClientOriginalName() }}</p>
                                @elseif ($editando_id)
                                    <p class="mt-1 text-[11px] text-gray-500">Dejá vacío para conservar la actual.</p>
                                @endif
                                @error('imagen') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Texto del cuerpo --}}
                        <div class="rounded-xl border border-gray-200 p-4 space-y-2">
                            <h4 class="text-sm font-semibold text-gray-800">Texto del cuerpo</h4>
                            <textarea wire:model.live.debounce.400ms="texto" rows="4"
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ha asistido {formula} {nombre_evento}..."></textarea>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($tokens as $token)
                                    <button type="button" wire:click="insertarToken('{{ $token }}')"
                                        class="inline-flex rounded bg-gray-100 hover:bg-indigo-100 px-2 py-0.5 text-[11px] font-mono text-gray-600">
                                        {{ $token }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Datos de ejemplo --}}
                        <details class="rounded-xl border border-gray-200 p-4">
                            <summary class="text-sm font-semibold text-gray-800 cursor-pointer">Datos de ejemplo (vista previa)</summary>
                            <div class="mt-3 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Apellido</label>
                                        <input wire:model.live.debounce.400ms="mock.apellido" type="text" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Nombres</label>
                                        <input wire:model.live.debounce.400ms="mock.nombres" type="text" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">DNI</label>
                                        <input wire:model.live.debounce.400ms="mock.dni" type="text" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Tipo de evento</label>
                                        <input wire:model.live.debounce.400ms="mock.tipo_evento" type="text" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Fórmula</label>
                                        <input wire:model.live.debounce.400ms="mock.formula" type="text" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                    <div class="col-span-2">
                                        <label class="block text-[11px] font-medium text-gray-600 mb-1">Nombre del evento</label>
                                        <input wire:model.live.debounce.400ms="mock.nombre_evento" type="text" class="w-full border-gray-300 rounded-md text-sm">
                                    </div>
                                </div>
                                <p class="text-[11px] text-gray-500">
                                    Los datos del contexto ({{ $contexto->nombre }}, institución, resolución, fechas, firmas) se toman de la configuración real.
                                </p>
                            </div>
                        </details>
                    </div>
                </div>
            </x-slot>

            <x-slot name="footer">
                <div class="flex justify-between items-center w-full gap-3">
                    <button type="button" wire:click="descargarPdfPrueba" wire:loading.attr="disabled"
                        class="btn text-xs font-semibold uppercase py-2 px-4 rounded-md border border-indigo-300 text-indigo-700 hover:bg-indigo-50">
                        <span wire:loading.remove wire:target="descargarPdfPrueba"><i class="fas fa-file-pdf mr-1"></i> PDF de prueba</span>
                        <span wire:loading wire:target="descargarPdfPrueba"><i class="fas fa-spinner fa-spin mr-1"></i> Generando...</span>
                    </button>

                    <div class="flex justify-end gap-3">
                        <x-secondary-button wire:click="$set('open_modal', false)">Cancelar</x-secondary-button>
                        <x-button type="submit">Guardar plantilla</x-button>
                    </div>
                </div>
            </x-slot>
        </x-dialog-modal>
    </form>
</div>
