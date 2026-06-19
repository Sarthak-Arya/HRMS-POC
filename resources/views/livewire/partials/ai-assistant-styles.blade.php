<style>
    @if(($mode ?? 'widget') === 'widget')
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
        .ai-assistant-widget .ai-assistant-panel {
            width: 680px;
            max-width: calc(100vw - 48px);
            height: 560px;
            max-height: calc(100vh - 80px);
        }
        @media (max-width: 720px) {
            .ai-assistant-widget .ai-assistant-panel {
                width: calc(100vw - 32px);
                height: calc(100vh - 80px);
            }
        }
    @endif

    @if(($mode ?? 'widget') === 'page')
        .ai-assistant-page .ai-assistant-panel-page {
            width: 100%;
            height: calc(100vh - 220px);
            min-height: 480px;
            max-width: none;
            max-height: none;
        }
        .ai-assistant-page .ai-history-sidebar-page {
            width: 260px;
        }
        @media (min-width: 721px) {
            .ai-assistant-page .ai-history-toggle {
                display: none;
            }
        }
    @endif

    .ai-assistant-panel {
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
        background: var(--hrms-ai-sidebar-bg, #f1f3f5);
        border-right: 1px solid var(--hrms-border-color, #e9ecef);
        min-height: 0;
    }
    .ai-new-chat-btn {
        margin: 10px;
        padding: 8px 12px;
        border: 1px solid var(--hrms-border-color, #dee2e6);
        border-radius: 8px;
        background: var(--hrms-card-bg, #fff);
        color: var(--hrms-text-primary, #344767);
        font-size: 0.8125rem;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .ai-new-chat-btn:hover {
        background: var(--hrms-ai-active-bg, #e8f1ff);
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
        color: var(--hrms-text-muted, #8392ab);
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
        background: var(--hrms-ai-active-bg, #e8f1ff);
    }
    .ai-history-item:hover {
        background: var(--hrms-hover-bg, #e9ecef);
    }
    .ai-history-item.active:hover {
        background: var(--hrms-ai-active-bg, #dce8ff);
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
        color: var(--hrms-text-primary, #344767);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .ai-history-time {
        display: block;
        font-size: 0.6875rem;
        color: var(--hrms-text-muted, #8392ab);
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
        color: var(--hrms-text-muted, #8392ab);
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
        .ai-history-sidebar:not(.ai-history-sidebar-page) {
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
        background: var(--hrms-ai-status-bg, #e8f1ff);
        color: var(--hrms-ai-status-text, #0052ff);
        font-size: 0.8125rem;
        font-weight: 600;
        text-align: center;
        border-bottom: 1px solid var(--hrms-ai-status-border, #d0e2ff);
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
        background: var(--hrms-ai-messages-bg, #f8f9fa);
    }
    .ai-assistant-footer {
        flex-shrink: 0;
        padding: 12px;
        background: var(--hrms-card-bg, #fff);
    }
    .ai-lang-tabs {
        display: flex;
        width: 100%;
        border: 1px solid var(--hrms-border-color, #dee2e6);
        border-radius: 8px;
        overflow: hidden;
    }
    .ai-lang-tab {
        flex: 1;
        padding: 6px 8px;
        border: none;
        background: var(--hrms-card-bg, #fff);
        color: var(--hrms-text-secondary, #67748e);
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: none;
        letter-spacing: normal;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .ai-lang-tab + .ai-lang-tab {
        border-left: 1px solid var(--hrms-border-color, #dee2e6);
    }
    .ai-lang-tab.active {
        background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
        color: #fff;
    }
    .ai-assistant-input {
        width: 100%;
        margin-top: 8px;
        border-radius: 8px;
        border: 1px solid var(--hrms-border-color, #dee2e6);
        background: var(--hrms-input-bg, #fff);
        color: var(--hrms-text-primary, #344767);
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
        border: 1px solid var(--hrms-border-color, #dee2e6);
        border-radius: 8px;
        background: var(--hrms-card-bg, #fff);
        color: var(--hrms-text-secondary, #67748e);
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
        background: var(--hrms-hover-bg, #f8f9fa);
        border-color: var(--hrms-input-border, #ced4da);
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
        background: var(--hrms-ai-bubble-bg, #fff);
        color: var(--hrms-text-primary, #344767);
        border: 1px solid var(--hrms-border-color, #e9ecef);
        border-bottom-left-radius: 4px;
    }
    .ai-assistant-root .ai-voice-btn.listening {
        background: linear-gradient(180deg, #0052ff 0%, #0072ff 100%);
        color: #fff;
        border-color: #0052ff;
    }
    .ai-assistant-root .ai-voice-btn.listening .ai-voice-icon {
        animation: ai-voice-pulse 1s infinite;
    }
    @keyframes ai-voice-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
