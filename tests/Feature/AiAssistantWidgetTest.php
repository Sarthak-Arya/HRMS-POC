<?php

namespace Tests\Feature;

use App\Http\Livewire\AiAssistantWidget;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AiAssistantWidgetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create(['company_handled_by' => $this->user->id]);
    }

    public function test_it_lists_saved_conversations_for_the_current_user_and_company(): void
    {
        $mine = AiConversation::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Employee import help',
        ]);

        AiConversation::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Attendance question',
        ]);

        $otherUser = User::factory()->create();
        AiConversation::create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'title' => 'Someone else chat',
        ]);

        Livewire::actingAs($this->user)
            ->test(AiAssistantWidget::class, ['company_id' => (string) $this->company->id])
            ->set('isOpen', true)
            ->assertSee('Employee import help')
            ->assertSee('Attendance question')
            ->assertDontSee('Someone else chat')
            ->call('loadConversation', $mine->id)
            ->assertSet('conversationId', $mine->id);
    }

    public function test_it_loads_user_and_assistant_messages_when_opening_a_conversation(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Payroll chat',
        ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'How many employees are active?',
        ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'tool',
            'content' => null,
            'tool_name' => 'search_employees',
            'tool_payload' => ['query' => 'active'],
        ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'You have 12 active employees.',
        ]);

        Livewire::actingAs($this->user)
            ->test(AiAssistantWidget::class, ['company_id' => (string) $this->company->id])
            ->call('loadConversation', $conversation->id)
            ->assertSet('conversationId', $conversation->id)
            ->assertSet('messages', [
                ['role' => 'user', 'content' => 'How many employees are active?'],
                ['role' => 'assistant', 'content' => 'You have 12 active employees.'],
            ]);
    }

    public function test_it_deletes_a_conversation_and_clears_the_active_chat(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Delete me',
        ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Hello',
        ]);

        Livewire::actingAs($this->user)
            ->test(AiAssistantWidget::class, ['company_id' => (string) $this->company->id])
            ->call('loadConversation', $conversation->id)
            ->call('deleteConversation', $conversation->id)
            ->set('isOpen', true)
            ->assertSet('conversationId', null)
            ->assertSet('messages', [])
            ->assertDontSee('Delete me');

        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('ai_messages', ['conversation_id' => $conversation->id]);
    }

    public function test_new_conversation_resets_the_active_chat(): void
    {
        $conversation = AiConversation::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Existing chat',
        ]);

        Livewire::actingAs($this->user)
            ->test(AiAssistantWidget::class, ['company_id' => (string) $this->company->id])
            ->call('loadConversation', $conversation->id)
            ->call('newConversation')
            ->set('isOpen', true)
            ->assertSet('conversationId', null)
            ->assertSet('messages', [])
            ->assertSet('statusMessage', 'New conversation started.');
    }
}
