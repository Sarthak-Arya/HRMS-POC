<?php

namespace App\Support;

class AiMarkdownRenderer
{
    public static function toHtml(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
        if ($markdown === '') {
            return '';
        }

        $blocks = self::splitBlocks($markdown);
        $html = [];

        foreach ($blocks as $block) {
            if ($block['type'] === 'table') {
                $html[] = self::renderTable($block['lines']);
                continue;
            }

            $rendered = self::renderTextBlock($block['content']);
            if ($rendered !== '') {
                $html[] = $rendered;
            }
        }

        return implode("\n", $html);
    }

    /**
     * @return array<int, array{type: string, content?: string, lines?: array<int, string>}>
     */
    private static function splitBlocks(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $blocks = [];
        $textBuffer = [];
        $tableBuffer = [];

        $flushText = static function () use (&$blocks, &$textBuffer): void {
            if ($textBuffer === []) {
                return;
            }

            $blocks[] = [
                'type' => 'text',
                'content' => implode("\n", $textBuffer),
            ];
            $textBuffer = [];
        };

        $flushTable = static function () use (&$blocks, &$tableBuffer, $flushText): void {
            if ($tableBuffer === []) {
                return;
            }

            $flushText();
            $blocks[] = [
                'type' => 'table',
                'lines' => $tableBuffer,
            ];
            $tableBuffer = [];
        };

        foreach ($lines as $line) {
            if (self::isTableLine($line)) {
                $tableBuffer[] = $line;
                continue;
            }

            $flushTable();
            $textBuffer[] = $line;
        }

        $flushTable();
        $flushText();

        return $blocks;
    }

    private static function isTableLine(string $line): bool
    {
        $trimmed = trim($line);

        return $trimmed !== '' && str_starts_with($trimmed, '|') && str_contains(substr($trimmed, 1), '|');
    }

    /**
     * @param array<int, string> $lines
     */
    private static function renderTable(array $lines): string
    {
        $rows = [];
        foreach ($lines as $line) {
            $cells = self::parseTableRow($line);
            if ($cells === []) {
                continue;
            }

            if (self::isSeparatorRow($cells)) {
                continue;
            }

            $rows[] = $cells;
        }

        if ($rows === []) {
            return self::renderParagraph(implode("\n", $lines));
        }

        $columnCount = max(array_map('count', $rows));
        if ($columnCount === 2 && self::shouldRenderAsKeyValueCard($rows)) {
            return self::renderKeyValueCard($rows);
        }

        $header = array_shift($rows);
        $thead = '';
        if ($header !== null) {
            $thead = '<thead><tr>' . self::renderTableCells($header, 'th') . '</tr></thead>';
        }

        $bodyRows = '';
        foreach ($rows as $row) {
            $bodyRows .= '<tr>' . self::renderTableCells($row, 'td') . '</tr>';
        }

        return '<div class="ai-md-table-wrap"><table class="ai-md-table">'
            . $thead
            . '<tbody>' . $bodyRows . '</tbody>'
            . '</table></div>';
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private static function shouldRenderAsKeyValueCard(array $rows): bool
    {
        $header = $rows[0] ?? [];
        if (count($header) !== 2) {
            return false;
        }

        $labels = array_map(static fn (string $value) => strtolower(trim($value)), $header);

        return in_array($labels[0], ['field', 'property', 'attribute', 'label', 'detail'], true)
            && in_array($labels[1], ['value', 'details', 'info', 'information'], true);
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private static function renderKeyValueCard(array $rows): string
    {
        $dataRows = $rows;
        $header = $dataRows[0] ?? [];
        $labels = array_map(static fn (string $value) => strtolower(trim($value)), $header);

        if (count($header) === 2
            && in_array($labels[0], ['field', 'property', 'attribute', 'label', 'detail'], true)
            && in_array($labels[1], ['value', 'details', 'info', 'information'], true)
        ) {
            array_shift($dataRows);
        }

        $html = '<div class="ai-md-kv-card">';
        foreach ($dataRows as $row) {
            $label = trim((string) ($row[0] ?? ''));
            $value = trim((string) ($row[1] ?? ''));
            if ($label === '' && $value === '') {
                continue;
            }

            $html .= '<div class="ai-md-kv-row">'
                . '<div class="ai-md-kv-label">' . self::applyInline($label) . '</div>'
                . '<div class="ai-md-kv-value">' . self::applyInline($value !== '' ? $value : '—') . '</div>'
                . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<int, string> $cells
     */
    private static function renderTableCells(array $cells, string $tag): string
    {
        $html = '';
        foreach ($cells as $cell) {
            $html .= '<' . $tag . '>' . self::applyInline($cell) . '</' . $tag . '>';
        }

        return $html;
    }

    /**
     * @return array<int, string>
     */
    private static function parseTableRow(string $line): array
    {
        $trimmed = trim($line);
        if (!str_starts_with($trimmed, '|')) {
            return [];
        }

        $trimmed = trim($trimmed, '|');

        return array_map('trim', explode('|', $trimmed));
    }

    /**
     * @param array<int, string> $cells
     */
    private static function isSeparatorRow(array $cells): bool
    {
        if ($cells === []) {
            return false;
        }

        foreach ($cells as $cell) {
            if (!preg_match('/^:?-{3,}:?$/', trim($cell))) {
                return false;
            }
        }

        return true;
    }

    private static function renderTextBlock(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $segments = preg_split("/\n{2,}/", $content) ?: [$content];
        $html = [];

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (preg_match('/^```(\w*)\n(.*?)```$/s', $segment, $matches)) {
                $code = self::escape($matches[2]);
                $html[] = '<pre class="ai-md-code-block"><code>' . rtrim($code, "\n") . '</code></pre>';
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/s', $segment, $matches)) {
                $level = strlen($matches[1]);
                $html[] = '<h' . $level . ' class="ai-md-heading ai-md-heading--' . $level . '">'
                    . self::applyInline($matches[2])
                    . '</h' . $level . '>';
                continue;
            }

            if (self::isListBlock($segment)) {
                $html[] = self::renderListBlock($segment);
                continue;
            }

            $html[] = self::renderParagraph($segment);
        }

        return implode("\n", $html);
    }

    private static function isListBlock(string $segment): bool
    {
        foreach (explode("\n", $segment) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            return (bool) preg_match('/^(\d+\.\s+|[-*+]\s+)/', $line);
        }

        return false;
    }

    private static function renderListBlock(string $segment): string
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $segment)), static fn (string $line) => $line !== ''));
        if ($lines === []) {
            return '';
        }

        $ordered = (bool) preg_match('/^\d+\.\s+/', $lines[0]);
        $tag = $ordered ? 'ol' : 'ul';
        $items = '';

        foreach ($lines as $line) {
            $text = preg_replace('/^(\d+\.\s+|[-*+]\s+)/', '', $line) ?? $line;
            $items .= '<li>' . self::applyInline($text) . '</li>';
        }

        return '<' . $tag . ' class="ai-md-list">' . $items . '</' . $tag . '>';
    }

    private static function renderParagraph(string $text): string
    {
        $lines = explode("\n", trim($text));
        $parts = array_map(static fn (string $line) => self::applyInline($line), $lines);

        return '<p class="ai-md-paragraph">' . implode("<br>\n", $parts) . '</p>';
    }

    private static function applyInline(string $text): string
    {
        $text = self::escape($text);
        $text = preg_replace('/`([^`]+)`/', '<code class="ai-md-inline-code">$1</code>', $text) ?? $text;
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;

        return $text;
    }

    private static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
