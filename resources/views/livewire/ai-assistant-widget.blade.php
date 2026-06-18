<div class="ai-assistant-widget">
    @if($companyId)
        {{-- Floating action button --}}
        @if(!$isOpen)
            <button type="button"
                class="ai-assistant-fab"
                wire:click="toggle"
                title="AI Assistant"
                aria-label="AI Assistant">
                <i class="fas fa-robot"></i>
            </button>
        @endif

        {{-- Chat panel --}}
        @if($isOpen)
            <div class="ai-assistant-panel card">
                <div class="ai-panel-layout">
                    {{-- Conversation history sidebar --}}
                    <aside class="ai-history-sidebar {{ $showHistory ? 'is-open' : '' }}">
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

                    {{-- Main chat area --}}
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
                                <button type="button" class="ai-header-btn" wire:click.stop="toggle" title="Close">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        @if($statusMessage)
                            <div class="ai-status-banner" wire:poll.2000ms="clearStatusMessage">
                                {{ $statusMessage }}
                            </div>
                        @endif

                        <div class="card-body ai-assistant-messages p-3" id="ai-messages-scroll" wire:key="ai-messages-{{ $conversationId ?? 'new' }}-{{ count($messages) }}">
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
                        id="ai-assistant-input"
                        @if($isProcessing) disabled @endif
                    ></textarea>
                    <div class="ai-assistant-actions">
                        <button type="button"
                            class="ai-action-btn"
                            id="ai-voice-btn"
                            title="Voice input"
                            @if($isProcessing) disabled @endif>
                            <i class="fas fa-microphone" id="ai-voice-icon"></i>
                        </button>
                        <label class="ai-action-btn mb-0" title="Upload Excel">
                            <i class="fas fa-file-excel"></i>
                            <input type="file" class="d-none" wire:model="excelFile" accept=".xlsx,.xls,.csv">
                        </label>
                        <button type="button"
                            class="ai-action-btn ai-action-btn-send"
                            wire:click="sendMessage"
                            title="Send message"
                            @if($isProcessing) disabled @endif>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    @if($excelFile)
                        <small class="text-success d-block mt-2">
                            <i class="fas fa-check"></i> File ready — send to import
                        </small>
                    @endif
                    <div wire:loading wire:target="excelFile" class="small text-muted mt-1">Uploading file...</div>
                </div>
                    </div>{{-- /.ai-chat-main --}}
                </div>{{-- /.ai-panel-layout --}}
            </div>
        @endif
    @endif

    <style>
        .ai-assistant-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
            width: auto;
            max-width: calc(100vw - 48px);
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        .ai-assistant-fab {
            width: 56px;
            height: 56px;
            min-width: 56px;
            padding: 0;
            border: none;
            border-radius: 50%;
            background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            flex-shrink: 0;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(0, 82, 255, 0.35);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .ai-assistant-fab:hover {
            box-shadow: 0 6px 18px rgba(0, 82, 255, 0.45);
            transform: translateY(-1px);
        }
        .ai-assistant-fab:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 82, 255, 0.25), 0 4px 14px rgba(0, 82, 255, 0.35);
        }
        .ai-assistant-panel {
            width: 680px;
            max-width: calc(100vw - 48px);
            height: 560px;
            max-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
        }
        .ai-panel-layout {
            display: flex;
            flex: 1;
            min-height: 0;
            position: relative;
        }
        .ai-history-sidebar {
            width: 220px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            background: #f1f3f5;
            border-right: 1px solid #e9ecef;
            min-height: 0;
        }
        .ai-new-chat-btn {
            margin: 10px;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            color: #344767;
            font-size: 0.8125rem;
            font-weight: 600;
            text-align: left;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease;
        }
        .ai-new-chat-btn:hover {
            background: #e8f1ff;
            border-color: #0052ff;
            color: #0052ff;
        }
        .ai-history-list {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 0 8px 8px;
        }
        .ai-history-empty {
            padding: 12px 8px;
            margin: 0;
            font-size: 0.8125rem;
            color: #8392ab;
            text-align: center;
        }
        .ai-history-item {
            display: flex;
            align-items: center;
            gap: 4px;
            border-radius: 8px;
            margin-bottom: 2px;
        }
        .ai-history-item.active {
            background: #e8f1ff;
        }
        .ai-history-item:hover {
            background: #e9ecef;
        }
        .ai-history-item.active:hover {
            background: #dce8ff;
        }
        .ai-history-link {
            flex: 1;
            min-width: 0;
            padding: 8px;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
        }
        .ai-history-title {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #344767;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ai-history-time {
            display: block;
            font-size: 0.6875rem;
            color: #8392ab;
            margin-top: 2px;
        }
        .ai-history-delete {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
            margin-right: 4px;
            border: none;
            border-radius: 6px;
            background: transparent;
            color: #8392ab;
            opacity: 0;
            cursor: pointer;
            transition: opacity 0.15s ease, background 0.15s ease, color 0.15s ease;
        }
        .ai-history-item:hover .ai-history-delete {
            opacity: 1;
        }
        .ai-history-delete:hover {
            background: #fee2e2;
            color: #dc3545;
        }
        .ai-chat-main {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .ai-history-toggle {
            display: none;
        }
        @media (max-width: 720px) {
            .ai-assistant-panel {
                width: calc(100vw - 32px);
                height: calc(100vh - 80px);
            }
            .ai-history-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                bottom: 0;
                z-index: 3;
                transform: translateX(-100%);
                transition: transform 0.2s ease;
                box-shadow: 4px 0 16px rgba(0, 0, 0, 0.1);
            }
            .ai-history-sidebar.is-open {
                transform: translateX(0);
            }
            .ai-history-toggle {
                display: inline-flex;
            }
        }
        .ai-assistant-panel .card-header {
            flex-shrink: 0;
            border-bottom: none;
        }
        .ai-assistant-header-actions {
            display: flex;
            gap: 4px;
            position: relative;
            z-index: 2;
        }
        .ai-status-banner {
            flex-shrink: 0;
            padding: 8px 12px;
            background: #e8f1ff;
            color: #0052ff;
            font-size: 0.8125rem;
            font-weight: 600;
            text-align: center;
            border-bottom: 1px solid #d0e2ff;
        }
        .ai-header-btn {
            width: 32px;
            height: 32px;
            padding: 0;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease;
        }
        .ai-header-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .ai-assistant-messages {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            background: #f8f9fa;
        }
        .ai-assistant-footer {
            flex-shrink: 0;
            padding: 12px;
            background: #fff;
        }
        .ai-lang-tabs {
            display: flex;
            width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .ai-lang-tab {
            flex: 1;
            padding: 6px 8px;
            border: none;
            background: #fff;
            color: #67748e;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: none;
            letter-spacing: normal;
            cursor: pointer;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .ai-lang-tab + .ai-lang-tab {
            border-left: 1px solid #dee2e6;
        }
        .ai-lang-tab.active {
            background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
            color: #fff;
        }
        .ai-assistant-input {
            width: 100%;
            margin-top: 8px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 0.875rem;
            resize: none;
            box-shadow: none;
        }
        .ai-assistant-input:focus {
            border-color: #0052ff;
            box-shadow: 0 0 0 2px rgba(0, 82, 255, 0.15);
        }
        .ai-assistant-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
        }
        .ai-action-btn {
            width: 36px;
            height: 36px;
            min-width: 36px;
            padding: 0;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background: #fff;
            color: #67748e;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-transform: none;
            letter-spacing: normal;
            box-shadow: none;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }
        .ai-action-btn:hover {
            background: #f8f9fa;
            border-color: #ced4da;
            transform: none;
        }
        .ai-action-btn-send {
            margin-left: auto;
            border: none;
            background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
            color: #fff;
        }
        .ai-action-btn-send:hover {
            background: linear-gradient(180deg, #0046dd 0%, #0066ee 100%);
            color: #fff;
            border-color: transparent;
        }
        .ai-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .ai-message-user .ai-message-bubble {
            background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
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
            background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
            color: #fff;
            border-color: #0052ff;
        }
        #ai-voice-btn.listening #ai-voice-icon {
            animation: pulse 1s infinite;
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
                setupAiVoiceDelegates();
                refreshAiVoiceMicBtn();
                syncSttLangButtons(getSttLang());
            });
            document.addEventListener('livewire:update', function () {
                scrollAiMessages();
                refreshAiVoiceMicBtn();
                syncSttLangButtons(getSttLang());
                restoreAiVoiceListeningUi();
            });
            window.addEventListener('ai-conversation-reset', function () {
                var scrollEl = document.getElementById('ai-messages-scroll');
                if (scrollEl) {
                    scrollEl.scrollTop = 0;
                }
                var input = document.getElementById('ai-assistant-input');
                if (input) {
                    input.focus();
                }
            });
            window.addEventListener('ai-conversation-loaded', function () {
                scrollAiMessages();
                var input = document.getElementById('ai-assistant-input');
                if (input) {
                    input.focus();
                }
            });

            function scrollAiMessages() {
                const el = document.getElementById('ai-messages-scroll');
                if (el) el.scrollTop = el.scrollHeight;
            }

            var aiVoiceState = {
                recognition: null,
                listening: false,
                manualStop: false,
                baseText: '',
                sessionFinal: '',
                micBtn: null,
            };

            function getAiVoiceWireId() {
                var btn = aiVoiceState.micBtn || document.getElementById('ai-voice-btn');
                if (!btn) return null;
                var component = btn.closest('[wire\\:id]');
                return component ? component.getAttribute('wire:id') : null;
            }

            function syncAiVoiceInputToLivewire(value) {
                var wireId = getAiVoiceWireId();
                if (window.Livewire && wireId) {
                    Livewire.find(wireId).set('input', value);
                }
            }

            function updateAiVoiceInputDom(value) {
                var input = document.getElementById('ai-assistant-input');
                if (input) {
                    input.value = value;
                }
            }

            function syncAiVoiceInput(value, pushToLivewire) {
                updateAiVoiceInputDom(value);
                if (pushToLivewire) {
                    syncAiVoiceInputToLivewire(value);
                }
            }

            function restoreAiVoiceListeningUi() {
                if (!aiVoiceState.listening || aiVoiceState.manualStop) return;
                var domBtn = document.getElementById('ai-voice-btn');
                if (!domBtn) return;
                aiVoiceState.micBtn = domBtn;
                domBtn.classList.add('listening');
                var lang = getSttLang();
                var labels = {'hi-IN': 'Hindi', 'en-IN': 'English (India)', 'en-US': 'English (US)'};
                domBtn.title = 'Stop voice input — ' + (labels[lang] || lang);
            }

            function setAiVoiceListeningUi(active) {
                aiVoiceState.listening = active;
                var btn = document.getElementById('ai-voice-btn') || aiVoiceState.micBtn;
                if (btn) {
                    aiVoiceState.micBtn = btn;
                }
                if (!btn) return;
                btn.classList.toggle('listening', active);
                var lang = getSttLang();
                var labels = {'hi-IN': 'Hindi', 'en-IN': 'English (India)', 'en-US': 'English (US)'};
                btn.title = active
                    ? 'Stop voice input — ' + (labels[lang] || lang)
                    : 'Voice input — ' + (labels[lang] || lang);
            }

            function pushAiVoiceInputToLivewire() {
                var input = document.getElementById('ai-assistant-input');
                if (input) {
                    syncAiVoiceInputToLivewire(input.value);
                }
            }

            function stopAiVoiceListening() {
                if (!aiVoiceState.listening && !aiVoiceState.recognition) return;
                aiVoiceState.manualStop = true;
                pushAiVoiceInputToLivewire();
                if (aiVoiceState.recognition) {
                    try {
                        aiVoiceState.recognition.stop();
                    } catch (err) {
                        setAiVoiceListeningUi(false);
                    }
                } else {
                    setAiVoiceListeningUi(false);
                }
            }

            function createAiVoiceRecognition(SpeechRecognition) {
                var recognition = new SpeechRecognition();
                recognition.lang = getSttLang();
                recognition.continuous = true;
                recognition.interimResults = true;
                recognition.maxAlternatives = 1;

                recognition.onstart = function () {
                    setAiVoiceListeningUi(true);
                };

                recognition.onend = function () {
                    if (!aiVoiceState.manualStop) {
                        try {
                            recognition.start();
                        } catch (err) {
                            setTimeout(function () {
                                if (!aiVoiceState.manualStop) {
                                    try {
                                        recognition.start();
                                    } catch (retryErr) {
                                        aiVoiceState.manualStop = true;
                                        setAiVoiceListeningUi(false);
                                    }
                                }
                            }, 150);
                        }
                        return;
                    }
                    setAiVoiceListeningUi(false);
                    pushAiVoiceInputToLivewire();
                    aiVoiceState.recognition = null;
                };

                recognition.onresult = function (event) {
                    var interim = '';
                    for (var i = event.resultIndex; i < event.results.length; i++) {
                        var transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            aiVoiceState.sessionFinal += transcript;
                            if (!aiVoiceState.sessionFinal.endsWith(' ')) {
                                aiVoiceState.sessionFinal += ' ';
                            }
                        } else {
                            interim += transcript;
                        }
                    }
                    syncAiVoiceInput(aiVoiceState.baseText + aiVoiceState.sessionFinal + interim, false);
                };

                recognition.onerror = function (event) {
                    if (event.error === 'aborted' && aiVoiceState.manualStop) return;
                    if (event.error === 'no-speech') return;
                    console.warn('Speech recognition error:', event.error);
                    aiVoiceState.manualStop = true;
                    setAiVoiceListeningUi(false);
                    aiVoiceState.recognition = null;
                };

                return recognition;
            }

            function startAiVoiceListening() {
                var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) return;

                var input = document.getElementById('ai-assistant-input');
                aiVoiceState.baseText = input ? input.value : '';
                if (aiVoiceState.baseText && !aiVoiceState.baseText.endsWith(' ')) {
                    aiVoiceState.baseText += ' ';
                }
                aiVoiceState.sessionFinal = '';
                aiVoiceState.manualStop = false;

                aiVoiceState.recognition = createAiVoiceRecognition(SpeechRecognition);

                try {
                    aiVoiceState.recognition.start();
                } catch (err) {
                    console.warn('Speech recognition start failed:', err.message);
                    aiVoiceState.recognition = null;
                    setAiVoiceListeningUi(false);
                }
            }

            function handleAiVoiceMicClick() {
                if (aiVoiceState.listening || aiVoiceState.recognition) {
                    stopAiVoiceListening();
                    return;
                }
                startAiVoiceListening();
            }

            function setupAiVoiceDelegates() {
                if (window.__aiVoiceDelegatesReady) return;
                window.__aiVoiceDelegatesReady = true;

                document.addEventListener('click', function (e) {
                    var micBtn = e.target.closest('#ai-voice-btn');
                    if (micBtn && micBtn.closest('.ai-assistant-widget') && !micBtn.disabled) {
                        aiVoiceState.micBtn = micBtn;
                        handleAiVoiceMicClick();
                        return;
                    }
                    var sendBtn = e.target.closest('.ai-assistant-widget .ai-action-btn-send');
                    if (sendBtn && !sendBtn.disabled) {
                        stopAiVoiceListening();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && !e.shiftKey && e.target && e.target.id === 'ai-assistant-input') {
                        stopAiVoiceListening();
                    }
                });
            }

            function refreshAiVoiceMicBtn() {
                var btn = document.getElementById('ai-voice-btn');
                if (!btn) return;
                aiVoiceState.micBtn = btn;

                var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                if (!SpeechRecognition) {
                    btn.title = 'Voice not supported in this browser';
                    btn.disabled = true;
                    return;
                }

                setSttLang(getSttLang());
            }

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                setTimeout(function () {
                    setupAiVoiceDelegates();
                    refreshAiVoiceMicBtn();
                    syncSttLangButtons(getSttLang());
                }, 500);
            }
        </script>
    @endonce
</div>
