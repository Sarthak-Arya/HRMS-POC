<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Services\Setup\CompanySetupProgressService;
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

    public function setCompanyId(CompanySetupProgressService $setupProgress)
    {
        request()->session()->put('companyId', $this->company_id_num);

        $company = Company::query()->find($this->company_id_num);
        $user = auth()->user();

        if (
            $company
            && $user
            && $user->hasPermission('settings.view')
            && ! $setupProgress->isSetupComplete($company)
        ) {
            return redirect()->route('getting-started', ['company_id' => $this->company_id_num]);
        }

        return redirect()->route('dashboard', ['company_id' => $this->company_id_num]);
    }

    public function render()
    {
        return view(view: 'livewire.company-list');
    }
}
