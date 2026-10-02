<div class="px-4 sm:px-6 lg:px-8 py-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Mis Certificados</h2>
        <p class="text-sm text-gray-500">Descargá los certificados que la institución te otorgó.</p>
    </div>

    @unless ($participante)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-5 text-amber-800">
            <p class="font-semibold">Tu cuenta todavía no está vinculada a un participante.</p>
            <p class="mt-1 text-sm">
                Para poder ver tus certificados, el DNI y el correo de tu cuenta deben coincidir con los datos con los
                que te registraste. Si el problema persiste, comunicate con la organización.
            </p>
        </div>
    @else
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-1 sm:grid-cols-3">
                <div><span class="text-xs uppercase text-gray-400">Participante</span>
                    <p class="font-medium text-gray-800">{{ $participante->apellido }}, {{ $participante->nombre }}</p>
                </div>
                <div><span class="text-xs uppercase text-gray-400">DNI</span>
                    <p class="font-medium text-gray-800">{{ $participante->dni }}</p>
                </div>
                <div><span class="text-xs uppercase text-gray-400">Correo</span>
                    <p class="font-medium text-gray-800">{{ $participante->mail }}</p>
                </div>
            </div>
        </div>

        @if ($emisiones->isEmpty() && $legacyEventos->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center text-gray-500">
                <i class="fa-solid fa-certificate mb-2 text-3xl text-gray-300"></i>
                <p>Todavía no tenés certificados emitidos.</p>
            </div>
        @endif

        @if ($emisiones->isNotEmpty())
            <h3 class="mb-3 mt-6 text-lg font-semibold text-gray-700">Certificados</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($emisiones as $emision)
                    @php
                        $snapshot = $emision->origen_snapshot ?? [];
                    @endphp
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-800">
                                {{ $snapshot['nombre'] ?? ucfirst($emision->tipoReconocimiento->nombre ?? 'Certificado') }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $emision->tipoReconocimiento->nombre ?? '' }}
                                @if (! empty($snapshot['contexto_nombre']))
                                    &middot; {{ $snapshot['contexto_nombre'] }}
                                @endif
                                @if ($emision->emitida_en)
                                    &middot; {{ $emision->emitida_en->format('d/m/Y') }}
                                @endif
                                @if ($emision->estaAnulada())
                                    &middot; <span class="font-semibold text-red-600">ANULADO</span>
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('mis_certificados.emision', $emision) }}" target="_blank"
                            class="ms-3 inline-flex shrink-0 items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            <i class="fa-solid fa-file-pdf"></i> Descargar
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        @if ($legacyEventos->isNotEmpty())
            <h3 class="mb-3 mt-8 text-lg font-semibold text-gray-700">Certificados de eventos (histórico)</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($legacyEventos as $certificado)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-800">{{ $certificado->evento->nombre ?? 'Evento' }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $certificado->evento->tipoEvento->nombre ?? '' }}
                                @if ($certificado->evento->fecha_inicio)
                                    &middot; {{ \Carbon\Carbon::parse($certificado->evento->fecha_inicio)->format('d/m/Y') }}
                                @endif
                                @if ($certificado->rol)
                                    &middot; {{ $certificado->rol->nombre }}
                                @endif
                            </p>
                        </div>
                        <a href="{{ route('mis_certificados.evento', $certificado) }}" target="_blank"
                            class="ms-3 inline-flex shrink-0 items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">
                            <i class="fa-solid fa-file-pdf"></i> Descargar
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    @endunless
</div>
