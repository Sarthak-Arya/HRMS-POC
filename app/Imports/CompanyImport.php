<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;
use App\Models\Company;
use Illuminate\Support\Facades\Log;



class CompanyImport implements ToModel, WithSkipDuplicates, ToCollection
{

    public function collection(Collection $rows)
    {
        Log::info("Inside CompanyImport collection function");
        Log::info(message: "The number rows are: " . $rows->count());
        foreach ($rows as $row) {
            Log::info("The row is: " . json_encode($row));
            return new Company(attributes: [
                'company_name' => $row['Company Name'],
                'company_address' => $row['Company Address'],
            ]);
        }
    }

    public function model(array $rows)
    {
        Log::info("Inside CompanyImport model function");
        foreach ($rows as $row) {
            Log::info("The row is: " . json_encode($row));
            return new Company([
                'company_name' => $row['Company Name'],
                'company_address' => $row['Company Address'],
            ]);
        }
    }
}
