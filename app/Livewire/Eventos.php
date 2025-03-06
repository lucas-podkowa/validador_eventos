<?php

namespace App\Livewire;

use App\Models\Evento;
use App\Models\PlanillaInscripcion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Eventos extends Component
{
    public $activeTab = 'pendientes'; // Define la primera pestaña como activa por defecto

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.eventos');
    }
}
