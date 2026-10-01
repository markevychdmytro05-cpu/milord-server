<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Місяць введення в обіг за планом НБУ (точна дата продажу відома пізніше). Наявні монети
 * отримують місяць із тексту опису, який створила імпортна команда: «... за планом – вересень 2026 року».
 */
return new class extends Migration
{
    private const MONTHS = [
        'січень' => 1, 'лютий' => 2, 'березень' => 3, 'квітень' => 4, 'травень' => 5, 'червень' => 6,
        'липень' => 7, 'серпень' => 8, 'вересень' => 9, 'жовтень' => 10, 'листопад' => 11, 'грудень' => 12,
    ];

    public function up(): void
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->unsignedTinyInteger('release_month')->nullable()->after('release_year');
            $table->index(['release_year', 'release_month']);
        });

        foreach (DB::table('coins')->whereNull('release_month')->get(['id', 'excerpt']) as $coin) {
            if (preg_match('/за планом – (\p{L}+) \d{4} року/u', (string) $coin->excerpt, $matches) && isset(self::MONTHS[$matches[1]])) {
                DB::table('coins')->where('id', $coin->id)->update(['release_month' => self::MONTHS[$matches[1]]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->dropIndex(['release_year', 'release_month']);
            $table->dropColumn('release_month');
        });
    }
};
