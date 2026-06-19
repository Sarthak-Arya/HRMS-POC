<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ai-assistant-page-wrap">
    @include('livewire.partials.ai-assistant-gemini-shell', [
        'inputId' => 'ai-assistant-input-page',
        'voiceBtnId' => 'ai-voice-btn-page',
        'voiceIconId' => 'ai-voice-icon-page',
        'messagesScrollId' => 'ai-messages-scroll-page',
    ])

    @include('livewire.partials.ai-assistant-gemini-styles')
    @include('livewire.partials.ai-assistant-scripts')
</main>
