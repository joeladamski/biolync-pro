<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class HomeUi
{
    public static function deviceReflection(): bool
    {
        $saved = json_decode(Storage::disk('local')->get('home-ui.json') ?? '{}', true) ?: [];
        if (array_key_exists('device_reflection', $saved)) return (bool) $saved['device_reflection'];
        $host = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '');
        return in_array($host, ['biolync.pro', 'www.biolync.pro', 'orderofthemetal.one', 'metalking.one', 'metalgod.one', 'metalwarrior.one'], true);
    }

    public static function previewCopy(): array
    {
        $saved = json_decode(Storage::disk('local')->get('home-ui.json') ?? '{}', true) ?: [];
        return array_merge(['title' => '', 'tagline' => '', 'description' => '', 'description_format' => 'text'], $saved['preview_copy'] ?? []);
    }

    public static function buttons(): array
    {
        $buttons = config('advanced-config.buttons', []);
        $saved = json_decode(Storage::disk('local')->get('home-ui.json') ?? '{}', true) ?: [];
        foreach ($buttons as $index => &$button) {
            if (isset($saved['buttons'][$index])) {
                $button['title'] = $saved['buttons'][$index]['title'];
                $button['link'] = $saved['buttons'][$index]['link'];
            }
        }
        return $buttons;
    }
}
