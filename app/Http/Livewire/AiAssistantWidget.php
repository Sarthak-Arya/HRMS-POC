<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\InteractsWithAiAssistant;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Livewire component for the AI Assistant widget.
 * Provides a chat interface with support for file uploads and conversation persistence.
 */
class AiAssistantWidget extends Component
{
    use InteractsWithAiAssistant;
    use WithFileUploads;

    /** @var bool Whether the assistant widget is open. */
    public bool $isOpen = false;

    public function mount(?string $company_id = null): void
    {
        $this->mountAiAssistant($company_id);
    }

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        $this->errorMessage = null;

        if (!$this->isOpen) {
            $this->showHistory = false;
        }
    }

    public function render()
    {
        return view('livewire.ai-assistant-widget', [
            'conversations' => $this->getAiConversations(),
        ]);
    }
}
