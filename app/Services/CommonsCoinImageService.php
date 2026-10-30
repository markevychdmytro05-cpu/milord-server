<?php

namespace App\Services;

use App\Models\Coin;
use App\Support\CoinTitleStems;
use Illuminate\Support\Facades\Http;

class CommonsCoinImageService
{
    private const API = 'https://commons.wikimedia.org/w/api.php';

    private const USER_AGENT = 'NumisCoinCatalog/1.0 (https://t.me/NumisNBU)';

    private const FREE_LICENSE = '~^(cc0|public domain|pd\b|pd-|cc[- ]by(?![- ]?nc)(?![- ]?nd)|attribution)~i';

    private const ALIASES = [
        'Великодня радість' => ['queries' => ['Pysanka-26 coin reverse'], 'contains' => 'pysanka-26'],
        'проголошення незалежності Сполучених Штатів' => ['queries' => ['250 років США монета'], 'contains' => '250 років сша'],
        'Мешканці морських глибин' => ['queries' => ['Inhabitants of the deep sea 2026'], 'contains' => 'deep sea 2026'],
        'Ракета-дрон' => ['queries' => ['Монета Паляниця аверс у блістері'], 'contains' => 'паляниця аверс'],
        'Зубр' => ['queries' => ['Chornobyl, Renaissance, Wisent А'], 'contains' => 'wisent'],
        'імені Тараса Шевченка' => ['queries' => ['Shevchenko Prize (coin) obverse'], 'contains' => 'shevchenko prize'],
        'Червона рута' => ['queries' => ['Червона рута A'], 'contains' => 'червона рута a'],
    ];

    public function find(Coin $coin): ?array
    {
        $stems = CoinTitleStems::of($coin->shortTitle());
        $queries = $this->queries($coin);

        foreach (self::ALIASES as $needle => $alias) {
            if (str_contains($coin->shortTitle(), $needle)) {
                [$stems, $queries] = [[$alias['contains']], $alias['queries']];
            }
        }

        $best = null;
        $bestScore = -1;

        foreach ($queries as $query) {
            foreach ($this->search($query) as $file) {
                $score = $this->score($file, $stems, (int) $coin->release_year);

                if ($score > $bestScore) {
                    $best = $file;
                    $bestScore = $score;
                }
            }

            if ($bestScore >= 0) {
                break;
            }
        }

        return $best === null ? null : [
            'url' => $best['url'],
            'page' => $best['page'],
            'credit' => $this->credit($best),
            'title' => $best['title'],
        ];
    }

    private function queries(Coin $coin): array
    {
        $title = trim(preg_replace('~\s*\(.*\)~u', '', $coin->shortTitle()));
        $parts = preg_split('~(?<=[.!?])\s+~u', $title, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter([$title, end($parts) ?: null, str_replace(['“', '”', '"', '«', '»'], '', $title)])));
    }

    private function search(string $query): array
    {
        usleep(1_200_000);

        $response = Http::withUserAgent(self::USER_AGENT)->timeout(30)->retry(2, 3000, throw: false)->get(self::API, [
            'action' => 'query', 'format' => 'json', 'generator' => 'search', 'gsrsearch' => $query,
            'gsrnamespace' => 6, 'gsrlimit' => 12, 'prop' => 'imageinfo',
            'iiprop' => 'url|size|mime|extmetadata|timestamp', 'iiurlwidth' => 1000,
            'iiextmetadatafilter' => 'LicenseShortName|Artist',
        ]);

        if (! $response->ok()) {
            return [];
        }

        $files = [];

        foreach ($response->json('query.pages', []) as $page) {
            $info = $page['imageinfo'][0] ?? null;

            if (! $info) {
                continue;
            }

            $files[] = [
                'title' => (string) ($page['title'] ?? ''),
                'url' => (string) (($info['width'] ?? 0) > 1000 ? ($info['thumburl'] ?? $info['url']) : $info['url']),
                'page' => (string) ($info['descriptionurl'] ?? ''),
                'license' => trim(strip_tags((string) ($info['extmetadata']['LicenseShortName']['value'] ?? ''))),
                'artist' => trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags((string) ($info['extmetadata']['Artist']['value'] ?? '')), ENT_QUOTES | ENT_HTML5))),
                'width' => (int) ($info['width'] ?? 0),
                'uploaded' => (int) substr((string) ($info['timestamp'] ?? ''), 0, 4),
                'mime' => (string) ($info['mime'] ?? ''),
            ];
        }

        return $files;
    }

    private function score(array $file, array $stems, int $year): int
    {
        $title = CoinTitleStems::normalize(preg_replace('~^File:|\.[a-z]+$~i', '', $file['title']));

        $otherYear = preg_match_all('~\b(19|20)\d{2}\b~', $title, $years) && array_filter($years[0], fn (string $found): bool => $year !== 0 && (int) $found !== $year) !== [];

        if ($otherYear || ($year !== 0 && $file['uploaded'] < $year)
            || ! in_array($file['mime'], ['image/jpeg', 'image/png'], true)
            || ! preg_match(self::FREE_LICENSE, $file['license'])
            || ! CoinTitleStems::allIn($stems, $title)) {
            return -1;
        }

        return 1
            + (str_contains($title, 'реверс') || preg_match('~\sr$~', $title) ? 4 : 0)
            + (preg_match('~блістер|пакуван|упаков~u', $title) ? 0 : 2)
            + (str_contains($title, 'аверс') ? -1 : 0);
    }

    private function credit(array $file): string
    {
        $author = mb_substr($file['artist'], 0, 80);

        return 'Wikimedia Commons'.($author !== '' ? ', '.$author : '').' ('.$file['license'].')';
    }
}
