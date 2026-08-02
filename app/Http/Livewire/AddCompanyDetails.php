<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Company;
use App\Imports\CompanyImport;
use App\Enums\Settings\CompanySettingsSection;
use App\Services\Observability\DomainTelemetry;
use App\Services\Settings\CompanySettingsService;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

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
        $this->validate();

        $this->showConfirmPopup = true;
    }

    public function save(DomainTelemetry $telemetry): void
    {
        $companyId = null;

        try {
            $this->validate();

            $user = auth()->user();

            $company = Company::create(attributes: [
                'company_name' => $this->companyName,
                'is_esi' => $this->is_esi,
                'is_pf' => $this->is_pf,
                'company_address' => $this->address,
                'b2b_firm_id' => $user?->b2b_firm_id,
            ]);
            $companyId = (int) $company->id;

            $telemetry->emit('company.created', 'audit', 'success', [
                'company.id' => $companyId,
            ]);

            // First company for a pure B2C user becomes their scoped company.
            if ($user && $user->b2b_firm_id === null && $user->company_id === null) {
                $user->forceFill(['company_id' => $company->id])->save();
            }

            $settingsService = app(CompanySettingsService::class);
            $settingsService->ensureExists($company->id);

            // Persist tax / registration fields captured on create into company settings.
            $settingsService->updateSection(
                $company->id,
                CompanySettingsSection::CompanyProfile,
                [
                    'timezone' => 'Asia/Kolkata',
                    'fiscalYearStartMonth' => 4,
                    'payrollCurrency' => 'INR',
                    'dateFormat' => 'd M Y',
                    'gstNumber' => $this->gstNumber ?: null,
                    'zipCode' => $this->zipCode ?: null,
                    'state' => $this->state ?: null,
                    'country' => $this->country ?: null,
                    'esiCode' => $this->esiCode ?: null,
                    'esiContribution' => $this->esiContribution ?: null,
                    'esiCoverageStartDate' => $this->esiCoverageStartDate ?: null,
                    'esiCoverageEndDate' => $this->esiCoverageEndDate ?: null,
                    'pfCode' => $this->pfCode ?: null,
                    'pfContribution' => $this->pfContribution ?: null,
                    'pfCoverageStartDate' => $this->pfCoverageStartDate ?: null,
                    'pfCoverageEndDate' => $this->pfCoverageEndDate ?: null,
                ],
                1,
                auth()->id(),
            );

            session()->put('companyId', (string) $company->id);
            $this->showConfirmPopup = false;

            $this->redirect(route('getting-started', ['company_id' => $company->id]));

            return;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            $context = [
                'error.type' => $e::class,
            ];
            if ($companyId !== null) {
                $context['company.id'] = $companyId;
            }
            $telemetry->emit('company.save.failed', 'business', 'failure', $context, 'error');
            $this->alertMessage = 'Company details not saved successfully due to a database error.';
            $this->alertType = 'error';
        } catch (\Exception $e) {
            $context = [
                'error.type' => $e::class,
            ];
            if ($companyId !== null) {
                $context['company.id'] = $companyId;
            }
            $telemetry->emit('company.save.failed', 'business', 'failure', $context, 'error');
            $this->alertMessage = 'Company details not saved successfully.';
            $this->alertType = 'error';
        }
    }

    public function import(DomainTelemetry $telemetry)
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,xlsx|max:2048',
        ]);

        $collection = Excel::toCollection(new CompanyImport, $this->file);
        $rowCount = $this->countImportRows($collection);

        $companyImport = new CompanyImport();
        $companyImport->collection($collection);

        $telemetry->emit('company.import.completed', 'business', 'success', [
            'processed_count' => $rowCount,
            'failed_count' => 0,
        ]);
    }

    /**
     * @param  Collection<int, mixed>  $collection
     */
    private function countImportRows(Collection $collection): int
    {
        $count = 0;

        foreach ($collection as $sheetOrRow) {
            if ($sheetOrRow instanceof Collection) {
                $count += $sheetOrRow->count();
            } else {
                $count++;
            }
        }

        return $count;
    }
}
