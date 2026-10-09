<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicSiteStyle
{
    public static function defaults(): array
    {
        return [
            'scheme' => 'dark',
            'primary' => '#33003B',
            'accent' => '#FF5DD3',
            'background' => '#33003B',
            'surface' => '#FFFFFF',
            'text_light' => '#FFFFFF',
            'text_dark' => '#000000',
            'heading_font' => 'system',
            'body_font' => 'system',
            'button_radius' => 14,
            'card_radius' => 18,
            'content_width' => 1200,
        ];
    }

    public static function settings(): array
    {
        $settings = self::defaults();
        if (!Storage::disk('local')->exists('public-site-styles.json')) {
            return $settings;
        }

        $decoded = json_decode(Storage::disk('local')->get('public-site-styles.json'), true);
        return is_array($decoded) ? array_replace($settings, $decoded) : $settings;
    }

    public static function save(array $settings): void
    {
        $clean = array_intersect_key($settings, self::defaults());
        Storage::disk('local')->put(
            'public-site-styles.json',
            json_encode(array_replace(self::defaults(), $clean), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
    }

    public static function fontStack(string $font): string
    {
        return match ($font) {
            'serif' => 'Georgia, "Times New Roman", serif',
            'modern' => '"Trebuchet MS", Arial, sans-serif',
            'clean' => 'Arial, Helvetica, sans-serif',
            default => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        };
    }
}
