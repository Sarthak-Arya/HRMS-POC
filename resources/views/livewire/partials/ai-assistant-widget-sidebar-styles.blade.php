<style>
    .ai-assistant-widget {
        --gemini-bg: #131314;
        --gemini-sidebar: #1e1f20;
        --gemini-surface: #28292a;
        --gemini-surface-hover: #37393b;
        --gemini-border: #3c4043;
        --gemini-text: #e3e3e3;
        --gemini-text-muted: #9aa0a6;
        --gemini-accent: #8ab4f8;
        --gemini-user-bg: #394457;
    }

    .ai-assistant-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 1051;
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
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(0, 82, 255, 0.35);
        transition: opacity 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }

    .ai-assistant-fab:hover {
        box-shadow: 0 6px 18px rgba(0, 82, 255, 0.45);
        transform: translateY(-1px);
    }

    .ai-assistant-fab.is-hidden {
        opacity: 0;
        pointer-events: none;
        transform: scale(0.85);
    }

    .ai-widget-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.35);
        z-index: 1048;
    }

    .ai-widget-sidebar {
        position: fixed;
        top: 0;
        right: 0;
        bottom: 0;
        width: min(440px, 100vw);
        z-index: 1049;
        display: flex;
        flex-direction: column;
        background: var(--gemini-bg);
        color: var(--gemini-text);
        border-left: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: -8px 0 32px rgba(0, 0, 0, 0.35);
        transform: translateX(100%);
        transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: "Google Sans", "Segoe UI", Roboto, Arial, sans-serif;
    }

    .ai-widget-sidebar.is-open {
        transform: translateX(0);
    }

    .ai-widget-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        flex-shrink: 0;
    }

    .ai-widget-sidebar-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .ai-widget-sidebar-title {
        font-size: 0.9375rem;
        font-weight: 500;
        color: var(--gemini-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ai-widget-sidebar-actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-shrink: 0;
    }

    .ai-widget-icon-btn {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: var(--gemini-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .ai-widget-icon-btn:hover {
        background: var(--gemini-surface-hover);
        color: var(--gemini-text);
    }

    .ai-widget-status {
        margin: 8px 16px 0;
        width: auto;
        max-width: none;
    }

    .ai-widget-sidebar-body {
        flex: 1;
        min-height: 0;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .ai-widget-sidebar-body .ai-gemini-messages {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 20px 16px 12px;
        background: transparent !important;
    }

    .ai-widget-sidebar-body .ai-gemini-welcome--compact {
        text-align: center;
        padding: 24px 8px;
    }

    .ai-widget-sidebar-body .ai-gemini-welcome--compact .ai-gemini-welcome-title {
        margin: 0 0 8px;
        font-size: 1.375rem;
        font-weight: 400;
        letter-spacing: -0.02em;
        color: var(--gemini-text);
    }

    .ai-widget-sidebar-body .ai-gemini-welcome--compact .ai-gemini-welcome-sub {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--gemini-text-muted);
        line-height: 1.5;
    }

    .ai-widget-sidebar-footer {
        flex-shrink: 0;
        padding: 12px 16px 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        background: var(--gemini-bg);
    }

    /* Shared gemini chat styles scoped to widget sidebar */
    .ai-widget-sidebar .ai-gemini-message {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        align-items: flex-start;
    }

    .ai-widget-sidebar .ai-gemini-message--user {
        justify-content: flex-end;
    }

    .ai-widget-sidebar .ai-gemini-message--user .ai-gemini-message-content {
        background: var(--gemini-user-bg);
        border-radius: 18px 18px 4px 18px;
        padding: 10px 14px;
        max-width: 85%;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .ai-widget-sidebar .ai-gemini-message--assistant .ai-gemini-message-content {
        flex: 1;
        min-width: 0;
        color: var(--gemini-text);
        font-size: 0.875rem;
        line-height: 1.6;
    }

    .ai-widget-sidebar .ai-gemini-message-content strong {
        font-weight: 600;
        color: #fff;
    }

    .ai-widget-sidebar .ai-gemini-message-avatar {
        width: 24px;
        height: 24px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ai-widget-sidebar .ai-gemini-message-content--loading {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gemini-text-muted);
    }

    .ai-widget-sidebar .ai-gemini-dot-pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--gemini-accent);
        animation: ai-widget-pulse 1.2s ease-in-out infinite;
    }

    @keyframes ai-widget-pulse {
        0%, 100% { opacity: 0.4; transform: scale(0.9); }
        50% { opacity: 1; transform: scale(1); }
    }

    .ai-widget-sidebar .ai-gemini-error {
        margin: 8px 0;
        padding: 10px 14px;
        border-radius: 12px;
        background: rgba(234, 67, 53, 0.12);
        color: #f28b82;
        font-size: 0.8125rem;
    }

    .ai-widget-sidebar .ai-gemini-status {
        padding: 8px 14px;
        border-radius: 999px;
        background: rgba(138, 180, 248, 0.12);
        color: var(--gemini-accent);
        font-size: 0.8125rem;
        font-weight: 500;
        text-align: center;
    }

    .ai-widget-sidebar .ai-gemini-input-pill {
        display: flex;
        align-items: flex-end;
        gap: 4px;
        padding: 6px 8px 6px 6px;
        background: var(--gemini-surface);
        border: 1px solid var(--gemini-border);
        border-radius: 24px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .ai-widget-sidebar .ai-gemini-input-pill:focus-within {
        border-color: rgba(138, 180, 248, 0.5);
        box-shadow: 0 0 0 1px rgba(138, 180, 248, 0.2);
    }

    .ai-widget-sidebar .ai-gemini-input {
        flex: 1;
        min-width: 0;
        border: none !important;
        background: transparent !important;
        color: var(--gemini-text) !important;
        font-size: 0.9375rem;
        line-height: 1.5;
        padding: 10px 4px;
        margin: 0 !important;
        resize: none;
        box-shadow: none !important;
        max-height: 120px;
    }

    .ai-widget-sidebar .ai-gemini-input:focus {
        outline: none;
    }

    .ai-widget-sidebar .ai-gemini-input::placeholder {
        color: var(--gemini-text-muted);
    }

    .ai-widget-sidebar .ai-gemini-pill-actions {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
    }

    .ai-widget-sidebar .ai-gemini-pill-btn {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: var(--gemini-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .ai-widget-sidebar .ai-gemini-pill-btn:hover:not(:disabled) {
        background: var(--gemini-surface-hover);
        color: var(--gemini-text);
    }

    .ai-widget-sidebar .ai-gemini-pill-btn--send {
        background: rgba(255, 255, 255, 0.1);
        color: var(--gemini-text);
    }

    .ai-widget-sidebar .ai-gemini-pill-btn--send:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.16);
    }

    .ai-widget-sidebar .ai-gemini-pill-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .ai-widget-sidebar .ai-voice-btn.listening {
        background: rgba(138, 180, 248, 0.2) !important;
        color: var(--gemini-accent) !important;
    }

    .ai-widget-sidebar .ai-gemini-composer-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
        padding: 0 4px;
    }

    .ai-widget-sidebar .ai-gemini-lang-chips {
        display: flex;
        gap: 6px;
    }

    .ai-widget-sidebar .ai-gemini-lang-chip {
        padding: 3px 10px;
        border: 1px solid var(--gemini-border);
        border-radius: 999px;
        background: transparent;
        color: var(--gemini-text-muted);
        font-size: 0.6875rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .ai-widget-sidebar .ai-gemini-lang-chip:hover {
        background: var(--gemini-surface-hover);
        color: var(--gemini-text);
    }

    .ai-widget-sidebar .ai-gemini-lang-chip.active {
        background: rgba(138, 180, 248, 0.15);
        border-color: rgba(138, 180, 248, 0.4);
        color: var(--gemini-accent);
    }

    .ai-widget-sidebar .ai-gemini-file-badge {
        font-size: 0.6875rem;
        color: #81c995;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .ai-widget-sidebar .ai-gemini-uploading {
        font-size: 0.6875rem;
        color: var(--gemini-text-muted);
    }

    @media (max-width: 480px) {
        .ai-widget-sidebar {
            width: 100vw;
        }
    }
</style>
