<?php

namespace App\Services\Settings\Adapters;

use App\Enums\Settings\CompanySettingsSection;
use App\Models\Company;
use App\Services\Observability\DomainTelemetry;
use App\Services\Settings\CompanySettingsService;

class CompanyProfileSettingsAdapter
{
    public function __construct(
        private readonly CompanySettingsService $settingsService,
        private readonly DomainTelemetry $telemetry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function read(int $companyId): array
    {
        $company = Company::query()->findOrFail($companyId);
        $preferences = $this->settingsService->getSection($companyId, CompanySettingsSection::CompanyProfile);

        $addressLine1 = $preferences['addressLine1'] ?? null;
        $addressLine2 = $preferences['addressLine2'] ?? null;
        $city = $preferences['city'] ?? null;

        // Backfill address lines from the legacy single company_address column when needed.
        if (($addressLine1 === null || $addressLine1 === '') && filled($company->company_address)) {
            $addressLine1 = $company->company_address;
        }

        return [
            'companyName' => $company->company_name,
            'address' => $company->company_address,
            'addressLine1' => $addressLine1,
            'addressLine2' => $addressLine2,
            'city' => $city,
            'isEsi' => (bool) $company->is_esi,
            'isPf' => (bool) $company->is_pf,
            'gstNumber' => $preferences['gstNumber'] ?? null,
            'zipCode' => $preferences['zipCode'] ?? null,
            'state' => $preferences['state'] ?? null,
            'country' => $preferences['country'] ?? 'India',
            'businessLocation' => $preferences['businessLocation'] ?? ($preferences['country'] ?? 'India'),
            'industry' => $preferences['industry'] ?? null,
            'logoPath' => $preferences['logoPath'] ?? null,
            'filingAddress' => $preferences['filingAddress'] ?? null,
            'filingLocationId' => $preferences['filingLocationId'] ?? null,
            'esiCode' => $preferences['esiCode'] ?? null,
            'esiContribution' => $preferences['esiContribution'] ?? null,
            'esiCoverageStartDate' => $preferences['esiCoverageStartDate'] ?? null,
            'esiCoverageEndDate' => $preferences['esiCoverageEndDate'] ?? null,
            'pfCode' => $preferences['pfCode'] ?? null,
            'pfContribution' => $preferences['pfContribution'] ?? null,
            'pfCoverageStartDate' => $preferences['pfCoverageStartDate'] ?? null,
            'pfCoverageEndDate' => $preferences['pfCoverageEndDate'] ?? null,
            'preferences' => [
                'timezone' => $preferences['timezone'] ?? 'Asia/Kolkata',
                'fiscalYearStartMonth' => $preferences['fiscalYearStartMonth'] ?? 4,
                'payrollCurrency' => $preferences['payrollCurrency'] ?? 'INR',
                'dateFormat' => $preferences['dateFormat'] ?? 'd/m/Y',
                'fieldSeparator' => $preferences['fieldSeparator'] ?? '/',
                'primaryColor' => $preferences['primaryColor'] ?? '#0058be',
                'secondaryColor' => $preferences['secondaryColor'] ?? '#131b2e',
                'fontFamily' => $preferences['fontFamily'] ?? 'Inter',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $companyFields
     * @param  array<string, mixed>  $preferences
     */
    public function write(
        int $companyId,
        array $companyFields,
        array $preferences,
        int $expectedVersion,
        ?int $actorUserId = null,
    ): void {
        $company = Company::query()->findOrFail($companyId);

        $addressLine1 = (string) ($companyFields['addressLine1'] ?? '');
        $addressLine2 = (string) ($companyFields['addressLine2'] ?? '');
        $city = (string) ($companyFields['city'] ?? '');
        $state = (string) ($companyFields['state'] ?? '');
        $zipCode = (string) ($companyFields['zipCode'] ?? '');
        $country = (string) ($companyFields['country'] ?? ($companyFields['businessLocation'] ?? 'India'));

        $composedAddress = $this->composeAddress(
            $addressLine1,
            $addressLine2,
            $city,
            $state,
            $zipCode,
            $country,
        );

        $company->update([
            'company_name' => $companyFields['companyName'],
            'company_address' => $composedAddress !== ''
                ? $composedAddress
                : ($companyFields['address'] ?? $company->company_address),
            'is_esi' => (bool) ($companyFields['isEsi'] ?? false),
            'is_pf' => (bool) ($companyFields['isPf'] ?? false),
        ]);

        $this->telemetry->emit('company.updated', 'audit', 'success', [
            'company.id' => $companyId,
        ]);

        $this->settingsService->updateSection(
            $companyId,
            CompanySettingsSection::CompanyProfile,
            array_merge($preferences, [
                'gstNumber' => $companyFields['gstNumber'] ?? null,
                'zipCode' => $companyFields['zipCode'] ?? null,
                'state' => $companyFields['state'] ?? null,
                'country' => $country !== '' ? $country : null,
                'city' => $city !== '' ? $city : null,
                'addressLine1' => $addressLine1 !== '' ? $addressLine1 : null,
                'addressLine2' => $addressLine2 !== '' ? $addressLine2 : null,
                'businessLocation' => $companyFields['businessLocation'] ?? $country,
                'industry' => $companyFields['industry'] ?? null,
                'logoPath' => $companyFields['logoPath'] ?? ($preferences['logoPath'] ?? null),
                'filingAddress' => $companyFields['filingAddress'] ?? null,
                'filingLocationId' => $companyFields['filingLocationId'] ?? null,
                'esiCode' => $companyFields['esiCode'] ?? null,
                'esiContribution' => $companyFields['esiContribution'] ?? null,
                'esiCoverageStartDate' => $companyFields['esiCoverageStartDate'] ?: null,
                'esiCoverageEndDate' => $companyFields['esiCoverageEndDate'] ?: null,
                'pfCode' => $companyFields['pfCode'] ?? null,
                'pfContribution' => $companyFields['pfContribution'] ?? null,
                'pfCoverageStartDate' => $companyFields['pfCoverageStartDate'] ?: null,
                'pfCoverageEndDate' => $companyFields['pfCoverageEndDate'] ?: null,
            ]),
            $expectedVersion,
            $actorUserId,
            'Company profile updated',
        );
    }

    private function composeAddress(
        string $line1,
        string $line2,
        string $city,
        string $state,
        string $zipCode,
        string $country,
    ): string {
        $parts = array_values(array_filter([
            $line1,
            $line2,
            $city,
            $state,
            $zipCode,
            $country,
        ], static fn (string $part): bool => trim($part) !== ''));

        return implode(', ', $parts);
    }
}
