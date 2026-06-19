<div class="ai-assistant-panel card {{ $panelClass ?? '' }}">
    <div class="ai-panel-layout">
        <aside class="ai-history-sidebar {{ $showHistory ? 'is-open' : '' }} {{ ($fullPage ?? false) ? 'ai-history-sidebar-page' : '' }}">
            <button type="button" class="ai-new-chat-btn" wire:click.stop="newConversation">
                <i class="fas fa-plus me-1"></i> New chat
            </button>
            <div class="ai-history-list">
                @forelse($conversations as $conversation)
                    <div class="ai-history-item {{ $conversationId === $conversation['id'] ? 'active' : '' }}">
                        <button type="button"
                            class="ai-history-link"
                            wire:click.stop="loadConversation({{ $conversation['id'] }})"
                            title="{{ $conversation['title'] }}">
                            <span class="ai-history-title">{{ $conversation['title'] }}</span>
                            <span class="ai-history-time">{{ $conversation['updated_at'] }}</span>
                        </button>
                        <button type="button"
                            class="ai-history-delete"
                            wire:click.stop="deleteConversation({{ $conversation['id'] }})"
                            title="Delete chat">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                @empty
                    <p class="ai-history-empty">No past chats yet.</p>
                @endforelse
            </div>
        </aside>

        <div class="ai-chat-main">
            <div class="card-header bg-gradient-primary d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2 text-white">
                    <button type="button" class="ai-header-btn ai-history-toggle" wire:click.stop="toggleHistory" title="Chat history">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <strong class="d-block">Payroll AI Assistant</strong>
                        <small class="opacity-8">Hindi / English</small>
                    </div>
                </div>
                <div class="ai-assistant-header-actions">
                    <button type="button" class="ai-header-btn" wire:click.stop="newConversation" title="New chat">
                        <i class="fas fa-plus"></i>
                    </button>
                    @if($showCloseButton ?? false)
                        <button type="button" class="ai-header-btn" wire:click.stop="toggle" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            </div>

            @if($statusMessage)
                <div class="ai-status-banner" wire:poll.2000ms="clearStatusMessage">
                    {{ $statusMessage }}
                </div>
            @endif

            <div class="card-body ai-assistant-messages p-3" id="{{ $messagesScrollId ?? 'ai-messages-scroll' }}" wire:key="ai-messages-{{ $conversationId ?? 'new' }}-{{ count($messages) }}">
                @if(empty($messages))
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p class="mb-0 small">Ask me to add, update, search employees, attendance, or attach an Excel file.</p>
                        <p class="mb-0 small text-secondary">कर्मचारी/हाजिरी अपडेट करें या Excel फ़ाइल संलग्न करें।</p>
                    </div>
                @endif

                @foreach($messages as $msg)
                    <div class="ai-message mb-2 {{ $msg['role'] === 'user' ? 'ai-message-user' : 'ai-message-assistant' }}">
                        <div class="ai-message-bubble">
                            {!! \App\Support\AiMessageFormatter::format($msg['content']) !!}
                        </div>
                    </div>
                @endforeach

                @if($isProcessing)
                    <div class="ai-message ai-message-assistant mb-2"
                        wire:key="ai-processing-{{ count($messages) }}"
                        @if($shouldProcess) wire:init="processMessage" @endif>
                        <div class="ai-message-bubble">
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Thinking...
                        </div>
                    </div>
                @endif

                @if($errorMessage)
                    <div class="alert alert-warning py-2 px-3 small mb-0">{{ $errorMessage }}</div>
                @endif
            </div>

            <div class="card-footer ai-assistant-footer border-top">
                <div class="ai-lang-tabs mb-2" role="group" aria-label="Speech language">
                    <button type="button" class="ai-lang-tab ai-stt-lang-btn" data-lang="hi-IN" title="Hindi">हिंदी</button>
                    <button type="button" class="ai-lang-tab ai-stt-lang-btn" data-lang="en-IN" title="English (India)">EN (IN)</button>
                    <button type="button" class="ai-lang-tab ai-stt-lang-btn" data-lang="en-US" title="English (US)">EN (US)</button>
                </div>
                <textarea
                    class="ai-assistant-input form-control"
                    rows="2"
                    placeholder="Type or use mic... / टाइप करें या माइक का उपयोग करें"
                    wire:model.defer="input"
                    wire:keydown.enter.prevent="sendMessage"
                    id="{{ $inputId ?? 'ai-assistant-input' }}"
                    @if($isProcessing) disabled @endif
                ></textarea>
                <div class="ai-assistant-actions">
                    <button type="button"
                        class="ai-action-btn ai-voice-btn"
                        id="{{ $voiceBtnId ?? 'ai-voice-btn' }}"
                        title="Voice input"
                        @if($isProcessing) disabled @endif>
                        <i class="fas fa-microphone ai-voice-icon" id="{{ $voiceIconId ?? 'ai-voice-icon' }}"></i>
                    </button>
                    <label class="ai-action-btn mb-0" title="Upload Excel">
                        <i class="fas fa-file-excel"></i>
                        <input type="file" class="d-none" wire:model="excelFile" accept=".xlsx,.xls,.csv">
                    </label>
                    <button type="button"
                        class="ai-action-btn ai-action-btn-send"
                        wire:click="sendMessage"
                        wire:loading.attr="disabled"
                        wire:target="sendMessage"
                        title="Send message"
                        @if($isProcessing) disabled @endif>
                        <span wire:loading.remove wire:target="sendMessage">
                            <i class="fas fa-paper-plane"></i>
                        </span>
                        <span wire:loading wire:target="sendMessage">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                </div>
                @if($excelFile)
                    <small class="text-success d-block mt-2">
                        <i class="fas fa-check"></i>
                        {{ $excelFile->getClientOriginalName() }} — add your instruction and send
                    </small>
                @endif
                <div wire:loading wire:target="excelFile" class="small text-muted mt-1">Uploading file...</div>
            </div>
        </div>
    </div>
</div>
