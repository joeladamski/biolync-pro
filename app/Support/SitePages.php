<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class SitePages
{
    public static function all(): array
    {
        $pages = json_decode(Storage::disk('local')->get('site-pages.json') ?? '[]', true);
        $pages = is_array($pages) ? $pages : [];
        usort($pages, fn ($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));
        return $pages;
    }

    public static function navigation(): array
    {
        return array_values(array_filter(self::all(), fn ($page) => !empty($page['published']) && !empty($page['show_in_header'])));
    }

    public static function update(callable $callback): void
    {
        $disk = Storage::disk('local');
        $lockPath = $disk->path('site-pages.lock');
        if (!is_dir(dirname($lockPath))) mkdir(dirname($lockPath), 0755, true);
        $lock = fopen($lockPath, 'c');
        if (!$lock || !flock($lock, LOCK_EX)) throw new \RuntimeException('Unable to lock site page settings.');
        $temporary = null;
        try {
            $pages = $callback(self::all());
            $temporary = tempnam(dirname($lockPath), 'site-pages-');
            if ($temporary === false || file_put_contents($temporary, json_encode(array_values($pages), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false || !rename($temporary, $disk->path('site-pages.json'))) {
                throw new \RuntimeException('Unable to save site pages. Check storage permissions.');
            }
        } finally {
            if ($temporary && is_file($temporary)) unlink($temporary);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
