<?php

namespace App\Http\Livewire;

use App\Services\Ai\AgentOrchestrator;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Livewire component for the AI Assistant widget.
 * Provides a chat interface with support for file uploads and conversation persistence.
 */
class AiAssistantWidget extends Component
{
    use WithFileUploads;

    /** @var string|null The ID of the current company context. */
    public ?string $companyId = null;

    /** @var bool Whether the assistant widget is open. */
    public bool $isOpen = false;

    /** @var string The user's message input. */
    public string $input = '';

    /** @var bool Whether the agent is currently processing a message. */
    public bool $isProcessing = false;

    /** @var string|null An error message to display in the UI. */
    public ?string $errorMessage = null;

    /** @var int|null The ID of the current conversation, if any. */
    public ?int $conversationId = null;

    /** @var array<int, array{role: string, content: string}> The history of messages in the current session. */
    public array $messages = [];

    /** @var \Livewire\TemporaryUploadedFile|null The uploaded Excel file, if any. */
    public $excelFile;

    /**
     * Initialize the component and set the company context.
     *
     * @param string|null $company_id The company ID.
     * @return void
     */
    public function mount(?string $company_id = null): void
    {
        $this->companyId = $company_id
            ?? request()->route('company_id')
            ?? session('companyId');

        if ($this->companyId) {
            session()->put('companyId', $this->companyId);
        }
    }

    /**
     * Toggle the visibility of the assistant widget.
     *
     * @return void
     */
    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
        $this->errorMessage = null;
    }

    /**
     * Send the user's message (and any uploaded file) to the AI orchestrator.
     *
     * @return void
     */
    public function sendMessage(): void
    {
        $text = trim($this->input);
        if ($text === '' && !$this->excelFile) {
            return;
        }

        if (!$this->companyId) {
            $this->errorMessage = 'Company context is required.';
            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = null;

        $excelPath = null;
        if ($this->excelFile) {
            $stored = $this->excelFile->store('ai-imports', 'local');
            $excelPath = storage_path('app/' . $stored);
            if ($text === '') {
                $text = 'Please import the uploaded employee Excel file.';
            }
        }

        $this->messages[] = ['role' => 'user', 'content' => $text];
        $this->input = '';

        try {
            $orchestrator = app(AgentOrchestrator::class);
            $result = $orchestrator->sendMessage(
                (int) $this->companyId,
                (int) auth()->id(),
                $text,
                $this->conversationId,
                $excelPath
            );

            $this->conversationId = $result['conversation_id'];
            $this->messages[] = ['role' => 'assistant', 'content' => $result['reply']];
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->messages[] = [
                'role' => 'assistant',
                'content' => 'Sorry, something went wrong: ' . $e->getMessage(),
            ];
        } finally {
            $this->isProcessing = false;
            $this->excelFile = null;
        }
    }

    /**
     * Set the input text from a transcript (e.g., from voice-to-text).
     *
     * @param string $text The transcribed text.
     * @return void
     */
    public function setTranscript(string $text): void
    {
        $this->input = trim($text);
    }

    /**
     * Reset the conversation and start a new session.
     *
     * @return void
     */
    public function newConversation(): void
    {
        $this->conversationId = null;
        $this->messages = [];
        $this->errorMessage = null;
        $this->input = '';
    }

    /**
     * Render the Livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.ai-assistant-widget');
    }
}

