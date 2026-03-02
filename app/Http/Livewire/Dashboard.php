<?php

namespace App\Http\Livewire;
use App\Models\Company;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
class Dashboard extends Component
{
    public string $companyName;


    public function boot(){

    $companyId = request()->session()->get('companyId');
    Log::info(message: "companyId: ".$companyId);
    $company = Company::where('id', operator: $companyId)->first();
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
