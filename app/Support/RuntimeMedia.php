<?php
namespace App\Support;

class RuntimeMedia
{
    public const DIRECTORIES = ['assets/img/background-img', 'assets/img', 'assets/profile-media', 'assets/profile-gallery'];

    public static function root(): string
    {
        return rtrim(config('estate-media.profile_path'), '/');
    }

    private static function relative(string $path): ?string
    {
        $path = rtrim($path, '/');
        foreach (self::DIRECTORIES as $directory) {
            if ($path === $directory || str_starts_with($path, $directory.'/')) {
                if (str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '\\')) throw new \InvalidArgumentException('Invalid media path.');
                return substr($path, strlen('assets/'));
            }
        }
        return null;
    }

    public static function path($path = '', string $unused = ''): string
    {
        $relative = self::relative((string)$path);
        if ($relative === null) return base_path($path);
        $target = self::root().'/'.$relative;
        if (in_array(rtrim($path, '/'), self::DIRECTORIES, true)) {
            if (!is_dir($target) && !mkdir($target, 0750, true) && !is_dir($target)) throw new \RuntimeException('Cannot create persistent profile media directory.');
            return $target;
        }
        if (is_file($target) && !is_link($target)) return $target;
        $legacy = base_path($path);
        return is_file($legacy) && !is_link($legacy) ? $legacy : $target;
    }

    public static function files(string $directory): array
    {
        $external = self::path($directory);
        $names = [];
        foreach ([$external, base_path($directory)] as $source) {
            foreach (glob(rtrim($source, '/').'/*') ?: [] as $file) {
                if (is_file($file) && !is_link($file)) $names[basename($file)] = true;
            }
        }
        return array_keys($names);
    }

    public static function allowed(string $path): bool
    {
        return preg_match('#^assets/(?:img/(?:background-img/)?[0-9]+(?:_[a-zA-Z0-9]+)?\.(?:png|jpe?g|gif|webp)|profile-(?:media|gallery)/[0-9]+_[a-f0-9]{32}\.(?:png|jpe?g|webp|mp4|webm))$#', $path) === 1;
    }
}
