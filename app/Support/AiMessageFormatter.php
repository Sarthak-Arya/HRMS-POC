<?php

namespace App\Support;

class AiMessageFormatter
{
    /**
     * Escape HTML, convert **bold** markers, and preserve line breaks.
     */
    public static function format(string $content): string
    {
        $escaped = e($content);
        $withBold = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;

        return nl2br($withBold, false);
    }
}
