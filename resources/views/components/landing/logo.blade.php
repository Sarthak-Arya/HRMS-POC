@props(['href' => null])
<a href="{{ $href ?? route('landing') }}" {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-primary">
        <x-landing.icon name="payments" class="text-[22px]" />
    </span>
    <span class="leading-tight">
        <span class="block font-[family-name:var(--font-display)] text-[17px] font-bold text-foreground">PayrollPro</span>
        <span class="mono-label block text-[9px] text-faint">Enterprise Admin</span>
    </span>
</a>
