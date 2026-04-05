<?php

namespace App\Services;

use App\Models\AssetDocumentItemCode;

class AssetDocumentValidationService
{
    public function __construct(private readonly AssetCodeService $assetCodeService) {}

    public function normalizeAndValidateItems(array $items): array
    {
        if (count($items) < 1) {
            abort(422, 'حداقل یک ردیف کالا باید ثبت شود.');
        }

        $allCodes = [];

        $normalized = collect($items)->map(function ($item, $index) use (&$allCodes) {
            $name = trim((string) ($item['item_name'] ?? ''));
            $quantity = (int) ($item['quantity'] ?? 0);
            $codesInput = (string) ($item['asset_codes_input'] ?? '');
            $description = trim((string) ($item['description'] ?? ''));

            if ($name === '') {
                abort(422, 'نام کالا در همه ردیف‌ها الزامی است.');
            }
            if ($quantity < 1) {
                abort(422, 'تعداد کالا باید حداقل 1 باشد.');
            }

            $codes = $this->assetCodeService->parseCodes($codesInput);
            $this->assetCodeService->validateFourDigitCodes($codes);

            if (count($codes) !== $quantity) {
                abort(422, 'تعداد کدهای اموال باید با تعداد کالا برابر باشد. (ردیف ' . ($index + 1) . ')');
            }

            $allCodes = array_merge($allCodes, $codes);

            return [
                'item_name' => $name,
                'quantity' => $quantity,
                'description' => $description !== '' ? $description : null,
                'codes' => $codes,
            ];
        })->values()->all();

        if (count($allCodes) !== count(array_unique($allCodes))) {
            abort(422, 'کد اموال تکراری بین ردیف‌ها مجاز نیست.');
        }

        $existing = AssetDocumentItemCode::query()
            ->whereIn('asset_code', $allCodes)
            ->pluck('asset_code')
            ->all();

        if (!empty($existing)) {
            abort(422, 'کد اموال ' . $existing[0] . ' قبلاً ثبت شده است.');
        }

        return $normalized;
    }
}
