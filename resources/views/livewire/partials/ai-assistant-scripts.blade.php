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
        document.querySelectorAll('.ai-assistant-root .ai-voice-btn').forEach(function (micBtn) {
            var labels = {'hi-IN': 'Hindi', 'en-IN': 'English (India)', 'en-US': 'English (US)'};
            micBtn.title = 'Voice input — ' + (labels[lang] || lang);
        });
    }

    function syncSttLangButtons(lang) {
        document.querySelectorAll('.ai-assistant-root .ai-stt-lang-btn, .ai-assistant-root .ai-gemini-lang-chip').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.lang === lang);
        });
    }

    function findAiRoot(el) {
        return el ? el.closest('.ai-assistant-root') : null;
    }

    function findAiInput(root) {
        return root ? root.querySelector('.ai-assistant-input') : null;
    }

    function findAiMessagesScroll(root) {
        return root ? root.querySelector('.ai-assistant-messages') : null;
    }

    function findAiVoiceBtn(root) {
        return root ? root.querySelector('.ai-voice-btn') : null;
    }

    document.addEventListener('click', function (e) {
        var langBtn = e.target.closest('.ai-stt-lang-btn');
        var root = findAiRoot(langBtn);
        if (!langBtn || !root) return;
        if (langBtn.dataset.lang) {
            setSttLang(langBtn.dataset.lang);
        }
    });

    document.addEventListener('livewire:load', function () {
        setupAiVoiceDelegates();
        refreshAiVoiceMicBtns();
        syncSttLangButtons(getSttLang());
    });
    document.addEventListener('livewire:update', function () {
        scrollAllAiMessages();
        refreshAiVoiceMicBtns();
        syncSttLangButtons(getSttLang());
        restoreAllAiVoiceListeningUi();
    });
    window.addEventListener('ai-conversation-reset', function () {
        document.querySelectorAll('.ai-assistant-root').forEach(function (root) {
            var scrollEl = findAiMessagesScroll(root);
            if (scrollEl) {
                scrollEl.scrollTop = 0;
            }
            var input = findAiInput(root);
            if (input) {
                input.focus();
            }
        });
    });
    window.addEventListener('ai-conversation-loaded', function () {
        scrollAllAiMessages();
        document.querySelectorAll('.ai-assistant-root .ai-assistant-input').forEach(function (input) {
            input.focus();
        });
    });

    function scrollAllAiMessages() {
        document.querySelectorAll('.ai-assistant-root .ai-assistant-messages').forEach(function (el) {
            el.scrollTop = el.scrollHeight;
        });
    }

    var aiVoiceState = {
        recognition: null,
        listening: false,
        manualStop: false,
        baseText: '',
        sessionFinal: '',
        activeRoot: null,
    };

    function getAiVoiceWireId(root) {
        if (!root) return null;
        var component = root.closest('[wire\\:id]') || root.querySelector('[wire\\:id]');
        if (!component && root.hasAttribute('wire:id')) {
            component = root;
        }
        return component ? component.getAttribute('wire:id') : null;
    }

    function syncAiVoiceInputToLivewire(root, value) {
        var wireId = getAiVoiceWireId(root);
        if (window.Livewire && wireId) {
            Livewire.find(wireId).set('input', value);
        }
    }

    function updateAiVoiceInputDom(root, value) {
        var input = findAiInput(root);
        if (input) {
            input.value = value;
        }
    }

    function syncAiVoiceInput(root, value, pushToLivewire) {
        updateAiVoiceInputDom(root, value);
        if (pushToLivewire) {
            syncAiVoiceInputToLivewire(root, value);
        }
    }

    function restoreAllAiVoiceListeningUi() {
        if (!aiVoiceState.listening || aiVoiceState.manualStop || !aiVoiceState.activeRoot) return;
        var micBtn = findAiVoiceBtn(aiVoiceState.activeRoot);
        if (!micBtn) return;
        setAiVoiceListeningUi(aiVoiceState.activeRoot, true);
    }

    function setAiVoiceListeningUi(root, active) {
        aiVoiceState.listening = active;
        if (active) {
            aiVoiceState.activeRoot = root;
        }
        var btn = findAiVoiceBtn(root);
        if (!btn) return;
        btn.classList.toggle('listening', active);
        var lang = getSttLang();
        var labels = {'hi-IN': 'Hindi', 'en-IN': 'English (India)', 'en-US': 'English (US)'};
        btn.title = active
            ? 'Stop voice input — ' + (labels[lang] || lang)
            : 'Voice input — ' + (labels[lang] || lang);
    }

    function pushAiVoiceInputToLivewire(root) {
        var input = findAiInput(root);
        if (input) {
            syncAiVoiceInputToLivewire(root, input.value);
        }
    }

    function stopAiVoiceListening() {
        if (!aiVoiceState.listening && !aiVoiceState.recognition) return;
        aiVoiceState.manualStop = true;
        if (aiVoiceState.activeRoot) {
            pushAiVoiceInputToLivewire(aiVoiceState.activeRoot);
        }
        if (aiVoiceState.recognition) {
            try {
                aiVoiceState.recognition.stop();
            } catch (err) {
                if (aiVoiceState.activeRoot) {
                    setAiVoiceListeningUi(aiVoiceState.activeRoot, false);
                }
            }
        } else if (aiVoiceState.activeRoot) {
            setAiVoiceListeningUi(aiVoiceState.activeRoot, false);
        }
    }

    function createAiVoiceRecognition(SpeechRecognition, root) {
        var recognition = new SpeechRecognition();
        recognition.lang = getSttLang();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;

        recognition.onstart = function () {
            setAiVoiceListeningUi(root, true);
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
                                setAiVoiceListeningUi(root, false);
                            }
                        }
                    }, 150);
                }
                return;
            }
            setAiVoiceListeningUi(root, false);
            pushAiVoiceInputToLivewire(root);
            aiVoiceState.recognition = null;
            aiVoiceState.activeRoot = null;
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
            syncAiVoiceInput(root, aiVoiceState.baseText + aiVoiceState.sessionFinal + interim, false);
        };

        recognition.onerror = function (event) {
            if (event.error === 'aborted' && aiVoiceState.manualStop) return;
            if (event.error === 'no-speech') return;
            console.warn('Speech recognition error:', event.error);
            aiVoiceState.manualStop = true;
            setAiVoiceListeningUi(root, false);
            aiVoiceState.recognition = null;
            aiVoiceState.activeRoot = null;
        };

        return recognition;
    }

    function startAiVoiceListening(root) {
        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return;

        var input = findAiInput(root);
        aiVoiceState.baseText = input ? input.value : '';
        if (aiVoiceState.baseText && !aiVoiceState.baseText.endsWith(' ')) {
            aiVoiceState.baseText += ' ';
        }
        aiVoiceState.sessionFinal = '';
        aiVoiceState.manualStop = false;
        aiVoiceState.activeRoot = root;

        aiVoiceState.recognition = createAiVoiceRecognition(SpeechRecognition, root);

        try {
            aiVoiceState.recognition.start();
        } catch (err) {
            console.warn('Speech recognition start failed:', err.message);
            aiVoiceState.recognition = null;
            aiVoiceState.activeRoot = null;
            setAiVoiceListeningUi(root, false);
        }
    }

    function handleAiVoiceMicClick(root) {
        if (aiVoiceState.listening || aiVoiceState.recognition) {
            stopAiVoiceListening();
            return;
        }
        startAiVoiceListening(root);
    }

    function setupAiVoiceDelegates() {
        if (window.__aiVoiceDelegatesReady) return;
        window.__aiVoiceDelegatesReady = true;

        document.addEventListener('click', function (e) {
            var micBtn = e.target.closest('.ai-voice-btn');
            var root = findAiRoot(micBtn);
            if (micBtn && root && !micBtn.disabled) {
                handleAiVoiceMicClick(root);
                return;
            }
            var sendBtn = e.target.closest('.ai-assistant-root .ai-action-btn-send');
            if (sendBtn && !sendBtn.disabled) {
                stopAiVoiceListening();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey && e.target && e.target.classList.contains('ai-assistant-input')) {
                stopAiVoiceListening();
            }
        });
    }

    function refreshAiVoiceMicBtns() {
        document.querySelectorAll('.ai-assistant-root .ai-voice-btn').forEach(function (btn) {
            var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                btn.title = 'Voice not supported in this browser';
                btn.disabled = true;
            }
        });
        setSttLang(getSttLang());
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(function () {
            setupAiVoiceDelegates();
            refreshAiVoiceMicBtns();
            syncSttLangButtons(getSttLang());
        }, 500);
    }
</script>
@endonce
