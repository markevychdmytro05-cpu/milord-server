<?php

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

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
