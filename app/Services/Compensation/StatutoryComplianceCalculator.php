<?php

namespace App\Services\Compensation;

/**
 * Centralized statutory calculation engine for EPF, ESI, Professional Tax,
 * Labour Welfare Fund and Statutory Bonus.
 *
 * Rates aligned with EPFO / ESIC rules and Code on Social Security practice:
 * - EPF/EPS employee+employer 12% each on PF wages
 * - EPS employer share 8.33% of PF wages, capped at wage ceiling (₹15,000)
 * - EDLI 0.50% of PF wages capped at wage ceiling
 * - EPF admin charges 0.50% of PF wages (establishment minimum ₹75)
 * - ESI employee 0.75% / employer 3.25% on gross wages up to ₹21,000
 */
class StatutoryComplianceCalculator
{
    public const PF_WAGE_CEILING = 15000.0;

    public const ESI_WAGE_CEILING = 21000.0;

    public const ESI_DAILY_WAGE_EXEMPTION = 176.0;

    public const EPS_SHARE_PERCENT = 8.33;

    public const EDLI_PERCENT = 0.50;

    public const PF_ADMIN_PERCENT = 0.50;

    public const PF_ADMIN_MINIMUM = 75.0;

    public const STATUTORY_BONUS_ELIGIBILITY_CEILING = 21000.0;

    public const STATUTORY_BONUS_CALC_CEILING = 7000.0;

    public const STATUTORY_BONUS_MIN_PERCENT = 8.33;

