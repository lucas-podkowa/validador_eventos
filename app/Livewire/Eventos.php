<?php

namespace App\Livewire;

use Livewire\Component;

class Eventos extends Component
{
    public $activeTab = 'pendientes'; // Define la primera pestaña como activa por defecto

    public function mount($tab = null)
    {
        if (in_array($tab, ['pendientes', 'en_curso', 'finalizados'])) {
            $this->activeTab = $tab;
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.eventos');
    }
}
