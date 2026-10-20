<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e) { fwrite(STDERR, (string)$e); exit(1); });
use App\Support\PublicSiteStyle;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
function contentCheck($ok,$message) { if (!$ok) throw new RuntimeException($message); echo "PASS content: $message\n"; }
if (($argv[1] ?? '') === '--save-worker') {
    config(['filesystems.disks.local.root'=>$argv[2], 'estate-media.profile_path'=>$argv[3]]);
    file_put_contents($argv[4], 'ready');
    (new App\Http\Controllers\PublicSiteStyleController)->save(Request::create('/admin/public-site-styles','POST',PublicSiteStyle::defaults()));
    exit;
}
Storage::fake('local');
$directory = sys_get_temp_dir().'/biolync-ui-media-'.bin2hex(random_bytes(5));
config(['estate-media.profile_path'=>$directory]);
$controller = new App\Http\Controllers\PublicSiteStyleController;
$payload = PublicSiteStyle::defaults();
$request = Request::create('/admin/public-site-styles','POST',array_replace($payload,['surface_opacity'=>35]));
$request->files->set('background_image_upload',UploadedFile::fake()->image('background.png',100,80));
$controller->save($request);
$settings = PublicSiteStyle::settings();
$imagePath = PublicSiteStyle::backgroundPath($settings['background_image']);
contentCheck(is_file($imagePath) && str_contains(PublicSiteStyle::backgroundUrl(),'/assets/img/background-img/'), 'background upload persists outside deployment root');
$response = (new App\Http\Controllers\RuntimeMediaController)->show(substr($settings['background_image'],7));
contentCheck($response->headers->get('Content-Type') === 'image/png', 'background is served by runtime image route');
contentCheck(PublicSiteStyle::surfaceColor($settings) === 'rgba(255,255,255,0.35)', 'surface opacity changes background alpha only');
$controller->save(Request::create('/admin/public-site-styles','POST',$payload));
contentCheck(PublicSiteStyle::settings()['background_image'] === $settings['background_image'], 'ordinary styles save preserves uploaded image');
try {
    $controller->save(Request::create('/admin/public-site-styles','POST',array_replace($payload,['surface_opacity'=>101])));
    throw new RuntimeException('Invalid opacity accepted');
} catch (ValidationException $e) { contentCheck(isset($e->errors()['surface_opacity']),'invalid opacity rejected'); }
$bad=Request::create('/admin/public-site-styles','POST',$payload);
$bad->files->set('background_image_upload',UploadedFile::fake()->createWithContent('evil.svg','<svg onload="alert(1)"></svg>'));
try { $controller->save($bad); throw new RuntimeException('Unsafe image accepted'); }
catch (ValidationException $e) { contentCheck(isset($e->errors()['background_image_upload']),'SVG upload rejected'); }
contentCheck(PublicSiteStyle::backgroundPath('../../.env') === null, 'background path cannot escape managed images');
$body = '<p style="text-align:center"><strong>Bold 💋</strong><em>Italic</em></p><script>alert(1)</script><a href="javascript:alert(1)" onclick="x()">Bad</a>';
$clean = RichText::render($body, 'html', true);
contentCheck(str_contains($clean,'<strong>Bold 💋</strong>') && !str_contains($clean,'script') && !str_contains($clean,'onclick') && !str_contains($clean,'javascript:'), 'editor formatting survives sanitization');
contentCheck(str_contains(RichText::render('<a href="https://example.test">Creator</a>', 'html', true), 'rel="noopener noreferrer nofollow"'), 'creator links preserve nofollow');
contentCheck(!str_contains(RichText::render('<a href="https://example.test">Editorial</a>'), 'nofollow'), 'editorial links retain their existing policy');
$heading=RichText::inline($body);
contentCheck(str_contains($heading,'<strong>') && !str_contains($heading,'<p') && !str_contains($heading,'script'), 'heading allows safe inline formatting without nested paragraphs');
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','linkstack.disable_random_user_ids'=>'true','session.driver'=>'array']);
Illuminate\Support\Facades\DB::purge();
Illuminate\Support\Facades\Schema::create('users',function($t){$t->id();$t->string('name');$t->string('email');$t->text('littlelink_description')->nullable();$t->timestamps();});
$user=App\Models\User::create(['name'=>'Creator','email'=>'creator@example.test']);
Illuminate\Support\Facades\Auth::setUser($user);
(new App\Http\Controllers\UserController)->editPage(Request::create('/studio/page','POST',['pageDescription'=>$body]));
contentCheck($user->fresh()->littlelink_description === $clean,'profile description saves sanitized rich text');
$controller->save(Request::create('/admin/public-site-styles','POST',array_replace($payload,['surface_opacity'=>35])));
// Generate actual Blade forms for browser interaction QA.
$fixtures = getenv('BIOLYNC_UI_DIR') ?: sys_get_temp_dir().'/biolync-content-ui';
@mkdir($fixtures,0750,true);
copy($imagePath, $fixtures.'/background.png');
$form=view('components.config.public-site-styles',['errors'=>new Illuminate\Support\ViewErrorBag])->render();
contentCheck(str_contains($form,'>BioLync</div>') && !str_contains($form,'PinkKiss.Love'), 'preview is branded BioLync');
file_put_contents($fixtures.'/styles.html','<!doctype html><title>BioLync style QA</title><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/assets/css/hope-ui.min.css"><style>body{background:#232838;color:white;padding:24px}</style>'.$form);
$fields = '<form id="profile-form" method="POST" action="/save"><label for="page-description">Page Description</label><textarea id="page-description" name="pageDescription" data-rich-text data-rich-text-label="Page Description">'.e($clean).'</textarea><button type="submit">Save profile</button></form>';
$fields .= '<form id="my-form" method="POST" action="/save"><input name="linkid" value="0" type="hidden"><input name="typename" value="text" type="hidden"><button type="button" id="btnLinkType">Select Block</button><a class="doSelectLinkType" href="#" data-typeid="heading" data-typename="Heading">Heading</a><a class="doSelectLinkType" href="#" data-typeid="text" data-typename="Text">Text</a><div id="SelectLinkType"></div><div id="link_params"></div><button type="submit">Save</button><button type="submit" name="param" value="add_more">Save and Add More</button></form>';
$source=file_get_contents(resource_path('views/studio/edit-link.blade.php'));
preg_match('/@push\("sidebar-scripts"\)(.*?)@endpush/s',$source,$scripts);
$compiled=Illuminate\Support\Facades\Blade::render($scripts[1]);
file_put_contents($fixtures.'/editors.html','<!doctype html><title>BioLync editor QA</title><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/assets/css/hope-ui.min.css"><link rel="stylesheet" href="/assets/vendor/jodit/jodit.min.css"><link rel="stylesheet" href="/assets/css/admin-rich-text.css"><style>body{background:#232838;color:white;padding:20px}form{margin-bottom:32px}#link_params{max-width:920px}</style>'.$fields.'<script src="/assets/js/jquery.min.js"></script><script>jQuery.fn.modal=function(){return this;};</script><script src="/assets/vendor/jodit/jodit.min.js"></script><script src="/assets/js/admin-rich-text.js"></script>'.$compiled);
foreach(['text','heading'] as $type) file_put_contents($fixtures.'/'.$type.'.html',view()->file(base_path('blocks/'.$type.'/form.blade.php'),['title'=>'Existing '.$type])->render());
$css=view('components.public-site-styles')->render();
contentCheck(str_contains($css,'background-image:') && str_contains($css,'rgba('),'public CSS contains image and surface alpha');
file_put_contents($fixtures.'/landing.html','<!doctype html><title>BioLync landing QA</title><meta name="viewport" content="width=device-width">'.$css.'<body class="pk-public-home pk-public-surface"><main class="home-layout"><section class="home-copy"><div class="auth-card"><div class="card-body"><h1>BioLync</h1><p>Landing page background and transparent card</p></div></div></section><iframe title="Homepage profile preview" src="/spotlight.html" style="width:300px;height:400px"></iframe></main></body>');
// Render the actual scoped scrollbar CSS with its preview query enabled.
$previewRequest=Request::create('/@creator?spotlight_preview=1');app()->instance('request',$previewRequest);
$profile=view('linkstack.layout')->render();
contentCheck(str_contains($profile,'spotlight-scrollbar'),'spotlight preview receives scoped dark scrollbars');
$profile=str_replace('<body>','<body style="background:#181820;color:white"><h1>Featured creator</h1><div style="height:1200px">Scrollable spotlight</div>',$profile);
file_put_contents($fixtures.'/spotlight.html',$profile);
$controller->save(Request::create('/admin/public-site-styles','POST',array_replace($payload,['remove_background_image'=>1])));
contentCheck(PublicSiteStyle::backgroundUrl() === null && !is_file($imagePath),'remove image deletes managed background');
$controller->reset();
contentCheck(PublicSiteStyle::settings()['surface_opacity'] === 100,'reset restores solid cards');
// A save that starts during replacement must read the new image after acquiring the lock.
$firstLogical='assets/img/background-img/0_landing'.str_repeat('a',32).'.png';
$secondLogical='assets/img/background-img/0_landing'.str_repeat('b',32).'.png';
copy($fixtures.'/background.png',PublicSiteStyle::backgroundPath($firstLogical));
copy($fixtures.'/background.png',PublicSiteStyle::backgroundPath($secondLogical));
PublicSiteStyle::save(array_replace($payload,['background_image'=>$firstLogical]));
$ready=$fixtures.'/save-worker-ready';@unlink($ready);
$worker=null;$pipes=[];
PublicSiteStyle::withLock(function () use ($payload,$firstLogical,$secondLogical,$ready,&$worker,&$pipes) {
    $worker=proc_open([PHP_BINARY,__FILE__,'--save-worker',Storage::disk('local')->path(''),config('estate-media.profile_path'),$ready],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
    if (!is_resource($worker)) throw new RuntimeException('Unable to launch save worker');
    fclose($pipes[0]);
    $deadline=microtime(true)+5;
    while(!is_file($ready) && microtime(true)<$deadline) usleep(10000);
    contentCheck(is_file($ready),'concurrent style request starts');
    usleep(100000);
    contentCheck(proc_get_status($worker)['running'],'style save waits for background replacement lock');
    PublicSiteStyle::save(array_replace($payload,['background_image'=>$secondLogical]));
    unlink(PublicSiteStyle::backgroundPath($firstLogical));
});
$workerOutput=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
contentCheck(proc_close($worker)===0,'concurrent save completes: '.$workerOutput);
contentCheck(PublicSiteStyle::settings()['background_image']===$secondLogical && is_file(PublicSiteStyle::backgroundPath($secondLogical)),'ordinary concurrent save preserves replacement image');
$controller->reset();
$compiler=app('blade.compiler');
foreach (['studio/page','studio/edit-link','home','components/config/public-site-styles','components/public-site-styles'] as $name) {
 $temp=tempnam(sys_get_temp_dir(),'content-blade-');file_put_contents($temp,$compiler->compileString(file_get_contents(resource_path('views/'.$name.'.blade.php'))));
 exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($temp).' 2>&1',$output,$code);unlink($temp);
 contentCheck($code===0,"$name compiles");
}
