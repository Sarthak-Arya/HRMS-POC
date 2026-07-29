@if($isOpen)
    <div class="ui-ai-widget-backdrop" wire:click="toggle" aria-hidden="true"></div>
@endif

<aside class="ui-ai-widget-panel ui-page {{ $isOpen ? 'is-open' : '' }}" aria-label="AI Assistant">
    @if($isOpen && $companyId)
        <div class="ui-ai-shell ui-ai-shell--widget ai-assistant-root {{ empty($messages) ? 'ui-ai-shell--welcome' : 'ui-ai-shell--chat' }}">
            <header class="ui-ai-widget-header">
                <div class="ui-ai-widget-brand">
                    <span class="ui-ai-widget-brand-icon" aria-hidden="true">
                        <span class="material-symbols-outlined">auto_awesome</span>
                    </span>
                    <div class="ui-ai-widget-brand-text">
                        <span class="ui-ai-widget-title">Payroll AI</span>
                        <span class="ui-ai-widget-subtitle">Assistant</span>
                    </div>
                </div>

                @include('livewire.partials.ai-assistant-history-dropdown')

                <div class="ui-ai-widget-header-actions">
                    <button type="button"
                        class="ui-ai-icon-btn"
                        wire:click.stop="newConversation"
                        title="New chat"
                        aria-label="New chat">
                        <span class="material-symbols-outlined">edit_square</span>
                    </button>
                    <a href="{{ route('ai-assistant', ['company_id' => $companyId]) }}"
                        class="ui-ai-icon-btn"
                        title="Open full page"
                        aria-label="Open full page assistant">
                        <span class="material-symbols-outlined">open_in_full</span>
                    </a>
                    <button type="button"
                        class="ui-ai-icon-btn"
                        wire:click.stop="toggle"
                        title="Close"
                        aria-label="Close assistant">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>
            </header>

            @if($statusMessage)
                <div class="ui-ai-status ui-ai-widget-status" wire:poll.2000ms="clearStatusMessage">
                    {{ $statusMessage }}
                </div>
            @endif

            <div class="ui-ai-body">
                @include('livewire.partials.ai-assistant-ui-messages', [
                    'messagesScrollId' => 'ai-messages-scroll',
                    'showEmptyState' => true,
                ])
            </div>

            <div class="ui-ai-composer-wrap">
                @include('livewire.partials.ai-assistant-ui-composer', [
                    'inputId' => 'ai-assistant-input',
                    'voiceBtnId' => 'ai-voice-btn',
                    'voiceIconId' => 'ai-voice-icon',
                ])
            </div>
        </div>
    @elseif($isOpen && !$companyId)
        <div class="ui-ai-widget-empty">
            <span class="material-symbols-outlined ui-ai-empty-icon" aria-hidden="true">domain_disabled</span>
            <p class="ui-ai-empty-text mb-0">Select a company to use Payroll AI.</p>
        </div>
    @endif
</aside>

@if($companyId)
    <button type="button"
        class="ui-ai-widget-fab {{ $isOpen ? 'is-hidden' : '' }}"
        wire:click="toggle"
        title="AI Assistant"
        aria-label="Open AI Assistant">
        <span class="material-symbols-outlined">auto_awesome</span>
    </button>
@endif
