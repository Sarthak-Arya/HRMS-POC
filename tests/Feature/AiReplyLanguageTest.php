<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\Ai\AgentOrchestrator;
use App\Services\Ai\ExcelPreviewService;
use App\Services\Ai\OpenRouterClient;
use App\Services\Ai\ToolRegistry;
use App\Services\Ai\Tools\EmployeeToolProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiReplyLanguageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->company = Company::create([
            'company_name' => 'Test Co',
            'company_address' => '123 Test St',
            'is_esi' => false,
            'is_pf' => false,
            'company_handled_by' => $this->user->id,
        ]);
    }

    public function test_english_user_message_uses_english_reply_instruction(): void
    {
        $capturedMessages = null;

        $mockClient = Mockery::mock(OpenRouterClient::class);
        $mockClient->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $messages) use (&$capturedMessages) {
                $capturedMessages = $messages;

                return true;
            })
            ->andReturn(['content' => 'Please upload an Excel file with employee details.']);

        $orchestrator = $this->makeOrchestrator($mockClient);

        $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'add employees to the record',
        );

        $systemPrompt = collect($capturedMessages)->firstWhere('role', 'system')['content'] ?? '';
        $this->assertStringContainsString('Reply in English only', $systemPrompt);
        $this->assertStringNotContainsString('Reply in Hindi only', $systemPrompt);
    }

    public function test_explicit_hindi_request_switches_to_hindi_reply_instruction(): void
    {
        $capturedMessages = null;

        $mockClient = Mockery::mock(OpenRouterClient::class);
        $mockClient->shouldReceive('chat')
            ->once()
            ->withArgs(function (array $messages) use (&$capturedMessages) {
                $capturedMessages = $messages;

                return true;
            })
            ->andReturn(['content' => 'ठीक है, मैं हिंदी में जवाब दूंगा।']);

        $orchestrator = $this->makeOrchestrator($mockClient);

        $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'reply in Hindi',
        );

        $systemPrompt = collect($capturedMessages)->firstWhere('role', 'system')['content'] ?? '';
        $this->assertStringContainsString('Reply in Hindi only', $systemPrompt);
    }

    public function test_english_request_after_hindi_request_switches_back_to_english(): void
    {
        $capturedMessages = null;

        $mockClient = Mockery::mock(OpenRouterClient::class);
        $mockClient->shouldReceive('chat')
            ->twice()
            ->withArgs(function (array $messages) use (&$capturedMessages) {
                $capturedMessages = $messages;

                return true;
            })
            ->andReturn(['content' => 'OK.']);

        $orchestrator = $this->makeOrchestrator($mockClient);

        $first = $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'reply in Hindi',
        );

        $orchestrator->sendMessage(
            $this->company->id,
            $this->user->id,
            'reply in English',
            $first['conversation_id'],
        );

        $systemPrompt = collect($capturedMessages)->firstWhere('role', 'system')['content'] ?? '';
        $this->assertStringContainsString('Reply in English only', $systemPrompt);
    }

    private function makeOrchestrator(OpenRouterClient $mockClient): AgentOrchestrator
    {
        $registry = new ToolRegistry();
        $registry->registerMany(EmployeeToolProvider::tools());

        return new AgentOrchestrator($mockClient, $registry, app(ExcelPreviewService::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
