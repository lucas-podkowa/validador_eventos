<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Participante;

class Participantes extends Component
{
    use WithPagination;

    public $participante_id, $nombre, $apellido, $dni, $mail, $telefono;
    public $open_modal = false;

    protected $rules = [
        'nombre' => 'required|string|max:255',
        'apellido' => 'required|string|max:255',
        'dni' => 'required|integer|unique:participante,dni',
        'mail' => 'required|email|unique:participante,mail',
        'telefono' => 'required|string|max:20',
    ];

    public function render()
    {
        $participantes = Participante::orderBy('apellido')->paginate(20);
        return view('livewire.participantes', compact('participantes'));
    }

    public function edit($id)
    {
        $participante = Participante::findOrFail($id);
        $this->participante_id = $participante->participante_id;
        $this->nombre = $participante->nombre;
        $this->apellido = $participante->apellido;
        $this->dni = $participante->dni;
        $this->mail = $participante->mail;
        $this->telefono = $participante->telefono;

        $this->open_modal = true;
    }

    public function update()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'dni' => "required|integer|unique:participante,dni,{$this->participante_id},participante_id",
            'mail' => "required|email|unique:participante,mail,{$this->participante_id},participante_id",
            'telefono' => 'required|string|max:20',
        ]);

        $participante = Participante::findOrFail($this->participante_id);
        $participante->update([
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'dni' => $this->dni,
            'mail' => $this->mail,
            'telefono' => $this->telefono,
        ]);
        $this->reset(['participante_id', 'nombre', 'apellido', 'dni', 'mail', 'telefono', 'open_modal']);
    }
}
