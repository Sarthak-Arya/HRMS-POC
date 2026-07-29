@php
    $activeTitle = 'New chat';
    foreach ($conversations as $conversation) {
        if ($conversationId === $conversation['id']) {
            $activeTitle = $conversation['title'] ?: 'New chat';
            break;
        }
    }
@endphp

<div class="ui-ai-history-dropdown" x-data="{ open: false }" @click.away="open = false">
    <button type="button"
        class="ui-ai-history-dropdown-trigger"
        @click.stop="open = !open"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox">
        <span class="ui-ai-history-dropdown-label">{{ $activeTitle }}</span>
        <span class="material-symbols-outlined ui-ai-history-dropdown-chevron" :class="{ 'is-open': open }">expand_more</span>
    </button>

    <div class="ui-ai-history-dropdown-menu" x-show="open" x-cloak role="listbox">
        @forelse($conversations as $conversation)
            <div class="ui-ai-history-dropdown-item {{ $conversationId === $conversation['id'] ? 'active' : '' }}">
                <button type="button"
                    class="ui-ai-history-dropdown-option"
                    wire:click.stop="loadConversation({{ $conversation['id'] }})"
                    @click="open = false"
                    title="{{ $conversation['title'] }}">
                    <span class="ui-ai-history-dropdown-option-title">{{ $conversation['title'] }}</span>
                    @if(!empty($conversation['updated_at']))
                        <span class="ui-ai-history-dropdown-option-time">{{ $conversation['updated_at'] }}</span>
                    @endif
                </button>
                <button type="button"
                    class="ui-ai-history-dropdown-delete"
                    wire:click.stop="deleteConversation({{ $conversation['id'] }})"
                    title="Delete chat"
                    aria-label="Delete chat">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </div>
        @empty
            <p class="ui-ai-history-dropdown-empty">No past chats yet</p>
        @endforelse
    </div>
</div>
