@if(!$companyId)
    <section class="ui-card ui-ai-empty">
        <div class="ui-card-body text-center py-5">
            <span class="material-symbols-outlined ui-ai-empty-icon" aria-hidden="true">domain_disabled</span>
            <p class="ui-ai-empty-text mb-0">No company selected. Please choose a company to use the AI assistant.</p>
        </div>
    </section>
@else
    <div class="ui-ai-shell ai-assistant-root {{ empty($messages) ? 'ui-ai-shell--welcome' : 'ui-ai-shell--chat' }}">
        <div class="ui-ai-main">
            <header class="ui-ai-main-header">
                <button type="button"
                    class="ui-ai-icon-btn ui-ai-sidebar-toggle d-xl-none"
                    wire:click.stop="toggleNavSidebar"
                    aria-label="Open chat history">
                    <span class="material-symbols-outlined">history</span>
                </button>
                <div class="ui-ai-main-header-spacer"></div>
            </header>

            @if($statusMessage)
                <div class="ui-ai-status" wire:poll.2000ms="clearStatusMessage">
                    {{ $statusMessage }}
                </div>
            @endif

            <div class="ui-ai-body">
                @include('livewire.partials.ai-assistant-ui-messages', [
                    'messagesScrollId' => $messagesScrollId ?? 'ai-messages-scroll-page',
                    'showEmptyState' => empty($messages),
                ])
            </div>

            <div class="ui-ai-composer-wrap">
                @include('livewire.partials.ai-assistant-ui-composer', [
                    'inputId' => $inputId ?? 'ai-assistant-input-page',
                    'voiceBtnId' => $voiceBtnId ?? 'ai-voice-btn-page',
                    'voiceIconId' => $voiceIconId ?? 'ai-voice-icon-page',
                ])
            </div>
        </div>

        <aside class="ui-ai-sidebar {{ $showNavSidebar ? 'is-open' : '' }}">
            <div class="ui-ai-sidebar-top">
                <button type="button" class="ui-btn-primary ui-ai-new-chat-btn" wire:click.stop="newConversation">
                    <span class="material-symbols-outlined">add</span>
                    New Chat
                </button>
                <button type="button"
                    class="ui-ai-icon-btn ui-ai-sidebar-close d-xl-none"
                    wire:click.stop="toggleNavSidebar"
                    aria-label="Close chat history">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <div class="ui-ai-history">
                <p class="ui-ai-history-label">Recent Queries</p>
                <div class="ui-ai-history-list">
                    @forelse($conversations as $conversation)
                        <div class="ui-ai-history-item {{ $conversationId === $conversation['id'] ? 'active' : '' }}">
                            <button type="button"
                                class="ui-ai-history-link"
                                wire:click.stop="loadConversation({{ $conversation['id'] }})"
                                title="{{ $conversation['title'] }}">
                                {{ $conversation['title'] }}
                            </button>
                            <button type="button"
                                class="ui-ai-history-delete"
                                wire:click.stop="deleteConversation({{ $conversation['id'] }})"
                                title="Delete chat"
                                aria-label="Delete chat">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    @empty
                        <p class="ui-ai-history-empty">No past chats yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="ui-ai-sidebar-footer">
                <div class="ui-ai-user">
                    <span class="ui-ai-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                    <span class="ui-ai-user-name">{{ auth()->user()->name ?? 'User' }}</span>
                </div>
            </div>
        </aside>

        @if($showNavSidebar)
            <div class="ui-ai-backdrop d-xl-none" wire:click.stop="toggleNavSidebar"></div>
        @endif
    </div>
@endif
