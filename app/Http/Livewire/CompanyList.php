<?php

namespace App\Http\Livewire;

use Livewire\Component;

class CompanyList extends Component
{
    #[Modelable]
    public $name;
    #[Modelable]
    public $address;
    #[Modelable]
    public $company_id;
    public $company_id_num;

    

    public function setCompanyId(){
        redirect()->route('dashboard', ['company_id' => $this->company_id]);
        #companyId is the key which is being written in company_id in SQL
        request()->session()->put('companyId', $this->company_id);
        #companyIdNum is the default primary key in the database which is made by SQL
        request()->session()->put('companyIdNum', $this->company_id_num);
    }

    public function render()
    {
        return view(view: 'livewire.company-list');
    }
}
