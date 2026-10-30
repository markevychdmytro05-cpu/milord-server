<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $site = DB::table('site_settings');

        (clone $site)->where('contact_email', 'help@numis.example')->update(['contact_email' => 'help@numis.in.ua']);
        (clone $site)->where('contact_email2', 'partners@numis.example')->update(['contact_email2' => null, 'contact_email2_label' => null]);
        (clone $site)->where('contact_phone', '+380 44 000 00 00')->update(['contact_phone' => null]);
    }

    public function down(): void
    {
    }
};
