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
 submit($controller,['remove_header'=>1]);
 check(UserData::getData($user->id,'profile_header')===[] && !file_exists(base_path($second['media'])),'removal clears metadata and file');
 $html=view('linkstack.elements.profile-header',['userinfo'=>$user])->render();
 check(!str_contains($html,'class="biolync-cover"'),'no-media profile omits cover');
} finally { foreach (glob(base_path('assets/profile-media/'.$user->id.'_*')) as $file) unlink($file); }
