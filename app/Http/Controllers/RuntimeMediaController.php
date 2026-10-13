<?php
namespace App\Http\Controllers;

use App\Support\RuntimeMedia;

class RuntimeMediaController extends Controller
{
    public function show(string $path)
    {
        $logical = 'assets/'.$path;
        abort_unless(RuntimeMedia::allowed($logical), 404);
        $file = RuntimeMedia::path($logical);
        abort_unless(is_file($file) && !is_link($file), 404);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);
        abort_unless(in_array($mime, ['image/png','image/jpeg','image/gif','image/webp','video/mp4','video/webm'], true), 404);
        return response()->file($file, ['Content-Type'=>$mime,'X-Content-Type-Options'=>'nosniff','Cache-Control'=>'public, max-age=300']);
    }
}
