<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'session.driver' => 'array', 'app.key' => 'base64:'.base64_encode(str_repeat('x',32)), 'linkstack.disable_random_user_ids' => 'true']);
Illuminate\Support\Facades\DB::purge();
Illuminate\Support\Facades\Schema::create('users', function ($t) { $t->id(); $t->text('image')->nullable(); $t->string('name'); $t->string('email'); $t->string('password'); $t->timestamps(); });
use App\Models\User;
use App\Models\UserData;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
function check($ok, $name) { if (!$ok) throw new RuntimeException($name); echo "PASS: $name\n"; }
$user = User::create(['name'=>'Test','email'=>'test@example.test','password'=>'unused']);
Auth::login($user);
$controller = new App\Http\Controllers\UserController;
function submit($controller, $fields=[], $files=[]) { $r=Request::create('/studio/profile-header','POST',array_merge(['header_position'=>'center'],$fields),[], $files); return $controller->profileHeader($r); }
try {
 submit($controller, [], ['header_media'=>UploadedFile::fake()->image('cover.png')]);
 $first=UserData::getData($user->id,'profile_header');
 check($first['type']==='image' && file_exists(base_path($first['media'])), 'image upload persists metadata and file');
 submit($controller,['header_position'=>'top']);
 check(UserData::getData($user->id,'profile_header')['media']===$first['media'],'empty upload preserves media');
 submit($controller, [], ['header_media'=>UploadedFile::fake()->image('new.jpg')]);
 $second=UserData::getData($user->id,'profile_header');
 check(!file_exists(base_path($first['media'])) && file_exists(base_path($second['media'])), 'replacement deletes previous file');
 foreach (['invalid'=>UploadedFile::fake()->create('bad.php',1,'application/x-php'),'oversize'=>UploadedFile::fake()->image('large.png')->size(10241)] as $label=>$file) {
  try { submit($controller,[],['header_media'=>$file]); throw new RuntimeException('Accepted '.$label); } catch (Illuminate\Validation\ValidationException $e) { check(isset($e->errors()['header_media']),$label.' rejected'); }
 }
 check(UserData::getData($user->id,'profile_header')['media']===$second['media'],'rejected upload preserves previous header');
 $other=User::create(['name'=>'Other','email'=>'other@example.test','password'=>'unused']);
 Auth::login($other); submit($controller,['remove_header'=>1]);
 check(file_exists(base_path($second['media'])),'another user cannot remove owner media');
 Auth::login($user);
 $html=view('linkstack.elements.profile-header',['userinfo'=>$user])->render();
 check(str_contains($html,'class="biolync-cover"') && str_contains($html,'Profile cover'),'image header renders');
 submit($controller, [], ['header_media'=>UploadedFile::fake()->createWithContent('clip.mp4', base64_decode('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')), 'header_poster'=>UploadedFile::fake()->image('poster.png')]);
 $video=UserData::getData($user->id,'profile_header');
 check($video['type']==='video' && file_exists(base_path($video['poster'])), 'real MP4 and poster upload');
 $html=view('linkstack.elements.profile-header',['userinfo'=>$user])->render();
 check(str_contains($html,'muted loop playsinline') && str_contains($html,'poster=') && str_contains($html,'prefers-reduced-motion'), 'video attributes and motion handling render');
 @mkdir(base_path('test-output'));
 foreach (['Ocean','Paper','Midnight','Rose'] as $theme) {
  $css=file_get_contents(base_path('themes/BioLync'.$theme.'/skeleton-auto.css'));
  file_put_contents(base_path('test-output/'.$theme.'.html'), '<!doctype html><style>body{margin:0}</style><meta name="viewport" content="width=device-width"><style>'.$css.'</style><div class="container biolync-profile"><div class="row"><div class="column biolync-profile-column">'.$html.'<div class="biolync-avatar"><img id="avatar" alt="avatar"></div><h1>Test profile</h1><p>Profile description</p><a class="button">Example link</a></div></div></div>');
 }
 submit($controller,['remove_header'=>1]);
 check(UserData::getData($user->id,'profile_header')===[] && !file_exists(base_path($video['media'])) && !file_exists(base_path($video['poster'])),'removal clears metadata and file');
 $html=view('linkstack.elements.profile-header',['userinfo'=>$user])->render();
 check(!str_contains($html,'class="biolync-cover"'),'no-media profile omits cover');
} finally { foreach (glob(base_path('assets/profile-media/'.$user->id.'_*')) as $file) unlink($file); }
