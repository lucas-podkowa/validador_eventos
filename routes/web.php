<?php

use App\Http\Controllers\QRController;
use App\Http\Controllers\WelcomeController;
use App\Livewire\Admin\Usuarios;
use App\Livewire\AsignarGestores;
use App\Livewire\Asistencias;
use App\Livewire\CrearEvento;
use App\Livewire\EmisorCertificados;
use App\Livewire\Eventos;
use App\Livewire\HabilitarPlanilla;
use App\Livewire\Indicadores;
use App\Livewire\Participantes;
use App\Livewire\ProcesarAprobaciones;
use App\Livewire\RegistroEventoPublico;
use App\Models\EventoParticipante;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Públicas
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/inscripcion/{tipoEvento}/{eventoId}', RegistroEventoPublico::class)->name('inscripcion.evento');
Route::get('/validar-participante/{evento_id}/{participante_id}', [QRController::class, 'show'])->name('validar.participante');



// Protegidas
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {

    // Rutas exclusivas del administrador
    Route::middleware('can:crear_eventos')->group(function () {
        Route::get('/registrar_evento/{evento_id?}', CrearEvento::class)->name('registrar_evento');
        Route::get('/eventos/{evento_id}/gestores', AsignarGestores::class)->name('asignar_gestores');
        Route::get('/indicadores', Indicadores::class)->name('indicadores');
        Route::get('/admin/usuarios', Usuarios::class)->name('usuarios');
        Route::get('/emision', EmisorCertificados::class)->name('emisor_certificados');
    });

    // Rutas compartidas entre administrador y gestor
    Route::middleware('can:eventos')->group(function () {
        Route::get('/eventos/{tab?}', Eventos::class)->name('eventos');
        Route::get('/eventos/{evento_id}/habilitar', HabilitarPlanilla::class)->name('habilitar_planilla');
        Route::get('/planilla/{evento_id}/editar', HabilitarPlanilla::class)->name('editar_planilla');
    });

    // Participantes visibles para administrador y gestor
    Route::middleware('can:ver_participantes')->group(function () {
        Route::get('/participantes', Participantes::class)->name('participantes');
    });

    // Aprobaciones (admin, gestor y revisor)
    Route::middleware('can:procesar_aprobaciones')->group(function () {
        Route::get('/procesar-aprobaciones', ProcesarAprobaciones::class)->name('procesar_aprobaciones');
    });

    // Asistencias (admin, gestor y asistente)
    Route::middleware('can:asistencias')->group(function () {
        Route::get('/asistencias', Asistencias::class)->name('asistencias');
    });
});


Route::get('/ver-certificado/{eventoParticipante}', function (EventoParticipante $eventoParticipante) {
    $path = $eventoParticipante->certificado_path;

    if (!$path || !Storage::disk('private')->exists($path)) {
        abort(404, 'Certificado no encontrado.');
    }

    return response()->file(storage_path("app/private/{$path}"));
})->name('ver.certificado');
