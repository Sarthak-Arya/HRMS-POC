<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\InteractsWithAiAssistant;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Full-page AI Assistant chat interface.
 */
class AiAssistantPage extends Component
{
    use InteractsWithAiAssistant;
    use WithFileUploads;

    public function mount(?string $company_id = null): void
    {
        $this->mountAiAssistant($company_id);
    }

    public function render()
    {
        return view('livewire.ai-assistant-page', [
            'conversations' => $this->getAiConversations(),
        ]);
    }
}
