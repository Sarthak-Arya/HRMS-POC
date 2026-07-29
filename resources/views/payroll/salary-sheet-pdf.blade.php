<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Sheet - {{ $sheet['company']->company_name ?? 'Company' }}</title>
    <style>
        @page { margin: 12mm 8mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: {{ count($sheet['earning_columns'] ?? []) > 5 ? 6 : 7 }}px; color: #111; }
        .header { text-align: center; margin-bottom: 8px; }
        .company-name { font-size: 12px; font-weight: bold; letter-spacing: 1px; }
        .title { font-size: 9px; font-weight: bold; margin-top: 4px; }
        .meta { width: 100%; margin: 6px 0 8px; font-size: 7px; }
        .meta td { padding: 2px 4px; }
        table.sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.sheet th, table.sheet td { border: 1px solid #333; padding: 2px 3px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }
        table.sheet th { background: #f2f2f2; font-size: 5.5px; text-align: center; line-height: 1.2; }
        table.sheet td.num { text-align: right; word-break: break-all; }
        table.sheet td.center { text-align: center; }
        .totals td { font-weight: bold; background: #fafafa; }
        .summary { margin-top: 14px; page-break-inside: avoid; }
        .summary h3 { font-size: 9px; margin: 0 0 6px; text-align: center; }
        .summary-grid { width: 100%; border-collapse: collapse; }
        .summary-grid td { border: 1px solid #ccc; padding: 3px 5px; font-size: 7px; }
        .summary-grid td.label { width: 55%; }
        .summary-grid td.value { text-align: right; font-weight: bold; }
        .section-title { background: #efefef; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ strtoupper($sheet['company']->company_name ?? 'COMPANY') }}</div>
        <div class="title">SALARY REGISTER FOR THE MONTH OF {{ $sheet['period_label'] }}</div>
        @if(($sheet['group_by'] ?? 'company') !== 'company')
            <div style="font-size:7px; margin-top:2px;">{{ $sheet['title'] }}</div>
        @endif
    </div>

    <table class="meta">
        <tr>
            <td><strong>E.S.I. NO.</strong> {{ $sheet['esi_code'] ?? '—' }}</td>
            <td style="text-align:right;"><strong>P.F. NO.</strong> {{ $sheet['pf_code'] ?? '—' }}</td>
        </tr>
    </table>

    @php
        $earningIds = array_keys($sheet['earning_columns']);
        $earningCount = count($earningIds);
        $earningWidth = max(4, min(6, (47 - $earningCount) / max(1, $earningCount)));
        $statutoryWidth = 3.5;
        $abbreviate = fn (string $label) => strlen($label) > 12 ? substr($label, 0, 10).'…' : $label;
    @endphp

    <table class="sheet">
        <thead>
            <tr>
                <th rowspan="2" style="width:3%;">SNO</th>
                <th rowspan="2" style="width:9%;">EMPLOYEE</th>
                <th rowspan="2" style="width:3%;">ESI#</th>
                <th rowspan="2" style="width:3%;">PF#</th>
                <th rowspan="2" style="width:3%;">WORK</th>
                <th rowspan="2" style="width:3%;">HOL</th>
                <th rowspan="2" style="width:3%;">TOT</th>
                <th colspan="{{ $earningCount + 1 }}">ACTUAL SALARY</th>
                <th colspan="{{ $earningCount + 2 }}">SALARY PAYABLE</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">ESI<br>WAGES</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">ESI<br>EMPL</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">PF<br>WAGES</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">PF</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">TDS</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">ADV</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">OTHER</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">TOT<br>DED</th>
                <th rowspan="2" style="width:{{ $statutoryWidth }}%;">NET</th>
                <th rowspan="2" style="width:4%;">SIGN</th>
            </tr>
            <tr>
                @foreach($sheet['earning_columns'] as $label)
                    <th style="width:{{ $earningWidth }}%;">{{ strtoupper($abbreviate($label)) }}</th>
                @endforeach
                <th style="width:{{ $earningWidth }}%;">GROSS</th>
                @foreach($sheet['earning_columns'] as $label)
                    <th style="width:{{ $earningWidth }}%;">{{ strtoupper($abbreviate($label)) }}</th>
                @endforeach
                <th style="width:{{ $earningWidth }}%;">VAR</th>
                <th style="width:{{ $earningWidth }}%;">GROSS</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sheet['employees'] as $employee)
                <tr>
                    <td class="center">{{ $employee['sno'] }}</td>
                    <td>
                        {{ $employee['employee_name'] }}<br>
                        <span style="color:#555;font-size:5.5px;">{{ $employee['father_name'] ?? '—' }}</span>
                    </td>
                    <td class="center" style="font-size:5.5px;">{{ $employee['esi_no'] ?? '—' }}</td>
                    <td class="center" style="font-size:5.5px;">{{ $employee['pf_no'] ?? '—' }}</td>
                    <td class="center">{{ number_format($employee['work_days'], 1) }}</td>
                    <td class="center">{{ number_format($employee['holiday_days'], 1) }}</td>
                    <td class="center">{{ number_format($employee['total_days'], 1) }}</td>
                    @foreach($earningIds as $componentId)
                        <td class="num">{{ number_format($employee['actual_earnings'][$componentId] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="num">{{ number_format($employee['actual_gross'], 2) }}</td>
                    @foreach($earningIds as $componentId)
                        <td class="num">{{ number_format($employee['payable_earnings'][$componentId] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="num">{{ number_format($employee['variable_pay'], 2) }}</td>
                    <td class="num">{{ number_format($employee['payable_gross'], 2) }}</td>
                    <td class="num">{{ number_format($employee['esi_wages'], 2) }}</td>
                    <td class="num">{{ number_format($employee['esi_employer'], 2) }}</td>
                    <td class="num">{{ number_format($employee['pf_wages'], 2) }}</td>
                    <td class="num">{{ number_format($employee['pf_employee'], 2) }}</td>
                    <td class="num">{{ number_format($employee['tds'], 2) }}</td>
                    <td class="num">{{ number_format($employee['advance'], 2) }}</td>
                    <td class="num">{{ number_format($employee['other_deductions'], 2) }}</td>
                    <td class="num">{{ number_format($employee['total_deductions'], 2) }}</td>
                    <td class="num">{{ number_format($employee['net_pay'], 2) }}</td>
                    <td></td>
                </tr>
            @endforeach
            <tr class="totals">
                <td colspan="2">PAGE TOTAL</td>
                <td></td><td></td><td></td><td></td><td></td>
                @foreach($earningIds as $componentId)
                    <td class="num">{{ number_format($sheet['totals']['actual_earnings'][$componentId] ?? 0, 2) }}</td>
                @endforeach
                <td class="num">{{ number_format($sheet['totals']['actual_gross'], 2) }}</td>
                @foreach($earningIds as $componentId)
                    <td class="num">{{ number_format($sheet['totals']['payable_earnings'][$componentId] ?? 0, 2) }}</td>
                @endforeach
                <td class="num">{{ number_format($sheet['totals']['variable_pay'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['payable_gross'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['esi_wages'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['esi_employer'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['pf_wages'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['pf_employee'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['tds'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['advance'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['other_deductions'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['total_deductions'], 2) }}</td>
                <td class="num">{{ number_format($sheet['totals']['net_pay'], 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @php $summary = $sheet['statutory_summary']; @endphp
    <div class="summary">
        <h3>SUMMARY OF DEDUCTIONS FOR THE MONTH OF {{ $sheet['period_label'] }}</h3>
        <table class="summary-grid">
            <tr><td class="label">Total No. of Employees</td><td class="value">{{ $summary['employee_count'] }}</td></tr>
            <tr><td class="label">Total Gross Salary</td><td class="value">{{ number_format($summary['total_gross_salary'], 2) }}</td></tr>
            <tr><td class="label">Exempted E.S.I. Salary</td><td class="value">{{ number_format($summary['exempted_esi_salary'], 2) }}</td></tr>
            <tr><td class="label">Exempted P.F. Salary</td><td class="value">{{ number_format($summary['exempted_pf_salary'], 2) }}</td></tr>
            <tr><td class="section-title" colspan="2">E.S.I. CONTRIBUTION</td></tr>
            <tr><td class="label">Total No. of ESI Employees</td><td class="value">{{ $summary['esi_employee_count'] }}</td></tr>
            <tr><td class="label">Total ESI Wages</td><td class="value">{{ number_format($summary['esi_wages'], 2) }}</td></tr>
            <tr><td class="label">Employees Contribution</td><td class="value">{{ number_format($summary['esi_employee_contribution'], 2) }}</td></tr>
            <tr><td class="label">Employers Contribution</td><td class="value">{{ number_format($summary['esi_employer_contribution'], 2) }}</td></tr>
            <tr><td class="label">Total E.S.I.</td><td class="value">{{ number_format($summary['esi_total'], 2) }}</td></tr>
            <tr><td class="section-title" colspan="2">E.P.F. CONTRIBUTION</td></tr>
            <tr><td class="label">Total No. of P.F. Employees</td><td class="value">{{ $summary['pf_employee_count'] }}</td></tr>
            <tr><td class="label">Total P.F. Wages</td><td class="value">{{ number_format($summary['pf_wages'], 2) }}</td></tr>
            <tr><td class="label">Chalan 01 Contribution</td><td class="value">{{ number_format($summary['pf_chalan_01'], 2) }}</td></tr>
            <tr><td class="label">Chalan 10 Contribution</td><td class="value">{{ number_format($summary['pf_chalan_10'], 2) }}</td></tr>
            <tr><td class="label">Chalan 21 Contribution</td><td class="value">{{ number_format($summary['pf_chalan_21'], 2) }}</td></tr>
            <tr><td class="label">Chalan 22 Contribution</td><td class="value">{{ number_format($summary['pf_chalan_22'], 2) }}</td></tr>
            <tr><td class="label">Chalan 02 Contribution</td><td class="value">{{ number_format($summary['pf_chalan_02'], 2) }}</td></tr>
            <tr><td class="label">Total P.F.</td><td class="value">{{ number_format($summary['pf_total'], 2) }}</td></tr>
        </table>
    </div>
</body>
</html>