    public const STATUTORY_BONUS_MAX_PERCENT = 20.0;

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     pf_wage: float,
     *     restricted_pf_wage: float,
     *     employee_epf: float,
     *     employer_eps: float,
     *     employer_epf: float,
     *     employer_total_12: float,
     *     edli: float,
     *     admin_charges: float,
     *     employer_outflow: float,
     *     include_edli_in_ctc: bool,
     *     include_admin_in_ctc: bool,
     *     include_employer_in_ctc: bool,
     *     ctc_employer_components: float,
     *     lines: list<array{label: string, amount: float}>
     * }
     */
    public function calculateEpf(float $pfWage, array $config = []): array
    {
        $wageCeiling = (float) ($config['wageCeiling'] ?? self::PF_WAGE_CEILING);
        $employeeRateCode = (string) ($config['employeeContributionRate'] ?? '12_actual');
        $employerRateCode = (string) ($config['employerContributionRate'] ?? '12_actual');
        $includeEmployerInCtc = (bool) ($config['includeEmployerContributionInCtc'] ?? true);
        $includeEdliInCtc = (bool) ($config['includeEdliInCtc'] ?? false);
        $includeAdminInCtc = (bool) ($config['includeAdminChargesInCtc'] ?? false);
        $proRateRestricted = (bool) ($config['proRateRestrictedPfWage'] ?? false);
        $workedDays = (float) ($config['workedDays'] ?? 30);
        $totalDays = max(1.0, (float) ($config['totalDays'] ?? 30));

        $actualPfWage = max(0.0, round($pfWage, 2));
        $restrictedPfWage = min($actualPfWage, $wageCeiling);

        if ($proRateRestricted && $restrictedPfWage > 0) {
            $restrictedPfWage = round($restrictedPfWage * ($workedDays / $totalDays), 2);
        }

        $employeeBase = str_contains($employeeRateCode, 'restricted') ? $restrictedPfWage : $actualPfWage;
        $employerBase = str_contains($employerRateCode, 'restricted') ? $restrictedPfWage : $actualPfWage;

        $employeePercent = 12.0;
        $employerPercent = 12.0;

        $employeeEpf = $this->roundRupee($employeeBase * ($employeePercent / 100));
        $employerTotal = $this->roundRupee($employerBase * ($employerPercent / 100));

        // EPS is always computed on PF wages subject to the statutory ceiling.
        $epsWage = min($employerBase, $wageCeiling);
        if ($proRateRestricted && (float) ($config['workedDays'] ?? 0) > 0) {
            $epsWage = min($restrictedPfWage, $wageCeiling);
        }
        $employerEps = $this->roundRupee($epsWage * (self::EPS_SHARE_PERCENT / 100));
        $employerEps = min($employerEps, $employerTotal);
        $employerEpf = max(0.0, $employerTotal - $employerEps);

        $edliBase = min($actualPfWage, $wageCeiling);
        $edli = $this->roundRupee($edliBase * (self::EDLI_PERCENT / 100));

        // Admin charges apply on PF wages of the establishment (per employee share shown in preview).
        $admin = $this->roundRupee($actualPfWage * (self::PF_ADMIN_PERCENT / 100));
        $admin = max($admin, 0.0);

        $selectedEdli = ($includeEmployerInCtc && $includeEdliInCtc) ? $edli : 0.0;
        $selectedAdmin = ($includeEmployerInCtc && $includeAdminInCtc) ? $admin : 0.0;
        $employerOutflow = $employerTotal + $selectedEdli + $selectedAdmin;

        $ctcEmployer = 0.0;
        if ($includeEmployerInCtc) {
            $ctcEmployer += $employerTotal;
            if ($includeEdliInCtc) {
                $ctcEmployer += $edli;
            }
            if ($includeAdminInCtc) {
                $ctcEmployer += $admin;
            }
        }

        return [
            'pf_wage' => $actualPfWage,
            'restricted_pf_wage' => $restrictedPfWage,
            'employee_epf' => $employeeEpf,
            'employer_eps' => $employerEps,
            'employer_epf' => $employerEpf,
            'employer_total_12' => $employerTotal,
            'edli' => $edli,
            'admin_charges' => $admin,
            'selected_edli' => $selectedEdli,
            'selected_admin_charges' => $selectedAdmin,
            'has_selected_additional_charges' => ($selectedEdli + $selectedAdmin) > 0,
            'employer_outflow' => $employerOutflow,
            'include_edli_in_ctc' => $includeEdliInCtc,
            'include_admin_in_ctc' => $includeAdminInCtc,
            'include_employer_in_ctc' => $includeEmployerInCtc,
            'ctc_employer_components' => $ctcEmployer,
            'lines' => [
                [
                    'label' => sprintf('EPF (%.0f%% of %s)', $employeePercent, $this->formatInr($employeeBase)),
                    'amount' => $employeeEpf,
                ],
                [
                    'label' => sprintf(
                        'EPS (%.2f%% of %s [Max of %s])',
                        self::EPS_SHARE_PERCENT,
                        $this->formatInr($employerBase),
                        $this->formatInr($wageCeiling),
                    ),
                    'amount' => $employerEps,
                ],
                [
                    'label' => sprintf('EPF (%.0f%% of %s - EPS)', $employerPercent, $this->formatInr($employerBase)),
                    'amount' => $employerEpf,
                ],
            ],
        ];
    }

    /**
     * Minimum admin charges apply at establishment level for the month.
     */
    public function applyEstablishmentAdminMinimum(float $totalAdminCharges): float
    {
        return max($totalAdminCharges, self::PF_ADMIN_MINIMUM);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     eligible: bool,
     *     gross_wage: float,
     *     employee_esi: float,
     *     employer_esi: float,
     *     total: float,
     *     employee_exempt: bool,
     *     reason: string|null
     * }
     */
    public function calculateEsi(float $grossWage, array $config = []): array
    {
        $ceiling = (float) ($config['wageCeiling'] ?? self::ESI_WAGE_CEILING);
        $employeePercent = (float) ($config['employeeContributionPercent'] ?? 0.75);
        $employerPercent = (float) ($config['employerContributionPercent'] ?? 3.25);
        $daysInMonth = max(1.0, (float) ($config['daysInMonth'] ?? 30));
        $forceCovered = (bool) ($config['forceCovered'] ?? false);

        $gross = max(0.0, round($grossWage, 2));
        $eligible = $forceCovered || $gross <= $ceiling;

        if (! $eligible) {
            return [
                'eligible' => false,
                'gross_wage' => $gross,
                'employee_esi' => 0.0,
                'employer_esi' => 0.0,
                'total' => 0.0,
                'employee_exempt' => false,
                'reason' => 'Gross wages exceed ESI wage ceiling of '.$this->formatInr($ceiling).'.',
            ];
        }

        $dailyWage = $gross / $daysInMonth;
        $employeeExempt = $dailyWage <= self::ESI_DAILY_WAGE_EXEMPTION;

        $employeeEsi = $employeeExempt ? 0.0 : $this->roundRupee($gross * ($employeePercent / 100));
        $employerEsi = $this->roundRupee($gross * ($employerPercent / 100));

        return [
            'eligible' => true,
            'gross_wage' => $gross,
            'employee_esi' => $employeeEsi,
            'employer_esi' => $employerEsi,
            'total' => $employeeEsi + $employerEsi,
            'employee_exempt' => $employeeExempt,
            'reason' => $employeeExempt
                ? 'Employee contribution exempt when average daily wage is ≤ ₹'.number_format(self::ESI_DAILY_WAGE_EXEMPTION, 0).'.'
                : null,
        ];
    }

    /**
     * @param  list<array{from?: float|int, to?: float|int|null, amount?: float|int}>  $slabs
     * @return array{amount: float, matched: bool}
     */
    public function calculateProfessionalTax(float $grossWage, array $slabs = []): array
    {
        $gross = max(0.0, $grossWage);
        $matchedAmount = 0.0;
        $matched = false;

        foreach ($slabs as $slab) {
            $from = (float) ($slab['from'] ?? 0);
            $to = array_key_exists('to', $slab) && $slab['to'] !== null && $slab['to'] !== ''
                ? (float) $slab['to']
                : null;
            $amount = (float) ($slab['amount'] ?? 0);

            $inRange = $gross >= $from && ($to === null || $gross <= $to);
            if ($inRange) {
                $matchedAmount = $amount;
                $matched = true;
                break;
            }
        }

        return [
            'amount' => $matched ? $this->roundRupee($matchedAmount) : 0.0,
            'matched' => $matched,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     employee_total: float,
     *     employer_total: float,
     *     combined_total: float,
     *     per_employee_employee: float,
     *     per_employee_employer: float
     * }
     */
    public function calculateLabourWelfareFund(int $employeeCount, array $config = []): array
    {
        $count = max(0, $employeeCount);
        $employeeShare = max(0.0, (float) ($config['employeeContribution'] ?? 0));
        $employerShare = max(0.0, (float) ($config['employerContribution'] ?? 0));

        return [
            'employee_total' => $this->roundRupee($employeeShare * $count),
            'employer_total' => $this->roundRupee($employerShare * $count),
            'combined_total' => $this->roundRupee(($employeeShare + $employerShare) * $count),
            'per_employee_employee' => $employeeShare,
            'per_employee_employer' => $employerShare,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     eligible: bool,
     *     annual_bonus: float,
     *     monthly_bonus_provision: float,
     *     calculation_wage: float,
     *     reason: string|null
     * }
     */
    public function calculateStatutoryBonus(float $monthlyWage, array $config = []): array
    {
        $eligibilityCeiling = (float) ($config['eligibilityWageCeiling'] ?? self::STATUTORY_BONUS_ELIGIBILITY_CEILING);
        $calcCeiling = (float) ($config['calculationWageCeiling'] ?? self::STATUTORY_BONUS_CALC_CEILING);
        $bonusPercent = (float) ($config['bonusPercent'] ?? self::STATUTORY_BONUS_MIN_PERCENT);
        $minimumDays = (int) ($config['minimumWorkedDays'] ?? 30);
        $workedDays = (int) ($config['workedDays'] ?? $minimumDays);
        $minimumWage = (float) ($config['minimumWage'] ?? 0);

        $wage = max(0.0, $monthlyWage);
        $bonusPercent = min(max($bonusPercent, self::STATUTORY_BONUS_MIN_PERCENT), self::STATUTORY_BONUS_MAX_PERCENT);

        if ($wage > $eligibilityCeiling) {
            return [
                'eligible' => false,
                'annual_bonus' => 0.0,
                'monthly_bonus_provision' => 0.0,
                'calculation_wage' => 0.0,
                'reason' => 'Monthly wage exceeds statutory bonus eligibility ceiling of '.$this->formatInr($eligibilityCeiling).'.',
            ];
        }

        if ($workedDays < $minimumDays) {
            return [
                'eligible' => false,
                'annual_bonus' => 0.0,
                'monthly_bonus_provision' => 0.0,
                'calculation_wage' => 0.0,
                'reason' => 'Employee has not worked the minimum '.$minimumDays.' days required for bonus eligibility.',
            ];
        }

        // Payment of Bonus Act: computed on wage not exceeding ₹7,000/month or minimum wage, whichever higher.
        $calcBase = max($calcCeiling, $minimumWage);
        $annualBonus = $this->roundRupee($calcBase * 12 * ($bonusPercent / 100));

        return [
            'eligible' => true,
            'annual_bonus' => $annualBonus,
            'monthly_bonus_provision' => $this->roundRupee($annualBonus / 12),
            'calculation_wage' => $calcBase,
            'reason' => null,
        ];
    }

    /**
     * EPFO nearest-rupee rule: 50 paise or more → next rupee.
     */
    public function roundRupee(float $amount): float
    {
        return (float) round($amount, 0, PHP_ROUND_HALF_UP);
    }

    private function formatInr(float $amount): string
    {
        return '₹ '.number_format($amount, 0);
    }
}
