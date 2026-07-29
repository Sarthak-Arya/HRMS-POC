<main class="main-content ui-page">
    <div class="container-fluid py-4">
        <section class="ui-page-header">
            <div>
                <h1 class="ui-page-title">Payroll AI</h1>
                <p class="ui-page-subtitle">
                    {{ $companyName }}<span class="ui-page-subtitle-sep">|</span>
                    Ask about employees, attendance, payroll, or attach an Excel file for analysis.
                </p>
            </div>
        </section>

        @include('livewire.partials.ai-assistant-ui-shell', [
            'inputId' => 'ai-assistant-input-page',
            'voiceBtnId' => 'ai-voice-btn-page',
            'voiceIconId' => 'ai-voice-icon-page',
            'messagesScrollId' => 'ai-messages-scroll-page',
        ])

        @include('livewire.partials.ai-assistant-scripts')
    </div>
</main>
