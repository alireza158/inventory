<?php

namespace Tests\Unit;

use App\Support\BugInvestigator\BugModuleClassifier;
use PHPUnit\Framework\TestCase;

class BugModuleClassifierTest extends TestCase
{
    public function test_it_detects_module_from_persian_keywords(): void
    {
        $this->assertSame('proforma', (new BugModuleClassifier())->classify('پیش‌فاکتور بعد از تایید مالی مشکل دارد'));
    }
}
