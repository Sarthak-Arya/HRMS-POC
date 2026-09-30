<?php

use App\Http\Livewire\AttendanceHub;
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
use App\Http\Livewire\GettingStarted;
use App\Http\Livewire\CompensationHub;
use App\Http\Livewire\EmployeeCompensation;
use App\Http\Livewire\PayrollRunList;
use App\Http\Livewire\PayrollRunDetail;
use App\Http\Livewire\EmployeePayrollDetail;
use App\Http\Livewire\PayrollHistory;
use App\Http\Livewire\ReportHub;
use App\Http\Livewire\SettingsHub;
use App\Http\Livewire\AiAssistantPage;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\LandingController;

use App\Http\Middleware\CompanyAccessMiddleware;
use App\Services\Auth\AuthLandingService;

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


Route::get('/', LandingController::class)->name('landing');

Route::get('/healthz', [\App\Http\Controllers\Health\HealthController::class, 'liveness'])->name('health.liveness');
Route::get('/readyz', [\App\Http\Controllers\Health\HealthController::class, 'readiness'])->name('health.readiness');
Route::get('/metrics', [\App\Http\Controllers\Health\HealthController::class, 'metrics'])->name('metrics');

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
    Route::middleware('permission:companies.manage_multiple')->group(function () {
        Route::get('/view-companies', ViewCompanies::class)->name('view-companies');
    });
    Route::get('/home', function (AuthLandingService $landing) {
        $user = auth()->user();
        abort_unless($user, 401);

        return redirect()->to($landing->homeRoute($user));
    })->name('home');
    Route::middleware('permission:employees.import')->group(function () {
        Route::post('/import-excel', [App\Http\Controllers\ImportExcel::class, 'import'])->name('import.excel');

        // Employee template download route
        Route::get('/download-employee-template', [App\Http\Controllers\EmployeeTemplateController::class, 'downloadTemplate'])->name('download.template');
    });

    Route::prefix('{company_id}')->group(function () {
        Route::middleware([CompanyAccessMiddleware::class])->group(function () {
            Route::middleware('permission:dashboard.view')->group(function () {
                Route::get('/dashboard', action: Dashboard::class)->name('dashboard');
            });

            Route::middleware('permission:settings.view')->group(function () {
                Route::get('/getting-started', GettingStarted::class)->name('getting-started');
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
                Route::get('/salary-generator', action: PayrollRunList::class)->name('salary-generator');
                Route::get('/payroll-runs/{run_id}', action: PayrollRunDetail::class)->name('payroll-run-detail');
                Route::get('/payroll-runs/{run_id}/employees/{employee_payroll_id}', action: EmployeePayrollDetail::class)->name('employee-payroll-detail');
                Route::get('/payroll-history', action: PayrollHistory::class)->name('payroll-history');
                Route::get('/payroll-runs/{run_id}/payslips/{employee_payroll_id}', [PayslipController::class, 'download'])->name('payroll.payslip');
                Route::get('/payroll-runs/{run_id}/payslips', [PayslipController::class, 'downloadBulk'])->name('payroll.payslip.bulk');
                Route::get('/payroll-runs/{run_id}/salary-sheet', [App\Http\Controllers\SalarySheetController::class, 'download'])->name('payroll.salary-sheet');
            });

            Route::middleware('permission:employees.view')->group(function () {
                Route::get('/view-employee-details', action: EmployeeList::class)->name('view-employee-details');
                Route::get('/view-employee-details/{employee_id}', action: ViewEmployeeDetails::class)->name('employee-details');
            });

            Route::middleware('permission:attendance.view,attendance.manage')->group(function () {
                Route::get('/attendance', AttendanceHub::class)->name('attendance');
                Route::get('/attendance-entry', function (string $company_id) {
                    return redirect()->route('attendance', ['company_id' => $company_id]);
                })->name('attendance-entry');
            });

            Route::middleware('permission:reports.view')->group(function () {
                Route::get('/reports', ReportHub::class)->name('reports');
            });

            Route::middleware('permission:reports.run')->group(function () {
                Route::get('/reports/salary-sheet/{run_id}', [App\Http\Controllers\SalarySheetController::class, 'download'])->name('reports.salary-sheet');
            });

            Route::middleware('permission:settings.view')->group(function () {
                Route::get('/settings/{category?}', SettingsHub::class)
                    ->where('category', 'organization-profile|attendance|compensation|reports|tax|statutory')
                    ->name('settings');
            });

            Route::middleware('permission:ai.assistant.use')->group(function () {
                Route::get('/ai-assistant', AiAssistantPage::class)->name('ai-assistant');
            });

            // Employee Self-Service portal
            Route::prefix('me')->name('ess.')->group(function () {
                Route::middleware('permission:ess.approvals,ess.approvals.any')->group(function () {
                    Route::get('/approvals', \App\Http\Livewire\Ess\LeaveApprovals::class)->name('approvals');
                });

                Route::middleware(['permission:ess.access', 'ess.employee'])->group(function () {
                    Route::get('/', \App\Http\Livewire\Ess\EssHome::class)->name('home');

                    Route::middleware('permission:ess.profile')->group(function () {
                        Route::get('/profile', \App\Http\Livewire\Ess\MyProfile::class)->name('profile');
                    });

                    Route::middleware('permission:ess.leave')->group(function () {
                        Route::get('/leave', \App\Http\Livewire\Ess\MyLeave::class)->name('leave');
                    });

                    Route::middleware('permission:ess.attendance')->group(function () {
                        Route::get('/attendance', \App\Http\Livewire\Ess\MyAttendance::class)->name('attendance');
                    });

                    Route::middleware('permission:ess.payslips')->group(function () {
                        Route::get('/payslips', \App\Http\Livewire\Ess\MyPayslips::class)->name('payslips');
                        Route::get('/payslips/{run_id}/{employee_payroll_id}', [\App\Http\Controllers\Ess\EssPayslipController::class, 'download'])
                            ->name('payslip.download');
                    });

                    Route::middleware('permission:ess.directory')->group(function () {
                        Route::get('/directory', \App\Http\Livewire\Ess\EssDirectory::class)->name('directory');
                    });
                });
            });
        });
    });
});

Route::get('/tables', action: Tables::class)->name('tables');
Route::get('/static-sign-in', action: StaticSignIn::class)->name('sign-in');
Route::get('/static-sign-up', action: StaticSignUp::class)->name('static-sign-up');
Route::get('/rtl', Rtl::class)->name('rtl');

