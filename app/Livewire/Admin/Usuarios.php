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
    public $open_edit = false;
    public $search = '';

    public $usuarioEdit_id;
    public $usuario_edit;
    public $rol_id_edit = null;

    public $name, $email, $password, $role;
    public $roles_selected = [];
    public $roles;
    public $confirmingUserEdit = false;

    use WithPagination;

    public function mount($usuarioEdit_id = null)
    {
        $this->roles = Role::all();
        //$this->roles_selected = $this->usuario_edit?->roles->pluck('id')->toArray() ?? [];


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
        $this->roles_selected = $usuario->roles->pluck('id')->toArray();
    }


    public function actualizar()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->usuarioEdit_id)],
            'roles_selected' => 'required|array|min:1',
            'roles_selected.*' => 'exists:roles,id',
            'password' => 'nullable|min:6'
        ]);

        // Validación: si seleccionaron más de un rol y uno es 'Invitado', lanzar error
        $invitadoId = Role::where('name', 'Invitado')->value('id');

        if (in_array($invitadoId, $this->roles_selected) && count($this->roles_selected) > 1) {
            $this->addError('roles_selected', 'El rol "Invitado" no puede combinarse con otros roles.');
            return;
        }

        $usuario = User::findOrFail($this->usuarioEdit_id);
        $usuario->name = $this->name;
        $usuario->email = $this->email;
        if ($this->password) {
            $usuario->password = Hash::make($this->password);
        }
        $usuario->save();

        $roles = Role::whereIn('id', $this->roles_selected)->pluck('name')->toArray();
        $usuario->syncRoles($roles);
        $this->open_edit = false;

        $this->dispatch('alert', message: 'Usuario actualizado');
        $this->reset(['usuarioEdit_id', 'name', 'email', 'password', 'roles_selected', 'open_edit']);
    }

    public function updatingSearch()
    {
        $this->resetPage();
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
