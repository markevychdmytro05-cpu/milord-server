<?php

namespace App\Http\Controllers;

use App\Models\Coin;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

/**
 * Файли для мовних моделей за специфікацією llmstxt.org: короткий індекс і повний текст сайту.
 */
class LlmsController extends Controller
{
    public function index(): Response
    {
        return $this->respond($this->render(full: false));
    }

    public function full(): Response
    {
        return $this->respond($this->render(full: true));
    }

    private function respond(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function render(bool $full): string
    {
        $site = SiteSetting::current();
        $home = Page::published()->where('slug', Page::ROOT_SLUG)->first();
        $pages = Page::published()->indexable()->where('slug', '!=', Page::ROOT_SLUG)->orderBy('id')->get();

        $lines = ['# '.$site->header_logo, ''];
        if ($summary = $home?->seoDescription()) {
            $lines[] = '> '.$summary;
            $lines[] = '';
        }
        $lines[] = __('site.llms.language', ['url' => route('home')]);
        $lines[] = '';

        $sections = [
            __('site.llms.features') => $pages->where('type', Page::TYPE_SERVICE),
            __('site.llms.about') => $pages->where('type', Page::TYPE_PAGE)->whereNull('category')->whereNotIn('slug', Page::LEGAL_SLUGS),
            __('site.llms.legal') => $pages->whereIn('slug', Page::LEGAL_SLUGS),
        ];
        foreach ($pages->whereNotNull('category')->groupBy('category') as $category => $group) {
            $sections[__('site.llms.knowledge_base', ['category' => $category])] = $group;
        }

        $coins = Coin::published()->indexable()->orderBy('id')->get();
        if ($coins->isNotEmpty()) {
            $lines[] = '## '.__('site.llms.coins');
            $lines[] = '';
            foreach ($coins as $coin) {
                $lines[] = '- ['.$coin->title.']('.$coin->url().')'.($coin->seoDescription() ? ': '.$coin->seoDescription() : '');
            }
            $lines[] = '';
        }

        foreach (array_filter($sections, fn (Collection $group): bool => $group->isNotEmpty()) as $heading => $group) {
            $lines[] = '## '.$heading;
            $lines[] = '';
            foreach ($group as $page) {
                $lines[] = $full ? $this->renderFull($page) : $this->renderLink($page);
            }
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    private function renderLink(Page $page): string
    {
        return '- ['.$page->title.']('.$page->url().')'.($page->seoDescription() ? ': '.$page->seoDescription() : '');
    }

    private function renderFull(Page $page): string
    {
        return "### {$page->title}\n\nURL: {$page->url()}\n\n".trim($this->htmlToMarkdown((string) $page->content))."\n";
    }

    private function htmlToMarkdown(string $html): string
    {
        $text = preg_replace('~<a\s[^>]*href="([^"]*)"[^>]*>(.*?)</a>~isu', '[$2]($1)', $html);
        $text = preg_replace_callback('~\((/[^)]*)\)~', fn (array $match): string => '('.url($match[1]).')', $text);
        $text = preg_replace('~<h[1-6][^>]*>(.*?)</h[1-6]>~isu', "\n\n#### $1\n\n", $text);
        $text = preg_replace('~</t[dh]>\s*<t[dh][^>]*>~iu', ' | ', $text);
        $text = preg_replace('~<li[^>]*>~iu', "\n- ", $text);
        $text = preg_replace('~</(p|tr|ul|ol|table|figure)>|<br\s*/?>~iu', "\n\n", $text);

        return trim(preg_replace("~\n{3,}~", "\n\n", html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5)));
    }
}
