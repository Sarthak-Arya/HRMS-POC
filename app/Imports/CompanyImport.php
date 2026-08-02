<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;
use App\Models\Company;

class CompanyImport implements ToModel, WithSkipDuplicates, ToCollection
{

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            return new Company(attributes: [
                'company_name' => $row['Company Name'],
                'company_address' => $row['Company Address'],
            ]);
        }
    }

    public function model(array $rows)
    {
        foreach ($rows as $row) {
            return new Company([
                'company_name' => $row['Company Name'],
                'company_address' => $row['Company Address'],
            ]);
        }
    }
}
