<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class UserData extends Model
{
    protected $table = 'users';
    protected $fillable = ['image'];

    public static function saveData($userId, $key, $value)
    {
        return self::mutateData($userId, function ($data) use ($key, $value) {
            $data[$key] = $value;
            return $data;
        });
    }

    private static function mutateData($userId, callable $change)
    {
        $result = \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $change) {
            $userData = self::whereKey($userId)->lockForUpdate()->first();
            if (!$userData) return "null";
            $data = json_decode($userData->image ?? '{}', true);
            $userData->image = json_encode($change(is_array($data) ? $data : []), JSON_THROW_ON_ERROR);
            $userData->save();
            return null;
        });
        Cache::forget('user_data_' . $userId);
        return $result;
    }

    public static function getData($userId, $key)
    {
        $userData = self::getCachedUserData($userId);

        if (!$userData || !$userData->image) {
            return "null";
        }

        $data = json_decode($userData->image, true) ?? [];

        return isset($data[$key]) ? $data[$key] : null;
    }

    public static function removeData($userId, $key)
    {
        return self::mutateData($userId, function ($data) use ($key) {
            unset($data[$key]);
            return $data;
        });
    }

    private static function getCachedUserData($userId)
    {
        return Cache::remember('user_data_' . $userId, now()->addMinutes(10), function () use ($userId) {
            return self::where('id', $userId)->first();
        });
    }

}
