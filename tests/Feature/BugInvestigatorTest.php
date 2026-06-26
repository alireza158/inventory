<?php

namespace Tests\Feature;

use App\Jobs\RunBugInvestigationJob;
use App\Models\BugCase;
use App\Support\BugInvestigator\BugReportBuilder;
use App\Support\BugInvestigator\CodexPromptBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_report_builder_generates_codex_prompt(): void
    {
        $case = BugCase::make(['description' => 'حواله فروش ساخته نشده است']);
        $report = (new BugReportBuilder())->build($case, ['summary' => 'خلاصه تست']);
        $prompt = (new CodexPromptBuilder())->build($report);

        $this->assertStringContainsString('You are fixing a Laravel warehouse/inventory system.', $prompt);
        $this->assertStringContainsString('خلاصه تست', $prompt);
    }
}
