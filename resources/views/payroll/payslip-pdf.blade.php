<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $employee->employee_name }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1a1a2e; margin: 0; padding: 24px; }
        .header { border-bottom: 3px solid #2d6a4f; padding-bottom: 12px; margin-bottom: 20px; }
        .company-name { font-size: 20px; font-weight: bold; color: #2d6a4f; }
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.lines th, table.lines td { border: 1px solid #ddd; padding: 8px; }
        table.lines th { background: #f8f9fa; }
        .text-right { text-align: right; }
        .section-title { background: #f0f4f0; padding: 8px; font-weight: bold; margin: 12px 0 6px; }
        .net-box { background: #e8f5e9; border: 2px solid #2d6a4f; padding: 14px; margin-top: 16px; text-align: right; }
        .net-box .amount { font-size: 22px; font-weight: bold; color: #2d6a4f; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->company_name ?? 'Company' }}</div>
        <div>Payslip for {{ $period }}</div>
    </div>
    <p><strong>{{ $employee->employee_name }}</strong> ({{ $employee->employee_code }})<br>
    {{ $employee->department?->department_name ?? '' }} · Days: {{ $payroll->attendanceSummary?->worked_days ?? '—' }}/{{ $payroll->attendanceSummary?->total_days ?? '—' }}</p>

    <div class="section-title">Earnings</div>
    <table class="lines"><thead><tr><th>Component</th><th class="text-right">Amount (₹)</th></tr></thead>
    <tbody>@foreach($earnings as $line)<tr><td>{{ $line->component_name }}</td><td class="text-right">{{ number_format($line->calculated_amount, 2) }}</td></tr>@endforeach
    <tr><td><strong>Total</strong></td><td class="text-right"><strong>{{ number_format($payroll->gross_earnings, 2) }}</strong></td></tr></tbody></table>

    <div class="section-title">Deductions</div>
    <table class="lines"><thead><tr><th>Component</th><th class="text-right">Amount (₹)</th></tr></thead>
    <tbody>@foreach($deductions as $line)<tr><td>{{ $line->component_name }}</td><td class="text-right">{{ number_format($line->calculated_amount, 2) }}</td></tr>@endforeach
    <tr><td><strong>Total</strong></td><td class="text-right"><strong>{{ number_format($payroll->gross_deductions, 2) }}</strong></td></tr></tbody></table>

    <div class="net-box"><div>Net Pay</div><div class="amount">₹ {{ number_format($payroll->net_pay, 2) }}</div></div>
</body>
</html>
