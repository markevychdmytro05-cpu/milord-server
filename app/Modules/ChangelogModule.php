<?php

namespace App\Modules;

use App\Interfaces\ModuleInterface;
use App\Models\Page;

class ChangelogModule implements ModuleInterface
{
    public function draw(array $settings): string
    {
        $page = Page::published()->where('slug', 'shcho-novoho')->first();
        $entries = [];

        if ($page && preg_match_all('~<h2[^>]*>(.*?)</h2>\s*<ul>(.*?)</ul>~su', $page->content, $matches, PREG_SET_ORDER)) {
            foreach (array_slice($matches, 0, max(1, (int) ($settings['limit'] ?? 2))) as $match) {
                preg_match_all('~<li>(.*?)</li>~su', $match[2], $items);
                $entries[] = ['date' => trim(strip_tags($match[1])), 'items' => $items[1]];
            }
        }

        if ($entries === []) {
            return '';
        }

        return view('modules.changelog', ['settings' => $settings, 'entries' => $entries, 'url' => $page->url()])->render();
    }
}
