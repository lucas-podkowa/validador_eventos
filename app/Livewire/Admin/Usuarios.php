<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class Usuarios extends Component
{
    //public $usuarios;
    public $open_edit = false;
    public $search;

    public $usuarioEdit_id;
    public $usuario_edit;
    public $rol_id_edit = null;

    public $name, $email, $password, $role;
    public $roles;
    public $confirmingUserEdit = false;

    use WithPagination;

    public function mount($usuarioEdit_id = null)
    {
        $this->roles = Role::all();

        if ($usuarioEdit_id) {
            $this->usuario_edit = User::find($usuarioEdit_id);

            if ($this->usuario_edit) {
                $this->usuarioEdit_id = $usuarioEdit_id;
                // Verifica si el usuario tiene roles asignados
                $this->rol_id_edit = $this->usuario_edit->roles->isNotEmpty() ? $this->usuario_edit->roles->first()->id : null;
            }
        }
    }

    public function editar($id)
    {
        $this->resetValidation();
        $this->open_edit = true;
        $usuario = User::findOrFail($id);
        $this->usuarioEdit_id = $usuario->id;
        $this->usuario_edit = $usuario;
        $this->name = $usuario->name;
        $this->email = $usuario->email;
        $this->rol_id_edit = $usuario->roles->first()?->id;
    }


    public function actualizar()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->usuarioEdit_id)],
            'rol_id_edit' => 'required|exists:roles,id',
            'password' => 'nullable|min:6'
        ]);

        $usuario = User::findOrFail($this->usuarioEdit_id);
        $usuario->name = $this->name;
        $usuario->email = $this->email;
        if ($this->password) {
            $usuario->password = Hash::make($this->password);
        }
        $usuario->save();

        $this->open_edit = false;

        $rol = Role::findOrFail($this->rol_id_edit);
        $usuario->syncRoles([$rol->name]);

        //$usuario->syncRoles([$this->role]);
        $this->dispatch('alert', message: 'Usuario actualizado');
        $this->reset(['usuarioEdit_id', 'name', 'email', 'password', 'rol_id_edit']);
    }


    public function render()
    {
        $usuarios = User::with('roles')
            ->where(function ($query) {
                $query->where('name', 'LIKE', '%' . $this->search . '%')
                    ->orWhere('email', 'LIKE', '%' . $this->search . '%');
            })
            ->paginate(10);

        return view('livewire.admin.usuarios', compact('usuarios'));
    }
}
