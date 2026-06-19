<style>
    .ai-gemini-shell {
        --gemini-bg: #131314;
        --gemini-sidebar: #1e1f20;
        --gemini-surface: #28292a;
        --gemini-surface-hover: #37393b;
        --gemini-border: #3c4043;
        --gemini-text: #e3e3e3;
        --gemini-text-muted: #9aa0a6;
        --gemini-accent: #8ab4f8;
        --gemini-user-bg: #394457;
        display: flex;
        background: var(--gemini-bg);
        color: var(--gemini-text);
        font-family: "Google Sans", "Segoe UI", Roboto, Arial, sans-serif;
        overflow: hidden;
    }

    .ai-gemini-shell--embedded {
        position: relative;
        width: 100%;
        height: calc(100vh - 7rem);
        min-height: 520px;
        margin: 0;
        border-radius: 12px;
        z-index: 1;
    }

    .ai-assistant-page-wrap {
        padding: 1rem 1rem 0;
    }

    .ai-gemini-shell--embedded::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 80% 50% at 50% 40%, rgba(66, 133, 244, 0.09) 0%, transparent 65%);
        pointer-events: none;
        z-index: 0;
        border-radius: inherit;
    }

    .ai-gemini-sidebar {
        position: relative;
        z-index: 2;
        width: 280px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        background: var(--gemini-sidebar);
        border-right: 1px solid rgba(255, 255, 255, 0.06);
        padding: 12px 8px;
    }

    .ai-gemini-sidebar--right {
        border-right: none;
        border-left: 1px solid rgba(255, 255, 255, 0.06);
        order: 2;
    }

    .ai-gemini-shell--embedded .ai-gemini-main {
        order: 1;
    }

    .ai-gemini-sidebar-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 12px 16px;
    }

    .ai-gemini-brand {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .ai-gemini-brand-text {
        font-size: 1.125rem;
        font-weight: 500;
        letter-spacing: -0.01em;
        color: var(--gemini-text);
    }

    .ai-gemini-sidebar-close,
    .ai-gemini-menu-btn {
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: var(--gemini-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .ai-gemini-sidebar-close:hover,
    .ai-gemini-menu-btn:hover {
        background: var(--gemini-surface-hover);
        color: var(--gemini-text);
    }

    .ai-gemini-nav {
        padding: 0 4px 12px;
    }

    .ai-gemini-nav-item {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border: none;
        border-radius: 999px;
        background: transparent;
        color: var(--gemini-text);
        font-size: 0.875rem;
        font-weight: 500;
        text-align: left;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .ai-gemini-nav-item:hover {
        background: var(--gemini-surface-hover);
    }

    .ai-gemini-nav-link {
        text-decoration: none;
        color: var(--gemini-text);
    }

    .ai-gemini-nav-link:hover {
        color: var(--gemini-text);
    }

    .ai-gemini-nav-item i {
        width: 18px;
        color: var(--gemini-text-muted);
        font-size: 0.9375rem;
    }

    .ai-gemini-history {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        padding: 8px 4px 0;
        overflow: hidden;
    }

    .ai-gemini-history-label {
        margin: 0 0 8px;
        padding: 0 16px;
        font-size: 0.6875rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--gemini-text-muted);
    }

    .ai-gemini-history-list {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0 4px;
    }

    .ai-gemini-history-empty {
        margin: 0;
        padding: 8px 16px;
        font-size: 0.8125rem;
        color: var(--gemini-text-muted);
    }

    .ai-gemini-history-item {
        display: flex;
        align-items: center;
        gap: 4px;
        border-radius: 999px;
        margin-bottom: 2px;
    }

    .ai-gemini-history-item.active,
    .ai-gemini-history-item:hover {
        background: var(--gemini-surface-hover);
    }

    .ai-gemini-history-link {
        flex: 1;
        min-width: 0;
        padding: 10px 16px;
        border: none;
        background: transparent;
        color: var(--gemini-text);
        font-size: 0.8125rem;
        text-align: left;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: pointer;
    }

    .ai-gemini-history-delete {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
        margin-right: 6px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: var(--gemini-text-muted);
        opacity: 0;
        cursor: pointer;
        transition: opacity 0.15s ease, background 0.15s ease;
    }

    .ai-gemini-history-item:hover .ai-gemini-history-delete {
        opacity: 1;
    }

    .ai-gemini-history-delete:hover {
        background: rgba(234, 67, 53, 0.15);
        color: #f28b82;
    }

    .ai-gemini-sidebar-footer {
        padding: 12px 8px 8px;
        border-top: 1px solid rgba(255, 255, 255, 0.06);
        margin-top: auto;
    }

    .ai-gemini-user {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 12px;
        border-radius: 999px;
    }

    .ai-gemini-user:hover {
        background: var(--gemini-surface-hover);
    }

    .ai-gemini-user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4285F4, #9B72CB);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .ai-gemini-user-name {
        font-size: 0.875rem;
        color: var(--gemini-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ai-gemini-main {
        position: relative;
        z-index: 1;
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .ai-gemini-main-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        flex-shrink: 0;
    }

    .ai-gemini-main-header .ai-gemini-main-spacer {
        display: none;
    }

    .ai-gemini-status {
        flex-shrink: 0;
        margin: 0 auto 8px;
        padding: 8px 16px;
        border-radius: 999px;
        background: rgba(138, 180, 248, 0.12);
        color: var(--gemini-accent);
        font-size: 0.8125rem;
        font-weight: 500;
        text-align: center;
        max-width: 720px;
        width: calc(100% - 32px);
    }

    .ai-gemini-body {
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    .ai-gemini-shell--welcome .ai-gemini-body {
        justify-content: center;
        align-items: center;
        padding-bottom: 120px;
    }

    .ai-gemini-shell--welcome .ai-gemini-messages-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
    }

    .ai-gemini-shell--welcome .ai-gemini-messages-wrap .ai-gemini-welcome {
        text-align: center;
        padding: 0 24px;
        max-width: 720px;
    }

    .ai-gemini-shell--welcome .ai-gemini-messages-wrap .ai-gemini-welcome-title {
        margin: 0 0 12px;
        font-size: clamp(1.75rem, 4vw, 2.75rem);
        font-weight: 400;
        letter-spacing: -0.02em;
        color: var(--gemini-text);
        line-height: 1.2;
    }

    .ai-gemini-welcome-sub {
        margin: 0;
        font-size: 0.9375rem;
        color: var(--gemini-text-muted);
        line-height: 1.5;
    }

    .ai-gemini-shell--welcome .ai-gemini-messages-wrap .ai-gemini-messages {
        flex: 0;
        overflow: visible;
        padding: 0;
        max-height: none;
    }

    .ai-gemini-messages {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 24px 24px 16px;
        max-width: 820px;
        width: 100%;
        margin: 0 auto;
        background: transparent !important;
    }

    .ai-gemini-shell--welcome .ai-gemini-messages {
        flex: 0;
        overflow: visible;
        padding: 0;
        max-height: 0;
    }

    .ai-gemini-message {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        align-items: flex-start;
    }

    .ai-gemini-message--user {
        justify-content: flex-end;
    }

    .ai-gemini-message--user .ai-gemini-message-content {
        background: var(--gemini-user-bg);
        border-radius: 20px 20px 4px 20px;
        padding: 12px 18px;
        max-width: 75%;
    }

    .ai-gemini-message--assistant .ai-gemini-message-content {
        flex: 1;
        min-width: 0;
        color: var(--gemini-text);
        font-size: 0.9375rem;
        line-height: 1.6;
    }

    .ai-gemini-message-avatar {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ai-gemini-message-content--loading {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gemini-text-muted);
    }

    .ai-gemini-dot-pulse {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--gemini-accent);
        animation: ai-gemini-pulse 1.2s ease-in-out infinite;
    }

    @keyframes ai-gemini-pulse {
        0%, 100% { opacity: 0.4; transform: scale(0.9); }
        50% { opacity: 1; transform: scale(1); }
    }

    .ai-gemini-error {
        margin: 8px auto;
        padding: 10px 16px;
        border-radius: 12px;
        background: rgba(234, 67, 53, 0.12);
        color: #f28b82;
        font-size: 0.875rem;
        max-width: 720px;
    }

    .ai-gemini-composer-wrap {
        flex-shrink: 0;
        padding: 0 24px 28px;
        width: 100%;
        max-width: 820px;
        margin: 0 auto;
    }

    .ai-gemini-shell--welcome .ai-gemini-composer-wrap {
        position: absolute;
        left: 50%;
        bottom: auto;
        top: 58%;
        transform: translateX(-50%);
        max-width: 720px;
        padding: 0 24px;
    }

    .ai-gemini-composer {
        width: 100%;
    }

    .ai-gemini-input-pill {
        display: flex;
        align-items: flex-end;
        gap: 4px;
        padding: 6px 8px 6px 6px;
        background: var(--gemini-surface);
        border: 1px solid var(--gemini-border);
        border-radius: 28px;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .ai-gemini-input-pill:focus-within {
        border-color: rgba(138, 180, 248, 0.5);
        box-shadow: 0 0 0 1px rgba(138, 180, 248, 0.2);
    }

    .ai-gemini-input {
        flex: 1;
        min-width: 0;
        border: none !important;
        background: transparent !important;
        color: var(--gemini-text) !important;
        font-size: 1rem;
        line-height: 1.5;
        padding: 10px 4px;
        margin: 0 !important;
        resize: none;
        box-shadow: none !important;
        max-height: 160px;
        min-height: 24px;
    }

    .ai-gemini-input:focus {
        outline: none;
        box-shadow: none !important;
    }

    .ai-gemini-input::placeholder {
        color: var(--gemini-text-muted);
    }

    .ai-gemini-pill-actions {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
    }

    .ai-gemini-pill-btn {
        width: 40px;
        height: 40px;
        border: none;
        border-radius: 50%;
        background: transparent;
        color: var(--gemini-text-muted);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
        margin: 0;
    }

    .ai-gemini-pill-btn:hover:not(:disabled) {
        background: var(--gemini-surface-hover);
        color: var(--gemini-text);
    }

    .ai-gemini-pill-btn--send {
        background: rgba(255, 255, 255, 0.08);
        color: var(--gemini-text);
    }

    .ai-gemini-pill-btn--send:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.14);
    }

    .ai-gemini-pill-btn--attach {
        cursor: pointer;
    }

    .ai-gemini-pill-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .ai-gemini-root .ai-voice-btn.listening,
    .ai-gemini-shell .ai-voice-btn.listening {
        background: rgba(138, 180, 248, 0.2) !important;
        color: var(--gemini-accent) !important;
    }

    .ai-gemini-shell .ai-voice-btn.listening .ai-voice-icon {
        animation: ai-gemini-pulse 1s infinite;
    }

    .ai-gemini-composer-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
        padding: 0 8px;
    }

    .ai-gemini-lang-chips {
        display: flex;
        gap: 6px;
    }

    .ai-gemini-lang-chip {
        padding: 4px 12px;
        border: 1px solid var(--gemini-border);
        border-radius: 999px;
        background: transparent;
        color: var(--gemini-text-muted);
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }

    .ai-gemini-lang-chip:hover {
        background: var(--gemini-surface-hover);
        color: var(--gemini-text);
    }

    .ai-gemini-lang-chip.active {
        background: rgba(138, 180, 248, 0.15);
        border-color: rgba(138, 180, 248, 0.4);
        color: var(--gemini-accent);
    }

    .ai-gemini-file-badge {
        font-size: 0.75rem;
        color: #81c995;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .ai-gemini-uploading {
        font-size: 0.75rem;
        color: var(--gemini-text-muted);
    }

    .ai-gemini-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
    }

    @media (max-width: 1199px) {
        .ai-gemini-shell--embedded .ai-gemini-sidebar--right {
            position: fixed;
            top: 0;
            right: 0;
            left: auto;
            bottom: 0;
            z-index: 1050;
            transform: translateX(100%);
            transition: transform 0.25s ease;
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.4);
        }

        .ai-gemini-shell--embedded .ai-gemini-sidebar--right.is-open {
            transform: translateX(0);
        }

        .ai-gemini-shell--embedded .ai-gemini-backdrop {
            z-index: 1049;
        }

        .ai-gemini-shell--welcome .ai-gemini-composer-wrap {
            position: static;
            transform: none;
            padding-bottom: 24px;
        }

        .ai-gemini-shell--welcome .ai-gemini-body {
            padding-bottom: 0;
            justify-content: flex-start;
            padding-top: 15vh;
        }
    }
</style>
