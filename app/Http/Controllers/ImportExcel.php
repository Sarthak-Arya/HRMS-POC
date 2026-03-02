<?php

namespace App\Http\Controllers;
use App\Imports\EmployeeImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;


class ImportExcel extends Controller
{

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        $import = new EmployeeImport();
        try {
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('excel_file'));
            $errors = $import->getErrors();
            if (!empty($errors)) {
                session()->flash('error', 'Import failed. No data was imported. Errors: ' . json_encode($errors));
                return redirect()->back();
            }
            $import->processImport();
            session()->flash('success', 'Data imported successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('error', 'Error importing data: ' . $e->getMessage());
            return redirect()->back();
        }
    }

}