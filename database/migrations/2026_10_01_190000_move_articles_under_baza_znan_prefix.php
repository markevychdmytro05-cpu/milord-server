<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $slugs = DB::table('pages')->where('type', 'page')->whereNotNull('category')->where('category', '!=', '')
            ->where('slug', '!=', '/')->pluck('slug')->all();

        if ($slugs === []) {
            return;
        }

        usort($slugs, fn ($a, $b) => strlen($b) <=> strlen($a));
        $pattern = '~(?<=["\'(])/('.implode('|', array_map(fn ($slug) => preg_quote($slug, '~'), $slugs)).')(?=["\')#?\s<])~';

        foreach (['pages' => ['content', 'excerpt'], 'modules' => null, 'site_settings' => null, 'menu_items' => ['url']] as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns ??= array_column(array_filter(Schema::getColumns($table), fn ($c) => in_array($c['type_name'], ['text', 'varchar', 'json', 'longtext'], true)), 'name');

            foreach (DB::table($table)->get() as $row) {
                $updates = [];
                foreach ($columns as $column) {
                    $value = $row->{$column} ?? null;
                    if (! is_string($value) || $value === '') {
                        continue;
                    }
                    $new = preg_replace($pattern, '/baza-znan/$1', $value);
                    if ($new !== null && $new !== $value) {
                        $updates[$column] = $new;
                    }
                }
                if ($updates) {
                    DB::table($table)->where('id', $row->id)->update($updates);
                }
            }
        }
    }

    public function down(): void
    {
    }
};
