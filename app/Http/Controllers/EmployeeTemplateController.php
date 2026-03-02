<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Response;

class EmployeeTemplateController extends Controller
{
    public function downloadTemplate()
    {
        try {
            // Log the download action
            Log::info('Employee template download requested by user: ' . auth()->id());

            // Get all column names from employees table
            $columns = Schema::getColumnListing('employees');
            
            // Remove timestamps columns as they're auto-generated
            $columns = array_filter($columns, function($column) {
                return !in_array($column, ['created_at', 'updated_at']);
            });

            // Create new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers with proper formatting
            $headers = [
                'first_name' => 'First Name*',
                'middle_name' => 'Middle Name',
                'last_name' => 'Last Name*',
                'father_name' => 'Father Name*',
                'gender' => 'Gender* (M/F/O)',
                'dob' => 'Date of Birth* (YYYY-MM-DD)',
                'company_code' => 'Company Code',
                'joining_date' => 'Joining Date* (YYYY-MM-DD)',
                'leaving_date' => 'Leaving Date (YYYY-MM-DD)',
                'esi_no' => 'ESI Number',
                'pf_no' => 'PF Number',
                'department' => 'Department*',
                'designation' => 'Designation*',
                'location' => 'Location',
                'email' => 'Email',
                'address1' => 'Address Line 1',
                'address2' => 'Address Line 2',
                'city' => 'City',
                'state' => 'State',
                'country' => 'Country',
                'zip_code' => 'Zip Code',
                'permanent_address1' => 'Permanent Address Line 1',
                'permanent_address2' => 'Permanent Address Line 2',
                'permanent_city' => 'Permanent City',
                'permanent_state' => 'Permanent State',
                'permanent_country' => 'Permanent Country',
                'permanent_zip_code' => 'Permanent Zip Code',
                'emergency_contact_name' => 'Emergency Contact Name',
                'emergency_contact_phone' => 'Emergency Contact Phone',
                'emergency_contact_relation' => 'Emergency Contact Relation',
                'emergency_contact_address1' => 'Emergency Contact Address Line 1',
                'emergency_contact_address2' => 'Emergency Contact Address Line 2',
                'emergency_contact_city' => 'Emergency Contact City',
                'emergency_contact_state' => 'Emergency Contact State',
                'emergency_contact_country' => 'Emergency Contact Country',
                'emergency_contact_zip_code' => 'Emergency Contact Zip Code',
                'emergency_contact_email' => 'Emergency Contact Email',
                'pf_status' => 'PF Status (A=active/I=inactive/P=pending)',
                'esi_status' => 'ESI Status (A=active/I=inactive/P=pending)',
                'wage_type' => 'Wage Type (M=monthly/D=daily/H=hourly)',
                'uan_no' => 'UAN Number'
            ];

            // Add headers to the first row
            $column = 'A';
            foreach ($headers as $key => $header) {
                $sheet->setCellValue($column . '1', $header);
                $column++;
            }

            // Style the header row
            $headerRange = 'A1:' . $column . '1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle($headerRange)->getFill()->getStartColor()->setRGB('E2E8F0');

            // Auto-size columns
            foreach (range('A', $column) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Add some sample data in row 2 for guidance
            $sampleData = [
                'John',
                'Michael',
                'Doe',
                'Robert Doe',
                'M',
                '1990-01-15',
                'EMP001',
                '2023-01-01',
                '',
                'ESI123456',
                'PF789012',
                'IT Department',
                'Software Engineer',
                'Main Office',
                'john.doe@example.com',
                '123 Main Street',
                'Apt 4B',
                'New York',
                'NY',
                'USA',
                '10001',
                '123 Main Street',
                'Apt 4B',
                'New York',
                'NY',
                'USA',
                '10001',
                'Jane Doe',
                '+1-555-0123',
                'Spouse',
                '123 Main Street',
                'Apt 4B',
                'New York',
                'NY',
                'USA',
                '10001',
                'jane.doe@example.com',
                'active',
                'active',
                'monthly'
            ];

            $column = 'A';
            foreach ($sampleData as $index => $value) {
                $sheet->setCellValue($column . '2', $value);
                $column++;
            }

            // Style sample data row
            $sampleRange = 'A2:' . $column . '2';
            $sheet->getStyle($sampleRange)->getFont()->setItalic(true);
            $sheet->getStyle($sampleRange)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK));

            // Create the Excel file in memory
            $writer = new Xlsx($spreadsheet);
            
            // Generate filename with current date
            $filename = 'employee_template_' . now()->format('Y_m_d') . '.xlsx';

            // Set headers for download
            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ];

            // Create response with file content
            ob_start();
            $writer->save('php://output');
            $content = ob_get_clean();

            Log::info('Employee template downloaded successfully. File: ' . $filename);

            return Response::make($content, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error generating employee template: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate template. Please try again.');
        }
    }
} 