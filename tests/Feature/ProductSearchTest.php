<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_word_product_search_requires_every_token_across_searchable_fields(): void
    {
        $category = Category::create(['name' => 'لوازم جانبی']);

        $bothInName = Product::create([
            'category_id' => $category->id,
            'name' => 'کاور کیفی مگنتی سامسونگ',
            'sku' => 'SKU-001',
            'stock' => 5,
            'price' => 1000,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'کاور کیفی ساده',
            'sku' => 'SKU-002',
            'stock' => 5,
            'price' => 1000,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'هولدر مگنتی خودرو',
            'sku' => 'SKU-003',
            'stock' => 5,
            'price' => 1000,
        ]);

        $bothAcrossVariant = Product::create([
            'category_id' => $category->id,
            'name' => 'قاب گوشی',
            'sku' => 'SKU-004',
            'stock' => 5,
            'price' => 1000,
        ]);
        ProductVariant::create([
            'product_id' => $bothAcrossVariant->id,
            'variant_name' => 'مدل مگنتی',
            'variety_name' => 'کیفی',
            'variant_code' => 'VM-004',
            'sell_price' => 1000,
            'stock' => 5,
        ]);

        $results = Product::query()->search('کیفی مگنتی')->pluck('id')->all();

        $this->assertContains($bothInName->id, $results);
        $this->assertContains($bothAcrossVariant->id, $results);
        $this->assertCount(2, $results);
    }

    public function test_multi_word_product_search_can_match_category_and_keeps_category_filters(): void
    {
        $samsung = Category::create(['name' => 'سامسونگ']);
        $apple = Category::create(['name' => 'اپل']);

        $samsungCase = Product::create([
            'category_id' => $samsung->id,
            'name' => 'کاور کیفی',
            'sku' => 'SKU-101',
            'stock' => 5,
            'price' => 1000,
        ]);

        Product::create([
            'category_id' => $apple->id,
            'name' => 'کاور کیفی',
            'sku' => 'SKU-102',
            'stock' => 5,
            'price' => 1000,
        ]);

        $results = Product::query()
            ->where('category_id', $samsung->id)
            ->search('کیفی سامسونگ')
            ->pluck('id')
            ->all();

        $this->assertSame([$samsungCase->id], $results);
    }

    public function test_single_word_and_code_search_still_match_related_products(): void
    {
        $category = Category::create(['name' => 'لوازم جانبی']);

        $magnetic = Product::create([
            'category_id' => $category->id,
            'name' => 'هولدر مگنتی',
            'sku' => 'SKU-201',
            'code' => 'PRD-201',
            'barcode' => '6260000000201',
            'short_barcode' => '0201',
            'stock' => 5,
            'price' => 1000,
        ]);

        $byVariantCode = Product::create([
            'category_id' => $category->id,
            'name' => 'کاور گوشی',
            'sku' => 'SKU-202',
            'stock' => 5,
            'price' => 1000,
        ]);
        ProductVariant::create([
            'product_id' => $byVariantCode->id,
            'variant_name' => 'مدل چرمی',
            'variant_code' => 'MAG-202',
            'sell_price' => 1000,
            'stock' => 5,
        ]);

        $this->assertContains($magnetic->id, Product::query()->search('مگنتی')->pluck('id')->all());
        $this->assertSame([$magnetic->id], Product::query()->search('6260000000201')->pluck('id')->all());
        $this->assertSame([$byVariantCode->id], Product::query()->search('MAG-202')->pluck('id')->all());
    }
}
