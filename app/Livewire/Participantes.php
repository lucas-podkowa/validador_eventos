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
    public $searchParticipante = '';

    protected $rules = [
        'nombre' => 'required|string|max:255',
        'apellido' => 'required|string|max:255',
        'dni' => 'required|integer|unique:participante,dni',
        'mail' => 'required|email|unique:participante,mail',
        'telefono' => 'required|string|max:20',
    ];

    public function render()
    {
        $query = Participante::orderBy('apellido');

        if (!empty($this->searchParticipante)) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->searchParticipante}%")
                    ->orWhere('apellido', 'like', "%{$this->searchParticipante}%")
                    ->orWhere('dni', 'like', "%{$this->searchParticipante}%");
            });
        }

        $participantes = $query->paginate(20);

        return view('livewire.participantes', compact('participantes'));
    }

    // Resetea la paginación cuando cambia el filtro
    public function updatedSearchParticipante()
    {
        $this->resetPage();
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
