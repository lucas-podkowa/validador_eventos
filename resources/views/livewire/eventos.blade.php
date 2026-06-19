<div class="px-4 sm:px-6 lg:px-8 py-8">
    <div>
        <!-- Navegación de Pestañas -->
        <ul class="nav nav-tabs" id="eventosTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'pendientes' ? 'active' : '' }}"
                    wire:click="setActiveTab('pendientes')" type="button" role="tab">
                    Eventos Pendientes
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'en_curso' ? 'active' : '' }}"
                    wire:click="setActiveTab('en_curso')" type="button" role="tab">
                    Eventos en Curso
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeTab === 'finalizados' ? 'active' : '' }}"
                    wire:click="setActiveTab('finalizados')" type="button" role="tab">
                    Eventos Finalizados
                </button>
            </li>
        </ul>

        <div class="mt-3">
            @if ($activeTab === 'pendientes')
                @livewire('eventos-pendientes')
            @elseif ($activeTab === 'en_curso')
                @livewire('eventos-activos')
            @elseif ($activeTab === 'finalizados')
                @livewire('eventos-finalizados')
            @endif
        </div>
    </div>

    <script>
        //script para el boton finalizarEvento en la pestaña de eventos en curso
        function confirmFinish(evento_id) {
            Swal.fire({
                title: '¿Estás seguro de finalizar el Evento?',
                text: "Esto significa que se cerarrán las inscripciones y estará listo para emitir los certificados",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, Finalizar',
                cancelButtonText: 'Volver'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.Livewire.dispatch('finalizarEvento', {
                        evento_id
                    });
                    Swal.fire({
                        timer: 2000,
                        position: "bottom-end",
                        icon: "info",
                        title: "Finalizado",
                        text: "Evento finalizado con éxito",
                        showConfirmButton: false
                    });
                }
            })
        }

        //script para el boton cancelarEvento en la pestaña de eventos en curso
        function confirmCancel(evento_id) {
            Swal.fire({
                title: '¿Estás seguro de Cancelar el Evento?',
                html: `
                    <p class="text-sm text-gray-600 mb-3">El evento volverá al estado <strong>Pendiente</strong>.</p>
                    <div class="flex items-start gap-3 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-left">
                        <input type="checkbox" id="swal-mantener-inscripciones" class="mt-1 w-4 h-4 accent-blue-600 cursor-pointer flex-shrink-0">
                        <div>
                            <label for="swal-mantener-inscripciones" class="text-sm font-semibold text-gray-800 cursor-pointer">Mantener inscripciones realizadas</label>
                            <p class="text-xs text-gray-500 mt-1">Las inscripciones se conservarán y podrán retomarse al reactivar el evento.</p>
                        </div>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, Cancelar',
                cancelButtonText: 'Volver'
            }).then((result) => {
                if (result.isConfirmed) {
                    const mantenerInscripciones = document.getElementById('swal-mantener-inscripciones')?.checked ??
                        false;
                    window.Livewire.dispatch('cancelarEvento', {
                        evento_id,
                        mantener_inscripciones: mantenerInscripciones
                    });
                    Swal.fire({
                        timer: 2000,
                        position: "bottom-end",
                        icon: "info",
                        title: "Cancelado",
                        text: "Evento cancelado con éxito",
                        showConfirmButton: false
                    });
                }
            })
        }

        // Función para copiar enlace del formulario
        function fallbackCopyFormularioLink(url) {
            const textArea = document.createElement('textarea');
            textArea.value = url;
            textArea.setAttribute('readonly', 'readonly');
            textArea.style.position = 'fixed';
            textArea.style.top = '-9999px';
            textArea.style.left = '-9999px';

            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            textArea.setSelectionRange(0, textArea.value.length);

            let copied = false;

            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }

            document.body.removeChild(textArea);

            return copied;
        }

        function showCopySuccess() {
            Swal.fire({
                position: 'bottom-end',
                icon: 'success',
                title: 'Enlace copiado',
                text: 'El formulario ya está listo para compartir.',
                showConfirmButton: false,
                timer: 2000
            });
        }

        function showCopyError(message) {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo copiar el enlace',
                text: message
            });
        }

        async function copyFormularioLink(url) {
            if (!url) {
                showCopyError('No se encontró una URL válida para el formulario.');
                return;
            }

            if (window.isSecureContext && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                try {
                    await navigator.clipboard.writeText(url);
                    showCopySuccess();
                    return;
                } catch (error) {
                    if (fallbackCopyFormularioLink(url)) {
                        showCopySuccess();
                        return;
                    }

                    showCopyError('El navegador bloqueó el acceso al portapapeles. Intente nuevamente o copie el enlace manualmente.');
                    return;
                }
            }

            if (fallbackCopyFormularioLink(url)) {
                showCopySuccess();
                return;
            }

            showCopyError('Este navegador o contexto no permite copiar automáticamente. Abra el formulario y copie la URL manualmente.');
        }

        // Función para confirmar desmatriculación
        function confirmUnregister(inscripcionId) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir la desmatriculación de este participante!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, desmatricular',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('confirmDesmatricular', {
                        inscripcion_id: inscripcionId
                    });
                }
            });
        }
    </script>
</div>
