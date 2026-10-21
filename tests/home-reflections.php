<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e){fwrite(STDERR,(string)$e);exit(1);});
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use App\Support\HomeUi;
function reflectionCheck($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS reflection: $message\n";}
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array','app.key'=>'base64:'.base64_encode(str_repeat('x',32)),
 'advanced-config.buttons'=>[['button'=>'github','title'=>'GitHub','link'=>'https://github.com','icon'=>'','custom_css'=>'']]]);
DB::purge();Storage::fake('local');
Schema::create('pages',function($t){$t->id();$t->text('home_message');});
DB::table('pages')->insert(['home_message'=>'Discover your world.']);
Schema::create('users',function($t){$t->id();$t->string('name');$t->string('littlelink_name');$t->string('block')->default('no');});
@mkdir(base_path('test-output/reflections'),0750,true);
foreach(['metalqueen.one'=>false,'biolync.pro'=>true,'pinkkiss.love'=>false,'metalking.one'=>true] as $host=>$expected){
 config(['app.url'=>'https://'.$host,'app.name'=>$host==='metalqueen.one'?'Metal Queen One':'BioLync']);
 reflectionCheck(HomeUi::deviceReflection()===$expected,'domain default for '.$host);
 $html=view('home',['message'=>App\Models\Page::first()])->render();
 $deviceCount=preg_match_all('/<div class="metal-reflection" data-direction="counterclockwise"/',$html);
 $cardCount=preg_match_all('/<div class="metal-reflection" data-direction="clockwise"/',$html);
 reflectionCheck($deviceCount===($expected?1:0),'device markup for '.$host);
 reflectionCheck($cardCount===($host==='metalking.one'?1:0),'message card markup for '.$host);
 // Keep the actual Blade/CSS layout while isolating the reflection script from unrelated dashboard bundles.
 $html=preg_replace_callback('#<script\b[^>]*\bsrc="([^"]+)"[^>]*>.*?</script>#s',fn($m)=>str_contains($m[1],'metal-home-reflections.js')?$m[0]:'',$html);
 $html=preg_replace('#(<iframe\b[^>]*\bsrc=")[^"]+#','$1/reflection-profile.html',$html);
 file_put_contents(base_path('test-output/reflections/'.$host.'.html'),$html);
}
file_put_contents(base_path('test-output/reflections/reflection-profile.html'),'<!doctype html><meta name="viewport" content="width=device-width"><style>body{margin:0;padding:24px;background:linear-gradient(140deg,#203747,#291735);color:white;font:16px system-ui;min-height:700px}button{padding:12px}</style><h1>BioLync</h1><p>Featured profile</p><button onclick="this.textContent=\'Opened\'">Open profile link</button>');
$controller=new App\Http\Controllers\AdminController;
$fields=['buttons'=>[['title'=>'GitHub','link'=>'https://github.com']]];
config(['app.url'=>'https://biolync.pro']);
$controller->editHomeUi(Request::create('/admin/home-ui','POST',array_merge($fields,['device_reflection'=>0])));
reflectionCheck(HomeUi::deviceReflection()===false,'device reflection can be disabled');
$controller->editHomeUi(Request::create('/admin/home-ui','POST',$fields));
reflectionCheck(HomeUi::deviceReflection()===false,'older form submissions preserve saved reflection preference');
config(['app.url'=>'https://metalqueen.one']);
$controller->editHomeUi(Request::create('/admin/home-ui','POST',array_merge($fields,['device_reflection'=>1])));
reflectionCheck(HomeUi::deviceReflection()===true,'shared device control can explicitly enable any estate');
try{$controller->editHomeUi(Request::create('/admin/home-ui','POST',array_merge($fields,['device_reflection'=>'invalid'])));throw new RuntimeException('Invalid setting accepted');}catch(Illuminate\Validation\ValidationException $e){reflectionCheck(HomeUi::deviceReflection()===true,'invalid preference cannot overwrite saved value');}
