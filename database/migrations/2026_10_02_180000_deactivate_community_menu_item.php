<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/** Спільнота тимчасово вимкнена: пункт у шапці приховано, повернути його можна в адмінці (Меню шапки). */
return new class extends Migration
{
    public function up(): void
    {
        MenuItem::where('url', '/spilnota')->update(['is_active' => false]);
    }

    public function down(): void
    {
        MenuItem::where('url', '/spilnota')->update(['is_active' => true]);
    }
};
