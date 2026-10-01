<?php

namespace App\Support;

/**
 * Зіставлення назви монети з текстом джерела за основами слів: «Кожум’яка» і «Кожум'яка» вважаються однаковими.
 */
final class CoinTitleStems
{
    /** Довжина основи слова: достатньо, щоб відрізнити «Чернівецька» від «Чернігівська». */
    private const LENGTH = 6;

    /** Слова, які не відрізняють одну монету від іншої. */
    private const STOP = ['років', 'річчя', 'україн', 'украї'];

    /** Нижній регістр і єдиний апостроф: НБУ пише і ’, і ' в одних і тих самих назвах. */
    public static function normalize(string $text): string
    {
        return str_replace(['’', 'ʼ', '`'], "'", mb_strtolower($text));
    }

    /**
     * @return list<string>
     */
    public static function of(string $title): array
    {
        $words = preg_split('~[^\p{L}\']+~u', self::normalize($title), -1, PREG_SPLIT_NO_EMPTY);
        $stems = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 4) {
                continue;
            }

            $stem = mb_substr($word, 0, self::LENGTH);

            if (! in_array($stem, self::STOP, true)) {
                $stems[] = $stem;
            }
        }

        return array_values(array_unique($stems));
    }

    /**
     * Чи містить текст усі основи слів.
     *
     * @param  list<string>  $stems
     */
    public static function allIn(array $stems, string $text): bool
    {
        $text = self::normalize($text);

        return $stems !== [] && array_filter($stems, fn (string $stem): bool => ! str_contains($text, $stem)) === [];
    }
}
