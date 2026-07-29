@props([
    'href',
    'active' => false,
    'icon',
    'label',
])

<li class="ui-sidebar-item">
    <a href="{{ $href }}" class="ui-sidebar-link {{ $active ? 'active' : '' }}">
        <span class="ui-sidebar-icon material-symbols-outlined" aria-hidden="true">{{ $icon }}</span>
        <span class="ui-sidebar-label">{{ $label }}</span>
    </a>
</li>
