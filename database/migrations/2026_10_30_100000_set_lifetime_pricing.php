<?php

use App\Models\Package;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    private const PLANS = [
        ['name' => '2 акаунти', 'max_accounts' => 2, 'price' => 35, 'show_on_home' => true],
        ['name' => '5 акаунтів', 'max_accounts' => 5, 'price' => 85, 'show_on_home' => true],
        ['name' => '10 акаунтів', 'max_accounts' => 10, 'price' => 170, 'show_on_home' => true],
        ['name' => '15 акаунтів', 'max_accounts' => 15, 'price' => 220, 'show_on_home' => true],
        ['name' => '20 акаунтів', 'max_accounts' => 20, 'price' => 265, 'show_on_home' => false],
        ['name' => '25 акаунтів', 'max_accounts' => 25, 'price' => 310, 'show_on_home' => false],
        ['name' => '30 акаунтів', 'max_accounts' => 30, 'price' => 355, 'show_on_home' => false],
    ];

    public function up(): void
    {
        foreach (self::PLANS as $plan) {
            Package::updateOrCreate(
                ['name' => $plan['name']],
                $plan + ['max_devices' => 3, 'duration_days' => null, 'is_active' => true],
            );
        }

        Package::where('price', 0)->where('duration_days', '>', 0)->update(['max_devices' => 3]);

        Package::where('price', '>', 0)
            ->whereNotIn('name', array_column(self::PLANS, 'name'))
            ->update(['is_active' => false, 'show_on_home' => false]);
    }

    public function down(): void
    {
        Package::whereIn('name', array_column(self::PLANS, 'name'))->doesntHave('licenses')->delete();
    }
};
