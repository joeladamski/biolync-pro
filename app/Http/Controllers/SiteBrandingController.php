<?php
namespace App\Http\Controllers;

use App\Support\SiteBranding;

class SiteBrandingController extends Controller
{
    public function show(string $kind)
    {
        $path = SiteBranding::path($kind);
        abort_unless($path && is_file($path), 404);
        return response()->file($path, ['Cache-Control'=>'public, max-age=300', 'X-Content-Type-Options'=>'nosniff']);
    }
}
