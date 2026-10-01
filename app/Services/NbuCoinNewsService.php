<?php

namespace App\Services;

use App\Models\Coin;
use App\Support\CoinTitleStems;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Дані про монети на bank.gov.ua: каталог обігових монет і новини про випуски (фото, характеристики, перше речення повідомлення).
 * Сайт НБУ відкритий для краулерів (robots.txt без обмежень); запити йдуть невеликими партіями з паузою й кешуються.
 */
class NbuCoinNewsService
{
    public const SITEMAP_URL = 'https://bank.gov.ua/sitemap/sitemap.xml';

    /** Запасна сторінка, якщо в sitemap не знайдено актуальної: на ній перелік усіх обігових монет по 10 гривень. */
    public const CIRCULATION_URL = 'https://bank.gov.ua/ua/uah/obig-coin/10-grn-2026-kherson';

    /** Новини, які точно не про монети: їх не завантажуємо. */
    private const SKIP_SLUGS = '~monetarn|oblikov|stavk|presbrifing|makroekonom|stabilnist|valyut|kurs-|licenz|likvid~';

    /**
     * Адреси останніх новин (sitemap відсортований від старих до нових).
     *
     * @return list<string>
     */
    public function candidateUrls(int $recent = 900): array
    {
        $news = array_filter($this->sitemapUrls(), fn (string $url): bool => str_contains($url, '/ua/news/all/'));

        return array_values(array_filter(
            array_slice($news, -$recent),
            fn (string $url): bool => ! preg_match(self::SKIP_SLUGS, $url),
        ));
    }

    /** Актуальна сторінка каталогу обігових монет по 10 гривень (остання за роком у sitemap). */
    public function circulationUrl(): string
    {
        $pages = array_filter($this->sitemapUrls(), fn (string $url): bool => (bool) preg_match('~/ua/uah/obig-coin/10-grn-\d{4}-~', $url));

        return $pages === [] ? self::CIRCULATION_URL : end($pages);
    }

    /**
     * Каталог обігових монет НБУ: назва, дата введення в обіг, характеристики й зображення (останнє – реверс із унікальним дизайном).
     *
     * @return list<array{url: string, title: string, description: string, image: ?string, text: string, facts: array<string, string>}>
     */
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

    /**
     * Завантажує новини партіями (з кешем на 30 днів); недоступні пропускає.
     *
     * @param  list<string>  $urls
     * @param  (callable(int): void)|null  $onProgress  викликається після кожної обробленої адреси
     * @return list<array{url: string, title: string, description: string, image: ?string, text: string}>
     */
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

    /**
     * Запис, у якому згадано всі відмінні слова назви монети (за основами слів); перемагає той, де їх більше в заголовку.
     * Записи каталогу (із полем facts) мають пріоритет над новинами.
     *
     * @param  list<array{url: string, title: string, description: string, image: ?string, text: string}>  $articles
     * @return array{url: string, title: string, description: string, image: ?string, text: string}|null
     */
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

            // Каталог обігових монет точніший за новину: там зображення самої монети й офіційні характеристики.
            $score += isset($article['facts']) ? 100 : 0;

            if ($score > $bestScore) {
                $best = $article;
                $bestScore = $score;
            }
        }

        return $best;
    }

    /**
     * Перше речення повідомлення, не довше $limit символів.
     *
     * @param  array{url: string, title: string, description: string, image: ?string, text: string}  $article
     */
    public function lead(array $article, int $limit = 320): string
    {
        $text = $article['description'] ?: $article['text'];
        $text = trim(preg_replace('~\s*(\.{3}|…)$~u', '', $text));
        $sentence = preg_match('~^(.+?[.!?])(\s|$)~u', $text, $m) ? $m[1] : $text;

        return mb_strlen($sentence) > $limit ? mb_substr($sentence, 0, $limit - 1).'…' : $sentence;
    }

    /**
     * Усі адреси sitemap НБУ (кеш на добу: файл має ~15 МБ); недоступний sitemap дає порожній список.
     *
     * @return list<string>
     */
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

    /**
     * @return array{url: string, title: string, description: string, image: ?string, text: string}
     */
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
