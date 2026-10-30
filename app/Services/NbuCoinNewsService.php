<?php

namespace App\Services;

use App\Models\Coin;
use App\Support\CoinTitleStems;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NbuCoinNewsService
{
    public const SITEMAP_URL = 'https://bank.gov.ua/sitemap/sitemap.xml';

    public const CIRCULATION_URL = 'https://bank.gov.ua/ua/uah/obig-coin/10-grn-2026-kherson';

    private const SKIP_SLUGS = '~monetarn|oblikov|stavk|presbrifing|makroekonom|stabilnist|valyut|kurs-|licenz|likvid~';

    public function candidateUrls(int $recent = 900): array
    {
        $news = array_filter($this->sitemapUrls(), fn (string $url): bool => str_contains($url, '/ua/news/all/'));

        return array_values(array_filter(
            array_slice($news, -$recent),
            fn (string $url): bool => ! preg_match(self::SKIP_SLUGS, $url),
        ));
    }

    public function circulationUrl(): string
    {
        $pages = array_filter($this->sitemapUrls(), fn (string $url): bool => (bool) preg_match('~/ua/uah/obig-coin/10-grn-\d{4}-~', $url));

        return $pages === [] ? self::CIRCULATION_URL : end($pages);
    }

    public function circulationCatalog(): array
    {
        $url = $this->circulationUrl();
        $response = Http::timeout(60)->get($url);

        if (! $response->ok()) {
            return [];
        }

        $entries = [];

        foreach (array_slice(preg_split('~<div class="hide-show-currency"~', $response->body()), 1) as $block) {
            if (! preg_match('~<h3>(.*?)</h3>~su', $block, $title)) {
                continue;
            }

            preg_match_all('~src="([^"]*admin_uploads/coin/[^"]+)"~', $block, $images);

            $facts = [];
            if (preg_match('~<div class="box">\s*<p>(.*?)</p>~su', $block, $paragraph)) {
                foreach (preg_split('~<br\s*/?>~i', $paragraph[1]) as $line) {
                    if (preg_match('~^\s*(.+?)\s+—\s+(.+?)\s*$~su', trim(html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5)), $fact)) {
                        $facts[trim($fact[1])] = trim($fact[2]);
                    }
                }
            }

            $entries[] = [
                'url' => $url,
                'title' => trim(html_entity_decode(strip_tags($title[1]), ENT_QUOTES | ENT_HTML5)),
                'description' => '',
                'image' => $images[1] === [] ? null : 'https://bank.gov.ua'.end($images[1]),
                'text' => implode('. ', array_map(fn (string $key, string $value): string => $key.' — '.$value, array_keys($facts), $facts)),
                'facts' => $facts,
            ];
        }

        return $entries;
    }

    public function articles(array $urls, ?callable $onProgress = null): array
    {
        $articles = [];

        foreach (array_chunk($urls, 8) as $chunk) {
            $missing = array_filter($chunk, fn (string $url): bool => ! Cache::has($this->cacheKey($url)));

            if ($missing !== []) {
                $responses = Http::pool(fn ($pool) => array_map(fn (string $url) => $pool->as($url)->timeout(30)->get($url), array_values($missing)));

                foreach ($missing as $url) {
                    $response = $responses[$url] ?? null;
                    Cache::put($this->cacheKey($url), $response instanceof Response && $response->ok() ? $this->parse($url, $response) : false, now()->addDays(30));
                }

                usleep(300_000);
            }

            foreach ($chunk as $url) {
                if ($article = Cache::get($this->cacheKey($url))) {
                    $articles[] = $article;
                }

                $onProgress && $onProgress(1);
            }
        }

        return $articles;
    }

    public function match(Coin $coin, array $articles): ?array
    {
        $stems = CoinTitleStems::of($coin->shortTitle());

        if ($stems === []) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($articles as $article) {
            $title = CoinTitleStems::normalize($article['title']);
            $description = CoinTitleStems::normalize($article['description']);
            $full = $title.' '.$description.' '.CoinTitleStems::normalize($article['text']);

            if (! $article['image'] || ! CoinTitleStems::allIn($stems, $full)) {
                continue;
            }

            $score = 0;
            foreach ($stems as $stem) {
                $score += 2 * (int) str_contains($title, $stem) + (int) str_contains($description, $stem);
            }

            $score += isset($article['facts']) ? 100 : 0;

            if ($score > $bestScore) {
                $best = $article;
                $bestScore = $score;
            }
        }

        return $best;
    }

    public function lead(array $article, int $limit = 320): string
    {
        $text = $article['description'] ?: $article['text'];
        $text = trim(preg_replace('~\s*(\.{3}|…)$~u', '', $text));
        $sentence = preg_match('~^(.+?[.!?])(\s|$)~u', $text, $m) ? $m[1] : $text;

        return mb_strlen($sentence) > $limit ? mb_substr($sentence, 0, $limit - 1).'…' : $sentence;
    }

    private function sitemapUrls(): array
    {
        $cached = Cache::get('nbu-sitemap-urls');

        if (is_array($cached)) {
            return $cached;
        }

        $response = Http::timeout(90)->get(self::SITEMAP_URL);

        if (! $response->ok()) {
            return [];
        }

        preg_match_all('~<loc>(https://bank\.gov\.ua/ua/[^<]+)</loc>~', $response->body(), $matches);
        Cache::put('nbu-sitemap-urls', $matches[1], now()->addDay());

        return $matches[1];
    }

    private function parse(string $url, Response $response): array
    {
        $html = $response->body();
        $meta = fn (string $property): ?string => preg_match('~<meta property="'.$property.'" content="([^"]*)"~u', $html, $m)
            ? trim(html_entity_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5), ENT_QUOTES | ENT_HTML5))
            : null;
        $body = preg_replace('~<script.*?</script>|<style.*?</style>~isu', '', substr($html, (int) strpos($html, '<h1')));
        $text = trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $body)), ENT_QUOTES | ENT_HTML5)));

        return [
            'url' => $url,
            'title' => (string) $meta('og:title'),
            'description' => (string) $meta('og:description'),
            'image' => $meta('og:image'),
            'text' => mb_substr($text, 0, 4000),
        ];
    }

    private function cacheKey(string $url): string
    {
        return 'nbu-news:'.sha1($url);
    }
}
