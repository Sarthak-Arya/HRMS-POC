<?php

namespace App\Http\Livewire;

use Livewire\Component;

class ViewEmployeeDetails extends Component
{
    #[Url]
    public string $companyId;
    public function render()
    {
        return view('livewire.view-employee-details');
    }
}
