@php
    $activeTitle = 'New chat';
    foreach ($conversations as $conversation) {
        if ($conversationId === $conversation['id']) {
            $activeTitle = $conversation['title'] ?: 'New chat';
            break;
        }
    }
@endphp

<div class="ai-chat-history-dropdown" x-data="{ open: false }" @click.away="open = false">
    <button type="button"
        class="ai-chat-history-trigger"
        @click.stop="open = !open"
        :aria-expanded="open.toString()"
        aria-haspopup="listbox">
        <span class="ai-chat-history-trigger-label">{{ $activeTitle }}</span>
        <i class="fas fa-chevron-down ai-chat-history-chevron" :class="{ 'is-open': open }"></i>
    </button>

    <div class="ai-chat-history-menu" x-show="open" x-cloak role="listbox">
        @forelse($conversations as $conversation)
            <div class="ai-chat-history-option {{ $conversationId === $conversation['id'] ? 'active' : '' }}">
                <button type="button"
                    class="ai-chat-history-option-btn"
                    wire:click.stop="loadConversation({{ $conversation['id'] }})"
                    @click="open = false"
                    title="{{ $conversation['title'] }}">
                    <span class="ai-chat-history-option-title">{{ $conversation['title'] }}</span>
                    @if(!empty($conversation['updated_at']))
                        <span class="ai-chat-history-option-time">{{ $conversation['updated_at'] }}</span>
                    @endif
                </button>
                <button type="button"
                    class="ai-chat-history-option-delete"
                    wire:click.stop="deleteConversation({{ $conversation['id'] }})"
                    title="Delete chat">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        @empty
            <p class="ai-chat-history-empty">No past chats yet</p>
        @endforelse
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }

    .ai-chat-history-dropdown {
        position: relative;
        min-width: 0;
    }

    .ai-chat-history-trigger {
        display: flex;
        align-items: center;
        gap: 8px;
        max-width: 100%;
        padding: 8px 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.04);
        color: #e3e3e3;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .ai-chat-history-trigger:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.16);
    }

    .ai-chat-history-trigger-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        min-width: 0;
    }

    .ai-chat-history-chevron {
        font-size: 0.625rem;
        color: #9aa0a6;
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }

    .ai-chat-history-chevron.is-open {
        transform: rotate(180deg);
    }

    .ai-chat-history-menu {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        min-width: 240px;
        max-height: 280px;
        overflow-y: auto;
        padding: 6px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 12px;
        background: #28292a;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.45);
        z-index: 20;
    }

    .ai-widget-sidebar-header .ai-chat-history-dropdown {
        flex: 1;
        margin: 0 8px;
    }

    .ai-gemini-main-header .ai-chat-history-dropdown {
        flex: 1;
        max-width: 360px;
        margin: 0 auto;
    }

    .ai-chat-history-option {
        display: flex;
        align-items: center;
        gap: 4px;
        border-radius: 8px;
        margin-bottom: 2px;
    }

    .ai-chat-history-option.active,
    .ai-chat-history-option:hover {
        background: rgba(255, 255, 255, 0.06);
    }

    .ai-chat-history-option-btn {
        flex: 1;
        min-width: 0;
        padding: 10px 12px;
        border: none;
        background: transparent;
        text-align: left;
        cursor: pointer;
        color: #e3e3e3;
    }

    .ai-chat-history-option-title {
        display: block;
        font-size: 0.8125rem;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ai-chat-history-option-time {
        display: block;
        margin-top: 2px;
        font-size: 0.6875rem;
        color: #9aa0a6;
    }

    .ai-chat-history-option-delete {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        margin-right: 4px;
        border: none;
        border-radius: 6px;
        background: transparent;
        color: #9aa0a6;
        opacity: 0;
        cursor: pointer;
        transition: opacity 0.15s ease, background 0.15s ease, color 0.15s ease;
    }

    .ai-chat-history-option:hover .ai-chat-history-option-delete {
        opacity: 1;
    }

    .ai-chat-history-option-delete:hover {
        background: rgba(234, 67, 53, 0.15);
        color: #f28b82;
    }

    .ai-chat-history-empty {
        margin: 0;
        padding: 12px;
        font-size: 0.8125rem;
        color: #9aa0a6;
        text-align: center;
    }
</style>
