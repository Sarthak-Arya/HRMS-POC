@php
    $inputId = $inputId ?? 'ai-assistant-input-page';
    $voiceBtnId = $voiceBtnId ?? 'ai-voice-btn-page';
    $voiceIconId = $voiceIconId ?? 'ai-voice-icon-page';
@endphp

<div class="ui-ai-composer">
    <div class="ui-ai-input-shell">
        <label class="ui-ai-icon-btn ui-ai-attach-btn" title="Upload Excel">
            <span class="material-symbols-outlined">attach_file</span>
            <input type="file" class="d-none" wire:model="excelFile" accept=".xlsx,.xls,.csv">
        </label>

        <textarea
            class="ai-assistant-input ui-ai-input"
            rows="1"
            placeholder="Ask about payroll, compliance, or tax reporting..."
            wire:model.defer="input"
            wire:keydown.enter.prevent="sendMessage"
            id="{{ $inputId }}"
            @if($isProcessing) disabled @endif
        ></textarea>

        <div class="ui-ai-composer-actions">
            <button type="button"
                class="ui-ai-icon-btn ai-voice-btn"
                id="{{ $voiceBtnId }}"
                title="Voice input"
                @if($isProcessing) disabled @endif>
                <span class="material-symbols-outlined ai-voice-icon" id="{{ $voiceIconId }}">mic</span>
            </button>
            <button type="button"
                class="ui-ai-send-btn ai-action-btn-send"
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                title="Send message"
                @if($isProcessing) disabled @endif>
                <span wire:loading.remove wire:target="sendMessage">
                    <span class="material-symbols-outlined">arrow_upward</span>
                </span>
                <span wire:loading wire:target="sendMessage">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </span>
            </button>
        </div>
    </div>

    <div class="ui-ai-composer-meta">
        <div class="ui-ai-lang-chips" role="group" aria-label="Speech language">
            <button type="button" class="ui-ai-lang-chip ai-stt-lang-btn" data-lang="hi-IN">हिंदी</button>
            <button type="button" class="ui-ai-lang-chip ai-stt-lang-btn" data-lang="en-IN">EN (IN)</button>
            <button type="button" class="ui-ai-lang-chip ai-stt-lang-btn" data-lang="en-US">EN (US)</button>
        </div>
        @if($excelFile)
            <span class="ui-ai-file-badge">
                <span class="material-symbols-outlined">description</span>
                {{ $excelFile->getClientOriginalName() }}
            </span>
        @endif
        <div wire:loading wire:target="excelFile" class="ui-ai-uploading">Uploading file...</div>
    </div>

    <p class="ui-ai-disclaimer">
        Payroll AI can make mistakes. Verify important financial data before final approval.
    </p>
</div>
