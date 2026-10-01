<?php

use App\Models\Package;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Package::firstOrCreate(
            ['name' => 'Пробний'],
            ['max_accounts' => 2, 'max_devices' => 1, 'price' => 0, 'duration_days' => 3, 'is_active' => true, 'show_on_home' => true],
        );
    }

    public function down(): void
    {
        Package::where('name', 'Пробний')->where('price', 0)->doesntHave('licenses')->delete();
    }
};
