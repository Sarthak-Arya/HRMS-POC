<?php

use App\Http\Livewire\AttendanceEntry;
use App\Http\Livewire\EmployeeList;
use Illuminate\Support\Facades\Route;

use App\Http\Livewire\Auth\ForgotPassword;
use App\Http\Livewire\Auth\ResetPassword;
use App\Http\Livewire\Auth\SignUp;
use App\Http\Livewire\Auth\Login;
use App\Http\Livewire\Dashboard;
use App\Http\Livewire\Billing;
use App\Http\Livewire\Profile;
use App\Http\Livewire\Tables;
use App\Http\Livewire\StaticSignIn;
use App\Http\Livewire\StaticSignUp;
use App\Http\Livewire\Rtl;
use App\Http\Livewire\AddEmployeeDetails;
use App\Http\Livewire\ViewEmployeeDetails;
use App\Http\Livewire\AddCompanyDetails;
use App\Http\Livewire\ViewCompanies;
use App\Http\Livewire\CompensationHub;
use App\Http\Livewire\EmployeeCompensation;
use App\Http\Livewire\SalaryGenerator;

use App\Http\Middleware\CompanyAccessMiddleware;

use App\Http\Livewire\LaravelExamples\UserProfile;
use App\Http\Livewire\LaravelExamples\UserManagement;


use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get('/', function () {
    return redirect('/login');
});

Route::get('/sign-up', SignUp::class)->name('sign-up');
Route::get('/login', Login::class)->name('login');

Route::get('/login/forgot-password', ForgotPassword::class)->name('forgot-password');

Route::get('/reset-password/{id}', ResetPassword::class)->name('reset-password')->middleware('signed');

Route::middleware('auth')->group(function () {
    Route::get('/billing', Billing::class)->name('billing');
    Route::get('/profile', Profile::class)->name('profile');
    Route::get('/laravel-user-profile', UserProfile::class)->name('user-profile');
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('/laravel-user-management', UserManagement::class)->name('user-management');
    });
    Route::middleware('permission:companies.create')->group(function () {
        Route::get('/add-company-details', AddCompanyDetails::class)->name('add-company-details');
    });
    Route::middleware('permission:companies.view')->group(function () {
        Route::get('/view-companies', ViewCompanies::class)->name('view-companies');
    });
    Route::middleware('permission:employees.import')->group(function () {
        Route::post('/import-excel', [App\Http\Controllers\ImportExcel::class, 'import'])->name('import.excel');
        Route::get('/download-employee-template', [App\Http\Controllers\EmployeeTemplateController::class, 'downloadTemplate'])->name('download.template');
    });

    Route::prefix('{company_id}')->group(function () {
        Route::middleware([CompanyAccessMiddleware::class])->group(function () {
            Route::middleware('permission:dashboard.view')->group(function () {
                Route::get('/dashboard', action: Dashboard::class)->name('dashboard');
            });
            Route::middleware('permission:employees.create,employees.edit')->group(function () {
                Route::get('/add-employee-details', action: AddEmployeeDetails::class)->name('add-employee-details');
                Route::post('/add-employee-details', action: AddEmployeeDetails::class)->name('add-employee-details');
                Route::get('/edit-employee-details/{employee_id}', action: AddEmployeeDetails::class)->name('edit-employee-details');
            });
            Route::middleware('permission:compensation.view')->group(function () {
                Route::get('/compensation', action: CompensationHub::class)->name('compensation');
                Route::get('/compensation-structures', function (string $company_id) {
                    return redirect()->route('compensation', ['company_id' => $company_id]);
                })->name('compensation-structures');
                Route::get('/employee-compensation/{employee_id}', action: EmployeeCompensation::class)->name('employee-compensation');
            });
            Route::middleware('permission:salary.generate')->group(function () {
                Route::get('/salary-generator', action: SalaryGenerator::class)->name('salary-generator');
            });
            Route::middleware('permission:employees.view')->group(function () {
                Route::get('/view-employee-details', action: EmployeeList::class)->name('view-employee-details');
                Route::get('/view-employee-details/{employee_id}', action: ViewEmployeeDetails::class)->name('employee-details');
            });
            Route::middleware('permission:attendance.view,attendance.manage')->group(function () {
                Route::get('/attendance-entry', AttendanceEntry::class)->name('attendance-entry');
            });
        });
    });

    Route::get('/tables', action: Tables::class)->name('tables');
    Route::get('/static-sign-in', action: StaticSignIn::class)->name('sign-in');
    Route::get('/static-sign-up', action: StaticSignUp::class)->name('static-sign-up');
    Route::get('/rtl', Rtl::class)->name('rtl');
});
