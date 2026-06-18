@php
    $summary = $summary ?? null;
@endphp

@if($summary && $summary['lines']->isNotEmpty())
    <div class="mt-3 pt-3 border-top">
        <div class="d-flex justify-content-between text-sm mb-1">
            <span class="text-success">Total Earnings</span>
            <span class="text-success fw-semibold">+ {{ number_format($summary['totalEarnings'], 2) }}</span>
        </div>
        <div class="d-flex justify-content-between text-sm mb-2">
            <span class="text-danger">Total Deductions</span>
            <span class="text-danger fw-semibold">- {{ number_format($summary['totalDeductions'], 2) }}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span class="fw-semibold">Net Monthly Pay</span>
            <span class="fw-bold">{{ number_format($summary['netPay'], 2) }}</span>
        </div>
    </div>
@endif
