@props(['status', 'icon' => null])
@php
    $tones = [
        'draft' => 'bg-surface-3 text-muted-foreground border-border',
        'processing' => 'bg-warning text-warning-foreground border-warning-foreground/25',
        'completed' => 'bg-secondary text-primary border-primary/25',
        'locked' => 'bg-foreground text-background border-foreground',
        'approved' => 'bg-secondary text-primary border-primary/25',
        'paid' => 'bg-success/12 text-success border-success/35',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'mono-label inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-[10px] '.($tones[$status] ?? $tones['draft'])]) }}>
    @if (! empty($icon))
        <x-landing.icon :name="$icon" class="text-[13px]" />
    @endif
    {{ $status }}
</span>
