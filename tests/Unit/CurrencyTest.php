<?php

namespace Tests\Unit;

use App\Support\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_rial_values_are_not_multiplied_when_formatted(): void
    {
        $this->assertSame('40,000 ریال', Currency::formatRial(40000));
        $this->assertSame('40,000', Currency::formatRialNumber(40000));
        $this->assertSame(40000, Currency::toRial(40000));
    }

    public function test_rial_input_is_normalized_without_unit_conversion(): void
    {
        $this->assertSame(40000, Currency::rialInput('40,000'));
        $this->assertSame(40000, Currency::rialInput('۴۰,۰۰۰ ریال'));
    }
}
