<?php

namespace App\Http\Livewire;
use App\Models\Company;
use Livewire\Component;

class Dashboard extends Component
{
    public string $companyName;


    public function boot(){

    $companyId = request()->session()->get('companyId');
    $company = Company::where('company_id', $companyId)->first();
    $this->companyName = $company->company_name;
    }
    // #[Url]
    // public string $companyId;

    // public function mount($company_id){
    //     $this->companyId = $company_id;
    // }
    public function render()
    {

        return view('livewire.dashboard');
    }
}
