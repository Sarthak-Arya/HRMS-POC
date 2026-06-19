@php
    $inputId = $inputId ?? 'ai-assistant-input';
    $voiceBtnId = $voiceBtnId ?? 'ai-voice-btn';
    $voiceIconId = $voiceIconId ?? 'ai-voice-icon';
@endphp

<div class="ai-gemini-composer">
    <div class="ai-gemini-input-pill">
        <label class="ai-gemini-pill-btn ai-gemini-pill-btn--attach" title="Upload Excel">
            <i class="fas fa-plus"></i>
            <input type="file" class="d-none" wire:model="excelFile" accept=".xlsx,.xls,.csv">
        </label>
        <textarea
            class="ai-assistant-input ai-gemini-input"
            rows="1"
            placeholder="{{ $placeholder ?? 'Ask Payroll AI' }}"
            wire:model.defer="input"
            wire:keydown.enter.prevent="sendMessage"
            id="{{ $inputId }}"
            @if($isProcessing) disabled @endif
        ></textarea>
        <div class="ai-gemini-pill-actions">
            <button type="button"
                class="ai-gemini-pill-btn ai-voice-btn"
                id="{{ $voiceBtnId }}"
                title="Voice input"
                @if($isProcessing) disabled @endif>
                <i class="fas fa-microphone ai-voice-icon" id="{{ $voiceIconId }}"></i>
            </button>
            <button type="button"
                class="ai-gemini-pill-btn ai-gemini-pill-btn--send ai-action-btn-send"
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                wire:target="sendMessage"
                title="Send message"
                @if($isProcessing) disabled @endif>
                <span wire:loading.remove wire:target="sendMessage">
                    <i class="fas fa-arrow-up"></i>
                </span>
                <span wire:loading wire:target="sendMessage">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </span>
            </button>
        </div>
    </div>

    <div class="ai-gemini-composer-meta">
        <div class="ai-gemini-lang-chips" role="group" aria-label="Speech language">
            <button type="button" class="ai-gemini-lang-chip ai-stt-lang-btn" data-lang="hi-IN">हिंदी</button>
            <button type="button" class="ai-gemini-lang-chip ai-stt-lang-btn" data-lang="en-IN">EN (IN)</button>
            <button type="button" class="ai-gemini-lang-chip ai-stt-lang-btn" data-lang="en-US">EN (US)</button>
        </div>
        @if($excelFile)
            <span class="ai-gemini-file-badge">
                <i class="fas fa-file-excel"></i>
                {{ $excelFile->getClientOriginalName() }}
            </span>
        @endif
        <div wire:loading wire:target="excelFile" class="ai-gemini-uploading">Uploading file...</div>
    </div>
</div>
