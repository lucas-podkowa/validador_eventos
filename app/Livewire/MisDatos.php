<?php

namespace App\Livewire;

use App\Models\Participante;
use App\Models\SolicitudCorreccionDni;
use App\Rules\LargoNombreCertificado;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class MisDatos extends Component
{
    use WithFileUploads;

    public $nombre;

    public $apellido;

    public $telefono;

    public $mail;

    public $nuevo_dni;

    public $motivo;

    public $imagen;

    public function mount(): void
    {
        $participante = $this->participante();

        if ($participante) {
            $this->nombre = $participante->nombre;
            $this->apellido = $participante->apellido;
            $this->telefono = $participante->telefono;
            $this->mail = $participante->mail;
        }
    }

    protected function participante(): ?Participante
    {
        return auth()->user()?->participante;
    }

    public function guardarDatos(): void
    {
        $participante = $this->participante();

        if (! $participante) {
            $this->dispatch('oops', message: 'Tu cuenta no está vinculada a un participante.');

            return;
        }

        $this->validate([
            'apellido' => ['required', 'regex:/^[\pL\s\-\.]+$/u', 'min:2', 'max:50', new LargoNombreCertificado($this->nombre)],
            'nombre' => ['required', 'regex:/^[\pL\s\-\.]+$/u', 'min:2', 'max:50'],
            'telefono' => ['required', 'regex:/^\d+$/', 'min:6', 'max:20'],
            'mail' => ['required', 'email', Rule::unique('participante', 'mail')->ignore($participante->participante_id, 'participante_id')],
        ]);

        $participante->update([
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'telefono' => $this->telefono,
            'mail' => $this->mail,
        ]);

        $this->dispatch('alert', message: 'Tus datos se actualizaron correctamente.');
    }

    public function solicitarCorreccion(): void
    {
        $participante = $this->participante();

        if (! $participante) {
            $this->dispatch('oops', message: 'Tu cuenta no está vinculada a un participante.');

            return;
        }

        $this->validate([
            'nuevo_dni' => ['required', 'digits_between:6,10', Rule::unique('participante', 'dni')->ignore($participante->participante_id, 'participante_id')],
            'motivo' => ['nullable', 'string', 'max:500'],
            'imagen' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ], [], [
            'imagen' => 'imagen del DNI',
        ]);

        $path = $this->imagen->store("solicitudes_dni/{$participante->participante_id}", 'private');

        SolicitudCorreccionDni::create([
            'participante_id' => $participante->participante_id,
            'user_id' => auth()->id(),
            'dni_actual' => $participante->dni,
            'dni_solicitado' => $this->nuevo_dni,
            'motivo' => $this->motivo,
            'imagen_path' => $path,
            'imagen_mime' => $this->imagen->getMimeType(),
            'imagen_original' => $this->imagen->getClientOriginalName(),
            'estado' => SolicitudCorreccionDni::ESTADO_PENDIENTE,
        ]);

        $this->reset(['nuevo_dni', 'motivo', 'imagen']);
        $this->dispatch('alert', message: 'Solicitud enviada. Un administrador la revisará.');
    }

    public function render()
    {
        $participante = $this->participante();

        return view('livewire.mis-datos', [
            'participante' => $participante,
            'solicitudes' => $participante
                ? $participante->solicitudesCorreccionDni()->latest()->get()
                : collect(),
        ]);
    }
}
