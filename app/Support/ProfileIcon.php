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
        $image = $source ? @imagecreatefromstring(file_get_contents($source)) : false;
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
}
