<?php

namespace App\Livewire\Admin;

use App\Models\ApiCliente;
use Livewire\Component;
use Livewire\WithPagination;

class ApiClientes extends Component
{
    use WithPagination;

    public const ABILITIES = ['certificados:emitir', 'certificados:leer'];

    public $open_modal = false;

    public $editando_id = null;

    public $nombre = '';

    public $descripcion = '';

    public $activo = true;

    public $search = '';

    public $token_modal = false;

    public $token_cliente_id = null;

    public $token_name = '';

    public array $abilities = ['certificados:emitir'];

    public $nuevo_token = null;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function abrirCrear(): void
    {
        $this->reset(['editando_id', 'nombre', 'descripcion', 'activo']);
        $this->activo = true;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function editar(int $id): void
    {
        $cliente = ApiCliente::findOrFail($id);
        $this->editando_id = $cliente->api_cliente_id;
        $this->nombre = $cliente->nombre;
        $this->descripcion = $cliente->descripcion ?? '';
        $this->activo = (bool) $cliente->activo;
        $this->resetValidation();
        $this->open_modal = true;
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
        ]);

        $datos = [
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion ?: null,
            'activo' => (bool) $this->activo,
        ];

        if ($this->editando_id) {
            ApiCliente::findOrFail($this->editando_id)->update($datos);
        } else {
            ApiCliente::create($datos);
        }

        $this->dispatch('alert', message: $this->editando_id
            ? 'Cliente de API actualizado.'
            : 'Cliente de API creado.');

        $this->open_modal = false;
        $this->reset(['editando_id', 'nombre', 'descripcion', 'activo']);
    }

    public function alternarActivo(int $id): void
    {
        $cliente = ApiCliente::findOrFail($id);
        $cliente->update(['activo' => ! $cliente->activo]);

        $this->dispatch('alert', message: $cliente->activo
            ? 'Cliente habilitado.'
            : 'Cliente deshabilitado.');
    }

    public function eliminar(int $id): void
    {
        ApiCliente::findOrFail($id)->delete();

        $this->dispatch('alert', message: 'Cliente de API eliminado.');
    }

    public function abrirTokens(int $id): void
    {
        $this->token_cliente_id = $id;
        $this->reset(['token_name', 'nuevo_token']);
        $this->abilities = ['certificados:emitir'];
        $this->token_modal = true;
    }

    public function generarToken(): void
    {
        $this->validate([
            'token_name' => 'nullable|string|max:100',
            'abilities' => 'required|array|min:1',
            'abilities.*' => 'in:'.implode(',', self::ABILITIES),
        ]);

        $cliente = ApiCliente::findOrFail($this->token_cliente_id);

        $token = $cliente->createToken(
            $this->token_name ?: 'token-'.now()->format('YmdHis'),
            $this->abilities
        );

        $this->nuevo_token = $token->plainTextToken;
        $this->token_name = '';

        $this->dispatch('alert', message: 'Token generado. Copialo: no se volverá a mostrar.');
    }

    public function revocarToken(int $tokenId): void
    {
        $cliente = ApiCliente::findOrFail($this->token_cliente_id);
        $cliente->tokens()->where('id', $tokenId)->delete();

        $this->dispatch('alert', message: 'Token revocado.');
    }

    public function render()
    {
        $clientes = ApiCliente::query()
            ->withCount('certificadosExternos')
            ->when($this->search, fn ($query) => $query->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->paginate(10);

        $tokens = collect();

        if ($this->token_cliente_id) {
            $tokens = ApiCliente::find($this->token_cliente_id)?->tokens()->latest()->get() ?? collect();
        }

        return view('livewire.admin.api-clientes', compact('clientes', 'tokens'));
    }
}
