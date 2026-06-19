<?php

namespace App\Services\Ai;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Company;
use App\Models\User;
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
        private ExcelPreviewService $excelPreview,
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

        $excelPath = $this->resolveExcelPath($conversation, $pendingExcelPath);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        $messages = $this->buildMessages($conversation, $companyId);
        $period = $this->resolveAttendancePeriod($conversation, $userMessage);
        if ($excelPath) {
            $messages = $this->enrichWithExcelContext($messages, $userMessage, $excelPath, $period);
        }

        $maxRounds = config('ai.agent.max_tool_rounds');

        for ($round = 0; $round < $maxRounds; $round++) {
            try {
                $response = $this->client->chat($messages, $this->registry->schemas());
            } catch (RuntimeException $e) {
                if ($fallback = $this->tryDirectAttendanceImport(
                    $conversation,
                    $excelPath,
                    $period,
                    $companyId,
                    $userId,
                    $userMessage,
                )) {
                    return $fallback;
                }

                throw $e;
            }

            if (!empty($response['tool_calls'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $response['content'] ?? null,
                    'tool_calls' => $response['tool_calls'],
                ];

                foreach ($response['tool_calls'] as $toolCall) {
                    $toolName = $toolCall['function']['name'] ?? '';
                    $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                    if ($excelPath && in_array($toolName, ['import_employees_excel', 'import_attendance_excel'], true)) {
                        if (empty($args['file_path'])) {
                            $args['file_path'] = $excelPath;
                        }
                        if ($toolName === 'import_attendance_excel' && $period) {
                            $args['month'] ??= $period['month'];
                            $args['year'] ??= $period['year'];
                        }
                    }

                    $result = $this->executeTool($toolName, $args, $companyId, $userId);

                    if ($excelPath && in_array($toolName, ['import_employees_excel', 'import_attendance_excel'], true) && ($result['success'] ?? false)) {
                        $conversation->update(['pending_excel_path' => null]);
                        $excelPath = null;
                    }

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

    private function resolveExcelPath(AiConversation $conversation, ?string $uploadedPath): ?string
    {
        if ($uploadedPath !== null && $uploadedPath !== '' && is_readable($uploadedPath)) {
            $conversation->update(['pending_excel_path' => $uploadedPath]);

            return $uploadedPath;
        }

        $storedPath = $conversation->pending_excel_path;
        if ($storedPath && is_readable($storedPath)) {
            return $storedPath;
        }

        if ($storedPath) {
            $conversation->update(['pending_excel_path' => null]);
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array{month: int, year: int}|null $period
     * @return array<int, array<string, mixed>>
     */
    private function enrichWithExcelContext(array $messages, string $userMessage, string $filePath, ?array $period): array
    {
        $previewText = $this->excelPreview->formatForPrompt($filePath);
        $enriched = trim($userMessage) !== ''
            ? trim($userMessage) . "\n\n[Uploaded Excel file]\n" . $previewText
            : "[Uploaded Excel file]\n" . $previewText;

        $enriched .= "\n\n[System: file_path for import tools: {$filePath}]";

        if ($period) {
            $enriched .= "\n[System: attendance period month={$period['month']} year={$period['year']}. Use import_attendance_excel with these defaults if rows omit month/year.]";
        }

        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                $messages[$i]['content'] = $enriched;
                return $messages;
            }
        }

        $messages[] = ['role' => 'user', 'content' => $enriched];

        return $messages;
    }

    /**
     * @return array{month: int, year: int}|null
     */
    private function resolveAttendancePeriod(AiConversation $conversation, string $currentMessage): ?array
    {
        if ($period = $this->parseMonthYear($currentMessage)) {
            return $period;
        }

        foreach ($conversation->messages()->where('role', 'user')->orderByDesc('id')->limit(6)->get() as $message) {
            if ($period = $this->parseMonthYear((string) ($message->content ?? ''))) {
                return $period;
            }
        }

        return null;
    }

    /**
     * @return array{reply: string, conversation_id: int}|null
     */
    private function tryDirectAttendanceImport(
        AiConversation $conversation,
        ?string $excelPath,
        ?array $period,
        int $companyId,
        int $userId,
        string $userMessage,
    ): ?array {
        if (!$excelPath || !$period || !is_readable($excelPath)) {
            return null;
        }

        if (!$this->conversationWantsAttendanceImport($conversation, $userMessage)) {
            return null;
        }

        try {
            $preview = $this->excelPreview->preview($excelPath);
        } catch (\Throwable) {
            return null;
        }

        if ($preview['detected_type'] !== 'attendance') {
            return null;
        }

        $result = $this->executeTool('import_attendance_excel', [
            'file_path' => $excelPath,
            'month' => $period['month'],
            'year' => $period['year'],
        ], $companyId, $userId);

        if (!($result['success'] ?? false)) {
            return null;
        }

        $conversation->update(['pending_excel_path' => null]);
        $reply = $this->formatAttendanceImportReply($result, $period);

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

    private function conversationWantsAttendanceImport(AiConversation $conversation, string $currentMessage): bool
    {
        $needles = ['attendance', 'hajiri', 'हाजिरी', 'import', 'update', 'upload'];
        $haystack = mb_strtolower($currentMessage);

        foreach ($conversation->messages()->where('role', 'user')->orderByDesc('id')->limit(6)->get() as $message) {
            $haystack .= ' ' . mb_strtolower((string) ($message->content ?? ''));
        }

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{month: int, year: int} $period
     * @param array<string, mixed> $result
     */
    private function formatAttendanceImportReply(array $result, array $period): string
    {
        $month = $period['month'];
        $year = $period['year'];

        return sprintf(
            'Attendance imported for %02d/%d. Created: %d, updated: %d, failed: %d.',
            $month,
            $year,
            (int) ($result['created'] ?? 0),
            (int) ($result['updated'] ?? 0),
            (int) ($result['failed'] ?? 0),
        );
    }

    /**
     * @return array{month: int, year: int}|null
     */
    private function parseMonthYear(string $text): ?array
    {
        if (preg_match('/\b(0?[1-9]|1[0-2])\s*[\/\-\.]\s*(20\d{2})\b/', $text, $matches)) {
            return ['month' => (int) $matches[1], 'year' => (int) $matches[2]];
        }

        if (preg_match('/\b(january|february|march|april|may|june|july|august|september|october|november|december)\s+(20\d{2})\b/i', $text, $matches)) {
            $months = [
                'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
                'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
                'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
            ];

            return [
                'month' => $months[strtolower($matches[1])],
                'year' => (int) $matches[2],
            ];
        }

        return null;
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
- import_employees_excel: import employee rows from an uploaded Excel/CSV file

You can manage monthly attendance using:
- search_attendance: list attendance for a month/year (filter by employee or department)
- get_attendance: fetch one employee's attendance for a month/year
- upsert_attendance: add or update attendance for one employee
- bulk_upsert_attendance: add or update attendance for multiple employees
- import_attendance_excel: import attendance rows from an uploaded Excel file

Field mapping (Hindi ↔ English):
- कर्मचारी कोड / employee code / EMPNO (shown in the app as "EMPNO & Employee Name")
- employee_id in Excel may mean EMPNO or internal numeric ID — the system preserves the exact value
- नाम / name, employee_name
- पिता का नाम / father_name
- विभाग / department
- पद / designation
- स्थान / location
- पुरुष/male → M, महिला/female → F
- हाजिरी / attendance
- CL / casual leave (cl)
- EL / earned leave (el)
- SL / sick leave (sl)
- अवकाश / holiday
- कुल दिन / total days (tot_dys)
- काम के दिन / worked days (auto-calculated)

Rules:
1. Always use tools to read or modify employee and attendance data — never invent database records.
2. When the user attaches an Excel file, read the provided file preview and follow the user's prompt. The file stays available for later messages in the same chat — do not ask the user to upload again.
3. For attendance Excel imports, if the user gives month/year (e.g. 06/2026) call import_attendance_excel with file_path, month, and year. Apply month/year to rows that omit them. Use import_attendance_excel — do not manually re-type rows into bulk_upsert_attendance.
4. Confirm with the user before bulk_upsert_employees or bulk_upsert_attendance with more than 3 records, or before import_employees_excel / import_attendance_excel unless the user explicitly asked to import or update from the file.
5. If required fields are missing (e.g. month, year, employee), ask the user in their language.
6. After successful mutations, summarize what changed clearly.
7. Employee codes (EMPNO): always use the exact code from the user or uploaded file — never pad, reformat, rename, or auto-generate unless the user did not provide one for a new employee. Never change an existing employee's code unless the user explicitly asks.
8. employee_id in tools means the internal numeric database ID only when the value is purely numeric. Values like EMP001 are employee_code (EMPNO), not database IDs.
9. For attendance, month (1-12) and year are always required. Use search_employees first if you need to resolve an employee by name.
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

        $user = User::find($userId);
        if (!$user || !$user->canAccessCompany($company)) {
            throw new RuntimeException('You do not have permission to access this company.');
        }
    }
}
