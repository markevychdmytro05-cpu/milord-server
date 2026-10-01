<?php

namespace App\Support;

/**
 * Зменшення зображень монет: довша сторона до $max пікселів, JPEG на білому тлі (прозорість PNG заливається).
 */
final class CoinImage
{
    /** @return string|null JPEG-байти або null, якщо GD недоступний чи файл не є зображенням */
    public static function optimize(string $binary, int $max = 900, int $quality = 84): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return null;
        }

        [$width, $height] = [imagesx($source), imagesy($source)];
        $scale = min(1, $max / max($width, $height));
        [$newWidth, $newHeight] = [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, $quality);

        return (string) ob_get_clean();
    }
}
