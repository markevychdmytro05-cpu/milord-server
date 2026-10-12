<?php

namespace App\Support;

use App\Models\License;
use Illuminate\Http\Request;

/**
 * Доступ до спільноти за ключем ліцензії: ключ перевіряється один раз, далі в сесії лежить id ліцензії.
 */
class CommunityAccess
{
    public const SESSION_KEY = 'community_license_id';

    public static function attempt(Request $request, string $key): ?License
    {
        $license = License::where('key', License::normalizeKey($key))->first();

        if (! $license || ! self::isUsable($license)) {
            return null;
        }

        $request->session()->put(self::SESSION_KEY, $license->id);

        return $license;
    }

    /** Ліцензія поточного відвідувача або null, якщо ключ не введено чи він уже недійсний. */
    public static function license(Request $request): ?License
    {
        $id = $request->session()->get(self::SESSION_KEY);
        $license = $id ? License::find($id) : null;

        if ($license && self::isUsable($license)) {
            return $license;
        }

        $request->session()->forget(self::SESSION_KEY);

        return null;
    }

    public static function leave(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    private static function isUsable(License $license): bool
    {
        return $license->status === License::STATUS_ACTIVE
            && ($license->expires_at === null || $license->expires_at->isFuture());
    }
}
