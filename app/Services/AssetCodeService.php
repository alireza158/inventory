<?php

namespace App\Services;

class AssetCodeService
{
    public function parseCodes(string $input): array
    {
        $normalized = preg_replace('/[\s,]+/', ',', trim($input));
        if (!$normalized) {
            return [];
        }

        return collect(explode(',', $normalized))
            ->map(fn ($code) => trim($code))
            ->filter(fn ($code) => $code !== '')
            ->values()
            ->all();
    }

    public function validateFourDigitCodes(array $codes): array
    {
        foreach ($codes as $code) {
            if (!preg_match('/^\d{4}$/', (string) $code)) {
                abort(422, 'هر کد اموال باید دقیقاً 4 رقم باشد.');
            }
        }

        if (count($codes) !== count(array_unique($codes))) {
            abort(422, 'کدهای اموال تکراری در یک ردیف مجاز نیست.');
        }

        return $codes;
    }
}
