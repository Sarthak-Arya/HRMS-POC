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
        $this->assertStringContainsString('class="ai-markdown"', $html);
    }

    public function test_escapes_html_before_formatting(): void
    {
        $html = AiMessageFormatter::format('<script>**bold**</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_preserves_line_breaks_in_paragraphs(): void
    {
        $html = AiMessageFormatter::format("Line one\nLine two");

        $this->assertStringContainsString("Line one<br>\nLine two", $html);
    }

    public function test_renders_field_value_markdown_table_as_key_value_card(): void
    {
        $markdown = <<<'MD'
| Field | Value |
|-------|-------|
| Employee Code | ARY-02-E004 |
| Name | Ananya Buckridge |
| PF No. | PF307749 |
MD;

        $html = AiMessageFormatter::format($markdown);

        $this->assertStringContainsString('ai-md-kv-card', $html);
        $this->assertStringContainsString('ai-md-kv-label', $html);
        $this->assertStringContainsString('Employee Code', $html);
        $this->assertStringContainsString('ARY-02-E004', $html);
        $this->assertStringNotContainsString('| Field |', $html);
    }

    public function test_renders_generic_markdown_table(): void
    {
        $markdown = <<<'MD'
| Month | Present | Absent |
|-------|---------|--------|
| Jan | 22 | 2 |
MD;

        $html = AiMessageFormatter::format($markdown);

        $this->assertStringContainsString('ai-md-table', $html);
        $this->assertStringContainsString('<th>Month</th>', $html);
        $this->assertStringContainsString('<td>22</td>', $html);
    }
}
