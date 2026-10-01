<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Старт', 'max_accounts' => 5, 'price' => 50],
            ['name' => 'Базовий', 'max_accounts' => 10, 'price' => 90],
            ['name' => 'Про', 'max_accounts' => 20, 'price' => 150],
        ];

        foreach ($packages as $package) {
            Package::firstOrCreate(['name' => $package['name']], $package + ['max_devices' => 1, 'duration_days' => 30]);
        }
    }
}
