@php
    $suggestions = [
        ['icon' => 'checklist', 'text' => 'Which employees are missing attendance for June?'],
        ['icon' => 'query_stats', 'text' => 'Is RUN-2026-06 ready to move to COMPLETED?'],
        ['icon' => 'receipt_long', 'text' => 'Show loan EMIs attached to this run.'],
    ];
@endphp
<div class="overflow-hidden rounded-[24px] border border-border bg-card shadow-soft">
    <div class="flex items-center justify-between border-b border-border bg-surface-2 px-5 py-4">
        <div class="flex items-center gap-3">
            <span class="flex size-9 items-center justify-center rounded-xl border border-border bg-card">
                <x-landing.icon name="auto_awesome" class="text-[19px] text-primary" />
            </span>
            <div>
                <p class="text-[14px] font-medium text-foreground">Payroll AI</p>
                <p class="mono-label text-[9px] text-faint">Operator assistant</p>
            </div>
        </div>
        <span class="mono-label rounded-full border border-success/35 bg-success/12 px-2.5 py-1 text-[10px] text-success">
            online
        </span>
    </div>

    <div class="px-5 py-7">
        <h3 class="bg-gradient-to-r from-primary to-success bg-clip-text text-2xl text-transparent">
            How can I help with June payroll?
        </h3>
        <p class="mt-2 text-[13px] text-muted-foreground">
            Ask about readiness, attendance gaps, or run status. I read the current run. I do not change it.
        </p>

        <div class="mt-5 grid gap-2">
            @foreach ($suggestions as $suggestion)
                <div class="flex items-center gap-3 rounded-xl border border-border bg-muted px-3.5 py-3 text-left">
                    <x-landing.icon :name="$suggestion['icon']" class="text-[18px] text-primary" />
                    <span class="text-[13px] text-muted-foreground">{{ $suggestion['text'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-5 flex gap-3 rounded-xl border border-border bg-surface-2 px-3.5 py-3">
            <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                <x-landing.icon name="auto_awesome" class="text-[15px]" />
            </span>
            <p class="text-[13px] leading-relaxed text-muted-foreground">
                8 employees have unapproved attendance in Plant, Nashik. Close the attendance lock for June 2026
                before you move <span class="mono-label text-[11px] text-foreground">RUN-2026-06</span> to
                <span class="mono-label text-[11px] text-foreground">PROCESSING</span>.
            </p>
        </div>
    </div>

    <p class="border-t border-border bg-muted px-5 py-3 text-[11px] text-faint">
        Payroll AI can make mistakes. Verify important financial data before final approval.
    </p>
</div>
