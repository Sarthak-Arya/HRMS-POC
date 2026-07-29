<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\B2bFirm;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use RuntimeException;

class CompanySeeder extends Seeder
{
    public const DEMO_ADMIN_EMAIL = 'admin@softui.com';
    public const DEMO_PAYROLL_EMAIL = 'payroll@softui.com';
    public const DEMO_B2B_FIRM_NAME = 'SoftUI Payroll Advisors';

    /**
     * @return list<array{company_name: string, company_address: string, handled_by_email: string}>
     */
    public static function companyDefinitions(): array
    {
        return [
            [
                'company_name' => 'Aryans Tech Solutions',
                'company_address' => '101 MG Road, Bengaluru',
                'handled_by_email' => self::DEMO_ADMIN_EMAIL,
            ],
            [
                'company_name' => 'Northstar Manufacturing',
                'company_address' => '22 Industrial Estate, Pune',
                'handled_by_email' => self::DEMO_ADMIN_EMAIL,
            ],
            [
                'company_name' => 'Bluewave Services',
                'company_address' => '5 Park Street, Kolkata',
                'handled_by_email' => self::DEMO_PAYROLL_EMAIL,
            ],
            [
                'company_name' => 'Summit Retail Group',
                'company_address' => '88 Ring Road, Ahmedabad',
                'handled_by_email' => self::DEMO_PAYROLL_EMAIL,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function demoCompanyNames(): array
    {
        return array_column(self::companyDefinitions(), 'company_name');
    }

    public static function demoCompaniesQuery(): Builder
    {
        return Company::query()->whereIn('company_name', self::demoCompanyNames());
    }

    public function run(): void
    {
        $firm = B2bFirm::firstOrCreate(
            ['name' => self::DEMO_B2B_FIRM_NAME],
        );

        $payrollManager = User::query()
            ->where('email', self::DEMO_PAYROLL_EMAIL)
            ->first();

        if ($payrollManager) {
            $payrollManager->forceFill([
                'b2b_firm_id' => $firm->id,
                'company_id' => null,
            ])->save();

            if (! $payrollManager->hasRole(UserRole::B2bStaff) && ! $payrollManager->hasRole(UserRole::B2bAdmin)) {
                $payrollManager->syncRoles([UserRole::B2bStaff->value]);
            }
        }

        foreach (self::companyDefinitions() as $definition) {
            $handler = User::query()
                ->where('email', $definition['handled_by_email'])
                ->first();

            if (! $handler) {
                throw new RuntimeException(
                    "Demo user [{$definition['handled_by_email']}] not found. Seed users before companies.",
                );
            }

            $isPlatformAdmin = $definition['handled_by_email'] === self::DEMO_ADMIN_EMAIL;

            Company::firstOrCreate(
                ['company_name' => $definition['company_name']],
                [
                    'company_address' => $definition['company_address'],
                    'b2b_firm_id' => $isPlatformAdmin ? null : $firm->id,
                    'is_esi' => true,
                    'is_pf' => true,
                ],
            );
        }
    }
}
