<?php

namespace App\Support\Observability;

/**
 * Central allowlist + redaction for structured telemetry.
 * Unknown keys are dropped. Sensitive patterns are scrubbed from free-text.
 */
class TelemetryRedactor
{
    /**
     * Keys allowed in structured log/event context (dot or underscore forms).
     *
     * @var list<string>
     */
    public const ALLOWED_KEYS = [
        'timestamp',
        'severity',
        'service.name',
        'service.version',
        'deployment.environment',
        'event.name',
        'event.category',
        'event.outcome',
        'event.schema_version',
        'request.id',
        'trace.id',
        'span.id',
        'company.id',
        'actor.user_id',
        'actor.role',
        'payroll.run_id',
        'report.run_id',
        'attendance.month',
        'attendance.year',
        'job.batch_id',
        'job.class',
        'job.attempts',
        'route.template',
        'http.method',
        'http.status',
        'http.status_class',
        'duration_ms',
        'error.type',
        'error.code',
        'error.fingerprint',
        'provider',
        'tool_name',
        'queue',
        'row_count',
        'processed_count',
        'failed_count',
        'skipped_count',
        'artifact_type',
        'format',
        'status',
        'from_status',
        'to_status',
        'permission',
        'policy',
        'section',
        'message',
        'exception',
    ];

    /**
     * @var list<string>
     */
    private const DENIED_SUBSTRINGS = [
        'password',
        'passwd',
        'secret',
        'token',
        'authorization',
        'cookie',
        'session',
        'email',
        'phone',
        'address',
        'bank',
        'iban',
        'pan',
        'aadhaar',
        'ssn',
        'salary',
        'gross',
        'net_pay',
        'netpay',
        'prompt',
        'response_body',
        'raw_body',
        'spreadsheet',
        'leave_reason',
        'employee_name',
        'first_name',
        'last_name',
    ];

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function sanitize(array $context): array
    {
        $allowed = array_flip(self::ALLOWED_KEYS);
        $clean = [];

        foreach ($context as $key => $value) {
            $normalized = $this->normalizeKey((string) $key);

            if ($this->isDeniedKey($normalized)) {
                continue;
            }

            if (! isset($allowed[$normalized]) && ! isset($allowed[(string) $key])) {
                // Allow nested allowlisted keys already in dotted form only.
                continue;
            }

            $clean[$normalized] = $this->sanitizeValue($value);
        }

        return $clean;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function sanitizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                if ($this->isDeniedKey($this->normalizeKey((string) $k))) {
                    continue;
                }
                // Nested arrays: keep only scalar allowlisted leaves by re-running sanitize on flat maps.
                if (is_array($v)) {
                    continue;
                }
                $out[$this->normalizeKey((string) $k)] = $this->sanitizeScalar($v);
            }

            return $out;
        }

        return $this->sanitizeScalar($value);
    }

    public function scrubMessage(string $message): string
    {
        $scrubbed = preg_replace(
            [
                '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
                '/\b\d{10,16}\b/',
                '/Bearer\s+[A-Za-z0-9._\-]+/i',
            ],
            ['[redacted-email]', '[redacted-number]', 'Bearer [redacted]'],
            $message
        );

        return is_string($scrubbed) ? $scrubbed : '[redacted]';
    }

    private function sanitizeScalar(mixed $value): mixed
    {
        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        if (is_string($value)) {
            return $this->scrubMessage($value);
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return $this->scrubMessage((string) $value);
        }

        return null;
    }

    private function normalizeKey(string $key): string
    {
        return strtolower(trim($key));
    }

    private function isDeniedKey(string $normalizedKey): bool
    {
        $compact = str_replace(['.', '-', ' '], '_', $normalizedKey);
        foreach (self::DENIED_SUBSTRINGS as $needle) {
            if (str_contains($compact, $needle) || str_contains($normalizedKey, $needle)) {
                // Allow bounded ID fields such as actor.user_id / company.id
                if (preg_match('/^(actor\.user_id|company\.id|request\.id|trace\.id|span\.id|payroll\.run_id|report\.run_id|job\.batch_id|actor\.user\.id)$/', $normalizedKey)) {
                    return false;
                }
                if (in_array($normalizedKey, ['actor.user_id', 'company.id', 'request.id', 'trace.id', 'span.id', 'payroll.run_id', 'report.run_id', 'job.batch_id'], true)) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }
}
