<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => '2 акаунти', 'max_accounts' => 2, 'price' => 35],
            ['name' => '5 акаунтів', 'max_accounts' => 5, 'price' => 85],
            ['name' => '10 акаунтів', 'max_accounts' => 10, 'price' => 170],
            ['name' => '15 акаунтів', 'max_accounts' => 15, 'price' => 220],
            ['name' => '20 акаунтів', 'max_accounts' => 20, 'price' => 265, 'show_on_home' => false],
            ['name' => '25 акаунтів', 'max_accounts' => 25, 'price' => 310, 'show_on_home' => false],
            ['name' => '30 акаунтів', 'max_accounts' => 30, 'price' => 355, 'show_on_home' => false],
        ];

        foreach ($packages as $package) {
            Package::firstOrCreate(['name' => $package['name']], $package + ['max_devices' => 3, 'duration_days' => null, 'show_on_home' => true]);
        }
    }
}
