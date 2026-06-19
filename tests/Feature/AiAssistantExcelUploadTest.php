<?php

namespace Tests\Feature;

use App\Http\Livewire\AiAssistantWidget;
use App\Models\Company;
use App\Models\User;
use App\Services\Ai\AgentOrchestrator;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AiAssistantExcelUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->user = User::factory()->hrManager()->create();
        $this->company = Company::factory()->create(['company_handled_by' => $this->user->id]);

        Config::set('filesystems.disks.tmp-for-tests', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/tmp-for-tests'),
        ]);
        Config::set('livewire.temporary_file_upload.disk', 'tmp-for-tests');
        Storage::fake('tmp-for-tests');
        Storage::fake('local');
    }

    public function test_widget_sends_excel_file_to_orchestrator(): void
    {
        $companyId = $this->company->id;
        $userId = $this->user->id;

        $mock = Mockery::mock(AgentOrchestrator::class);
        $mock->shouldReceive('sendMessage')
            ->once()
            ->withArgs(function (int $cid, int $uid, string $message, ?int $conversationId, ?string $excelPath) use ($companyId, $userId) {
                return $cid === $companyId
                    && $uid === $userId
                    && str_contains($message, 'Update the attendance')
                    && $excelPath !== null
                    && str_contains($excelPath, 'ai-imports');
            })
            ->andReturn([
                'reply' => 'Please provide month and year.',
                'conversation_id' => 99,
            ]);

        $this->app->instance(AgentOrchestrator::class, $mock);

        $file = UploadedFile::fake()->create(
            'demo_attendance_template.xlsx',
            10,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );

        Livewire::actingAs($this->user)
            ->test(AiAssistantWidget::class, ['company_id' => (string) $this->company->id])
            ->set('isOpen', true)
            ->set('input', 'Update the attendance')
            ->set('excelFile', $file)
            ->call('sendMessage')
            ->call('processMessage')
            ->assertSet('conversationId', 99)
            ->assertSee('Please provide month and year.');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
