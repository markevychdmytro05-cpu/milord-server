<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->where('type', 'page')->where('slug', 'baza-znan')->update(['title' => 'База знань']);
    }

    public function down(): void
    {
    }
};
