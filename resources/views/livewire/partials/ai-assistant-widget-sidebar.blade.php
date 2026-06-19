@if($isOpen)
    <div class="ai-widget-backdrop" wire:click="toggle"></div>
@endif

<aside class="ai-widget-sidebar ai-assistant-root {{ $isOpen ? 'is-open' : '' }}">
    @if($isOpen && $companyId)
        <header class="ai-widget-sidebar-header">
            <div class="ai-widget-sidebar-brand">
                <span class="ai-gemini-logo" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="url(#ai-widget-star)"/>
                        <defs>
                            <linearGradient id="ai-widget-star" x1="2" y1="2" x2="22" y2="22">
                                <stop stop-color="#4285F4"/>
                                <stop offset="0.5" stop-color="#9B72CB"/>
                                <stop offset="1" stop-color="#D96570"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </span>
                <span class="ai-widget-sidebar-title">Payroll AI</span>
            </div>

            @include('livewire.partials.ai-assistant-history-dropdown')

            <div class="ai-widget-sidebar-actions">
                <button type="button" class="ai-widget-icon-btn" wire:click.stop="newConversation" title="New chat">
                    <i class="far fa-edit"></i>
                </button>
                <button type="button" class="ai-widget-icon-btn" wire:click.stop="toggle" title="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </header>

        @if($statusMessage)
            <div class="ai-gemini-status ai-widget-status" wire:poll.2000ms="clearStatusMessage">
                {{ $statusMessage }}
            </div>
        @endif

        <div class="ai-widget-sidebar-body">
            @include('livewire.partials.ai-assistant-chat-messages', [
                'messagesScrollId' => 'ai-messages-scroll',
                'starGradientId' => 'ai-widget-star-msg',
                'showEmptyState' => true,
                'welcomeCompact' => true,
            ])
        </div>

        <footer class="ai-widget-sidebar-footer">
            @include('livewire.partials.ai-assistant-chat-composer', [
                'inputId' => 'ai-assistant-input',
                'voiceBtnId' => 'ai-voice-btn',
                'voiceIconId' => 'ai-voice-icon',
                'placeholder' => 'Ask Payroll AI',
            ])
        </footer>
    @endif
</aside>

@if($companyId)
    <button type="button"
        class="ai-assistant-fab {{ $isOpen ? 'is-hidden' : '' }}"
        wire:click="toggle"
        title="AI Assistant"
        aria-label="AI Assistant">
        <i class="fas fa-robot"></i>
    </button>
@endif
