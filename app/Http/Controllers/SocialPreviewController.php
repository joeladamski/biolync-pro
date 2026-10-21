<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Support\SocialPreview;

class SocialPreviewController extends Controller
{
    public function site() { return $this->image(); }
    public function profile(string $user)
    {
        $profile=User::findOrFail($user);
        abort_if($profile->block==='yes' || trim((string)$profile->littlelink_name)==='',404);
        return $this->image($profile);
    }
    private function image(?User $user=null)
    {
        return response(SocialPreview::jpeg($user),200,['Content-Type'=>'image/jpeg','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'public, max-age=300']);
    }
}
