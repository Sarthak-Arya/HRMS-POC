@props(['eyebrow', 'title', 'body' => null, 'align' => 'left'])
<div @class(['max-w-2xl', 'mx-auto text-center' => $align === 'center'])>
    <x-landing.eyebrow>{{ $eyebrow }}</x-landing.eyebrow>
    <h2 class="mt-3 text-3xl leading-tight text-foreground sm:text-4xl">{{ $title }}</h2>
    @if ($body)
        <p class="mt-4 text-[15px] leading-relaxed text-muted-foreground">{{ $body }}</p>
    @endif
</div>
