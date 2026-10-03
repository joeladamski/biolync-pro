<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array','app.key'=>'base64:'.base64_encode(str_repeat('x',32)),'linkstack.disable_random_user_ids'=>'true']);
Illuminate\Support\Facades\DB::purge();
Illuminate\Support\Facades\Schema::create('users', function ($t) { $t->id(); $t->text('image')->nullable(); $t->string('role')->default('user'); $t->string('name'); $t->string('email'); $t->string('password'); $t->timestamps(); });
use App\Models\User;
use App\Models\UserData;
use App\Support\ProfileGallery;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
function verifyGallery($ok,$message) {if(!$ok) throw new RuntimeException($message);echo "PASS: $message\n";}
function saveGallery($fields=[], $files=[]) {return (new App\Http\Controllers\ProfileGalleryController)->save(Request::create('/studio/photo-gallery','POST',array_merge(['enabled'=>1],$fields),[],$files));}
$owner=User::create(['role'=>'vip','name'=>'Owner','email'=>'owner@example.test','password'=>'unused']);
$other=User::create(['role'=>'vip','name'=>'Other','email'=>'other@example.test','password'=>'unused']);
try {
 Auth::login($owner);
 (new App\Http\Controllers\UserController)->profileHeader(Request::create('/studio/profile-header','POST',['header_position'=>'top'],[],['header_media'=>UploadedFile::fake()->image('header.jpg',1200,440)]));
 $header=UserData::getData($owner->id,'profile_header');
 saveGallery([],['photos'=>[UploadedFile::fake()->image('portrait.jpg',100,160),UploadedFile::fake()->image('landscape.png',160,100),UploadedFile::fake()->image('square.jpg',100,100)]]);
 // Simulate a stale cached profile from before the gallery upload.
 $stale = App\Models\UserData::whereKey($owner->id)->first();
 $stale->image = json_encode(['profile_header'=>['position'=>'top']]);
 Illuminate\Support\Facades\Cache::put('user_data_'.$owner->id, $stale, 600);
 UserData::saveData($owner->id, 'links-new-tab', true);
 verifyGallery(count(ProfileGallery::get($owner->id)['photos'])===3, 'stale metadata save preserves gallery');
 UserData::removeData($owner->id, 'links-new-tab');
 verifyGallery(UserData::getData($owner->id,'profile_header')['position']==='top' && count(ProfileGallery::get($owner->id)['photos'])===3, 'metadata removal preserves header and gallery');
 $gallery=ProfileGallery::get($owner->id);$first=$gallery['photos'][0];$second=$gallery['photos'][1];
 verifyGallery(count($gallery['photos'])===3 && $first['height']===160 && $second['width']===160,'mixed photo proportions persist');
 verifyGallery(UserData::getData($owner->id,'profile_header')['media']===$header['media'] && is_file(base_path($header['media'])),'gallery saves preserve header image and file');
 verifyGallery(str_contains(view('linkstack.elements.profile-header',['userinfo'=>$owner])->render(),$header['media']) && str_contains(view('linkstack.elements.photo-gallery',['userinfo'=>$owner])->render(),$first['path']),'header and gallery both render on the public profile');
 Auth::login($other);
 saveGallery(['remove'=>[$first['id']],'captions'=>[$first['id']=>'Changed']]);
 verifyGallery(is_file(base_path($first['path'])) && ProfileGallery::get($owner->id)['photos'][0]['caption']==='','another account cannot edit or remove owner photos');
 Auth::login($owner);
 saveGallery(['captions'=>[$first['id']=>'<script>alert(1)</script>'],'positions'=>[$first['id']=>3,$second['id']=>1]]);
 verifyGallery(ProfileGallery::get($owner->id)['photos'][0]['id']===$second['id'],'display order saves');
 $html=view('linkstack.elements.photo-gallery',['userinfo'=>$owner])->render();
 verifyGallery(!str_contains($html,'<script>alert(1)</script>') && str_contains($html,'&lt;script&gt;'),'captions escape HTML');
 @mkdir(base_path('test-output'));
 file_put_contents(base_path('test-output/gallery.html'),'<meta name="viewport" content="width=device-width"><style>body{margin:0;padding:16px;box-sizing:border-box}.biolync-gallery{color:#fff}body{background:#151826}</style>'.$html);
 foreach ([UploadedFile::fake()->create('bad.php',1,'application/x-php'),UploadedFile::fake()->image('huge.jpg')->size(2049)] as $bad) {
  try {saveGallery([],['photos'=>[$bad]]);throw new RuntimeException('Invalid upload accepted');}catch(Illuminate\Validation\ValidationException $e) {}
 }
 verifyGallery(count(ProfileGallery::get($owner->id)['photos'])===3,'invalid and oversized uploads rejected without changing photos');
 $many=[];for($i=0;$i<10;$i++)$many[]=UploadedFile::fake()->image('photo.jpg');
 try {saveGallery([],['photos'=>$many]);throw new RuntimeException('Photo limit ignored');}catch(Illuminate\Validation\ValidationException $e) {}
 verifyGallery(count(ProfileGallery::get($owner->id)['photos'])===3,'total photo limit enforced');
 saveGallery(['enabled'=>0]);
 verifyGallery(!str_contains(view('linkstack.elements.photo-gallery',['userinfo'=>$owner])->render(),'biolync-gallery-columns') && is_file(base_path($first['path'])),'hiding gallery preserves photos');
 saveGallery(['remove'=>[$first['id']]]);
 verifyGallery(!is_file(base_path($first['path'])) && count(ProfileGallery::get($owner->id)['photos'])===2,'removal deletes only selected photo');
 verifyGallery(is_file(base_path($header['media'])) && UserData::getData($owner->id,'profile_header')['media']===$header['media'],'gallery removal leaves header intact');
 verifyGallery(!ProfileGallery::ownedPath($owner->id,'assets/profile-gallery/../secret.jpg'),'traversal path rejected');
 $standard=User::create(['name'=>'Standard','email'=>'standard@example.test','password'=>'unused']);
 Auth::login($standard);
 foreach(['show','save'] as $action) {
  try { $controller=new App\Http\Controllers\ProfileGalleryController; $action==='show' ? $controller->show() : saveGallery(); throw new RuntimeException('Standard user allowed gallery access'); }
  catch(Symfony\Component\HttpKernel\Exception\HttpException $e) {verifyGallery($e->getStatusCode()===403,'standard user gallery '.$action.' denied');}
 }
 $owner->role='user';$owner->save();
 verifyGallery(count(ProfileGallery::get($owner->id)['photos'])===0, 'demoted account gallery hidden publicly');
 $owner->role='admin';$owner->save();Auth::login($owner);
 verifyGallery(count(ProfileGallery::get($owner->id)['photos'])===2, 'admin gallery access and retained photos');
 $response=saveGallery();verifyGallery(str_ends_with($response->getTargetUrl(),'/studio/photo-gallery'),'gallery save returns to dedicated page');
 // Keep fixture assets for the browser job; all uploads are disposable CI files.
} catch (Throwable $e) {
 foreach(glob(base_path('assets/profile-gallery/*')) as $file) unlink($file);
 throw $e;
}
