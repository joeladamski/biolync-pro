<?php
namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class SiteBranding
{
    private const TYPES = ['image/png'=>'png', 'image/jpeg'=>'jpg', 'image/webp'=>'webp', 'image/gif'=>'gif'];

    public static function directory(): string
    {
        return rtrim(config('estate-media.branding_path'), '/');
    }

    private static function kind(string $kind): void
    {
        if (!in_array($kind, ['avatar', 'favicon', 'share'], true)) throw new \InvalidArgumentException('Unknown branding kind.');
    }

    public static function path(string $kind, bool $legacy = true): ?string
    {
        self::kind($kind);
        foreach ($legacy ? [self::directory(), base_path('assets/linkstack/images')] : [self::directory()] as $directory) {
            $files = array_filter(glob($directory.'/'.$kind.'*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE) ?: [],
                fn ($file) => is_file($file) && !is_link($file) && preg_match('/^'.$kind.'(?:_[a-zA-Z0-9]+)?\.(png|jpe?g|webp|gif)$/', basename($file)));
            usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            if ($files) return $files[0];
        }
        return null;
    }

    public static function exists(string $kind): bool
    {
        return self::path($kind) !== null;
    }

    public static function url(string $kind): string
    {
        $path = self::path($kind);
        if (!$path) return asset('assets/linkstack/images/logo.svg');
        return url('/site-branding/'.$kind).'?v='.substr(hash_file('sha256', $path), 0, 16);
    }

    public static function save(string $kind, UploadedFile $file): void
    {
        self::copy($kind, $file->getRealPath());
    }

    public static function copy(string $kind, string $source): void
    {
        self::kind($kind);
        $image = @getimagesize($source);
        $extension = self::TYPES[$image['mime'] ?? ''] ?? null;
        if (!$extension || filesize($source) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([$kind === 'avatar' ? 'image' : 'icon' => 'Use a PNG, JPG, WebP or GIF image up to 2 MB.']);
        }
        $directory = self::directory();
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new \RuntimeException('Cannot create persistent branding directory.');
        $lock = fopen($directory.'/branding.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new \RuntimeException('Cannot lock persistent branding.');
        $temp = null;
        try {
            $temp = tempnam($directory, 'branding-');
            if (!$temp || !copy($source, $temp)) throw new \RuntimeException('Cannot save persistent branding.');
            chmod($temp, 0640);
            $target = $directory.'/'.$kind.'.'.$extension;
            if (!rename($temp, $target)) throw new \RuntimeException('Cannot publish persistent branding.');
            foreach (glob($directory.'/'.$kind.'.*') ?: [] as $old) {
                if ($old !== $target && is_file($old)) unlink($old);
            }
        } finally {
            if ($temp && is_file($temp)) unlink($temp);
            flock($lock, LOCK_UN); fclose($lock);
        }
    }

    public static function remove(string $kind): void
    {
        self::kind($kind);
        // Remove legacy copies too, so fallback cannot resurrect deleted branding.
        foreach ([self::directory(), base_path('assets/linkstack/images')] as $directory) {
            foreach (glob($directory.'/'.$kind.'*') ?: [] as $file) {
                if (is_file($file) && preg_match('/^'.$kind.'(?:_[a-zA-Z0-9]+)?\.(png|jpe?g|webp|gif)$/', basename($file))) unlink($file);
            }
        }
    }
}
