<?php

namespace App\Livewire\Admin;

use App\Models\CertificadoExterno;
use App\Models\Participante;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class CertificadosExternos extends Component
{
    use WithPagination;

    public $search = '';

    public $solo_revision = false;

    public $vincular_modal = false;

    public $certificado_id = null;

    public $vincular_email = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSoloRevision(): void
    {
        $this->resetPage();
    }

    public function abrirVincular(int $id): void
    {
        $this->certificado_id = $id;
        $this->vincular_email = '';
        $this->resetValidation();
        $this->vincular_modal = true;
    }

    public function vincular(): void
    {
        $this->validate([
            'vincular_email' => 'required|email',
        ]);

        $certificado = CertificadoExterno::findOrFail($this->certificado_id);
        $participante = $certificado->participante;

        if (! $participante) {
            $this->dispatch('oops', message: 'El certificado no tiene participante asociado.');

            return;
        }

        $user = User::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($this->vincular_email))])->first();

        if (! $user) {
            $this->addError('vincular_email', 'No existe una cuenta con ese correo.');

            return;
        }

        if (Participante::where('user_id', $user->id)
            ->where('participante_id', '!=', $participante->participante_id)
            ->exists()) {
            $this->addError('vincular_email', 'Esa cuenta ya está vinculada a otro participante.');

            return;
        }

        $participante->user_id = $user->id;
        $participante->save();

        $certificado->update(['match_estado' => CertificadoExterno::MATCH_AUTO]);

        $this->vincular_modal = false;
        $this->dispatch('alert', message: 'Certificado vinculado a la cuenta.');
    }

    public function render()
    {
        $certificados = CertificadoExterno::query()
            ->with('participante')
            ->when($this->solo_revision, fn ($query) => $query->where('match_estado', CertificadoExterno::MATCH_REVISAR))
            ->when($this->search, function ($query) {
                $busqueda = '%'.$this->search.'%';
                $query->where(function ($q) use ($busqueda) {
                    $q->where('receptor_nombre', 'like', $busqueda)
                        ->orWhere('receptor_dni', 'like', $busqueda)
                        ->orWhere('external_ref', 'like', $busqueda);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.admin.certificados-externos', compact('certificados'));
    }
}
