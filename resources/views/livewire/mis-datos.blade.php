<div class="px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Mis Datos</h2>
        <p class="text-sm text-gray-500">Revisá y corregí la información con la que aparecés en tus certificados.</p>
    </div>

    @unless ($participante)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-800">
            <p class="font-semibold">Tu cuenta todavía no está vinculada a un participante.</p>
            <p class="mt-1 text-sm">
                Para ver tus certificados y editar tus datos, el DNI y el correo de tu cuenta deben coincidir con los
                de tu inscripción. Si usaste el mismo correo, podés vincularte ahora ingresando tu DNI.
            </p>

            <form wire:submit.prevent="vincularCuenta" class="mt-4 max-w-md space-y-3">
                <div>
                    <x-label for="vincular_dni" value="DNI con el que te inscribiste" />
                    <x-input id="vincular_dni" class="mt-1 block w-full" type="text" inputmode="numeric"
                        wire:model="vincular_dni" placeholder="Sin puntos" />
                    @error('vincular_dni')
                        <span class="text-sm text-red-600">{{ $message }}</span>
                    @enderror
                </div>

                <x-button type="submit">Vincular mi cuenta</x-button>
            </form>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-4 text-lg font-semibold text-gray-700">Datos personales</h3>

                <form wire:submit.prevent="guardarDatos" class="space-y-4">
                    <div>
                        <x-label for="apellido" value="Apellido" />
                        <x-input id="apellido" class="mt-1 block w-full" type="text" wire:model="apellido" />
                        @error('apellido')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <x-label for="nombre" value="Nombre" />
                        <x-input id="nombre" class="mt-1 block w-full" type="text" wire:model="nombre" />
                        @error('nombre')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <x-label for="telefono" value="Teléfono" />
                        <x-input id="telefono" class="mt-1 block w-full" type="text" wire:model="telefono" />
                        @error('telefono')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <x-label for="mail" value="Correo electrónico" />
                        <x-input id="mail" class="mt-1 block w-full" type="email" wire:model="mail" />
                        @error('mail')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="rounded-md bg-gray-50 p-3 text-sm text-gray-600">
                        DNI registrado: <strong>{{ $participante->dni }}</strong>
                        <span class="block text-xs text-gray-400">Para corregir el DNI usá el formulario de la derecha.</span>
                    </div>

                    <x-button type="submit">Guardar cambios</x-button>
                </form>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="mb-1 text-lg font-semibold text-gray-700">Corregir DNI</h3>
                <p class="mb-4 text-sm text-gray-500">
                    Adjuntá una foto del documento para que un administrador valide el cambio.
                    Formatos: JPG, PNG o WEBP (máx. 10 MB).
                </p>

                <form wire:submit.prevent="solicitarCorreccion" class="space-y-4">
                    <div>
                        <x-label for="nuevo_dni" value="DNI correcto" />
                        <x-input id="nuevo_dni" class="mt-1 block w-full" type="text" inputmode="numeric" wire:model="nuevo_dni" placeholder="Sin puntos" />
                        @error('nuevo_dni')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <x-label for="motivo" value="Motivo (opcional)" />
                        <textarea id="motivo" wire:model="motivo" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-primary focus:ring-brand-accent"></textarea>
                        @error('motivo')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <x-label for="imagen" value="Imagen del DNI" />
                        <input id="imagen" type="file" wire:model="imagen" accept="image/jpeg,image/png,image/webp"
                            class="mt-1 block w-full text-sm text-gray-600">
                        @error('imagen')
                            <span class="text-sm text-red-600">{{ $message }}</span>
                        @enderror
                        <div wire:loading wire:target="imagen" class="text-xs text-gray-500">Subiendo imagen...</div>
                    </div>

                    <x-button type="submit">Enviar solicitud</x-button>
                </form>

                @if ($solicitudes->isNotEmpty())
                    <div class="mt-6 border-t pt-4">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700">Solicitudes enviadas</h4>
                        <ul class="space-y-2 text-sm">
                            @foreach ($solicitudes as $solicitud)
                                <li class="flex items-center justify-between rounded border border-gray-100 bg-gray-50 px-3 py-2">
                                    <span>DNI {{ $solicitud->dni_solicitado }} — {{ $solicitud->created_at->format('d/m/Y') }}</span>
                                    @php
                                        $colores = [
                                            'pendiente' => 'text-amber-600',
                                            'aprobada' => 'text-green-600',
                                            'rechazada' => 'text-red-600',
                                        ];
                                    @endphp
                                    <span class="font-medium {{ $colores[$solicitud->estado] ?? 'text-gray-600' }}">
                                        {{ ucfirst($solicitud->estado) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endunless
</div>
