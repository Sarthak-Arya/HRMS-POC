<?php

namespace Tests\Unit;

use App\Support\AiMessageFormatter;
use PHPUnit\Framework\TestCase;

class AiMessageFormatterTest extends TestCase
{
    public function test_converts_bold_markers_to_strong_tags(): void
    {
        $html = AiMessageFormatter::format('Use **an Excel/CSV file** to import.');

        $this->assertStringContainsString('<strong>an Excel/CSV file</strong>', $html);
        $this->assertStringNotContainsString('**', $html);
    }

    public function test_escapes_html_before_formatting(): void
    {
        $html = AiMessageFormatter::format('<script>**bold**</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_preserves_line_breaks(): void
    {
        $html = AiMessageFormatter::format("Line one\nLine two");

        $this->assertStringContainsString("Line one<br>\nLine two", $html);
    }
}
