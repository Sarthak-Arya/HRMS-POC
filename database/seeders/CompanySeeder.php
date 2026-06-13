<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /** @var list<int> */
    public const HANDLER_USER_IDS = [1, 2];

    /**
     * @return list<array{company_name: string, company_address: string, handled_by: int}>
     */
    public static function companyDefinitions(): array
    {
        return [
            ['company_name' => 'Aryans Tech Solutions', 'company_address' => '101 MG Road, Bengaluru', 'handled_by' => 1],
            ['company_name' => 'Northstar Manufacturing', 'company_address' => '22 Industrial Estate, Pune', 'handled_by' => 1],
            ['company_name' => 'Bluewave Services', 'company_address' => '5 Park Street, Kolkata', 'handled_by' => 2],
            ['company_name' => 'Summit Retail Group', 'company_address' => '88 Ring Road, Ahmedabad', 'handled_by' => 2],
        ];
    }

    public function run(): void
    {
        foreach (self::companyDefinitions() as $definition) {
            Company::firstOrCreate(
                [
                    'company_name' => $definition['company_name'],
                    'company_handled_by' => $definition['handled_by'],
                ],
                [
                    'company_address' => $definition['company_address'],
                    'is_esi' => true,
                    'is_pf' => true,
                ],
            );
        }
    }
}
