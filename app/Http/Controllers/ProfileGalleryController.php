<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ProfileGallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class ProfileGalleryController extends Controller
{
    public function save(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'required|boolean',
            'photos' => 'nullable|array|max:12',
            'photos.*' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000',
            'remove' => 'nullable|array|max:12',
            'remove.*' => 'string|regex:/^[a-f0-9]{32}$/',
            'captions' => 'nullable|array|max:12',
            'captions.*' => 'nullable|string|max:200',
            'positions' => 'nullable|array|max:12',
            'positions.*' => 'integer|min:1|max:12',
        ]);
        $userId = Auth::id();
        $created = [];
        $deleted = [];
        try {
            DB::transaction(function () use ($request, $data, $userId, &$created, &$deleted) {
                $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
                $metadata = json_decode($user->image ?? '{}', true) ?: [];
                $gallery = $metadata['photo_gallery'] ?? [];
                $photos = [];
                foreach ($gallery['photos'] ?? [] as $photo) {
                    if (!ProfileGallery::ownedPath($userId, $photo['path'] ?? null)) continue;
                    $id = $photo['id'];
                    if (in_array($id, $data['remove'] ?? [], true)) {
                        $deleted[] = $photo['path'];
                        continue;
                    }
                    $photo['caption'] = $data['captions'][$id] ?? ($photo['caption'] ?? '');
                    $photo['position'] = $data['positions'][$id] ?? count($photos) + 1;
                    $photos[] = $photo;
                }
                $uploads = $request->file('photos', []);
                if (count($photos) + count($uploads) > 12) {
                    throw ValidationException::withMessages(['photos' => 'Your gallery can contain up to 12 photos. Remove photos before adding more.']);
                }
                usort($photos, fn ($a, $b) => $a['position'] <=> $b['position']);
                foreach ($uploads as $file) {
                    $id = bin2hex(random_bytes(16));
                    $path = 'assets/profile-gallery/' . $userId . '_' . $id . '.' . $file->extension();
                    [$width, $height] = getimagesize($file->getRealPath());
                    File::ensureDirectoryExists(base_path('assets/profile-gallery'));
                    $created[] = $path;
                    $file->move(base_path('assets/profile-gallery'), basename($path));
                    $photos[] = ['id' => $id, 'path' => $path, 'caption' => '', 'width' => $width, 'height' => $height];
                }
                foreach ($photos as &$photo) unset($photo['position']);
                $metadata['photo_gallery'] = ['enabled' => (bool) $data['enabled'], 'photos' => $photos];
                $user->image = json_encode($metadata, JSON_THROW_ON_ERROR);
                $user->save();
            });
        } catch (\Throwable $error) {
            foreach ($created as $path) File::delete(base_path($path));
            throw $error;
        }
        Cache::forget('user_data_' . $userId);
        foreach ($deleted as $path) File::delete(base_path($path));
        return redirect('/studio/page')->with('gallery_success', 'Photo gallery saved.');
    }
}
