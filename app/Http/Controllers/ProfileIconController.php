<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ProfileIcon;

class ProfileIconController extends Controller
{
    public function show(string $user, string $size)
    {
        abort_unless(in_array($size, ['32','180'], true), 404);
        $profile = User::findOrFail($user);
        abort_if($profile->block === 'yes' || trim((string)$profile->littlelink_name) === '', 404);
        return response(ProfileIcon::png(ProfileIcon::source($profile), (int)$size), 200, [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
