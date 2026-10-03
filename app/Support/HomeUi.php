<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class HomeUi
{
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
