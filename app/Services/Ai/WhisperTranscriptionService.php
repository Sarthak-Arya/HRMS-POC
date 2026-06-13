<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Phase 2 stub: server-side STT via OpenRouter-compatible transcription when browser STT fails.
 */
class WhisperTranscriptionService
{
    public function isEnabled(): bool
    {
        return (bool) config('ai.agent.stt_fallback');
    }

    public function transcribe(string $audioPath, string $language = 'hi'): string
    {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Server-side STT fallback is not enabled.');
        }

        // Placeholder for future OpenRouter / Whisper integration.
        throw new RuntimeException('Whisper transcription is not yet implemented.');
    }
}
