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

            $province = Province::updateOrCreate(
                ['name' => $provinceName],
                [
                    'slug' => $this->slug($provinceName),
                    'is_active' => true,
                ]
            );

            foreach ($provinceData['cities'] ?? [] as $cityData) {
                $cityName = trim((string) $cityData['name']);

                if ($cityName === '') {
                    continue;
                }

                City::updateOrCreate(
                    [
                        'province_id' => $province->id,
                        'name' => $cityName,
                    ],
                    [
                        'slug' => $this->slug($cityName),
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    private function slug(string $name): ?string
    {
        $slug = Str::slug($name);

        return $slug !== '' ? $slug : null;
    }
}
