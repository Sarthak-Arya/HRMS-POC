<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ShowEmployees extends Component
{
    #[Url]
    public string $companyId;
    public function render()
    {
        return view('livewire.show-employees');
    }
}
