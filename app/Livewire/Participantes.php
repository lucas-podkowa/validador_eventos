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
    public $sort = 'nombre';
    public $direction = 'asc';

    protected $rules = [
        'nombre' => 'required|string|max:255',
        'apellido' => 'required|string|max:255',
        'dni' => 'required|integer|unique:participante,dni',
        'mail' => 'required|email|unique:participante,mail',
        'telefono' => 'required|string|max:20',
    ];


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
            'apellido' => ['required', 'regex:/^[\pL\s\-]+$/u', 'min:2', 'max:50'], // letras, espacios, guiones
            'nombre'   => ['required', 'regex:/^[\pL\s\-]+$/u', 'min:2', 'max:50'],
            'dni'      => ['required', 'digits_between:6,10', 'numeric'],
            'mail'     => ['required', 'email'],
            'telefono' => ['required', 'regex:/^\d+$/', 'min:6', 'max:20'],
        ]);


        // Normalizar nombre y apellido antes de guardar o actualizar
        $this->nombre = ucfirst(mb_strtolower(trim($this->nombre)));
        $this->apellido = ucfirst(mb_strtolower(trim($this->apellido)));

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


    public function order($field)
    {
        if ($this->sort == $field) { //si estoy en la misma columna me pregunto por la direccion de ordenamiento
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else { //si es una columna nueva, ordeno de forma ascendente
            $this->sort = $field;
            $this->direction = 'asc';
        }
    }
}
