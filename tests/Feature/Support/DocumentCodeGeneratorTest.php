<?php

namespace Tests\Feature\Support;

use App\Models\Invoice;
use App\Models\PreinvoiceOrder;
use App\Support\DocumentCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_starts_preinvoice_sequence_after_existing_real_invoice_floor_while_ignoring_legacy_four_digit_codes(): void
    {
        PreinvoiceOrder::query()->create($this->preinvoiceData(['uuid' => '1234']));
        PreinvoiceOrder::query()->create($this->preinvoiceData(['uuid' => '9999']));

        $this->assertSame('00118', DocumentCodeGenerator::generateUnique5DigitCode(PreinvoiceOrder::class));
    }

    public function test_it_continues_existing_five_digit_invoice_sequence(): void
    {
        Invoice::query()->create($this->invoiceData(['uuid' => '9999']));
        Invoice::query()->create($this->invoiceData(['uuid' => '00001']));
        Invoice::query()->create($this->invoiceData(['uuid' => '00002']));

        $this->assertSame('00118', DocumentCodeGenerator::generateUnique5DigitCode(Invoice::class));
    }


    public function test_invoice_and_preinvoice_share_the_same_official_sequence(): void
    {
        $this->assertSame('00118', DocumentCodeGenerator::generateUnique5DigitCode(PreinvoiceOrder::class));
        $this->assertSame('00119', DocumentCodeGenerator::generateUnique5DigitCode(Invoice::class));
        $this->assertSame('00120', DocumentCodeGenerator::generateUnique5DigitCode(PreinvoiceOrder::class));
    }

    private function preinvoiceData(array $overrides = []): array
    {
        return array_merge([
            'uuid' => '00000',
            'status' => PreinvoiceOrder::STATUS_RESERVED_WAITING_WAREHOUSE,
            'customer_name' => 'مشتری تست',
            'customer_mobile' => '09120000000',
            'customer_address' => 'آدرس تست',
            'province_id' => 1,
            'city_id' => 1,
            'shipping_id' => 0,
            'shipping_price' => 0,
            'discount_amount' => 0,
            'total_price' => 0,
        ], $overrides);
    }

    private function invoiceData(array $overrides = []): array
    {
        return array_merge([
            'uuid' => '00000',
            'customer_name' => 'مشتری تست',
            'customer_mobile' => '09120000000',
            'customer_address' => 'آدرس تست',
            'shipping_price' => 0,
            'discount_amount' => 0,
            'subtotal' => 0,
            'total' => 0,
            'status' => Invoice::STATUS_PENDING_WAREHOUSE_APPROVAL,
        ], $overrides);
    }
}
