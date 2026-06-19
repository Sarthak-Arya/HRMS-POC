@php
    $wrapperTag = $wrapper ?? 'li';
@endphp

@if ($wrapperTag === 'li')
    <li class="nav-item d-flex align-items-center mb-0 {{ $class ?? '' }}">
@elseif ($wrapperTag === 'div')
    <div class="d-flex align-items-center {{ $class ?? '' }}">
@endif
    <button type="button"
        class="btn btn-link theme-toggle-btn {{ $btnClass ?? 'text-body' }}"
        onclick="window.__toggleTheme()"
        aria-label="Toggle dark mode"
        title="Toggle dark mode">
        <i class="fas fa-moon theme-icon-moon" aria-hidden="true"></i>
        <i class="fas fa-sun theme-icon-sun d-none" aria-hidden="true"></i>
    </button>
@if ($wrapperTag === 'li')
    </li>
@elseif ($wrapperTag === 'div')
    </div>
@endif
