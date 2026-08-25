@props(['name'])
<span {{ $attributes->merge(['class' => 'material-symbols-outlined', 'aria-hidden' => 'true']) }}>{{ $name }}</span>
