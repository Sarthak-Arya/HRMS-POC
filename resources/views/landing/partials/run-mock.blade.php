@php
    $rows = [
        ['code' => 'EMP-1042', 'name' => 'Anjali Deshpande', 'dept' => 'Operations', 'net' => '82,410.00', 'status' => 'paid'],
        ['code' => 'EMP-1078', 'name' => 'Rahul Menon', 'dept' => 'Finance', 'net' => '1,14,250.00', 'status' => 'approved'],
        ['code' => 'EMP-1103', 'name' => 'Farhan Qureshi', 'dept' => 'Plant, Pune', 'net' => '46,980.00', 'status' => 'approved'],
        ['code' => 'EMP-1129', 'name' => 'Sneha Iyer', 'dept' => 'People Ops', 'net' => '68,500.00', 'status' => 'paid'],
        ['code' => 'EMP-1156', 'name' => 'Vikram Singh', 'dept' => 'Plant, Nashik', 'net' => '39,120.00', 'status' => 'draft'],
    ];
    $adjustments = [
        ['type' => 'ARREARS', 'employee' => 'Rahul Menon', 'amount' => '+ 12,000.00', 'credit' => true],
        ['type' => 'LOAN EMI', 'employee' => 'Farhan Qureshi', 'amount' => '− 4,500.00', 'credit' => false],
        ['type' => 'BONUS', 'employee' => 'Sneha Iyer', 'amount' => '+ 15,000.00', 'credit' => true],
    ];
@endphp
<div class="overflow-hidden rounded-[24px] border border-border bg-card shadow-soft">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border bg-surface-2 px-5 py-4">
        <div>
            <p class="mono-label text-[9px] text-faint">Payroll run · June 2026</p>
            <h3 class="mt-1 text-[17px] text-foreground">Monthly payroll run · RUN-2026-06</h3>
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            <x-landing.status-badge status="draft" />
            <x-landing.status-badge status="processing" />
            <x-landing.status-badge status="completed" icon="check" />
            <x-landing.status-badge status="locked" icon="lock" />
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-b border-border bg-accent px-5 py-3.5">
        <x-landing.icon name="fact_check" class="text-[20px] text-primary" />
        <p class="text-[13px] text-foreground">
            <span class="tabular font-medium">142/150</span> employees ready for June 2026
        </p>
        <span class="mono-label rounded-full border border-warning-foreground/25 bg-warning px-2.5 py-1 text-[10px] text-warning-foreground">
            8 attendance gaps
        </span>
    </div>

    <div class="px-2 pt-2 pb-1 sm:px-4">
        <div class="grid grid-cols-[minmax(0,1fr)_96px_88px] items-center gap-2 border-b border-border px-2 py-2 sm:grid-cols-[100px_minmax(0,1.5fr)_minmax(0,1fr)_110px_92px]">
            <span class="mono-label hidden text-[9px] text-faint sm:block">Code</span>
            <span class="mono-label text-[9px] text-faint">Employee</span>
            <span class="mono-label hidden text-[9px] text-faint sm:block">Department</span>
            <span class="mono-label text-right text-[9px] text-faint">Net pay</span>
            <span class="mono-label text-[9px] text-faint">Status</span>
        </div>
        @foreach ($rows as $row)
            <div class="grid grid-cols-[minmax(0,1fr)_96px_88px] items-center gap-2 border-b border-border/60 px-2 py-2.5 last:border-b-0 sm:grid-cols-[100px_minmax(0,1.5fr)_minmax(0,1fr)_110px_92px]">
                <span class="tabular hidden text-[11px] text-faint sm:block">{{ $row['code'] }}</span>
                <span class="truncate text-[13px] font-medium text-foreground">{{ $row['name'] }}</span>
                <span class="hidden truncate text-[12px] text-muted-foreground sm:block">{{ $row['dept'] }}</span>
                <span class="tabular text-right text-[12px] text-foreground">₹{{ $row['net'] }}</span>
                <span class="flex justify-end sm:justify-start">
                    <x-landing.status-badge :status="$row['status']" />
                </span>
            </div>
        @endforeach
    </div>

    <div class="border-t border-border bg-muted px-5 py-4">
        <div class="flex items-center justify-between">
            <p class="mono-label text-[9px] text-faint">Adjustments attached to run</p>
            <p class="mono-label text-[9px] text-primary">3 lines</p>
        </div>
        <div class="mt-3 grid gap-2 sm:grid-cols-3">
            @foreach ($adjustments as $adjustment)
                <div class="rounded-xl border border-border bg-card px-3 py-2.5">
                    <p class="mono-label text-[9px] text-faint">{{ $adjustment['type'] }}</p>
                    <p class="mt-1 truncate text-[12px] text-muted-foreground">{{ $adjustment['employee'] }}</p>
                    <p @class(['tabular mt-1 text-[13px]', 'text-success' => $adjustment['credit'], 'text-destructive' => ! $adjustment['credit']])>
                        {{ $adjustment['amount'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
</div>
