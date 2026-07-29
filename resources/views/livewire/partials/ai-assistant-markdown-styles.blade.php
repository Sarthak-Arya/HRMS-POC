<style>
    .ai-gemini-messages,
    .ai-assistant-messages {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .ai-gemini-messages::-webkit-scrollbar,
    .ai-assistant-messages::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }

    .ai-markdown {
        font-size: inherit;
        line-height: 1.6;
        color: inherit;
    }

    .ai-markdown > :first-child {
        margin-top: 0;
    }

    .ai-markdown > :last-child {
        margin-bottom: 0;
    }

    .ai-markdown .ai-md-paragraph {
        margin: 0 0 0.75rem;
    }

    .ai-markdown .ai-md-paragraph:last-child {
        margin-bottom: 0;
    }

    .ai-markdown .ai-md-heading {
        margin: 1rem 0 0.5rem;
        font-weight: 600;
        line-height: 1.35;
    }

    .ai-markdown .ai-md-heading--1 { font-size: 1.125rem; }
    .ai-markdown .ai-md-heading--2 { font-size: 1rem; }
    .ai-markdown .ai-md-heading--3 { font-size: 0.9375rem; }

    .ai-markdown .ai-md-list {
        margin: 0.5rem 0 0.75rem;
        padding-left: 1.25rem;
    }

    .ai-markdown .ai-md-list li {
        margin-bottom: 0.25rem;
    }

    .ai-markdown .ai-md-inline-code,
    .ai-markdown .ai-md-code-block code {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        font-size: 0.85em;
    }

    .ai-markdown .ai-md-inline-code {
        padding: 0.1rem 0.35rem;
        border-radius: 6px;
        background: rgba(127, 127, 127, 0.14);
    }

    .ai-markdown .ai-md-code-block {
        margin: 0.75rem 0;
        padding: 0.75rem 0.875rem;
        border-radius: 12px;
        overflow-x: auto;
        background: rgba(127, 127, 127, 0.12);
        border: 1px solid rgba(127, 127, 127, 0.18);
    }

    .ai-markdown .ai-md-code-block code {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .ai-markdown .ai-md-kv-card {
        margin: 0.75rem 0;
        border: 1px solid var(--gemini-border, rgba(127, 127, 127, 0.22));
        border-radius: 14px;
        overflow: hidden;
        background: var(--gemini-surface, rgba(127, 127, 127, 0.06));
    }

    .ai-markdown .ai-md-kv-row {
        display: grid;
        grid-template-columns: minmax(120px, 36%) 1fr;
        gap: 12px 16px;
        padding: 10px 14px;
        border-bottom: 1px solid var(--gemini-border, rgba(127, 127, 127, 0.14));
        align-items: start;
    }

    .ai-markdown .ai-md-kv-row:last-child {
        border-bottom: none;
    }

    .ai-markdown .ai-md-kv-label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--gemini-text-muted, #67748e);
    }

    .ai-markdown .ai-md-kv-value {
        font-size: 0.875rem;
        color: inherit;
        word-break: break-word;
    }

    .ai-markdown .ai-md-table-wrap {
        margin: 0.75rem 0;
        border: 1px solid var(--gemini-border, rgba(127, 127, 127, 0.22));
        border-radius: 14px;
        overflow-x: auto;
        background: var(--gemini-surface, rgba(127, 127, 127, 0.04));
    }

    .ai-markdown .ai-md-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .ai-markdown .ai-md-table th,
    .ai-markdown .ai-md-table td {
        padding: 9px 12px;
        text-align: left;
        border-bottom: 1px solid var(--gemini-border, rgba(127, 127, 127, 0.14));
        vertical-align: top;
    }

    .ai-markdown .ai-md-table th {
        font-weight: 600;
        background: rgba(127, 127, 127, 0.08);
        white-space: nowrap;
    }

    .ai-markdown .ai-md-table tbody tr:last-child td {
        border-bottom: none;
    }

    .ai-message-user .ai-markdown .ai-md-kv-card,
    .ai-message-user .ai-markdown .ai-md-table-wrap,
    .ai-message-user .ai-markdown .ai-md-code-block {
        border-color: rgba(255, 255, 255, 0.24);
        background: rgba(255, 255, 255, 0.1);
    }

    .ai-message-user .ai-markdown .ai-md-kv-label {
        color: rgba(255, 255, 255, 0.78);
    }

    .ai-message-user .ai-markdown .ai-md-kv-row,
    .ai-message-user .ai-markdown .ai-md-table th,
    .ai-message-user .ai-markdown .ai-md-table td {
        border-color: rgba(255, 255, 255, 0.18);
    }

    .ai-message-user .ai-markdown .ai-md-inline-code {
        background: rgba(255, 255, 255, 0.16);
    }

    .ai-gemini-message--user .ai-markdown .ai-md-kv-label {
        color: rgba(255, 255, 255, 0.78);
    }

    @media (max-width: 576px) {
        .ai-markdown .ai-md-kv-row {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
</style>
