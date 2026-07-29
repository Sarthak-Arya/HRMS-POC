<?php

namespace App\Http\Livewire;

use App\Models\Company;
use App\Services\Auth\AuthLandingService;
use App\Services\Setup\CompanySetupProgressService;
use Livewire\Component;

class GettingStarted extends Component
{
    public string $companyId = '';

    /** @var array<string, mixed> */
    public array $progress = [];

    public bool $expandedStatutory = true;

    public function mount(
        ?string $company_id = null,
        CompanySetupProgressService $progressService,
    ): void {
        $this->companyId = (string) ($company_id ?? session('companyId', ''));

        abort_if($this->companyId === '', 404);

        $company = Company::query()->findOrFail($this->companyId);
        $user = auth()->user();

        abort_unless($user && $user->canAccessCompany($company), 403);

        session()->put('companyId', $this->companyId);

        $this->progress = $progressService->forCompany($company);
    }

    public function refreshProgress(CompanySetupProgressService $progressService): void
    {
        $this->progress = $progressService->forCompany((int) $this->companyId);
    }

    public function goToDashboard()
    {
        return redirect()->route('dashboard', ['company_id' => $this->companyId]);
    }

    public function render(AuthLandingService $landingService)
    {
        return view('livewire.getting-started', [
            'canManageMultipleCompanies' => $landingService->canManageMultipleCompanies(auth()->user()),
            'progress' => $this->progress,
        ]);
    }
}
