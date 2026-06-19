@if(!$companyId)
    <div class="ai-gemini-shell ai-gemini-shell--empty ai-gemini-shell--embedded">
        <div class="ai-gemini-main d-flex align-items-center justify-content-center">
            <div class="alert alert-warning mb-0" role="alert">
                No company selected. Please choose a company to use the AI assistant.
            </div>
        </div>
    </div>
@else
    <div class="ai-gemini-shell ai-gemini-shell--embedded ai-assistant-root {{ empty($messages) ? 'ai-gemini-shell--welcome' : 'ai-gemini-shell--chat' }}">
        <main class="ai-gemini-main">
            <div class="ai-gemini-main-header">
                <button type="button" class="ai-gemini-menu-btn d-xl-none" wire:click.stop="toggleNavSidebar" aria-label="Open assistant panel">
                    <i class="fas fa-bars"></i>
                </button>
                @include('livewire.partials.ai-assistant-history-dropdown')
                <div class="ai-gemini-main-spacer"></div>
            </div>

            @if($statusMessage)
                <div class="ai-gemini-status" wire:poll.2000ms="clearStatusMessage">
                    {{ $statusMessage }}
                </div>
            @endif

            <div class="ai-gemini-body">
                <div class="ai-gemini-messages-wrap">
                    @include('livewire.partials.ai-assistant-chat-messages', [
                        'messagesScrollId' => $messagesScrollId ?? 'ai-messages-scroll-page',
                        'starGradientId' => 'gemini-star-msg',
                        'showEmptyState' => empty($messages),
                    ])
                </div>
            </div>

            <div class="ai-gemini-composer-wrap">
                @include('livewire.partials.ai-assistant-chat-composer', [
                    'inputId' => $inputId ?? 'ai-assistant-input-page',
                    'voiceBtnId' => $voiceBtnId ?? 'ai-voice-btn-page',
                    'voiceIconId' => $voiceIconId ?? 'ai-voice-icon-page',
                    'placeholder' => 'Ask Payroll AI',
                ])
            </div>
        </main>

        <aside class="ai-gemini-sidebar ai-gemini-sidebar--right {{ $showNavSidebar ? 'is-open' : '' }}">
            <div class="ai-gemini-sidebar-top">
                <div class="ai-gemini-brand">
                    <span class="ai-gemini-logo" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="url(#gemini-star)"/>
                            <defs>
                                <linearGradient id="gemini-star" x1="2" y1="2" x2="22" y2="22">
                                    <stop stop-color="#4285F4"/>
                                    <stop offset="0.5" stop-color="#9B72CB"/>
                                    <stop offset="1" stop-color="#D96570"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </span>
                    <span class="ai-gemini-brand-text">Payroll AI</span>
                </div>
                <button type="button" class="ai-gemini-sidebar-close d-xl-none" wire:click.stop="toggleNavSidebar" aria-label="Close assistant panel">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <nav class="ai-gemini-nav">
                <button type="button" class="ai-gemini-nav-item" wire:click.stop="newConversation">
                    <i class="far fa-edit"></i>
                    <span>New chat</span>
                </button>
            </nav>

            <div class="ai-gemini-sidebar-footer">
                <div class="ai-gemini-user">
                    <span class="ai-gemini-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</span>
                    <span class="ai-gemini-user-name">{{ auth()->user()->name ?? 'User' }}</span>
                </div>
            </div>
        </aside>

        @if($showNavSidebar)
            <div class="ai-gemini-backdrop d-xl-none" wire:click.stop="toggleNavSidebar"></div>
        @endif
    </div>
@endif
