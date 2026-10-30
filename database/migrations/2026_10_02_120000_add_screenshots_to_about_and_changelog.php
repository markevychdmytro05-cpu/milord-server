<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->insertAfter('pro-nas', '~<h2>Як це працює</h2>\s*<ol>.*?</ol>~su',
            $this->figure('tasks', 1440, 900, 'Головне вікно Numis із запланованими завданнями', 'Завдання в Numis: монета, час старту за Києвом і профілі.'));

        $this->insertAfter('shcho-novoho', '~<h2>26 вересня 2026</h2>\s*<ul>.*?</ul>~su',
            $this->figure('new-task', 1440, 900, 'Планувальник завдань у Numis', 'Планувальник завдань: посилання на монету, час старту й профілі AdsPower.'));
    }

    public function down(): void
    {
    }

    private function insertAfter(string $slug, string $pattern, string $html): void
    {
        $page = Page::where('slug', $slug)->first();

        if (! $page || str_contains($page->content, $html)) {
            return;
        }

        $page->update(['content' => preg_replace($pattern, '$0'."\n".addcslashes($html, '\\$'), $page->content, 1)]);
    }

    private function figure(string $name, int $width, int $height, string $alt, string $caption): string
    {
        return '<figure><img src="/images/app/numis-'.$name.'.png" alt="'.e($alt).'" width="'.$width.'" height="'.$height.'" loading="lazy"><figcaption>'.e($caption).'</figcaption></figure>';
    }
};
