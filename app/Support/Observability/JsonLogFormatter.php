<?php

namespace App\Support\Observability;

use Monolog\Formatter\JsonFormatter as BaseJsonFormatter;
use Monolog\LogRecord;

/**
 * JSON log lines for Filebeat / container stdout collection.
 */
class JsonLogFormatter extends BaseJsonFormatter
{
    public function __construct()
    {
        parent::__construct(self::BATCH_MODE_NEWLINES, true);
        $this->includeStacktraces(false);
    }

    public function format(LogRecord $record): string
    {
        $context = $record->context;
        $payload = array_merge($context, [
            '@timestamp' => $record->datetime->format('c'),
            'severity' => strtolower($record->level->getName()),
            'message' => $record->message !== '' ? $record->message : ($context['event.name'] ?? 'log'),
            'channel' => $record->channel,
        ]);

        unset($payload['exception']);

        return $this->toJson($this->normalize($payload), true).($this->appendNewline ? "\n" : '');
    }
}
