<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">My payslips</h1>
                <p class="ui-page-subtitle">Download payslips from completed payroll runs.</p>
            </div>
        </section>

        <section class="ui-panel">
            <div class="table-responsive">
                <table class="table align-items-center mb-0">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Gross</th>
                            <th>Deductions</th>
                            <th>Net pay</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $payslip)
                            <tr>
                                <td>
                                    {{ \Carbon\Carbon::create($payslip->payrollRun->year, $payslip->payrollRun->month, 1)->format('F Y') }}
                                </td>
                                <td>{{ number_format((float) $payslip->gross_earnings, 2) }}</td>
                                <td>{{ number_format((float) $payslip->gross_deductions, 2) }}</td>
                                <td><strong>{{ number_format((float) $payslip->net_pay, 2) }}</strong></td>
                                <td class="text-end">
                                    <a class="ui-btn-secondary"
                                        href="{{ route('ess.payslip.download', [
                                            'company_id' => $companyId,
                                            'run_id' => $payslip->payroll_run_id,
                                            'employee_payroll_id' => $payslip->id,
                                        ]) }}">
                                        Download PDF
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">No payslips available yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
