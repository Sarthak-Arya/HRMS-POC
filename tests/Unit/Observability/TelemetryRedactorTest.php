<?php

namespace Tests\Unit\Observability;

use App\Support\Observability\TelemetryRedactor;
use PHPUnit\Framework\TestCase;

class TelemetryRedactorTest extends TestCase
{
    public function test_it_keeps_allowlisted_keys_and_drops_unknown(): void
    {
        $redactor = new TelemetryRedactor();
        $result = $redactor->sanitize([
            'event.name' => 'payroll.run.started',
            'company.id' => 12,
            'actor.user_id' => 44,
            'mystery_field' => 'drop-me',
            'password' => 'secret',
            'email' => 'a@b.com',
        ]);

        $this->assertSame('payroll.run.started', $result['event.name']);
        $this->assertSame(12, $result['company.id']);
        $this->assertSame(44, $result['actor.user_id']);
        $this->assertArrayNotHasKey('mystery_field', $result);
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('email', $result);
    }

    public function test_it_scrubs_pii_from_messages(): void
    {
        $redactor = new TelemetryRedactor();
        $scrubbed = $redactor->scrubMessage('user admin@example.com token Bearer abc.def.ghi called');

        $this->assertStringNotContainsString('admin@example.com', $scrubbed);
        $this->assertStringNotContainsString('abc.def.ghi', $scrubbed);
        $this->assertStringContainsString('[redacted-email]', $scrubbed);
    }
}
