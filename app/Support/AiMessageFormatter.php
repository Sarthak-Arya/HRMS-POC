<?php

namespace App\Support;

class AiMessageFormatter
{
    /**
     * Render assistant markdown (tables, lists, emphasis) into styled HTML.
     */
    public static function format(string $content): string
    {
        $html = AiMarkdownRenderer::toHtml($content);

        if ($html === '') {
            return '';
        }

        return '<div class="ai-markdown">' . $html . '</div>';
    }
}
