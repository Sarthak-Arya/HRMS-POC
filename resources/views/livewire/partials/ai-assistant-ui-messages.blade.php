@php
    $messagesScrollId = $messagesScrollId ?? 'ai-messages-scroll-page';
    $suggestions = [
        [
            'icon' => 'analytics',
            'title' => "Analyze last month's deductions",
            'description' => 'Compare variances between recent payroll cycles.',
            'prompt' => "Analyze last month's payroll deductions and highlight any significant variances.",
        ],
        [
            'icon' => 'gavel',
            'title' => 'Generate a compliance summary',
            'description' => 'Review statutory contributions and filing readiness.',
            'prompt' => 'Generate a compliance summary for PF, ESI, and tax obligations for this company.',
        ],
        [
            'icon' => 'trending_up',
            'title' => 'Explain payroll variance',
            'description' => 'Identify key drivers behind net pay changes.',
            'prompt' => 'Explain the main drivers behind payroll variance in the latest run compared to the previous month.',
        ],
    ];
@endphp

<div class="ui-ai-messages ai-assistant-messages"
    id="{{ $messagesScrollId }}"
    wire:key="ai-messages-{{ $conversationId ?? 'new' }}-{{ count($messages) }}">

    @if(empty($messages) && ($showEmptyState ?? true))
        <div class="ui-ai-welcome">
            <h2 class="ui-ai-welcome-title">How can I help you today?</h2>
            <div class="ui-ai-suggestions">
                @foreach($suggestions as $suggestion)
                    <button type="button"
                        class="ui-ai-suggestion-card"
                        wire:click.stop="applySuggestion({{ json_encode($suggestion['prompt']) }})"
                        @if($isProcessing) disabled @endif>
                        <span class="material-symbols-outlined ui-ai-suggestion-icon">{{ $suggestion['icon'] }}</span>
                        <span class="ui-ai-suggestion-title">{{ $suggestion['title'] }}</span>
                        <span class="ui-ai-suggestion-desc">{{ $suggestion['description'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @foreach($messages as $msg)
        <div class="ui-ai-message ui-ai-message--{{ $msg['role'] }}">
            @if($msg['role'] === 'assistant')
                <div class="ui-ai-message-avatar" aria-hidden="true">
                    <span class="material-symbols-outlined">auto_awesome</span>
                </div>
            @endif
            <div class="ui-ai-message-content">
                {!! \App\Support\AiMessageFormatter::format($msg['content']) !!}
            </div>
        </div>
    @endforeach

    @if($isProcessing)
        <div class="ui-ai-message ui-ai-message--assistant"
            wire:key="ai-processing-{{ count($messages) }}"
            @if($shouldProcess) wire:init="processMessage" @endif>
            <div class="ui-ai-message-avatar" aria-hidden="true">
                <span class="material-symbols-outlined">auto_awesome</span>
            </div>
            <div class="ui-ai-message-content ui-ai-message-content--loading">
                <span class="ui-ai-dot-pulse"></span>
                Thinking...
            </div>
        </div>
    @endif

    @if($errorMessage)
        <div class="ui-ai-error">{{ $errorMessage }}</div>
    @endif
</div>

@include('livewire.partials.ai-assistant-markdown-styles')
