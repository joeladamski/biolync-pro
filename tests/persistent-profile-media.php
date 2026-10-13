<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e) { fwrite(STDERR,(string)$e);exit(1); });
use App\Support\RuntimeMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
function verifyMedia($ok,$message) { if(!$ok)throw new RuntimeException($message);echo "PASS persistent profile media: $message\n"; }
$root=sys_get_temp_dir().'/profile-media-test-'.bin2hex(random_bytes(6));
mkdir($root,0750,true);
config(['estate-media.profile_path'=>$root.'/persistent']);
$logical=['assets/img/123_456.png','assets/img/background-img/123_456.png','assets/profile-media/123_'.str_repeat('a',32).'.png','assets/profile-gallery/123_'.str_repeat('b',32).'.png'];
try {
    $image=UploadedFile::fake()->image('image.png',80,80);
    foreach($logical as $path) { mkdir(dirname($root.'/backup/'.$path),0750,true);copy($image->getRealPath(),$root.'/backup/'.$path); }
    verifyMedia(Artisan::call('estate:import-profile-media',['--from'=>$root.'/backup'])===0,'legacy import succeeds');
    foreach($logical as $path) {
        verifyMedia(is_file(RuntimeMedia::path($path)) && str_starts_with(RuntimeMedia::path($path),$root.'/persistent/'),'media is outside application root');
        unlink($root.'/backup/'.$path);
        verifyMedia(is_file(RuntimeMedia::path($path)),'media survives removal of deployed/source copy');
        $response=(new App\Http\Controllers\RuntimeMediaController)->show(substr($path,7));
        verifyMedia($response->getFile()->getPathname()===RuntimeMedia::path($path),'existing public URL resolves to persistent image');
    }
    verifyMedia(findAvatar(123)==='assets/img/123_456.png' && findBackground(123)==='123_456.png','avatar/background discovery includes persistent files');
    $target=RuntimeMedia::path('assets/img/123_456.png');$hash=hash_file('sha256',$target);
    copy($image->getRealPath(),$root.'/backup/assets/img/123_456.png');
    Artisan::call('estate:import-profile-media',['--from'=>$root.'/backup']);
    verifyMedia(hash_file('sha256',$target)===$hash,'repeated import retains current files');
    foreach(['img/../.env','img/123.php','img/background-img/123.svg','profile-media/not-a-profile.png'] as $path) {
        try { (new App\Http\Controllers\RuntimeMediaController)->show($path);throw new RuntimeException('Invalid media served'); }
        catch(Symfony\Component\HttpKernel\Exception\HttpException $e) { verifyMedia($e->getStatusCode()===404,'invalid public path rejected'); }
    }
    file_put_contents(RuntimeMedia::path('assets/img/999.png'),'<?php echo "invalid";');
    try { (new App\Http\Controllers\RuntimeMediaController)->show('img/999.png');throw new RuntimeException('Invalid content served'); }
    catch(Symfony\Component\HttpKernel\Exception\HttpException $e) { verifyMedia($e->getStatusCode()===404,'non-media content rejected'); }
    verifyMedia(RuntimeMedia::path('composer.json')===base_path('composer.json'),'non-media application paths remain unchanged');
    foreach(RuntimeMedia::DIRECTORIES as $directory)verifyMedia(is_dir(RuntimeMedia::path($directory)),'upload destination exists');
} finally {
    $files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($files as $file)$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());rmdir($root);
}
