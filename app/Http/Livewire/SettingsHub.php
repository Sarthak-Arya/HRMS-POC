<?php

namespace App\Http\Livewire;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\Company;
use App\Services\Attendance\AttendancePolicyService;
use App\Services\Compensation\StatutoryComplianceCalculator;
use App\Services\Settings\Adapters\CompanyProfileSettingsAdapter;
use App\Services\Settings\Adapters\StatutorySettingsAdapter;
use App\Services\Settings\CompanySettingsService;
use App\Services\Settings\OrganizationStructureService;
use App\Support\Settings\SettingsSectionAuthorization;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsHub extends Component
{
    use WithFileUploads;

    public string $companyId = '';

    /** Main settings category: organization-profile|attendance|compensation|reports|tax|statutory */
    public string $category = 'organization-profile';

    /** Sub-tab within organization-profile */
    public string $orgSubTab = 'profile';

    /** Sub-tab within statutory components: epf|esi|professional_tax|lwf|statutory_bonus */
    public string $statutorySubTab = 'epf';

    /** UI mode for EPF: splash (not enabled) | configure (enable form) | enabled (saved) */
    public string $epfUiMode = 'splash';

    public bool $showEpfSplitup = false;

    public int $settingsVersion = 1;

    // Company profile
    public string $companyName = '';
    public string $gstNumber = '';
    public string $address = '';
    public string $addressLine1 = '';
    public string $addressLine2 = '';
    public string $city = '';
    public string $zipCode = '';
    public string $state = '';
    public string $country = 'India';
    public string $businessLocation = 'India';
    public string $industry = '';
    public string $fieldSeparator = '/';
    public ?string $logoPath = null;
    public $logo = null;
    public string $filingAddress = '';
    public string $filingLocationId = '';
    public bool $isEsi = false;
    public bool $isPf = false;
    public string $esiCode = '';
    public string $esiContribution = '';
    public string $esiCoverageStartDate = '';
    public string $esiCoverageEndDate = '';
    public string $pfCode = '';
    public string $pfContribution = '';
    public string $pfCoverageStartDate = '';
    public string $pfCoverageEndDate = '';
    public string $timezone = 'Asia/Kolkata';
    public int $fiscalYearStartMonth = 4;
    public string $payrollCurrency = 'INR';
    public string $dateFormat = 'd/m/Y';

    // Branding
    public string $primaryColor = '#0058be';
    public string $secondaryColor = '#131b2e';
    public string $fontFamily = 'Inter';

    // Organization preferences
    public bool $requireDepartment = true;
    public bool $requireDesignation = true;
    public bool $requireLocation = false;
    public bool $allowDuplicateDepartmentNames = false;

    // Organization CRUD
    public string $departmentName = '';
    public ?int $editingDepartmentId = null;
    public string $designationName = '';
    public ?int $editingDesignationId = null;
    public string $locationName = '';
    public string $locationCode = '';
    public string $locationAddress = '';
    public string $locationCity = '';
    public string $locationState = '';
    public string $locationPincode = '';
    public string $locationCountry = '';
    public string $locationPhone = '';
    public string $locationEmail = '';
    public ?int $editingLocationId = null;

    // Attendance preferences
    public string $attendanceDefaultView = 'monthly_summary';
    public bool $attendanceMonthLockEnforced = true;
    public bool $attendanceShowDailyMarking = true;
    public string $attendanceDefaultPolicyId = '';

    // Compensation preferences
    public string $compensationDefaultPayCycle = 'monthly';
    public int $compensationRoundPayrollToNearest = 1;
    public bool $compensationShowInactiveComponents = false;

    // Reports preferences
    public string $reportsDefaultExportFormat = 'xlsx';
    public bool $reportsIncludeCompanyLogo = false;
    public string $reportsDefaultTemplateCategory = 'all';

    // Tax details
    public string $taxPan = '';
    public string $taxTan = '';
    public string $taxTdsCircleArea = '';
    public string $taxTdsCircleRange = '';
    public string $taxTdsCircleNumber = '';
    public string $taxTdsCircleAo = '';
    public string $taxPaymentFrequency = 'monthly';
    public string $taxDeductorType = 'employee';
    public string $taxDeductorName = '';
    public string $taxDeductorFatherName = '';

    // Statutory – EPF
    public bool $epfEnabled = false;
    public string $epfNumber = '';
    public string $epfDeductionCycle = 'monthly';
    public string $epfEmployeeContributionRate = '12_actual';
    public string $epfEmployerContributionRate = '12_actual';
    public bool $epfIncludeEmployerContributionInCtc = true;
    public bool $epfIncludeEdliInCtc = false;
    public bool $epfIncludeAdminChargesInCtc = false;
    public bool $epfAllowEmployeeRateOverride = false;
    public bool $epfProRateRestrictedPfWage = false;
    public bool $epfConsiderAllComponentsWhenBelowCeilingAfterLop = true;
    public float $epfWageCeiling = 15000;
    public float $epfSamplePfWage = 20000;

    // Statutory – ESI
    public bool $esiEnabled = false;
    public string $esiNumber = '';
    public string $esiDeductionCycle = 'monthly';
    public float $esiEmployeeContributionPercent = 0.75;
    public float $esiEmployerContributionPercent = 3.25;
    public float $esiWageCeiling = 21000;
    public float $esiSampleGrossWage = 20000;

    // Statutory – Professional Tax
    public bool $ptEnabled = false;
    public string $ptStateCode = '';
    public string $ptDeductionCycle = 'monthly';
    public string $ptRegistrationNumber = '';
    public float $ptSampleGrossWage = 25000;
    /** @var list<array{from: string, to: string, amount: string}> */
    public array $ptSlabs = [];

    // Statutory – Labour Welfare Fund
    public bool $lwfEnabled = false;
    public string $lwfStateCode = '';
    public string $lwfDeductionCycle = 'half_yearly';
    public float $lwfEmployeeContribution = 0;
    public float $lwfEmployerContribution = 0;
    public int $lwfSampleEmployees = 1;

    // Statutory – Bonus
    public bool $bonusEnabled = false;
    public float $bonusPercent = 8.33;
    public float $bonusEligibilityWageCeiling = 21000;
    public float $bonusCalculationWageCeiling = 7000;
    public int $bonusMinimumWorkedDays = 30;
    public float $bonusSampleMonthlyWage = 15000;

    /** @var list<string> */
    private const CATEGORIES = [
        'organization-profile',
        'attendance',
        'compensation',
        'reports',
        'tax',
        'statutory',
    ];

    /** @var list<string> */
    private const ORG_SUB_TABS = [
        'profile',
        'branding',
        'locations',
        'departments',
        'designations',
    ];

    /** @var list<string> */
    private const STATUTORY_SUB_TABS = [
        'epf',
        'esi',
        'professional_tax',
        'lwf',
        'statutory_bonus',
    ];

    public function mount(?string $company_id = null, ?string $category = null): void
    {
        $this->companyId = (string) ($company_id ?? session('companyId'));
        session()->put('companyId', $this->companyId);

        $requestedCategory = $category
            ?? request()->query('category')
            ?? request()->query('tab');

        $this->category = $this->normalizeCategory((string) ($requestedCategory ?: 'organization-profile'));

        $requestedSubTab = request()->query('sub');
        if ($this->category === 'statutory') {
            if ($requestedSubTab !== null && in_array((string) $requestedSubTab, self::STATUTORY_SUB_TABS, true)) {
                $this->statutorySubTab = (string) $requestedSubTab;
            }
        } elseif ($requestedSubTab !== null && in_array((string) $requestedSubTab, self::ORG_SUB_TABS, true)) {
            $this->orgSubTab = (string) $requestedSubTab;
        } elseif ($this->category !== 'organization-profile') {
            $this->orgSubTab = 'profile';
        }

        // Legacy compatibility: ?tab=organization|company_profile|...
        $legacyTab = request()->query('tab');
        if ($legacyTab !== null && $category === null) {
            $this->applyLegacyTab((string) $legacyTab);
        }

        $this->loadSettings();
    }

    public function setCategory(string $category)
    {
        if (! in_array($category, self::CATEGORIES, true)) {
            return;
        }

        $this->category = $category;

        return redirect()->route('settings', [
            'company_id' => $this->companyId,
            'category' => $category,
        ]);
    }

    public function setOrgSubTab(string $tab): void
    {
        if (! in_array($tab, self::ORG_SUB_TABS, true)) {
            return;
        }

        $this->orgSubTab = $tab;
    }

    public function setStatutorySubTab(string $tab): void
    {
        if (! in_array($tab, self::STATUTORY_SUB_TABS, true)) {
            return;
        }

        $this->statutorySubTab = $tab;
    }

    public function beginEnableEpf(): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->epfUiMode = 'configure';
        $this->epfIncludeEmployerContributionInCtc = true;
    }

    public function cancelEnableEpf(): void
    {
        if ($this->epfEnabled) {
            $this->epfUiMode = 'enabled';
        } else {
            $this->epfUiMode = 'splash';
        }
        $this->loadStatutorySettings();
    }

    public function toggleEpfSplitup(): void
    {
        $this->showEpfSplitup = ! $this->showEpfSplitup;
    }

    public function updatedEpfIncludeEmployerContributionInCtc(bool $value): void
    {
        if (! $value) {
            $this->epfIncludeEdliInCtc = false;
            $this->epfIncludeAdminChargesInCtc = false;
        }
    }

    public function enableEpf(StatutorySettingsAdapter $adapter): void
    {
        $this->epfEnabled = true;
        $this->saveEpfSettings($adapter, 'EPF enabled successfully.');
        $this->epfUiMode = 'enabled';
    }

    public function saveEpfSettings(StatutorySettingsAdapter $adapter, ?string $successMessage = null): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);

        $this->validate([
            'epfNumber' => 'required|string|max:64',
            'epfDeductionCycle' => 'required|in:monthly',
            'epfEmployeeContributionRate' => 'required|in:12_actual,12_restricted',
            'epfEmployerContributionRate' => 'required|in:12_actual,12_restricted',
            'epfSamplePfWage' => 'required|numeric|min:0',
        ]);

        $payload = $this->buildStatutoryPayload();
        $payload['epf']['enabled'] = true;

        $adapter->write(
            (int) $this->companyId,
            $payload,
            $this->settingsVersion,
            auth()->id(),
            'EPF settings updated',
        );

        $this->loadSettings();
        $this->epfUiMode = 'enabled';
        session()->flash('success', $successMessage ?? 'EPF settings saved successfully.');
    }

    public function disableEpf(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->epfEnabled = false;

        $payload = $this->buildStatutoryPayload();
        $payload['epf']['enabled'] = false;

        $adapter->write(
            (int) $this->companyId,
            $payload,
            $this->settingsVersion,
            auth()->id(),
            'EPF disabled',
        );

        $this->loadSettings();
        $this->epfUiMode = 'splash';
        session()->flash('success', 'EPF has been disabled for this company.');
    }

    public function beginEnableEsi(): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->esiEnabled = true;
    }

    public function saveEsiSettings(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);

        $this->validate([
            'esiNumber' => 'required|string|max:64',
            'esiEmployeeContributionPercent' => 'required|numeric|min:0|max:100',
            'esiEmployerContributionPercent' => 'required|numeric|min:0|max:100',
            'esiSampleGrossWage' => 'required|numeric|min:0',
        ]);

        $this->esiEnabled = true;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'ESI settings updated',
        );

        $this->loadSettings();
        session()->flash('success', 'ESI settings saved successfully.');
    }

    public function disableEsi(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->esiEnabled = false;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'ESI disabled',
        );
        $this->loadSettings();
        session()->flash('success', 'ESI has been disabled for this company.');
    }

    public function saveProfessionalTaxSettings(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);

        $this->validate([
            'ptStateCode' => 'required|string|max:8',
            'ptDeductionCycle' => 'required|in:monthly,half_yearly,yearly',
            'ptSampleGrossWage' => 'required|numeric|min:0',
            'ptSlabs' => 'array',
            'ptSlabs.*.from' => 'nullable|numeric|min:0',
            'ptSlabs.*.to' => 'nullable|numeric|min:0',
            'ptSlabs.*.amount' => 'nullable|numeric|min:0',
        ]);

        $this->ptEnabled = true;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'Professional Tax settings updated',
        );
        $this->loadSettings();
        session()->flash('success', 'Professional Tax settings saved successfully.');
    }

    public function disableProfessionalTax(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->ptEnabled = false;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'Professional Tax disabled',
        );
        $this->loadSettings();
        session()->flash('success', 'Professional Tax has been disabled.');
    }

    public function addPtSlab(): void
    {
        $this->ptSlabs[] = ['from' => '', 'to' => '', 'amount' => ''];
    }

    public function removePtSlab(int $index): void
    {
        if (! array_key_exists($index, $this->ptSlabs)) {
            return;
        }
        unset($this->ptSlabs[$index]);
        $this->ptSlabs = array_values($this->ptSlabs);
    }

    public function saveLwfSettings(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);

        $this->validate([
            'lwfStateCode' => 'required|string|max:8',
            'lwfDeductionCycle' => 'required|in:monthly,half_yearly,yearly',
            'lwfEmployeeContribution' => 'required|numeric|min:0',
            'lwfEmployerContribution' => 'required|numeric|min:0',
            'lwfSampleEmployees' => 'required|integer|min:1',
        ]);

        $this->lwfEnabled = true;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'Labour Welfare Fund settings updated',
        );
        $this->loadSettings();
        session()->flash('success', 'Labour Welfare Fund settings saved successfully.');
    }

    public function disableLwf(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->lwfEnabled = false;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'Labour Welfare Fund disabled',
        );
        $this->loadSettings();
        session()->flash('success', 'Labour Welfare Fund has been disabled.');
    }

    public function saveBonusSettings(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);

        $this->validate([
            'bonusPercent' => 'required|numeric|min:8.33|max:20',
            'bonusEligibilityWageCeiling' => 'required|numeric|min:0',
            'bonusCalculationWageCeiling' => 'required|numeric|min:0',
            'bonusMinimumWorkedDays' => 'required|integer|min:1',
            'bonusSampleMonthlyWage' => 'required|numeric|min:0',
        ]);

        $this->bonusEnabled = true;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'Statutory Bonus settings updated',
        );
        $this->loadSettings();
        session()->flash('success', 'Statutory Bonus settings saved successfully.');
    }

    public function disableBonus(StatutorySettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::Statutory);
        $this->bonusEnabled = false;
        $adapter->write(
            (int) $this->companyId,
            $this->buildStatutoryPayload(),
            $this->settingsVersion,
            auth()->id(),
            'Statutory Bonus disabled',
        );
        $this->loadSettings();
        session()->flash('success', 'Statutory Bonus has been disabled.');
    }

    public function canManageSection(string $sectionKey): bool
    {
        $section = $this->resolveSectionKey($sectionKey);

        return $section !== null
            && SettingsSectionAuthorization::canManageSection(auth()->user(), $section);
    }

    public function updatedLogo(): void
    {
        $this->validate([
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);
    }

    public function removeLogo(): void
    {
        $this->authorizeSection(CompanySettingsSection::CompanyProfile);
        $this->logo = null;

        if ($this->logoPath && Storage::disk('public')->exists($this->logoPath)) {
            Storage::disk('public')->delete($this->logoPath);
        }

        $this->logoPath = null;
        $this->persistProfileFields();
        session()->flash('success', 'Organisation logo removed.');
    }

    public function saveCompanyProfile(CompanyProfileSettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::CompanyProfile);

        $this->validate([
            'companyName' => 'required|string|max:255',
            'addressLine1' => 'required|string|max:255',
            'addressLine2' => 'nullable|string|max:255',
            'city' => 'required|string|max:128',
            'state' => 'required|string|max:128',
            'zipCode' => 'required|string|max:16',
            'businessLocation' => 'required|string|max:128',
            'industry' => 'required|string|max:128',
            'gstNumber' => 'nullable|string|max:64',
            'country' => 'nullable|string|max:128',
            'timezone' => 'required|string|max:64',
            'fiscalYearStartMonth' => 'required|integer|min:1|max:12',
            'payrollCurrency' => 'required|string|size:3',
            'dateFormat' => 'required|string|max:32',
            'fieldSeparator' => 'required|string|max:8',
            'filingAddress' => 'nullable|string|max:500',
            'filingLocationId' => 'nullable|integer',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        $storedLogoPath = $this->logoPath;
        if ($this->logo) {
            if ($storedLogoPath && Storage::disk('public')->exists($storedLogoPath)) {
                Storage::disk('public')->delete($storedLogoPath);
            }
            $storedLogoPath = $this->logo->store('company-logos/'.$this->companyId, 'public');
        }

        $composedFilingAddress = $this->resolveFilingAddress();

        $adapter->write(
            (int) $this->companyId,
            [
                'companyName' => $this->companyName,
                'gstNumber' => $this->gstNumber ?: null,
                'address' => $this->composeOrganisationAddress(),
                'addressLine1' => $this->addressLine1,
                'addressLine2' => $this->addressLine2 ?: null,
                'city' => $this->city,
                'zipCode' => $this->zipCode ?: null,
                'state' => $this->state ?: null,
                'country' => $this->country ?: $this->businessLocation,
                'businessLocation' => $this->businessLocation,
                'industry' => $this->industry,
                'logoPath' => $storedLogoPath,
                'filingAddress' => $composedFilingAddress,
                'filingLocationId' => $this->filingLocationId !== '' ? (int) $this->filingLocationId : null,
                'isEsi' => $this->isEsi,
                'isPf' => $this->isPf,
                'esiCode' => $this->esiCode ?: null,
                'esiContribution' => $this->esiContribution ?: null,
                'esiCoverageStartDate' => $this->esiCoverageStartDate ?: null,
                'esiCoverageEndDate' => $this->esiCoverageEndDate ?: null,
                'pfCode' => $this->pfCode ?: null,
                'pfContribution' => $this->pfContribution ?: null,
                'pfCoverageStartDate' => $this->pfCoverageStartDate ?: null,
                'pfCoverageEndDate' => $this->pfCoverageEndDate ?: null,
            ],
            [
                'timezone' => $this->timezone,
                'fiscalYearStartMonth' => $this->fiscalYearStartMonth,
                'payrollCurrency' => strtoupper($this->payrollCurrency),
                'dateFormat' => $this->dateFormat,
                'fieldSeparator' => $this->fieldSeparator,
                'primaryColor' => $this->primaryColor,
                'secondaryColor' => $this->secondaryColor,
                'fontFamily' => $this->fontFamily,
                'logoPath' => $storedLogoPath,
            ],
            $this->settingsVersion,
            auth()->id(),
        );

        $this->logo = null;
        $this->loadSettings();
        session()->flash('success', 'Organisation profile saved successfully.');
    }

    public function saveBranding(CompanyProfileSettingsAdapter $adapter): void
    {
        $this->authorizeSection(CompanySettingsSection::CompanyProfile);

        $this->validate([
            'primaryColor' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondaryColor' => ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'fontFamily' => 'required|string|max:64',
        ]);

        $adapter->write(
            (int) $this->companyId,
            [
                'companyName' => $this->companyName,
                'gstNumber' => $this->gstNumber ?: null,
                'address' => $this->composeOrganisationAddress() ?: $this->address,
                'addressLine1' => $this->addressLine1 ?: null,
                'addressLine2' => $this->addressLine2 ?: null,
                'city' => $this->city ?: null,
                'zipCode' => $this->zipCode ?: null,
                'state' => $this->state ?: null,
                'country' => $this->country ?: $this->businessLocation,
                'businessLocation' => $this->businessLocation,
                'industry' => $this->industry ?: null,
                'logoPath' => $this->logoPath,
                'filingAddress' => $this->filingAddress ?: null,
                'filingLocationId' => $this->filingLocationId !== '' ? (int) $this->filingLocationId : null,
                'isEsi' => $this->isEsi,
                'isPf' => $this->isPf,
                'esiCode' => $this->esiCode ?: null,
                'esiContribution' => $this->esiContribution ?: null,
                'esiCoverageStartDate' => $this->esiCoverageStartDate ?: null,
                'esiCoverageEndDate' => $this->esiCoverageEndDate ?: null,
                'pfCode' => $this->pfCode ?: null,
                'pfContribution' => $this->pfContribution ?: null,
                'pfCoverageStartDate' => $this->pfCoverageStartDate ?: null,
                'pfCoverageEndDate' => $this->pfCoverageEndDate ?: null,
            ],
            [
                'timezone' => $this->timezone,
                'fiscalYearStartMonth' => $this->fiscalYearStartMonth,
                'payrollCurrency' => strtoupper($this->payrollCurrency),
                'dateFormat' => $this->dateFormat,
                'fieldSeparator' => $this->fieldSeparator,
                'primaryColor' => $this->primaryColor,
                'secondaryColor' => $this->secondaryColor,
                'fontFamily' => $this->fontFamily,
                'logoPath' => $this->logoPath,
            ],
            $this->settingsVersion,
            auth()->id(),
        );

        $this->loadSettings();
        session()->flash('success', 'Branding settings saved successfully.');
    }

    public function useOrganisationAddressAsFiling(): void
    {
        $this->filingLocationId = '';
        $this->filingAddress = $this->composeOrganisationAddress();
    }

    public function syncFilingAddressFromLocation(OrganizationStructureService $service): void
    {
        if ($this->filingLocationId === '') {
            return;
        }

        $location = $service->listLocations((int) $this->companyId)
            ->firstWhere('id', (int) $this->filingLocationId);

        if (! $location) {
            return;
        }

        $this->filingAddress = trim((string) ($location->full_address ?: $location->location_name));
    }

    public function saveOrganizationPreferences(CompanySettingsService $settingsService): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);

        $settingsService->updateSection(
            (int) $this->companyId,
            CompanySettingsSection::Organization,
            [
                'requireDepartment' => $this->requireDepartment,
                'requireDesignation' => $this->requireDesignation,
                'requireLocation' => $this->requireLocation,
                'allowDuplicateDepartmentNames' => $this->allowDuplicateDepartmentNames,
            ],
            $this->settingsVersion,
            auth()->id(),
            'Organization preferences updated',
        );

        $this->loadSettings();
        session()->flash('success', 'Organization preferences saved successfully.');
    }

    public function saveAttendancePreferences(CompanySettingsService $settingsService): void
    {
        $this->authorizeSection(CompanySettingsSection::Attendance);

        $settingsService->updateSection(
            (int) $this->companyId,
            CompanySettingsSection::Attendance,
            [
                'defaultView' => $this->attendanceDefaultView,
                'monthLockEnforced' => $this->attendanceMonthLockEnforced,
                'showDailyMarking' => $this->attendanceShowDailyMarking,
                'defaultPolicyId' => $this->attendanceDefaultPolicyId !== ''
                    ? (int) $this->attendanceDefaultPolicyId
                    : null,
            ],
            $this->settingsVersion,
            auth()->id(),
            'Attendance preferences updated',
        );

        $this->loadSettings();
        session()->flash('success', 'Attendance preferences saved successfully.');
    }

    public function saveCompensationPreferences(CompanySettingsService $settingsService): void
    {
        $this->authorizeSection(CompanySettingsSection::Compensation);

        $settingsService->updateSection(
            (int) $this->companyId,
            CompanySettingsSection::Compensation,
            [
                'defaultPayCycle' => $this->compensationDefaultPayCycle,
                'roundPayrollToNearest' => $this->compensationRoundPayrollToNearest,
                'showInactiveComponents' => $this->compensationShowInactiveComponents,
            ],
            $this->settingsVersion,
            auth()->id(),
            'Compensation preferences updated',
        );

        $this->loadSettings();
        session()->flash('success', 'Compensation preferences saved successfully.');
    }

    public function saveReportsPreferences(CompanySettingsService $settingsService): void
    {
        $this->authorizeSection(CompanySettingsSection::Reports);

        $settingsService->updateSection(
            (int) $this->companyId,
            CompanySettingsSection::Reports,
            [
                'defaultExportFormat' => $this->reportsDefaultExportFormat,
                'includeCompanyLogo' => $this->reportsIncludeCompanyLogo,
                'defaultTemplateCategory' => $this->reportsDefaultTemplateCategory,
            ],
            $this->settingsVersion,
            auth()->id(),
            'Report preferences updated',
        );

        $this->loadSettings();
        session()->flash('success', 'Report preferences saved successfully.');
    }

    public function saveTaxDetails(CompanySettingsService $settingsService): void
    {
        $this->authorizeSection(CompanySettingsSection::Tax);

        $settingsService->updateSection(
            (int) $this->companyId,
            CompanySettingsSection::Tax,
            [
                'pan' => $this->normalizeTaxCode($this->taxPan),
                'tan' => $this->normalizeTaxCode($this->taxTan),
                'tdsCircleArea' => $this->normalizeTaxCode($this->taxTdsCircleArea),
                'tdsCircleRange' => $this->normalizeTaxCode($this->taxTdsCircleRange),
                'tdsCircleNumber' => $this->normalizeTaxCode($this->taxTdsCircleNumber),
                'tdsCircleAo' => $this->normalizeTaxCode($this->taxTdsCircleAo),
                'taxPaymentFrequency' => $this->taxPaymentFrequency,
                'deductorType' => $this->taxDeductorType,
                'deductorName' => $this->blankToNull($this->taxDeductorName),
                'deductorFatherName' => $this->blankToNull($this->taxDeductorFatherName),
            ],
            $this->settingsVersion,
            auth()->id(),
            'Tax details updated',
        );

        $this->loadSettings();
        session()->flash('success', 'Tax details saved successfully.');
    }

    public function saveDepartment(OrganizationStructureService $service): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);
        $this->validate(['departmentName' => 'required|string|max:255']);

        if ($this->editingDepartmentId) {
            $service->updateDepartment((int) $this->companyId, $this->editingDepartmentId, $this->departmentName);
            session()->flash('success', 'Department updated successfully.');
        } else {
            $service->createDepartment((int) $this->companyId, $this->departmentName);
            session()->flash('success', 'Department created successfully.');
        }

        $this->resetDepartmentForm();
    }

    public function editDepartment(int $departmentId, OrganizationStructureService $service): void
    {
        $department = $service->listDepartments((int) $this->companyId)->firstWhere('id', $departmentId);
        if (! $department) {
            return;
        }

        $this->editingDepartmentId = $departmentId;
        $this->departmentName = $department->department_name;
    }

    public function deleteDepartment(int $departmentId, OrganizationStructureService $service): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);
        $service->deleteDepartment((int) $this->companyId, $departmentId);
        session()->flash('success', 'Department deleted successfully.');
    }

    public function saveDesignation(OrganizationStructureService $service): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);
        $this->validate(['designationName' => 'required|string|max:255']);

        if ($this->editingDesignationId) {
            $service->updateDesignation((int) $this->companyId, $this->editingDesignationId, $this->designationName);
            session()->flash('success', 'Designation updated successfully.');
        } else {
            $service->createDesignation((int) $this->companyId, $this->designationName);
            session()->flash('success', 'Designation created successfully.');
        }

        $this->resetDesignationForm();
    }

    public function editDesignation(int $designationId, OrganizationStructureService $service): void
    {
        $designation = $service->listDesignations((int) $this->companyId)->firstWhere('id', $designationId);
        if (! $designation) {
            return;
        }

        $this->editingDesignationId = $designationId;
        $this->designationName = $designation->designation_name;
    }

    public function deleteDesignation(int $designationId, OrganizationStructureService $service): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);
        $service->deleteDesignation((int) $this->companyId, $designationId);
        session()->flash('success', 'Designation deleted successfully.');
    }

    public function saveLocation(OrganizationStructureService $service): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);
        $this->validate([
            'locationName' => 'required|string|max:255',
            'locationCode' => 'nullable|string|max:64',
            'locationAddress' => 'nullable|string|max:500',
            'locationCity' => 'nullable|string|max:128',
            'locationState' => 'nullable|string|max:128',
            'locationPincode' => 'nullable|string|max:16',
            'locationCountry' => 'nullable|string|max:128',
            'locationPhone' => 'nullable|string|max:32',
            'locationEmail' => 'nullable|email|max:255',
        ]);

        $payload = [
            'location_name' => $this->locationName,
            'location_code' => $this->locationCode ?: null,
            'location_address' => $this->locationAddress ?: null,
            'location_city' => $this->locationCity ?: null,
            'location_state' => $this->locationState ?: null,
            'location_pincode' => $this->locationPincode ?: null,
            'location_country' => $this->locationCountry ?: null,
            'location_phone' => $this->locationPhone ?: null,
            'location_email' => $this->locationEmail ?: null,
        ];

        if ($this->editingLocationId) {
            $service->updateLocation((int) $this->companyId, $this->editingLocationId, $payload);
            session()->flash('success', 'Location updated successfully.');
        } else {
            $service->createLocation((int) $this->companyId, $payload);
            session()->flash('success', 'Location created successfully.');
        }

        $this->resetLocationForm();
    }

    public function editLocation(int $locationId, OrganizationStructureService $service): void
    {
        $location = $service->listLocations((int) $this->companyId)->firstWhere('id', $locationId);
        if (! $location) {
            return;
        }

        $this->editingLocationId = $locationId;
        $this->locationName = (string) $location->location_name;
        $this->locationCode = (string) ($location->location_code ?? '');
        $this->locationAddress = (string) ($location->location_address ?? '');
        $this->locationCity = (string) ($location->location_city ?? '');
        $this->locationState = (string) ($location->location_state ?? '');
        $this->locationPincode = (string) ($location->location_pincode ?? '');
        $this->locationCountry = (string) ($location->location_country ?? '');
        $this->locationPhone = (string) ($location->location_phone ?? '');
        $this->locationEmail = (string) ($location->location_email ?? '');
    }

    public function deleteLocation(int $locationId, OrganizationStructureService $service): void
    {
        $this->authorizeSection(CompanySettingsSection::Organization);
        $service->deleteLocation((int) $this->companyId, $locationId);
        session()->flash('success', 'Location deleted successfully.');
    }

    public function render(
        CompanyProfileSettingsAdapter $profileAdapter,
        CompanySettingsService $settingsService,
        OrganizationStructureService $organizationService,
        AttendancePolicyService $policyService,
        StatutoryComplianceCalculator $statutoryCalculator,
    ) {
        $company = Company::query()->findOrFail((int) $this->companyId);

        return view('livewire.settings-hub', [
            'companyName' => $company->company_name,
            'departments' => $organizationService->listDepartments((int) $this->companyId),
            'designations' => $organizationService->listDesignations((int) $this->companyId),
            'locations' => $organizationService->listLocations((int) $this->companyId),
            'attendancePolicies' => $policyService->listForCompany((int) $this->companyId),
            'manageableSections' => SettingsSectionAuthorization::manageableSections(auth()->user()),
            'logoUrl' => $this->logoPath ? Storage::disk('public')->url($this->logoPath) : null,
            'dateFormatPreview' => $this->formatDatePreview($this->dateFormat),
            'epfSampleCalculation' => $statutoryCalculator->calculateEpf(
                (float) $this->epfSamplePfWage,
                $this->epfConfigForCalculator(),
            ),
            'esiSampleCalculation' => $statutoryCalculator->calculateEsi(
                (float) $this->esiSampleGrossWage,
                [
                    'wageCeiling' => $this->esiWageCeiling,
                    'employeeContributionPercent' => $this->esiEmployeeContributionPercent,
                    'employerContributionPercent' => $this->esiEmployerContributionPercent,
                ],
            ),
            'ptSampleCalculation' => $statutoryCalculator->calculateProfessionalTax(
                (float) $this->ptSampleGrossWage,
                $this->normalizedPtSlabs(),
            ),
            'lwfSampleCalculation' => $statutoryCalculator->calculateLabourWelfareFund(
                (int) $this->lwfSampleEmployees,
                [
                    'employeeContribution' => $this->lwfEmployeeContribution,
                    'employerContribution' => $this->lwfEmployerContribution,
                ],
            ),
            'bonusSampleCalculation' => $statutoryCalculator->calculateStatutoryBonus(
                (float) $this->bonusSampleMonthlyWage,
                [
                    'bonusPercent' => $this->bonusPercent,
                    'eligibilityWageCeiling' => $this->bonusEligibilityWageCeiling,
                    'calculationWageCeiling' => $this->bonusCalculationWageCeiling,
                    'minimumWorkedDays' => $this->bonusMinimumWorkedDays,
                ],
            ),
        ]);
    }

    private function loadSettings(): void
    {
        $companyId = (int) $this->companyId;
        $settingsService = app(CompanySettingsService::class);
        $profileAdapter = app(CompanyProfileSettingsAdapter::class);

        $this->settingsVersion = $settingsService->currentVersion($companyId);
        $profile = $profileAdapter->read($companyId);

        $this->companyName = (string) ($profile['companyName'] ?? '');
        $this->gstNumber = (string) ($profile['gstNumber'] ?? '');
        $this->address = (string) ($profile['address'] ?? '');
        $this->addressLine1 = (string) ($profile['addressLine1'] ?? '');
        $this->addressLine2 = (string) ($profile['addressLine2'] ?? '');
        $this->city = (string) ($profile['city'] ?? '');
        $this->zipCode = (string) ($profile['zipCode'] ?? '');
        $this->state = (string) ($profile['state'] ?? '');
        $this->country = (string) ($profile['country'] ?? 'India');
        $this->businessLocation = (string) ($profile['businessLocation'] ?? 'India');
        $this->industry = (string) ($profile['industry'] ?? '');
        $this->logoPath = $profile['logoPath'] ?? null;
        $this->filingAddress = (string) ($profile['filingAddress'] ?? '');
        $this->filingLocationId = $profile['filingLocationId'] !== null
            ? (string) $profile['filingLocationId']
            : '';
        $this->isEsi = (bool) ($profile['isEsi'] ?? false);
        $this->isPf = (bool) ($profile['isPf'] ?? false);
        $this->esiCode = (string) ($profile['esiCode'] ?? '');
        $this->esiContribution = (string) ($profile['esiContribution'] ?? '');
        $this->esiCoverageStartDate = (string) ($profile['esiCoverageStartDate'] ?? '');
        $this->esiCoverageEndDate = (string) ($profile['esiCoverageEndDate'] ?? '');
        $this->pfCode = (string) ($profile['pfCode'] ?? '');
        $this->pfContribution = (string) ($profile['pfContribution'] ?? '');
        $this->pfCoverageStartDate = (string) ($profile['pfCoverageStartDate'] ?? '');
        $this->pfCoverageEndDate = (string) ($profile['pfCoverageEndDate'] ?? '');

        $prefs = $profile['preferences'] ?? [];
        $this->timezone = (string) ($prefs['timezone'] ?? 'Asia/Kolkata');
        $this->fiscalYearStartMonth = (int) ($prefs['fiscalYearStartMonth'] ?? 4);
        $this->payrollCurrency = (string) ($prefs['payrollCurrency'] ?? 'INR');
        $this->dateFormat = (string) ($prefs['dateFormat'] ?? 'd/m/Y');
        $this->fieldSeparator = (string) ($prefs['fieldSeparator'] ?? '/');
        $this->primaryColor = (string) ($prefs['primaryColor'] ?? '#0058be');
        $this->secondaryColor = (string) ($prefs['secondaryColor'] ?? '#131b2e');
        $this->fontFamily = (string) ($prefs['fontFamily'] ?? 'Inter');

        $organization = $settingsService->getSection($companyId, CompanySettingsSection::Organization);
        $this->requireDepartment = (bool) ($organization['requireDepartment'] ?? true);
        $this->requireDesignation = (bool) ($organization['requireDesignation'] ?? true);
        $this->requireLocation = (bool) ($organization['requireLocation'] ?? false);
        $this->allowDuplicateDepartmentNames = (bool) ($organization['allowDuplicateDepartmentNames'] ?? false);

        $attendance = $settingsService->getSection($companyId, CompanySettingsSection::Attendance);
        $this->attendanceDefaultView = (string) ($attendance['defaultView'] ?? 'monthly_summary');
        $this->attendanceMonthLockEnforced = (bool) ($attendance['monthLockEnforced'] ?? true);
        $this->attendanceShowDailyMarking = (bool) ($attendance['showDailyMarking'] ?? true);
        $this->attendanceDefaultPolicyId = $attendance['defaultPolicyId'] !== null
            ? (string) $attendance['defaultPolicyId']
            : '';

        $compensation = $settingsService->getSection($companyId, CompanySettingsSection::Compensation);
        $this->compensationDefaultPayCycle = (string) ($compensation['defaultPayCycle'] ?? 'monthly');
        $this->compensationRoundPayrollToNearest = (int) ($compensation['roundPayrollToNearest'] ?? 1);
        $this->compensationShowInactiveComponents = (bool) ($compensation['showInactiveComponents'] ?? false);

        $reports = $settingsService->getSection($companyId, CompanySettingsSection::Reports);
        $this->reportsDefaultExportFormat = (string) ($reports['defaultExportFormat'] ?? 'xlsx');
        $this->reportsIncludeCompanyLogo = (bool) ($reports['includeCompanyLogo'] ?? false);
        $this->reportsDefaultTemplateCategory = (string) ($reports['defaultTemplateCategory'] ?? 'all');

        $tax = $settingsService->getSection($companyId, CompanySettingsSection::Tax);
        $this->taxPan = (string) ($tax['pan'] ?? '');
        $this->taxTan = (string) ($tax['tan'] ?? '');
        $this->taxTdsCircleArea = (string) ($tax['tdsCircleArea'] ?? '');
        $this->taxTdsCircleRange = (string) ($tax['tdsCircleRange'] ?? '');
        $this->taxTdsCircleNumber = (string) ($tax['tdsCircleNumber'] ?? '');
        $this->taxTdsCircleAo = (string) ($tax['tdsCircleAo'] ?? '');
        $this->taxPaymentFrequency = (string) ($tax['taxPaymentFrequency'] ?? 'monthly');
        $this->taxDeductorType = (string) ($tax['deductorType'] ?? 'employee');
        $this->taxDeductorName = (string) ($tax['deductorName'] ?? '');
        $this->taxDeductorFatherName = (string) ($tax['deductorFatherName'] ?? '');

        $this->loadStatutorySettings();
    }

    private function loadStatutorySettings(): void
    {
        $statutory = app(StatutorySettingsAdapter::class)->read((int) $this->companyId);

        $epf = $statutory['epf'];
        $this->epfEnabled = (bool) ($epf['enabled'] ?? false);
        $this->epfNumber = (string) ($epf['epfNumber'] ?? '');
        $this->epfDeductionCycle = (string) ($epf['deductionCycle'] ?? 'monthly');
        $this->epfEmployeeContributionRate = (string) ($epf['employeeContributionRate'] ?? '12_actual');
        $this->epfEmployerContributionRate = (string) ($epf['employerContributionRate'] ?? '12_actual');
        $this->epfIncludeEmployerContributionInCtc = (bool) ($epf['includeEmployerContributionInCtc'] ?? true);
        $this->epfIncludeEdliInCtc = (bool) ($epf['includeEdliInCtc'] ?? false);
        $this->epfIncludeAdminChargesInCtc = (bool) ($epf['includeAdminChargesInCtc'] ?? false);
        $this->epfAllowEmployeeRateOverride = (bool) ($epf['allowEmployeeRateOverride'] ?? false);
        $this->epfProRateRestrictedPfWage = (bool) ($epf['proRateRestrictedPfWage'] ?? false);
        $this->epfConsiderAllComponentsWhenBelowCeilingAfterLop = (bool) ($epf['considerAllComponentsWhenBelowCeilingAfterLop'] ?? true);
        $this->epfWageCeiling = (float) ($epf['wageCeiling'] ?? 15000);
        $this->epfSamplePfWage = (float) ($epf['samplePfWage'] ?? 20000);
        $this->epfUiMode = $this->epfEnabled ? 'enabled' : 'splash';

        $esi = $statutory['esi'];
        $this->esiEnabled = (bool) ($esi['enabled'] ?? false);
        $this->esiNumber = (string) ($esi['esiNumber'] ?? '');
        $this->esiDeductionCycle = (string) ($esi['deductionCycle'] ?? 'monthly');
        $this->esiEmployeeContributionPercent = (float) ($esi['employeeContributionPercent'] ?? 0.75);
        $this->esiEmployerContributionPercent = (float) ($esi['employerContributionPercent'] ?? 3.25);
        $this->esiWageCeiling = (float) ($esi['wageCeiling'] ?? 21000);
        $this->esiSampleGrossWage = (float) ($esi['sampleGrossWage'] ?? 20000);

        $pt = $statutory['professionalTax'];
        $this->ptEnabled = (bool) ($pt['enabled'] ?? false);
        $this->ptStateCode = (string) ($pt['stateCode'] ?? '');
        $this->ptDeductionCycle = (string) ($pt['deductionCycle'] ?? 'monthly');
        $this->ptRegistrationNumber = (string) ($pt['registrationNumber'] ?? '');
        $this->ptSampleGrossWage = (float) ($pt['sampleGrossWage'] ?? 25000);
        $this->ptSlabs = collect((array) ($pt['slabs'] ?? []))
            ->map(fn ($slab) => [
                'from' => (string) ($slab['from'] ?? ''),
                'to' => (string) ($slab['to'] ?? ''),
                'amount' => (string) ($slab['amount'] ?? ''),
            ])
            ->values()
            ->all();

        $lwf = $statutory['labourWelfareFund'];
        $this->lwfEnabled = (bool) ($lwf['enabled'] ?? false);
        $this->lwfStateCode = (string) ($lwf['stateCode'] ?? '');
        $this->lwfDeductionCycle = (string) ($lwf['deductionCycle'] ?? 'half_yearly');
        $this->lwfEmployeeContribution = (float) ($lwf['employeeContribution'] ?? 0);
        $this->lwfEmployerContribution = (float) ($lwf['employerContribution'] ?? 0);
        $this->lwfSampleEmployees = (int) ($lwf['sampleEmployees'] ?? 1);

        $bonus = $statutory['statutoryBonus'];
        $this->bonusEnabled = (bool) ($bonus['enabled'] ?? false);
        $this->bonusPercent = (float) ($bonus['bonusPercent'] ?? 8.33);
        $this->bonusEligibilityWageCeiling = (float) ($bonus['eligibilityWageCeiling'] ?? 21000);
        $this->bonusCalculationWageCeiling = (float) ($bonus['calculationWageCeiling'] ?? 7000);
        $this->bonusMinimumWorkedDays = (int) ($bonus['minimumWorkedDays'] ?? 30);
        $this->bonusSampleMonthlyWage = (float) ($bonus['sampleMonthlyWage'] ?? 15000);
    }

    private function persistProfileFields(): void
    {
        $adapter = app(CompanyProfileSettingsAdapter::class);

        $adapter->write(
            (int) $this->companyId,
            [
                'companyName' => $this->companyName,
                'gstNumber' => $this->gstNumber ?: null,
                'address' => $this->composeOrganisationAddress() ?: $this->address,
                'addressLine1' => $this->addressLine1 ?: null,
                'addressLine2' => $this->addressLine2 ?: null,
                'city' => $this->city ?: null,
                'zipCode' => $this->zipCode ?: null,
                'state' => $this->state ?: null,
                'country' => $this->country ?: $this->businessLocation,
                'businessLocation' => $this->businessLocation,
                'industry' => $this->industry ?: null,
                'logoPath' => $this->logoPath,
                'filingAddress' => $this->filingAddress ?: null,
                'filingLocationId' => $this->filingLocationId !== '' ? (int) $this->filingLocationId : null,
                'isEsi' => $this->isEsi,
                'isPf' => $this->isPf,
                'esiCode' => $this->esiCode ?: null,
                'esiContribution' => $this->esiContribution ?: null,
                'esiCoverageStartDate' => $this->esiCoverageStartDate ?: null,
                'esiCoverageEndDate' => $this->esiCoverageEndDate ?: null,
                'pfCode' => $this->pfCode ?: null,
                'pfContribution' => $this->pfContribution ?: null,
                'pfCoverageStartDate' => $this->pfCoverageStartDate ?: null,
                'pfCoverageEndDate' => $this->pfCoverageEndDate ?: null,
            ],
            [
                'timezone' => $this->timezone,
                'fiscalYearStartMonth' => $this->fiscalYearStartMonth,
                'payrollCurrency' => strtoupper($this->payrollCurrency),
                'dateFormat' => $this->dateFormat,
                'fieldSeparator' => $this->fieldSeparator,
                'primaryColor' => $this->primaryColor,
                'secondaryColor' => $this->secondaryColor,
                'fontFamily' => $this->fontFamily,
                'logoPath' => $this->logoPath,
            ],
            $this->settingsVersion,
            auth()->id(),
        );

        $this->loadSettings();
    }

    private function authorizeSection(CompanySettingsSection $section): void
    {
        if (! SettingsSectionAuthorization::canManageSection(auth()->user(), $section)) {
            abort(403);
        }
    }

    private function resolveSectionKey(string $sectionKey): ?CompanySettingsSection
    {
        return match ($sectionKey) {
            'company_profile', 'profile', 'branding', 'organization-profile' => CompanySettingsSection::CompanyProfile,
            'organization', 'locations', 'departments', 'designations' => CompanySettingsSection::Organization,
            'attendance' => CompanySettingsSection::Attendance,
            'compensation' => CompanySettingsSection::Compensation,
            'reports' => CompanySettingsSection::Reports,
            'tax' => CompanySettingsSection::Tax,
            'statutory', 'statutory_components', 'epf', 'esi', 'professional_tax', 'lwf', 'statutory_bonus' => CompanySettingsSection::Statutory,
            default => CompanySettingsSection::fromTabKey($sectionKey),
        };
    }

    private function normalizeCategory(string $category): string
    {
        $mapped = match ($category) {
            'company_profile', 'organization', 'organization_profile' => 'organization-profile',
            'statutory_components' => 'statutory',
            default => $category,
        };

        return in_array($mapped, self::CATEGORIES, true)
            ? $mapped
            : 'organization-profile';
    }

    private function applyLegacyTab(string $tab): void
    {
        match ($tab) {
            'company_profile' => $this->applyCategoryAndSub('organization-profile', 'profile'),
            'organization' => $this->applyCategoryAndSub('organization-profile', 'departments'),
            'attendance' => $this->category = 'attendance',
            'compensation' => $this->category = 'compensation',
            'reports' => $this->category = 'reports',
            'tax' => $this->category = 'tax',
            'statutory', 'statutory_components' => $this->category = 'statutory',
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatutoryPayload(): array
    {
        return [
            'epf' => [
                'enabled' => $this->epfEnabled,
                'epfNumber' => $this->blankToNull($this->epfNumber),
                'deductionCycle' => $this->epfDeductionCycle,
                'employeeContributionRate' => $this->epfEmployeeContributionRate,
                'employerContributionRate' => $this->epfEmployerContributionRate,
                'includeEmployerContributionInCtc' => $this->epfIncludeEmployerContributionInCtc,
                'includeEdliInCtc' => $this->epfIncludeEmployerContributionInCtc && $this->epfIncludeEdliInCtc,
                'includeAdminChargesInCtc' => $this->epfIncludeEmployerContributionInCtc && $this->epfIncludeAdminChargesInCtc,
                'allowEmployeeRateOverride' => $this->epfAllowEmployeeRateOverride,
                'proRateRestrictedPfWage' => $this->epfProRateRestrictedPfWage,
                'considerAllComponentsWhenBelowCeilingAfterLop' => $this->epfConsiderAllComponentsWhenBelowCeilingAfterLop,
                'wageCeiling' => $this->epfWageCeiling,
                'samplePfWage' => $this->epfSamplePfWage,
            ],
            'esi' => [
                'enabled' => $this->esiEnabled,
                'esiNumber' => $this->blankToNull($this->esiNumber),
                'deductionCycle' => $this->esiDeductionCycle,
                'employeeContributionPercent' => $this->esiEmployeeContributionPercent,
                'employerContributionPercent' => $this->esiEmployerContributionPercent,
                'wageCeiling' => $this->esiWageCeiling,
                'sampleGrossWage' => $this->esiSampleGrossWage,
            ],
            'professionalTax' => [
                'enabled' => $this->ptEnabled,
                'stateCode' => $this->blankToNull($this->ptStateCode),
                'deductionCycle' => $this->ptDeductionCycle,
                'registrationNumber' => $this->blankToNull($this->ptRegistrationNumber),
                'slabs' => $this->normalizedPtSlabs(),
                'sampleGrossWage' => $this->ptSampleGrossWage,
            ],
            'labourWelfareFund' => [
                'enabled' => $this->lwfEnabled,
                'stateCode' => $this->blankToNull($this->lwfStateCode),
                'deductionCycle' => $this->lwfDeductionCycle,
                'employeeContribution' => $this->lwfEmployeeContribution,
                'employerContribution' => $this->lwfEmployerContribution,
                'sampleEmployees' => $this->lwfSampleEmployees,
            ],
            'statutoryBonus' => [
                'enabled' => $this->bonusEnabled,
                'bonusPercent' => $this->bonusPercent,
                'eligibilityWageCeiling' => $this->bonusEligibilityWageCeiling,
                'calculationWageCeiling' => $this->bonusCalculationWageCeiling,
                'minimumWorkedDays' => $this->bonusMinimumWorkedDays,
                'sampleMonthlyWage' => $this->bonusSampleMonthlyWage,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function epfConfigForCalculator(): array
    {
        return [
            'wageCeiling' => $this->epfWageCeiling,
            'employeeContributionRate' => $this->epfEmployeeContributionRate,
            'employerContributionRate' => $this->epfEmployerContributionRate,
            'includeEmployerContributionInCtc' => $this->epfIncludeEmployerContributionInCtc,
            'includeEdliInCtc' => $this->epfIncludeEdliInCtc,
            'includeAdminChargesInCtc' => $this->epfIncludeAdminChargesInCtc,
            'proRateRestrictedPfWage' => $this->epfProRateRestrictedPfWage,
        ];
    }

    /**
     * @return list<array{from: float, to: float|null, amount: float}>
     */
    private function normalizedPtSlabs(): array
    {
        $slabs = [];
        foreach ($this->ptSlabs as $slab) {
            $from = trim((string) ($slab['from'] ?? ''));
            $to = trim((string) ($slab['to'] ?? ''));
            $amount = trim((string) ($slab['amount'] ?? ''));
            if ($from === '' && $to === '' && $amount === '') {
                continue;
            }
            $slabs[] = [
                'from' => (float) $from,
                'to' => $to === '' ? null : (float) $to,
                'amount' => (float) $amount,
            ];
        }

        return $slabs;
    }

    private function applyCategoryAndSub(string $category, string $subTab): void
    {
        $this->category = $category;
        $this->orgSubTab = $subTab;
    }

    private function composeOrganisationAddress(): string
    {
        $parts = array_values(array_filter([
            $this->addressLine1,
            $this->addressLine2,
            $this->city,
            $this->state,
            $this->zipCode,
        ], static fn (string $part): bool => trim($part) !== ''));

        return implode(', ', $parts);
    }

    private function resolveFilingAddress(): string
    {
        if ($this->filingAddress !== '') {
            return $this->filingAddress;
        }

        return $this->composeOrganisationAddress();
    }

    private function formatDatePreview(string $format): string
    {
        try {
            return now()->format($format);
        } catch (\Throwable) {
            return now()->format('d/m/Y');
        }
    }

    private function resetDepartmentForm(): void
    {
        $this->departmentName = '';
        $this->editingDepartmentId = null;
    }

    private function resetDesignationForm(): void
    {
        $this->designationName = '';
        $this->editingDesignationId = null;
    }

    private function resetLocationForm(): void
    {
        $this->editingLocationId = null;
        $this->locationName = '';
        $this->locationCode = '';
        $this->locationAddress = '';
        $this->locationCity = '';
        $this->locationState = '';
        $this->locationPincode = '';
        $this->locationCountry = '';
        $this->locationPhone = '';
        $this->locationEmail = '';
    }

    private function blankToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeTaxCode(?string $value): ?string
    {
        $normalized = $this->blankToNull($value);

        return $normalized !== null ? strtoupper($normalized) : null;
    }
}
