<?php

namespace App\Services\Auth;

use App\Enums\Permission;
use App\Models\Company;
use App\Models\User;
use App\Services\Setup\CompanySetupProgressService;
use Illuminate\Support\Collection;

class AuthLandingService
{
    public function __construct(
        private readonly CompanySetupProgressService $setupProgress,
    ) {}

    /**
     * Roles allowed to browse / manage multiple companies via the company picker.
     */
    public function canManageMultipleCompanies(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasPermission(Permission::CompaniesManageMultiple);
    }

    /**
     * @return Collection<int, Company>
     */
    public function accessibleCompanies(User $user): Collection
    {
        if ($user->isAdmin()) {
            return Company::query()->orderBy('company_name')->get();
        }

        if ($user->b2b_firm_id !== null) {
            return Company::query()
                ->where('b2b_firm_id', $user->b2b_firm_id)
                ->orderBy('company_name')
                ->get();
        }

        if ($user->company_id !== null) {
            return Company::query()
                ->whereKey($user->company_id)
                ->orderBy('company_name')
                ->get();
        }

        return collect();
    }

    public function primaryCompany(User $user): ?Company
    {
        return $this->accessibleCompanies($user)->first();
    }

    /**
     * Resolve post-login / post-signup destination.
     */
    public function homeRoute(User $user): string
    {
        $linkedEmployee = app(\App\Services\Ess\EmployeeContext::class)->linkedEmployee($user);
        $isEssOnly = $user->hasPermission(Permission::EssAccess)
            && ! $user->hasPermission(Permission::DashboardView);

        if ($isEssOnly && $linkedEmployee) {
            session()->put('companyId', (string) $linkedEmployee->company_id);
            session()->put('company_id', (string) $linkedEmployee->company_id);

            return route('ess.home', ['company_id' => $linkedEmployee->company_id]);
        }

        $companies = $this->accessibleCompanies($user);
        $canManageMultiple = $this->canManageMultipleCompanies($user);

        if ($companies->isEmpty()) {
            if ($user->hasPermission(Permission::CompaniesCreate)) {
                return route('add-company-details');
            }

            if ($canManageMultiple) {
                return route('view-companies');
            }

            if ($linkedEmployee) {
                session()->put('companyId', (string) $linkedEmployee->company_id);

                return route('ess.home', ['company_id' => $linkedEmployee->company_id]);
            }

            return route('profile');
        }

        // Single-company users (HR, etc.) never see the multi-company picker.
        if (! $canManageMultiple) {
            return $this->companyEntryRoute($user, $companies->first());
        }

        // Multi-company roles: if they only have one company still being set up,
        // take them straight into Getting Started (Zoho-style onboarding).
        if ($companies->count() === 1) {
            $company = $companies->first();
            if (
                $user->hasPermission(Permission::SettingsView)
                && ! $this->setupProgress->isSetupComplete($company)
            ) {
                session()->put('companyId', (string) $company->id);

                return route('getting-started', ['company_id' => $company->id]);
            }
        }

        return route('view-companies');
    }

    private function companyEntryRoute(User $user, Company $company): string
    {
        session()->put('companyId', (string) $company->id);

        $isEssOnly = $user->hasPermission(Permission::EssAccess)
            && ! $user->hasPermission(Permission::DashboardView);

        if ($isEssOnly) {
            return route('ess.home', ['company_id' => $company->id]);
        }

        if (
            $user->hasPermission(Permission::SettingsView)
            && ! $this->setupProgress->isSetupComplete($company)
        ) {
            return route('getting-started', ['company_id' => $company->id]);
        }

        if ($user->hasPermission(Permission::DashboardView)) {
            return route('dashboard', ['company_id' => $company->id]);
        }

        if ($user->hasPermission(Permission::EssAccess)) {
            return route('ess.home', ['company_id' => $company->id]);
        }

        return route('getting-started', ['company_id' => $company->id]);
    }
}
