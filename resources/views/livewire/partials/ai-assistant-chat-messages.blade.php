@php
    $messagesScrollId = $messagesScrollId ?? 'ai-messages-scroll';
    $inputId = $inputId ?? 'ai-assistant-input';
    $voiceBtnId = $voiceBtnId ?? 'ai-voice-btn';
    $voiceIconId = $voiceIconId ?? 'ai-voice-icon';
    $starGradientId = $starGradientId ?? 'ai-star-gradient';
@endphp

<div class="ai-gemini-messages ai-assistant-messages"
    id="{{ $messagesScrollId }}"
    wire:key="ai-messages-{{ $conversationId ?? 'new' }}-{{ count($messages) }}">
    @if(empty($messages) && ($showEmptyState ?? true))
        <div class="ai-gemini-welcome {{ !empty($welcomeCompact) ? 'ai-gemini-welcome--compact' : '' }}">
            <h2 class="ai-gemini-welcome-title">What should we focus on?</h2>
            <p class="ai-gemini-welcome-sub">Ask about employees, attendance, payroll, or attach an Excel file.</p>
        </div>
    @endif

    @foreach($messages as $msg)
        <div class="ai-gemini-message ai-gemini-message--{{ $msg['role'] }}">
            @if($msg['role'] === 'assistant')
                <div class="ai-gemini-message-avatar" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="url(#{{ $starGradientId }})"/>
                        <defs>
                            <linearGradient id="{{ $starGradientId }}" x1="2" y1="2" x2="22" y2="22">
                                <stop stop-color="#4285F4"/>
                                <stop offset="0.5" stop-color="#9B72CB"/>
                                <stop offset="1" stop-color="#D96570"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            @endif
            <div class="ai-gemini-message-content">
                {!! \App\Support\AiMessageFormatter::format($msg['content']) !!}
            </div>
        </div>
    @endforeach

    @if($isProcessing)
        <div class="ai-gemini-message ai-gemini-message--assistant"
            wire:key="ai-processing-{{ count($messages) }}"
            @if($shouldProcess) wire:init="processMessage" @endif>
            <div class="ai-gemini-message-avatar" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z" fill="url(#{{ $starGradientId }}-loading"/>
                    <defs>
                        <linearGradient id="{{ $starGradientId }}-loading" x1="2" y1="2" x2="22" y2="22">
                            <stop stop-color="#4285F4"/>
                            <stop offset="0.5" stop-color="#9B72CB"/>
                            <stop offset="1" stop-color="#D96570"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
            <div class="ai-gemini-message-content ai-gemini-message-content--loading">
                <span class="ai-gemini-dot-pulse"></span>
                Thinking...
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="ai-gemini-error">{{ $errorMessage }}</div>
    @endif
</div>
