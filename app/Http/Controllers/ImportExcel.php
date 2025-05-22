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

        try {
            Excel::import(new EmployeeImport, $request->file('excel_file'));
            session()->flash('success', 'Data imported successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            session()->flash('error', 'Error importing data: ' . $e->getMessage());
            return redirect()->back();
        }
    }

}