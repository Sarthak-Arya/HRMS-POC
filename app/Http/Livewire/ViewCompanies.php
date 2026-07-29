<?php

namespace App\Http\Livewire;

use App\Services\Auth\AuthLandingService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ViewCompanies extends Component
{
    public $rows;
    public $companies;
    public $searchedCompanies;
    public $searchString;

    public function mount(AuthLandingService $landing)
    {
        $user = Auth::user();
        abort_unless($landing->canManageMultipleCompanies($user), 403);

        $this->companies = $landing->accessibleCompanies($user);
        $this->searchedCompanies = $this->companies;
        $this->rows = $this->companies->count() / 3;
    }

    public function searchCompanies()
    {
        if (strcmp($this->searchString, '') == 0) {
            $this->searchedCompanies = $this->companies;
        } else {
            $this->searchedCompanies = $this->companies->filter(function ($company) {
                if (str_contains(strtolower($company->company_name), strtolower($this->searchString))) {
                    return $company;
                }
            });
        }
    }

    public function render()
    {
        return view('livewire.view-companies', ['searchedCompanies' => $this->searchedCompanies]);
    }
}
