<?php
$root = dirname(__DIR__);
$probe = in_array('--probe', $argv, true);
if (!$probe) {
    $snapshots = [];
    foreach ([$root.'/.env', $root.'/bootstrap/cache/config.php'] as $path) {
        $snapshots[$path] = is_file($path) ? file_get_contents($path) : null;
    }
    register_shutdown_function(function () use ($snapshots) {
        foreach ($snapshots as $path => $contents) {
            if ($contents === null) @unlink($path); else file_put_contents($path, $contents);
        }
    });
    @unlink($root.'/bootstrap/cache/config.php');
    file_put_contents($root.'/.env', "APP_ENV=testing\nAPP_URL=http://localhost\nAPP_KEY=base64:".base64_encode(str_repeat('x',32))."\nCACHE_DRIVER=array\nSESSION_DRIVER=array\n");
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $e) { fwrite(STDERR, (string)$e); exit(1); });

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

if ($probe) {
    echo json_encode(['settings'=>config('footer'), 'html'=>view('components.landing-footer-links')->render()]);
    exit;
}
function footerCheck($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS footer: $message\n";
}
function probeFooter() {
    exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --probe 2>&1', $output, $code);
    if ($code) throw new RuntimeException(implode("\n", $output));
    return json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
}
$initial = probeFooter();
footerCheck($initial['settings']['DISPLAY_FOOTER'] === false && !str_contains($initial['html'], '<a '), 'missing flags are disabled consistently');
$controller = new App\Http\Controllers\AdminController;
foreach (['DISPLAY_FOOTER','DISPLAY_FOOTER_HOME','DISPLAY_FOOTER_TERMS','DISPLAY_FOOTER_PRIVACY','DISPLAY_FOOTER_CONTACT'] as $key) {
    $controller->editConfig(Request::create('/admin/config','POST',['type'=>'toggle','entry'=>$key,'toggle'=>'1']));
}
foreach (['TITLE_FOOTER_HOME'=>'Welcome', 'TITLE_FOOTER_TERMS'=>'Terms', 'TITLE_FOOTER_PRIVACY'=>'Privacy', 'TITLE_FOOTER_CONTACT'=>'Contact', 'HOME_FOOTER_LINK'=>'https://example.test/welcome'] as $key=>$value) {
    $controller->editConfig(Request::create('/admin/config','POST',['type'=>'text','entry'=>$key,'value'=>$value]));
}
$enabled = probeFooter();
footerCheck(substr_count($enabled['html'], '<a ') === 4, 'saving absent settings enables all four landing links');
footerCheck(str_contains($enabled['html'],'https://example.test/welcome') && str_contains($enabled['html'],'Welcome'), 'new home URL and label persist');
foreach (['terms','privacy','contact'] as $slug) footerCheck(str_contains($enabled['html'],'/pages/'.$slug), "$slug destination renders");
$controller->editConfig(Request::create('/admin/config','POST',['type'=>'toggle','entry'=>'DISPLAY_FOOTER_PRIVACY','toggle'=>'0']));
$partial = probeFooter();
footerCheck(substr_count($partial['html'], '<a ') === 3 && !str_contains($partial['html'],'/pages/privacy'), 'individual link can be disabled');
footerCheck(Artisan::call('config:cache') === 0, 'configuration cache builds');
$cached = probeFooter();
footerCheck($cached['html'] === $partial['html'], 'cached configuration preserves links, titles and URL');
Artisan::call('config:clear');
$controller->editConfig(Request::create('/admin/config','POST',['type'=>'toggle','entry'=>'DISPLAY_FOOTER','toggle'=>'0']));
footerCheck(!str_contains(probeFooter()['html'],'<a '), 'master switch hides links');
$controller->editConfig(Request::create('/admin/config','POST',['type'=>'text','entry'=>'HOME_FOOTER_LINK','value'=>'']));
footerCheck(probeFooter()['settings']['HOME_FOOTER_LINK'] === '', 'custom home URL can be cleared');
$compiler = app('blade.compiler');
foreach (['home','pages','layouts/sidebar','linkinfo','components/config/config','components/landing-footer-links','linkstack/modules/footer'] as $name) {
    $path = tempnam(sys_get_temp_dir(),'footer-blade-');
    file_put_contents($path, $compiler->compileString(file_get_contents(resource_path('views/'.$name.'.blade.php'))));
    exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1', $output, $code);
    unlink($path);
    footerCheck($code === 0, "$name compiles");
}
footerCheck(!str_contains(file_get_contents(resource_path('views/linkstack/linkstack.blade.php')), "@include('linkstack.modules.footer')"), 'resident profiles retain existing footer behavior');
