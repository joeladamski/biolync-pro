<?php

namespace App\Http\Controllers;

use App\Models\UserData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorExperienceController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'audio_enabled' => 'nullable|boolean',
            'audio_title' => 'nullable|string|max:120',
            'audio_artist' => 'nullable|string|max:120',
            'audio_url' => ['nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'audio_cover_url' => ['nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i'],

            'live_enabled' => 'nullable|boolean',
            'live_label' => 'nullable|string|max:40',
            'live_invitation' => 'nullable|string|max:120',
            'live_venue' => 'nullable|string|max:160',
            'live_description' => 'nullable|string|max:600',
            'live_url' => ['nullable', 'url', 'max:2048', 'regex:/^https?:\/\//i'],
            'live_start_at' => 'nullable|date',
            'live_end_at' => 'nullable|date|after_or_equal:live_start_at',
            'live_click_behavior' => 'nullable|in:preview,direct',

            'emoji_fx_enabled' => 'nullable|boolean',
            'emoji_fx_direction' => 'nullable|in:rise,fall',
            'emoji_fx_intensity' => 'nullable|in:subtle,normal,party',
            'emoji_1' => 'nullable|string|max:12',
            'emoji_2' => 'nullable|string|max:12',
            'emoji_3' => 'nullable|string|max:12',
        ]);

        $user = Auth::user();

        UserData::saveData($user->id, 'profile_audio', [
            'enabled' => $request->boolean('audio_enabled'),
            'title' => trim((string)($data['audio_title'] ?? '')),
            'artist' => trim((string)($data['audio_artist'] ?? '')),
            'url' => $data['audio_url'] ?? '',
            'cover_url' => $data['audio_cover_url'] ?? '',
        ]);

        UserData::saveData($user->id, 'live_cta', [
            'enabled' => $request->boolean('live_enabled'),
            'label' => trim((string)($data['live_label'] ?? 'LIVE TONIGHT')) ?: 'LIVE TONIGHT',
            'invitation' => trim((string)($data['live_invitation'] ?? 'Catch me here tonight')) ?: 'Catch me here tonight',
            'venue' => trim((string)($data['live_venue'] ?? '')),
            'description' => trim((string)($data['live_description'] ?? '')),
            'url' => $data['live_url'] ?? '',
            'start_at' => $data['live_start_at'] ?? null,
            'end_at' => $data['live_end_at'] ?? null,
            'click_behavior' => $data['live_click_behavior'] ?? 'preview',
        ]);

        if ($user->role === 'vip') {
            $emojis = array_values(array_filter([
                trim((string)($data['emoji_1'] ?? '')),
                trim((string)($data['emoji_2'] ?? '')),
                trim((string)($data['emoji_3'] ?? '')),
            ], fn ($emoji) => $emoji !== ''));

            UserData::saveData($user->id, 'vip_emoji_fx', [
                'enabled' => $request->boolean('emoji_fx_enabled'),
                'direction' => $data['emoji_fx_direction'] ?? 'rise',
                'intensity' => $data['emoji_fx_intensity'] ?? 'normal',
                'emojis' => array_slice($emojis ?: ['💋'], 0, 3),
            ]);
        }

        return redirect('/studio/page')->with('success', 'Creator experience updated.');
    }
}
