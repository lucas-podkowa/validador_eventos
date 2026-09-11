<?php

namespace App\Livewire\Admin;

use App\Models\Participante;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Usuarios extends Component
{
    public $open_edit = false;

    public $search = '';

    public $selectedRole = 'Todos';

    public $usuarioEdit_id;

    public $usuario_edit;

    public $rol_id_edit = null;

    public $name;

    public $email;

    public $dni;

    public $password;

    public $role;

    public $roles_selected = [];

    public $previous_roles_selected = [];

    public $roles;

    public $confirmingUserEdit = false;

    public $invitadoRoleId = null;

    public $participante_vinculado = null;

    public $busqueda_participante = '';

    public $participante_candidato = null;

    use WithPagination;

    public function mount($usuarioEdit_id = null)
    {
        $this->roles = Role::all();
        $this->invitadoRoleId = Role::where('name', 'Invitado')->value('id');
        // $this->roles_selected = $this->usuario_edit?->roles->pluck('id')->toArray() ?? [];

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
        $this->dni = $usuario->dni;
        $this->rol_id_edit = $usuario->roles->first()?->id;
        $this->roles_selected = $usuario->roles->pluck('id')->toArray();
        $this->previous_roles_selected = $this->roles_selected;

        $this->busqueda_participante = '';
        $this->participante_candidato = null;
        $this->cargarParticipanteVinculado($usuario);
    }

    protected function cargarParticipanteVinculado(?User $usuario): void
    {
        $participante = $usuario?->participante;

        $this->participante_vinculado = $participante ? [
            'participante_id' => $participante->participante_id,
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'mail' => $participante->mail,
        ] : null;
    }

    public function buscarParticipante()
    {
        $this->resetErrorBag('busqueda_participante');
        $this->participante_candidato = null;

        $this->validate(['busqueda_participante' => 'required|string|min:3']);
        $termino = trim($this->busqueda_participante);

        $participante = Participante::whereNull('user_id')
            ->where(function ($query) use ($termino) {
                $query->where('dni', $termino)
                    ->orWhere('mail', 'like', "%{$termino}%")
                    ->orWhere('apellido', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%");
            })
            ->orderBy('apellido')
            ->first();

        if (! $participante) {
            $this->addError('busqueda_participante', 'No se encontró un participante pendiente de vinculación.');

            return;
        }

        $this->participante_candidato = [
            'participante_id' => $participante->participante_id,
            'nombre' => $participante->nombre,
            'apellido' => $participante->apellido,
            'dni' => $participante->dni,
            'mail' => $participante->mail,
        ];
    }

    public function vincularParticipante()
    {
        if (! $this->participante_candidato || ! $this->usuario_edit) {
            return;
        }

        $participante = Participante::find($this->participante_candidato['participante_id']);

        if (! $participante || $participante->user_id) {
            $this->addError('busqueda_participante', 'El participante ya no está disponible para vincular.');

            return;
        }

        $participante->user_id = $this->usuario_edit->id;
        $participante->save();

        $this->cargarParticipanteVinculado($this->usuario_edit->fresh());
        $this->participante_candidato = null;
        $this->busqueda_participante = '';

        $this->dispatch('alert', message: 'Participante vinculado');
    }

    public function desvincularParticipante()
    {
        $participante = $this->usuario_edit?->participante;

        if (! $participante) {
            return;
        }

        $participante->user_id = null;
        $participante->save();

        $this->cargarParticipanteVinculado($this->usuario_edit->fresh());

        $this->dispatch('alert', message: 'Participante desvinculado');
    }

    public function updatingRolesSelected()
    {
        $this->previous_roles_selected = $this->roles_selected;
    }

    public function updatedRolesSelected()
    {
        if (! $this->invitadoRoleId) {
            return;
        }

        $tieneInvitado = in_array($this->invitadoRoleId, $this->roles_selected);

        if (! $tieneInvitado || count($this->roles_selected) <= 1) {
            return;
        }

        if (in_array($this->invitadoRoleId, $this->previous_roles_selected)) {
            $this->roles_selected = array_values(array_diff($this->roles_selected, [$this->invitadoRoleId]));

            return;
        }

        $this->roles_selected = [$this->invitadoRoleId];
    }

    public function actualizar()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->usuarioEdit_id)],
            'dni' => ['nullable', 'digits_between:6,12', Rule::unique('users', 'dni')->ignore($this->usuarioEdit_id)],
            'roles_selected' => 'required|array|min:1',
            'roles_selected.*' => 'exists:roles,id',
            'password' => 'nullable|min:6',
        ]);

        // Validación: si seleccionaron más de un rol y uno es 'Invitado', lanzar error
        $invitadoId = $this->invitadoRoleId;

        if (in_array($invitadoId, $this->roles_selected) && count($this->roles_selected) > 1) {
            $this->addError('roles_selected', 'El rol "Invitado" no puede combinarse con otros roles.');

            return;
        }

        $usuario = User::findOrFail($this->usuarioEdit_id);
        $usuario->name = $this->name;
        $usuario->email = $this->email;
        $usuario->dni = $this->dni !== '' ? $this->dni : null;
        if ($this->password) {
            $usuario->password = Hash::make($this->password);
        }
        $usuario->save();

        $roles = Role::whereIn('id', $this->roles_selected)->pluck('name')->toArray();
        $usuario->syncRoles($roles);
        $this->open_edit = false;

        $this->dispatch('alert', message: 'Usuario actualizado');
        $this->reset(['usuarioEdit_id', 'name', 'email', 'dni', 'password', 'roles_selected', 'open_edit', 'participante_vinculado', 'busqueda_participante', 'participante_candidato']);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedRole()
    {
        $this->resetPage();
    }

    public function render()
    {
        $usuarios = User::with('roles')
            ->when($this->search !== '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('name', 'LIKE', '%'.$this->search.'%')
                        ->orWhere('email', 'LIKE', '%'.$this->search.'%');
                });
            })
            ->when($this->selectedRole !== 'Todos', function ($query) {
                $query->whereHas('roles', function ($roleQuery) {
                    $roleQuery->where('name', $this->selectedRole);
                });
            })
            ->paginate(10);

        return view('livewire.admin.usuarios', compact('usuarios'));
    }
}
