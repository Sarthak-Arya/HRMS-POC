<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\InteractsWithAiAssistant;
use App\Models\Company;
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
        $company = $this->companyId
            ? Company::query()->find($this->companyId)
            : null;

        return view('livewire.ai-assistant-page', [
            'conversations' => $this->getAiConversations(),
            'companyName' => $company?->company_name ?? 'Company',
        ]);
    }
}
