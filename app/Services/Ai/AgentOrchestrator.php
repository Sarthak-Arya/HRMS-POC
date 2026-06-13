<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orchestrates AI agent interactions, including message handling, tool execution, and conversation management.
 * Interfaces with the OpenRouter AI client and a registry of executable tools.
 */
class AgentOrchestrator
{
    /**
     * Create a new AgentOrchestrator instance.
     *
     * @param OpenRouterClient $client The client for interacting with the AI model.
     * @param ToolRegistry $registry The registry of tools available to the AI.
     */
    public function __construct(
        private OpenRouterClient $client,
        private ToolRegistry $registry,
    ) {}

    /**
     * Send a message to the AI agent and handle the response, including tool calls.
     *
     * @param int $companyId The ID of the company context.
     * @param int $userId The ID of the user sending the message.
     * @param string $userMessage The content of the user's message.
     * @param int|null $conversationId Optional ID of an existing conversation.
     * @param string|null $pendingExcelPath Optional path to a pending Excel file for import.
     * @return array{reply: string, conversation_id: int} The AI's reply and the conversation ID.
     * @throws RuntimeException If company access is denied or max tool rounds are exceeded.
     */
    public function sendMessage(
        int $companyId,
        int $userId,
        string $userMessage,
        ?int $conversationId = null,
        ?string $pendingExcelPath = null,
    ): array {
        $this->assertCompanyAccess($companyId, $userId);

        $conversation = $conversationId
            ? AiConversation::where('company_id', $companyId)->where('user_id', $userId)->findOrFail($conversationId)
            : AiConversation::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'title' => mb_substr($userMessage, 0, 80),
            ]);

        $content = $userMessage;
        if ($pendingExcelPath) {
            $content .= "\n\n[System: User uploaded Excel file at path: {$pendingExcelPath}. Use import_employees_excel tool with this file_path.]";
        }

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        $messages = $this->buildMessages($conversation, $companyId);

        $maxRounds = config('ai.agent.max_tool_rounds');

        for ($round = 0; $round < $maxRounds; $round++) {
            $response = $this->client->chat($messages, $this->registry->schemas());

            if (!empty($response['tool_calls'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response['content'] ?? null,
                    'tool_calls' => $response['tool_calls'],
                ];

                foreach ($response['tool_calls'] as $toolCall) {
                    $toolName = $toolCall['function']['name'] ?? '';
                    $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                    if ($pendingExcelPath && $toolName === 'import_employees_excel' && empty($args['file_path'])) {
                        $args['file_path'] = $pendingExcelPath;
                    }

                    $result = $this->executeTool($toolName, $args, $companyId, $userId);

                    AiMessage::create([
                        'conversation_id' => $conversation->id,
                        'role' => 'tool',
                        'content' => json_encode($result),
                        'tool_name' => $toolName,
                        'tool_payload' => $args,
                    ]);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode($result),
                    ];
                }

                continue;
            }

            $reply = trim((string) ($response['content'] ?? ''));
            if ($reply === '') {
                $reply = 'I could not generate a response. Please try again.';
            }

            AiMessage::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $reply,
            ]);

            return [
                'reply' => $reply,
                'conversation_id' => $conversation->id,
            ];
        }

        throw new RuntimeException('Agent exceeded maximum tool rounds.');
    }

    /**
     * Build the message history for the AI model.
     *
     * @param AiConversation $conversation The conversation model.
     * @param int $companyId The ID of the company context.
     * @return array<int, array<string, mixed>> The array of messages formatted for the AI client.
     */
    private function buildMessages(AiConversation $conversation, int $companyId): array
    {
        $company = Company::find($companyId);
        $companyName = $company?->company_name ?? 'Company';

        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt($companyName, $companyId),
            ],
        ];

        $history = $conversation->messages()
            ->orderBy('id')
            ->get();

        foreach ($history as $msg) {
            if ($msg->role === 'user') {
                $messages[] = ['role' => 'user', 'content' => $msg->content];
            } elseif ($msg->role === 'assistant') {
                $messages[] = ['role' => 'assistant', 'content' => $msg->content];
            } elseif ($msg->role === 'tool') {
                $messages[] = [
                    'role' => 'tool',
                    'content' => $msg->content,
                ];
            }
        }

        return $messages;
    }

    /**
     * Generate the system prompt for the AI agent.
     *
     * @param string $companyName The name of the company.
     * @param int $companyId The ID of the company.
     * @return string The formatted system prompt.
     */
    private function systemPrompt(string $companyName, int $companyId): string
    {
        return <<<PROMPT
You are a helpful payroll assistant for "{$companyName}" (company ID: {$companyId}).
You understand and respond fluently in both Hindi and English — match the user's language.

You can manage employees using the available tools:
- search_employees: find employees by name, code, department, etc.
- get_employee: fetch one employee by ID or code
- create_employee: add a single new employee
- update_employee: update an existing employee (partial updates OK)
- bulk_upsert_employees: create/update multiple employees at once
- import_employees_excel: import from an uploaded Excel/CSV file

Field mapping (Hindi ↔ English):
- कर्मचारी कोड / employee code
- नाम / name, employee_name
- पिता का नाम / father_name
- विभाग / department
- पद / designation
- स्थान / location
- पुरुष/male → M, महिला/female → F

Rules:
1. Always use tools to read or modify employee data — never invent database records.
2. Confirm with the user before bulk_upsert_employees with more than 3 employees or before import_employees_excel unless the user explicitly asked to import.
3. If required fields are missing, ask the user in their language.
4. After successful mutations, summarize what changed clearly.
5. Employee codes: use what the user provides; otherwise the system auto-generates EMP* codes.
PROMPT;
    }

    /**
     * Execute a specific tool requested by the AI.
     *
     * @param string $toolName The name of the tool to execute.
     * @param array<string, mixed> $args The arguments for the tool.
     * @param int $companyId The ID of the company context.
     * @param int $userId The ID of the user executing the tool.
     * @return array<string, mixed> The result of the tool execution.
     */
    private function executeTool(string $toolName, array $args, int $companyId, int $userId): array
    {
        try {
            $tool = $this->registry->get($toolName);

            if ($tool->isMutating()) {
                Log::info('AI tool executed', [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'tool' => $toolName,
                    'args_summary' => array_keys($args),
                ]);
            }

            return $tool->handle($args, $companyId, $userId);
        } catch (\Throwable $e) {
            Log::error('AI tool failed', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Assert that the user has access to the specified company.
     *
     * @param int $companyId The ID of the company.
     * @param int $userId The ID of the user.
     * @return void
     * @throws RuntimeException If company is not found or access is denied.
     */
    private function assertCompanyAccess(int $companyId, int $userId): void
    {
        $company = Company::find($companyId);
        if (!$company) {
            throw new RuntimeException('Company not found.');
        }

        if ((int) $company->company_handled_by !== $userId) {
            throw new RuntimeException('You do not have permission to access this company.');
        }
    }
}

