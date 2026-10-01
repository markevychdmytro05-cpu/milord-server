<?php

namespace App\Support;

/**
 * Набір ліній-іконок (viewBox 24×24) для модулів.
 */
final class ModuleIcons
{
    private const ICONS = [
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="14" rx="1"/><path d="M8 21h8M12 18v3"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'lock' => '<rect x="4" y="10" width="16" height="11" rx="1"/><path d="M8 10V7a4 4 0 018 0v3"/>',
        'list' => '<path d="M5 4h14v16H5z"/><path d="M9 9h6M9 13h6M9 17h3"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
    ];

    public static function svg(?string $name): string
    {
        return self::ICONS[$name] ?? self::ICONS['list'];
    }
}
