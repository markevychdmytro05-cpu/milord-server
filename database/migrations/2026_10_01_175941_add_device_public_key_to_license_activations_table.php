<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_activations', function (Blueprint $table) {
            $table->string('device_public_key', 44)->nullable()->after('device_id');
        });
    }

    public function down(): void
    {
        Schema::table('license_activations', function (Blueprint $table) {
            $table->dropColumn('device_public_key');
        });
    }
};
