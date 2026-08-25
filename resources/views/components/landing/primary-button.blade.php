@props(['href'])
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'mono-label inline-flex items-center justify-center gap-2 rounded-[20px] text-[12px] transition-colors disabled:opacity-60 bg-primary px-5 py-3 text-primary-foreground shadow-primary hover:bg-primary-hover']) }}>
    {{ $slot }}
</a>
