<div class="ai-assistant-widget" wire:ignore.self>
    @if($companyId)
        {{-- Floating action button --}}
        @if(!$isOpen)
            <button type="button"
                class="ai-assistant-fab btn btn-primary btn-lg rounded-circle shadow-lg"
                wire:click="toggle"
                title="AI Assistant">
                <i class="fas fa-robot"></i>
            </button>
        @endif

        {{-- Chat panel --}}
        @if($isOpen)
            <div class="ai-assistant-panel card shadow-lg">
                <div class="card-header bg-gradient-primary d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="text-white">
                        <i class="fas fa-robot me-2"></i>
                        <strong>Payroll AI Assistant</strong>
                        <small class="d-block opacity-8">Hindi / English</small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-link text-white btn-sm p-1" wire:click="newConversation" title="New chat">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-link text-white btn-sm p-1" wire:click="toggle" title="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body ai-assistant-messages p-3" id="ai-messages-scroll">
                    @if(empty($messages))
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-comments fa-2x mb-2"></i>
                            <p class="mb-0 small">Ask me to add, update, search employees or import Excel.</p>
                            <p class="mb-0 small text-secondary">मुझसे कर्मचारी जोड़ने, अपडेट करने या Excel import करने को कहें।</p>
                        </div>
                    @endif

                    @foreach($messages as $msg)
                        <div class="ai-message mb-2 {{ $msg['role'] === 'user' ? 'ai-message-user' : 'ai-message-assistant' }}">
                            <div class="ai-message-bubble">
                                {!! nl2br(e($msg['content'])) !!}
                            </div>
                        </div>
                    @endforeach

                    @if($isProcessing)
                        <div class="ai-message ai-message-assistant mb-2">
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

                <div class="card-footer p-2 border-top">
                    <div class="ai-stt-lang-select btn-group btn-group-sm w-100 mb-2" role="group" aria-label="Speech language">
                        <button type="button" class="btn btn-outline-secondary ai-stt-lang-btn" data-lang="hi-IN" title="Hindi">हिंदी</button>
                        <button type="button" class="btn btn-outline-secondary ai-stt-lang-btn" data-lang="en-IN" title="English (India)">EN (IN)</button>
                        <button type="button" class="btn btn-outline-secondary ai-stt-lang-btn" data-lang="en-US" title="English (US)">EN (US)</button>
                    </div>
                    <div class="d-flex gap-1 align-items-end">
                        <div class="flex-grow-1">
                            <textarea
                                class="form-control form-control-sm"
                                rows="2"
                                placeholder="Type or use mic... / टाइप करें या माइक का उपयोग करें"
                                wire:model.defer="input"
                                wire:keydown.enter.prevent="sendMessage"
                                id="ai-assistant-input"
                                @if($isProcessing) disabled @endif
                            ></textarea>
                        </div>
                        <div class="d-flex flex-column gap-1">
                            <button type="button"
                                class="btn btn-outline-secondary btn-sm"
                                id="ai-voice-btn"
                                title="Voice input"
                                @if($isProcessing) disabled @endif>
                                <i class="fas fa-microphone" id="ai-voice-icon"></i>
                            </button>
                            <label class="btn btn-outline-secondary btn-sm mb-0" title="Upload Excel">
                                <i class="fas fa-file-excel"></i>
                                <input type="file" class="d-none" wire:model="excelFile" accept=".xlsx,.xls,.csv">
                            </label>
                            <button type="button"
                                class="btn btn-primary btn-sm"
                                wire:click="sendMessage"
                                @if($isProcessing) disabled @endif>
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                    @if($excelFile)
                        <small class="text-success d-block mt-1">
                            <i class="fas fa-check"></i> File ready — send to import
                        </small>
                    @endif
                    <div wire:loading wire:target="excelFile" class="small text-muted mt-1">Uploading file...</div>
                </div>
            </div>
        @endif
    @endif

    <style>
        .ai-assistant-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
        }
        .ai-assistant-fab {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .ai-assistant-panel {
            width: 380px;
            max-width: calc(100vw - 48px);
            height: 520px;
            max-height: calc(100vh - 100px);
            display: flex;
            flex-direction: column;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        .ai-assistant-messages {
            flex: 1;
            overflow-y: auto;
            background: #f8f9fa;
        }
        .ai-message-user {
            display: flex;
            justify-content: flex-end;
        }
        .ai-message-assistant {
            display: flex;
            justify-content: flex-start;
        }
        .ai-message-bubble {
            max-width: 85%;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        .ai-message-user .ai-message-bubble {
            background: linear-gradient(180deg, #0052ff 0%, #000000 100%);
            color: #fff;
            border-bottom-right-radius: 4px;
        }
        .ai-message-assistant .ai-message-bubble {
            background: #fff;
            color: #344767;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }
        #ai-voice-btn.listening {
            background: linear-gradient(180deg, #0052ff 0%, #000000 100%);
            color: #fff;
            border-color: #0052ff;
        }
        #ai-voice-btn.listening #ai-voice-icon {
            animation: pulse 1s infinite;
        }
        .ai-stt-lang-select .btn.active {
            background: linear-gradient(180deg, #0052ff 0%, #000000 100%);
            border-color: #0052ff;
            color: #fff;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>

    @once
        <script>
            var AI_STT_LANG_KEY = 'ai-assistant-stt-lang';
            var AI_STT_LANG_DEFAULT = 'en-IN';

            function getSttLang() {
                return localStorage.getItem(AI_STT_LANG_KEY) || AI_STT_LANG_DEFAULT;
            }

            function setSttLang(lang) {
                localStorage.setItem(AI_STT_LANG_KEY, lang);
                syncSttLangButtons(lang);
                var micBtn = document.getElementById('ai-voice-btn');
                if (micBtn) {
                    var labels = {'hi-IN': 'Hindi', 'en-IN': 'English (India)', 'en-US': 'English (US)'};
                    micBtn.title = 'Voice input — ' + (labels[lang] || lang);
                }
            }

            function syncSttLangButtons(lang) {
                document.querySelectorAll('.ai-assistant-widget .ai-stt-lang-btn').forEach(function (btn) {
                    btn.classList.toggle('active', btn.dataset.lang === lang);
                });
            }

            document.addEventListener('click', function (e) {
                var langBtn = e.target.closest('.ai-stt-lang-btn');
                if (!langBtn || !langBtn.closest('.ai-assistant-widget')) return;
                if (langBtn.dataset.lang) {
                    setSttLang(langBtn.dataset.lang);
                }
            });

            document.addEventListener('livewire:load', function () {
                initAiVoice();
                syncSttLangButtons(getSttLang());
            });
            document.addEventListener('livewire:update', function () {
                scrollAiMessages();
                initAiVoice();
                syncSttLangButtons(getSttLang());
            });

            function scrollAiMessages() {
                const el = document.getElementById('ai-messages-scroll');
                if (el) el.scrollTop = el.scrollHeight;
            }

            function initAiVoice() {
                const btn = document.getElementById('ai-voice-btn');
                if (!btn || btn.dataset.initialized) return;
                btn.dataset.initialized = '1';

                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) {
                    btn.title = 'Voice not supported in this browser';
                    btn.disabled = true;
                    return;
                }

                setSttLang(getSttLang());

                let recognition = null;
                let listening = false;

                btn.addEventListener('click', function () {
                    if (listening && recognition) {
                        recognition.stop();
                        return;
                    }

                    recognition = new SpeechRecognition();
                    recognition.lang = getSttLang();
                    recognition.interimResults = false;
                    recognition.maxAlternatives = 1;

                    recognition.onstart = function () {
                        listening = true;
                        btn.classList.add('listening');
                    };

                    recognition.onend = function () {
                        listening = false;
                        btn.classList.remove('listening');
                    };

                    recognition.onresult = function (event) {
                        const transcript = event.results[0][0].transcript;
                        const input = document.getElementById('ai-assistant-input');
                        const component = btn.closest('[wire\\:id]');
                        const wireId = component ? component.getAttribute('wire:id') : null;

                        if (input) {
                            input.value = transcript;
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        }
                        if (window.Livewire && wireId) {
                            Livewire.find(wireId).set('input', transcript);
                        }
                    };

                    recognition.onerror = function () {
                        listening = false;
                        btn.classList.remove('listening');
                    };

                    try {
                        recognition.start();
                    } catch (err) {
                        console.warn('Speech recognition start failed:', err.message);
                    }
                });
            }

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                setTimeout(function () {
                    initAiVoice();
                    syncSttLangButtons(getSttLang());
                }, 500);
            }
        </script>
    @endonce
</div>
