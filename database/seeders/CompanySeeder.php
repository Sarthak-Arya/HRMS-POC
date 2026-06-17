<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use RuntimeException;

class CompanySeeder extends Seeder
{
    public const DEMO_ADMIN_EMAIL = 'admin@softui.com';
    public const DEMO_PAYROLL_EMAIL = 'payroll@softui.com';

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
        foreach (self::companyDefinitions() as $definition) {
            $handlerId = User::query()
                ->where('email', $definition['handled_by_email'])
                ->value('id');

            if (!$handlerId) {
                throw new RuntimeException(
                    "Demo user [{$definition['handled_by_email']}] not found. Seed users before companies.",
                );
            }

            Company::firstOrCreate(
                ['company_name' => $definition['company_name']],
                [
                    'company_address' => $definition['company_address'],
                    'company_handled_by' => $handlerId,
                    'is_esi' => true,
                    'is_pf' => true,
                ],
            );
        }
    }
}
