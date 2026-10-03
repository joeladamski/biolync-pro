<?php

namespace App\Support;

use App\Models\UserData;

class ProfileGallery
{
    public static function ownedPath($userId, $path): bool
    {
        return is_string($path) && preg_match('#^assets/profile-gallery/' . preg_quote((string) $userId, '#') . '_[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#', $path) === 1;
    }

    public static function get($userId): array
    {
        $gallery = UserData::getData($userId, 'photo_gallery');
        $gallery = is_array($gallery) ? $gallery : [];
        $gallery['photos'] = array_values(array_filter($gallery['photos'] ?? [], function ($photo) use ($userId) {
            return is_array($photo) && self::ownedPath($userId, $photo['path'] ?? null) && is_file(base_path($photo['path']));
        }));
        return $gallery;
    }
}
