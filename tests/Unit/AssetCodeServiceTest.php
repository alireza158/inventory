<?php

namespace Tests\Unit;

use App\Services\AssetCodeService;
use PHPUnit\Framework\TestCase;

class AssetCodeServiceTest extends TestCase
{
    public function test_parse_codes_with_mixed_separators(): void
    {
        $service = new AssetCodeService();
        $codes = $service->parseCodes("1023\n1024, 1025 1026");

        $this->assertSame(['1023', '1024', '1025', '1026'], $codes);
    }

    public function test_validate_four_digit_codes_accepts_valid_list(): void
    {
        $service = new AssetCodeService();
        $this->assertSame(['1023', '9999'], $service->validateFourDigitCodes(['1023', '9999']));
    }
}
