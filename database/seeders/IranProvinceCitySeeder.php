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
            $province = Province::updateOrCreate(
                ['id' => (int) $provinceData['id']],
                ['name' => $provinceData['name'], 'slug' => Str::slug($provinceData['name']), 'is_active' => true]
            );

            foreach ($provinceData['cities'] ?? [] as $cityData) {
                City::updateOrCreate(
                    ['id' => (int) $cityData['id']],
                    ['province_id' => $province->id, 'name' => $cityData['name'], 'slug' => Str::slug($cityData['name']), 'is_active' => true]
                );
            }
        }
    }
}
