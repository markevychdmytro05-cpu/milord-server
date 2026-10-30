<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('packages')->where('price', '>', 0)->exists()) {
            return;
        }

        $now = now();

        foreach ([
            ['Старт', 5, 1, 15],
            ['Стандарт', 15, 1, 35],
            ['Максимум', 40, 2, 80],
        ] as [$name, $accounts, $devices, $price]) {
            DB::table('packages')->insert([
                'name' => $name,
                'max_accounts' => $accounts,
                'max_devices' => $devices,
                'price' => $price,
                'duration_days' => 30,
                'is_active' => 1,
                'show_on_home' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
    }
};
