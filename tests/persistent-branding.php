<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e) { fwrite(STDERR, (string)$e); exit(1); });
use App\Support\SiteBranding;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

function verifyBranding($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS persistent branding: $message\n";
}
$root = sys_get_temp_dir().'/branding-test-'.bin2hex(random_bytes(6));
mkdir($root.'/deployment/assets', 0750, true);
mkdir($root.'/backup', 0750, true);
config(['estate-media.branding_path'=>$root.'/persistent/branding']);
try {
    $image = UploadedFile::fake()->image('logo.png', 80, 80);
    copy($image->getRealPath(), $root.'/backup/avatar_123.png');
    copy($image->getRealPath(), $root.'/backup/favicon_123.png');
    verifyBranding(Artisan::call('estate:import-branding', ['--from'=>$root.'/backup']) === 0, 'backup import succeeds');
    verifyBranding(SiteBranding::path('avatar', false) && SiteBranding::path('favicon', false), 'both files are outside deployment');
    $before = hash_file('sha256', SiteBranding::path('avatar'));
    unlink($root.'/backup/avatar_123.png'); unlink($root.'/backup/favicon_123.png');
    rmdir($root.'/backup'); rmdir($root.'/deployment/assets'); rmdir($root.'/deployment');
    verifyBranding(hash_file('sha256', SiteBranding::path('avatar')) === $before && SiteBranding::exists('favicon'), 'branding survives removal of deployment and import-source directories');
    $response = (new App\Http\Controllers\SiteBrandingController)->show('avatar');
    verifyBranding($response->getFile()->getPathname() === SiteBranding::path('avatar') && $response->headers->get('X-Content-Type-Options') === 'nosniff', 'public response serves persistent image safely');
    $url = SiteBranding::url('avatar');
    SiteBranding::save('avatar', UploadedFile::fake()->image('new.jpg', 90, 90));
    verifyBranding(SiteBranding::url('avatar') !== $url && str_ends_with(SiteBranding::path('avatar'), '.jpg'), 'replacement updates URL and actual image type');
    mkdir($root.'/backup'); copy($image->getRealPath(), $root.'/backup/avatar_456.png');
    Artisan::call('estate:import-branding', ['--from'=>$root.'/backup']);
    verifyBranding(str_ends_with(SiteBranding::path('avatar'), '.jpg'), 'repeated import retains current persistent image');
    file_put_contents($root.'/bad.png', '<?php echo "bad";');
    try { SiteBranding::copy('avatar', $root.'/bad.png'); throw new RuntimeException('Invalid image accepted'); }
    catch (ValidationException $e) { verifyBranding(str_ends_with(SiteBranding::path('avatar'), '.jpg'), 'invalid upload preserves existing branding'); }
    try { SiteBranding::path('../.env'); throw new RuntimeException('Invalid kind accepted'); }
    catch (InvalidArgumentException $e) { verifyBranding(true, 'arbitrary file paths rejected'); }
    $route = app('router')->getRoutes()->getByName('siteBranding');
    verifyBranding($route->wheres['kind'] === 'avatar|favicon', 'public route exposes only two branding kinds');
    SiteBranding::remove('avatar');
    verifyBranding(!SiteBranding::path('avatar', false), 'delete removes persistent logo');
    // Compile every branding-bearing view and lint generated PHP.
    $compiler = app('blade.compiler');
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'))) as $file) {
        if (!$file->isFile() || !str_ends_with($file->getFilename(), '.blade.php')) continue;
        $source = file_get_contents($file->getPathname());
        if (!str_contains($source, 'SiteBranding::')) continue;
        $compiled = tempnam(sys_get_temp_dir(), 'branding-blade-');
        file_put_contents($compiled, $compiler->compileString($source));
        exec('php -l '.escapeshellarg($compiled).' 2>&1', $output, $code);
        unlink($compiled);
        verifyBranding($code === 0, 'Blade syntax: '.$file->getFilename());
    }
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    rmdir($root);
}
