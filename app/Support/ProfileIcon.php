<?php
namespace App\Support;

use App\Models\User;

class ProfileIcon
{
    public static function source(?User $user): ?string
    {
        $avatar = $user ? RuntimeMedia::path(findAvatar($user->id)) : null;
        foreach ([$avatar, SiteBranding::path('favicon'), SiteBranding::path('avatar')] as $path) {
            if (!$path || !is_file($path) || is_link($path) || filesize($path) > 8 * 1024 * 1024) continue;
            $info = @getimagesize($path);
            if ($info && in_array($info['mime'], ['image/png','image/jpeg','image/webp','image/gif'], true)
                && $info[0] > 0 && $info[1] > 0 && $info[0] * $info[1] <= 16000000) return $path;
        }
        return null;
    }

    public static function url(User $user, int $size): string
    {
        $source = self::source($user);
        $version = $source ? substr(hash_file('sha256', $source), 0, 16) : 'default';
        return route('profileIcon', ['user' => $user->id, 'size' => $size]).'?v='.$version;
    }

    public static function png(?string $source, int $size): string
    {
        if (!in_array($size, [32, 180], true)) throw new \InvalidArgumentException('Invalid icon size.');
        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        $image = $source ? self::raster($source) : false;
        try {
            if ($image) {
                $width = imagesx($image); $height = imagesy($image); $edge = min($width, $height);
                imagecopyresampled($canvas, $image, 0, 0, (int)(($width-$edge)/2), (int)(($height-$edge)/2), $size, $size, $edge, $edge);
            } else {
                // A raster fallback also works when the estate only has an SVG logo.
                imagefill($canvas, 0, 0, imagecolorallocate($canvas, 51, 0, 59));
                $scale = max(1, (int)($size / 40));
                $glyph = imagecreatetruecolor(9, 15);
                imagefill($glyph, 0, 0, imagecolorallocate($glyph, 51, 0, 59));
                imagestring($glyph, 5, 0, 0, 'B', imagecolorallocate($glyph, 255, 255, 255));
                imagecopyresized($canvas, $glyph, (int)(($size-9*$scale)/2), (int)(($size-15*$scale)/2), 0, 0, 9*$scale, 15*$scale, 9, 15);
                imagedestroy($glyph);
            }
            ob_start();
            try { imagepng($canvas); return ob_get_contents(); }
            finally { ob_end_clean(); }
        } finally {
            if ($image) imagedestroy($image);
            imagedestroy($canvas);
        }
    }

    public static function raster(string $source)
    {
        $image = @imagecreatefromstring(file_get_contents($source));
        if ($image) {
            $orientation = self::orientation($source);
            if (in_array($orientation, [2, 5, 7], true)) imageflip($image, IMG_FLIP_HORIZONTAL);
            if ($orientation === 4) imageflip($image, IMG_FLIP_VERTICAL);
            $angle = [3 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle) {
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated) { imagedestroy($image); $image = $rotated; }
            }
        }
        return $image;
    }

    /** Read the JPEG APP1 orientation without requiring the optional PHP EXIF extension. */
    private static function orientation(string $source): int
    {
        $bytes = file_get_contents($source, false, null, 0, 262144);
        if (substr($bytes, 0, 2) !== "\xFF\xD8") return 1;
        $length = strlen($bytes);
        for ($offset = 2; $offset + 4 <= $length;) {
            if (ord($bytes[$offset++]) !== 255) break;
            while ($offset < $length && ord($bytes[$offset]) === 255) $offset++;
            if ($offset + 3 > $length) break;
            $marker = ord($bytes[$offset++]);
            if ($marker === 0xDA || $marker === 0xD9) break;
            $size = unpack('n', substr($bytes, $offset, 2))[1];
            if ($size < 2 || $offset + $size > $length) break;
            $segment = substr($bytes, $offset + 2, $size - 2);
            $offset += $size;
            if ($marker !== 0xE1 || substr($segment, 0, 6) !== "Exif\0\0") continue;
            $tiff = substr($segment, 6);
            $little = substr($tiff, 0, 2) === 'II';
            if (!$little && substr($tiff, 0, 2) !== 'MM') continue;
            $read = function (int $at, bool $long = false) use ($tiff, $little): ?int {
                $width = $long ? 4 : 2;
                if ($at < 0 || $at + $width > strlen($tiff)) return null;
                return unpack($long ? ($little ? 'V' : 'N') : ($little ? 'v' : 'n'), substr($tiff, $at, $width))[1];
            };
            if ($read(2) !== 42) continue;
            $ifd = $read(4, true);
            if ($ifd === null || $ifd < 8) continue;
            $count = $read($ifd);
            if ($count === null) continue;
            for ($i = 0; $i < min($count, 128); $i++) {
                $entry = $ifd + 2 + 12 * $i;
                if ($read($entry) === 0x112 && $read($entry + 2) === 3 && $read($entry + 4, true) === 1) {
                    $value = $read($entry + 8);
                    return in_array($value, range(1, 8), true) ? $value : 1;
                }
            }
        }
        return 1;
    }
}
