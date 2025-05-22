<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;


class ViewCompanies extends Component
{
    use WithPagination;
    public $rows;
    private $authData;
    private $userId; 
    public $companies; 
    public $searchedCompanies;
    public $searchString;


    public function mount(){
        $this->authData = Auth::user();
        $this->userId = $this->authData['id'];
        $this->companies = Company::where('handled_by', $this->userId)->get();
        $this->searchedCompanies = $this->companies;
        $this->rows = $this->companies->count()/3;
    }

    public function searchCompanies(){
        
        if(strcmp($this->searchString, '') == 0){
            $this->searchedCompanies = $this->companies;
        }
        else{
            $this->searchedCompanies = $this->companies->filter(function($company){
                if(str_contains(strtolower($company->company_name), strtolower($this->searchString))){
                    return $company;
                }
            });
        }
        
    }

    public function render()
    {
        return view('livewire.view-companies', ['searchedCompanies'=>$this->searchedCompanies]);
    }
}

