<?php

namespace App\Http\Livewire;

use App\Models\AiConversation;
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

    /** @var bool Whether the conversation history sidebar is visible (mobile overlay). */
    public bool $showHistory = false;

    /** @var string The user's message input. */
    public string $input = '';

    /** @var bool Whether the agent is currently processing a message. */
    public bool $isProcessing = false;

    /** @var bool Whether a queued user message still needs AI processing. */
    public bool $shouldProcess = false;

    /** @var string Text queued for the next AI request. */
    public string $pendingMessageText = '';

    /** @var string|null Path to an Excel file queued for the next AI request. */
    public ?string $pendingExcelPath = null;

    /** @var string|null An error message to display in the UI. */
    public ?string $errorMessage = null;

    /** @var int|null The ID of the current conversation, if any. */
    public ?int $conversationId = null;

    /** @var array<int, array{role: string, content: string}> The history of messages in the current session. */
    public array $messages = [];

    /** @var string|null A short-lived status message shown in the widget UI. */
    public ?string $statusMessage = null;

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
     * Toggle the conversation history sidebar.
     *
     * @return void
     */
    public function toggleHistory(): void
    {
        $this->showHistory = !$this->showHistory;
    }

    /**
     * Send the user's message (and any uploaded file) to the AI orchestrator.
     *
     * @return void
     */
    public function sendMessage(): void
    {
        if ($this->isProcessing) {
            return;
        }

        $text = trim($this->input);
        if ($text === '' && !$this->excelFile) {
            return;
        }

        if (!$this->companyId) {
            $this->errorMessage = 'Company context is required.';
            return;
        }

        $this->errorMessage = null;
        $this->statusMessage = null;

        $excelPath = null;
        $excelFileName = null;
        if ($this->excelFile) {
            $excelFileName = $this->excelFile->getClientOriginalName();
            $stored = $this->excelFile->store('ai-imports', 'local');
            $excelPath = storage_path('app/' . $stored);
            if ($text === '') {
                $text = 'Process the attached Excel file as described in my request.';
            }
        }

        $displayMessage = $text;
        if ($excelFileName) {
            $displayMessage = trim($text) . "\n\n📎 " . $excelFileName;
        }

        $this->messages[] = ['role' => 'user', 'content' => $displayMessage];
        $this->pendingMessageText = $text;
        $this->pendingExcelPath = $excelPath;
        $this->input = '';
        $this->excelFile = null;
        $this->isProcessing = true;
        $this->shouldProcess = true;
    }

    /**
     * Process the queued user message with the AI orchestrator.
     * Triggered after sendMessage() renders so the UI updates immediately.
     *
     * @return void
     */
    public function processMessage(): void
    {
        if (!$this->shouldProcess) {
            return;
        }

        $this->shouldProcess = false;

        $text = $this->pendingMessageText;
        $excelPath = $this->pendingExcelPath;
        $this->pendingMessageText = '';
        $this->pendingExcelPath = null;

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
        $this->isProcessing = false;
        $this->shouldProcess = false;
        $this->pendingMessageText = '';
        $this->pendingExcelPath = null;
        $this->excelFile = null;
        $this->statusMessage = 'New conversation started.';

        $this->dispatchBrowserEvent('ai-conversation-reset');
    }

    /**
     * Load an existing conversation from the database.
     *
     * @param int $conversationId
     * @return void
     */
    public function loadConversation(int $conversationId): void
    {
        if (!$this->companyId) {
            return;
        }

        $conversation = AiConversation::query()
            ->where('company_id', $this->companyId)
            ->where('user_id', auth()->id())
            ->findOrFail($conversationId);

        $this->conversationId = $conversation->id;
        $this->messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => (string) ($message->content ?? ''),
            ])
            ->filter(fn (array $message) => $message['content'] !== '')
            ->values()
            ->all();

        $this->errorMessage = null;
        $this->input = '';
        $this->isProcessing = false;
        $this->shouldProcess = false;
        $this->pendingMessageText = '';
        $this->pendingExcelPath = null;
        $this->excelFile = null;
        $this->statusMessage = null;
        $this->showHistory = false;

        $this->dispatchBrowserEvent('ai-conversation-loaded');
    }

    /**
     * Delete a saved conversation.
     *
     * @param int $conversationId
     * @return void
     */
    public function deleteConversation(int $conversationId): void
    {
        if (!$this->companyId) {
            return;
        }

        $conversation = AiConversation::query()
            ->where('company_id', $this->companyId)
            ->where('user_id', auth()->id())
            ->findOrFail($conversationId);

        $wasActive = $this->conversationId === $conversation->id;
        $conversation->delete();

        if ($wasActive) {
            $this->conversationId = null;
            $this->messages = [];
            $this->input = '';
            $this->errorMessage = null;
            $this->statusMessage = null;
            $this->dispatchBrowserEvent('ai-conversation-reset');
        }
    }

    /**
     * Clear the transient status banner from the widget.
     *
     * @return void
     */
    public function clearStatusMessage(): void
    {
        $this->statusMessage = null;
    }

    /**
     * @return array<int, array{id: int, title: string, updated_at: string}>
     */
    private function getConversations(): array
    {
        if (!$this->companyId || !auth()->check()) {
            return [];
        }

        return AiConversation::query()
            ->where('company_id', $this->companyId)
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get()
            ->map(fn (AiConversation $conversation) => [
                'id' => $conversation->id,
                'title' => $conversation->title ?: 'New chat',
                'updated_at' => $conversation->updated_at?->diffForHumans() ?? '',
            ])
            ->all();
    }

    /**
     * Render the Livewire component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.ai-assistant-widget', [
            'conversations' => $this->getConversations(),
        ]);
    }
}
