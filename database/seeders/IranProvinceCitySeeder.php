<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IranProvinceCitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('iran.provinces', []) as $provinceData) {
            $provinceName = trim((string) $provinceData['name']);

            if ($provinceName === '') {
                continue;
            }

            $province = $this->upsertProvince($provinceData, $provinceName);

            foreach ($provinceData['cities'] ?? [] as $cityData) {
                $cityName = trim((string) $cityData['name']);

                if ($cityName === '') {
                    continue;
                }

                $this->upsertCity($cityData, $province->id, $cityName);
            }
        }
    }

    private function upsertProvince(array $provinceData, string $provinceName): Province
    {
        $values = [
            'name' => $provinceName,
            'slug' => $this->slug($provinceName),
            'is_active' => true,
        ];

        if (Province::query()->where('name', $provinceName)->exists()) {
            return Province::updateOrCreate(['name' => $provinceName], $values);
        }

        $configuredId = (int) ($provinceData['id'] ?? 0);

        if ($configuredId > 0 && !Province::query()->whereKey($configuredId)->exists()) {
            return Province::unguarded(fn () => Province::updateOrCreate(['id' => $configuredId], $values));
        }

        return Province::updateOrCreate(['name' => $provinceName], $values);
    }

    private function upsertCity(array $cityData, int $provinceId, string $cityName): City
    {
        $values = [
            'province_id' => $provinceId,
            'name' => $cityName,
            'slug' => $this->slug($cityName),
            'is_active' => true,
        ];

        $identity = [
            'province_id' => $provinceId,
            'name' => $cityName,
        ];

        if (City::query()->where($identity)->exists()) {
            return City::updateOrCreate($identity, $values);
        }

        $configuredId = (int) ($cityData['id'] ?? 0);

        if ($configuredId > 0 && !City::query()->whereKey($configuredId)->exists()) {
            return City::unguarded(fn () => City::updateOrCreate(['id' => $configuredId], $values));
        }

        return City::updateOrCreate($identity, $values);
    }

    private function slug(string $name): ?string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : null;
    }
}
