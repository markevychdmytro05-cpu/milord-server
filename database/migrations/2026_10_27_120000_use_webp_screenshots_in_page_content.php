<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pages')->where('content', 'like', '%/images/app/%.png%')->orderBy('id')->get(['id', 'content'])
            ->each(function (object $page): void {
                $content = preg_replace_callback(
                    '~src="(/images/app/[A-Za-z0-9_-]+)\.png"~',
                    fn (array $match): string => is_file(public_path(ltrim($match[1], '/').'.webp')) ? 'src="'.$match[1].'.webp"' : $match[0],
                    $page->content,
                );

                if ($content !== $page->content) {
                    DB::table('pages')->where('id', $page->id)->update(['content' => $content]);
                }
            });
    }

    public function down(): void
    {
    }
};
