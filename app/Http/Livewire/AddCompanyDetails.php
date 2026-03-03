<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Company;
use App\Imports\CompanyImport;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class AddCompanyDetails extends Component
{
    use WithFileUploads;
    public $showConfirmPopup = false;

    public $file;

    // Add public properties for form fields
    public $companyName;
    public $gstNumber;
    public $address;
    public $zipCode;
    public $state;
    public $country;
    public $esiCode;
    public $esiContribution;
    public $esiCoverageEndDate;
    public $esiCoverageStartDate;
    public $pfCode;
    public $pfCoverageStartDate;
    public $pfCoverageEndDate;
    public $pfContribution;
    public $servicesOpted = [];

    public $is_esi = 0;
    public $is_pf = 0;

    public $alertMessage = '';
    public $alertType = '';

    protected function rules(): array
    {
        return [
            'companyName' => 'required|string|max:255',
            'address' => 'required|string',
            'is_esi' => 'boolean',
            'is_pf' => 'boolean',
        ];
    }

    public function render()
    {
        return view('livewire.add-company-details');
    }

    public function validateForm(): void
    {
        Log::info(message: "The new company details are being validated");
        $this->validate();

        $this->showConfirmPopup = true;
    }

    public function save(): void
    {
        Log::info(message: "The new company details are being saved");
        try {
            $this->validate();

            $company = Company::create(attributes: [
                'company_name' => $this->companyName,
                'is_esi' => $this->is_esi,
                'is_pf' => $this->is_pf,
                'company_address' => $this->address,
                'company_handled_by' => auth()->id(),
            ]);

            $this->showConfirmPopup = false;
            $this->alertMessage = 'Company details saved successfully.';
            $this->alertType = 'success';
            Log::info(message: 'Company details saved successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error(message: 'Database error: ' . $e->getMessage());
            $this->alertMessage = 'Company details not saved successfully due to a database error.';
            $this->alertType = 'error';
        } catch (\Exception $e) {
            Log::error(message: 'Error saving company details: ' . $e->getMessage());
            $this->alertMessage = 'Company details not saved successfully.';
            $this->alertType = 'error';
        }
    }

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:2048',
        ]);

        $collection = Excel::toCollection(new CompanyImport, $this->file);
        Log::info(message: "The collection is: " . json_encode($collection));

        $companyImport = new CompanyImport();
        $companyImport->collection($collection);
    }
}
