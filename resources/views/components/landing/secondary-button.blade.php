@props(['href'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'mono-label inline-flex items-center justify-center gap-2 rounded-[20px] text-[12px] transition-colors disabled:opacity-60 border border-border bg-secondary px-5 py-3 text-secondary-foreground hover:bg-surface-3']) }}>
    {{ $slot }}
</a>
