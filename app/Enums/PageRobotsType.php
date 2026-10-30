<?php

namespace App\Enums;

enum PageRobotsType: string
{
    case IndexFollow = 'index, follow';
    case IndexNofollow = 'index, nofollow';
    case NoindexFollow = 'noindex, follow';
    case NoindexNofollow = 'noindex, nofollow';

    public function isIndexable(): bool
    {
        return $this === self::IndexFollow || $this === self::IndexNofollow;
    }

    public static function asSelectArray(): array
    {
        return array_column(self::cases(), 'value', 'value');
    }
}
