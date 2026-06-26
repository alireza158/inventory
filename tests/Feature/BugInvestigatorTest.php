<?php

namespace Tests\Feature;

use App\Jobs\RunBugInvestigationJob;
use App\Models\BugCase;
use App\Models\User;
use App\Support\BugInvestigator\BugReportBuilder;
use App\Support\BugInvestigator\CodexPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BugInvestigatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_bug_case_can_be_created(): void
    {
        $case = BugCase::create([
            'description' => 'فاکتور در حواله فروش دیده نمی‌شود',
            'module' => 'invoice',
            'severity' => 'مهم',
        ]);

        $this->assertDatabaseHas('bug_cases', ['id' => $case->id, 'module' => 'invoice', 'severity' => 'مهم']);
    }

    public function test_investigation_job_creates_a_report(): void
    {
        $case = BugCase::create(['description' => 'مشکل موجودی کالا', 'module' => 'stock']);

        (new RunBugInvestigationJob($case))->handle(app(\App\Support\BugInvestigator\BugInvestigator::class));

        $this->assertDatabaseHas('bug_cases', ['id' => $case->id, 'status' => 'completed']);
        $this->assertDatabaseHas('bug_case_reports', ['bug_case_id' => $case->id]);
    }

    public function test_pending_or_failed_bug_case_can_be_queued_for_investigation_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        $case = BugCase::create([
            'description' => 'خطای تست',
            'status' => 'failed',
            'error_message' => 'Previous failure',
        ]);
        $report = $case->report()->create([
            'summary' => 'Existing summary',
            'raw_report' => 'Existing report',
            'codex_prompt' => 'Existing prompt',
        ]);

        $response = $this->actingAs($user)->post(route('admin.bug-investigator.rerun', $case));

        $response->assertRedirect(route('admin.bug-investigator.show', $case));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('bug_cases', [
            'id' => $case->id,
            'status' => 'pending',
            'error_message' => null,
        ]);
        $this->assertDatabaseHas('bug_case_reports', [
            'id' => $report->id,
            'bug_case_id' => $case->id,
            'summary' => 'Existing summary',
        ]);
        Queue::assertPushed(RunBugInvestigationJob::class);
    }

    public function test_report_builder_generates_codex_prompt(): void
    {
        $case = BugCase::make(['description' => 'حواله فروش ساخته نشده است']);
        $report = (new BugReportBuilder())->build($case, ['summary' => 'خلاصه تست']);
        $prompt = (new CodexPromptBuilder())->build($report);

        $this->assertStringContainsString('You are fixing a Laravel warehouse/inventory system.', $prompt);
        $this->assertStringContainsString('خلاصه تست', $prompt);
    }
}
