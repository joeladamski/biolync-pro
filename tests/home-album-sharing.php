<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e){fwrite(STDERR,(string)$e);exit(1);});
use App\Support\HomeAlbum;
use App\Support\RuntimeMedia;
use App\Support\SeoDiscovery;
use App\Support\SocialPreview;
use App\Support\SiteBranding;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
function albumCheck($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS album/sharing: $message\n";}
$root=sys_get_temp_dir().'/album-sharing-'.bin2hex(random_bytes(5));
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array','app.key'=>'base64:'.base64_encode(str_repeat('x',32)),
 'estate-media.profile_path'=>$root.'/media','estate-media.branding_path'=>$root.'/branding','linkstack.disable_random_user_ids'=>'true','app.name'=>'BioLync']);
DB::purge();Storage::fake('local');
Schema::create('pages',function($t){$t->id();$t->text('home_message');});
DB::table('pages')->insert(['home_message'=>'<p>Hello &amp;#13; &amp;#13; world 💋</p><p>AT&amp;T creators</p>']);
Schema::create('users',function($t){$t->id();$t->string('name');$t->string('email');$t->string('littlelink_name');$t->text('image')->nullable();$t->text('littlelink_description')->nullable();$t->string('block')->default('no');$t->timestamps();});
$user=App\Models\User::create(['name'=>'Mara','email'=>'mara@example.test','littlelink_name'=>'mara']);
$fixtures=getenv('ALBUM_UI_DIR') ?: '/tmp/biolync-album-ui';@mkdir($fixtures,0750,true);
$image=imagecreatetruecolor(800,500);
imagefill($image,0,0,imagecolorallocate($image,55,19,75));
imagefilledellipse($image,460,220,500,500,imagecolorallocate($image,235,88,155));
imagefilledrectangle($image,0,360,800,500,imagecolorallocate($image,27,60,70));
imagepng($image,$fixtures.'/album.png');imagedestroy($image);
function photoUpload($fixtures){$temp=tempnam(sys_get_temp_dir(),'album-upload-');copy($fixtures.'/album.png',$temp);return new UploadedFile($temp,'photo.png','image/png',UPLOAD_ERR_OK,true);}
$controller=new App\Http\Controllers\HomeAlbumController;
$rows=[];foreach([580,250,250,400] as $i=>$width)$rows[]=['width'=>$width,'alt'=>'Featured photo '.($i+1),'caption'=>'Meet the community '.($i+1),'url'=>'https://example.test/creator/'.$i,'icon'=>'instagram','new_tab'=>1,'order'=>$i];
$request=Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$rows]);
$fileRows=[];foreach($rows as $i=>$row)$fileRows[$i]=['image'=>photoUpload($fixtures)];$request->files->set('album',$fileRows);
$response=$controller->save($request);
albumCheck($response->getStatusCode()===302 && count(HomeAlbum::items())===4,'real uploads save album and redirect');
$saved=HomeAlbum::settings()['items'];
foreach($saved as $item){$path=HomeAlbum::path($item['image']);albumCheck(is_file($path) && str_starts_with($path,$root.'/media'),'album media persists outside deployment root');$served=(new App\Http\Controllers\RuntimeMediaController)->show(substr($item['image'],7));albumCheck($served->headers->get('Content-Type')==='image/png','album image is public via runtime route');copy($path,$fixtures.'/'.basename($path));}
$paths=array_column($saved,'image');
foreach($saved as &$item)$item['caption']='Saved caption';unset($item);
$controller->save(Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$saved]));
albumCheck(array_column(HomeAlbum::items(),'image')===$paths,'metadata-only save preserves image files');
$bad=$saved;$bad[0]['url']='javascript:alert(1)';
try{$controller->save(Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$bad]));throw new RuntimeException('Unsafe destination accepted');}catch(Illuminate\Validation\ValidationException $e){albumCheck(HomeAlbum::items()[0]['caption']==='Saved caption','unsafe link rejected without changing album');}
$bad=$saved;$bad[0]['icon']='../../evil';
try{$controller->save(Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$bad]));throw new RuntimeException('Unsafe logo accepted');}catch(Illuminate\Validation\ValidationException $e){}
$bad=$saved;$bad[0]['width']=999;
try{$controller->save(Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$bad]));throw new RuntimeException('Unsafe width accepted');}catch(Illuminate\Validation\ValidationException $e){}
$badRequest=Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>[['width'=>250]]]);$badRequest->files->set('album',[0=>['image'=>UploadedFile::fake()->createWithContent('evil.svg','<svg onload="alert(1)"/>')]]);
try{$controller->save($badRequest);throw new RuntimeException('SVG accepted');}catch(Illuminate\Validation\ValidationException $e){}
albumCheck(HomeAlbum::path('../../.env')===null && !RuntimeMedia::allowed('assets/home-album/../../.env'),'media path traversal rejected');
$album=view('components.home-album')->render();
albumCheck(str_contains($album,'target="_blank" rel="noopener noreferrer"') && str_contains($album,'home-album-logo'),'linked photos and social logos render');
$form=view('components.config.home-album',['errors'=>new Illuminate\Support\ViewErrorBag])->render();
file_put_contents($fixtures.'/album.html','<!doctype html><title>BioLync album QA</title><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/assets/css/hope-ui.min.css"><style>body{margin:0;background:#230033;color:white;padding:10px}main{max-width:620px;margin:auto}</style><main><h1>Featured community</h1><p>Photo album below the description</p>'.$album.'</main>');
file_put_contents($fixtures.'/admin.html','<!doctype html><title>BioLync album settings QA</title><meta name="viewport" content="width=device-width"><link rel="stylesheet" href="/assets/css/hope-ui.min.css"><style>body{margin:0;background:#232838;color:white;padding:20px}form{max-width:900px;margin:auto}label{max-width:100%}</style>'.$form);
$replace=Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$saved]);$replace->files->set('album',[0=>['image'=>photoUpload($fixtures)]]);$controller->save($replace);
albumCheck(!is_file(HomeAlbum::path($paths[0])) && is_file(HomeAlbum::path(HomeAlbum::items()[0]['image'])),'replacement cleans old image after saving');
$rows=HomeAlbum::settings()['items'];$removed=$rows[1]['image'];$rows[1]['remove']=1;$controller->save(Request::create('/admin/home-album','POST',['album_enabled'=>0,'album'=>$rows]));
albumCheck(HomeAlbum::items()===[] && count(HomeAlbum::settings()['items'])===3 && !is_file(HomeAlbum::path($removed)),'remove deletes selected photo while hide preserves others');
$rows=HomeAlbum::settings()['items'];$rows[0]['id']='0000000000000000';try{$controller->save(Request::create('/admin/home-album','POST',['album_enabled'=>1,'album'=>$rows]));throw new RuntimeException('Unknown photo ID accepted');}catch(Illuminate\Validation\ValidationException $e){}

albumCheck(SeoDiscovery::text('<p>Welcome &amp;#13; &amp;#13; friends 💋</p><p>A&nbsp;B &amp; C</p>')==='Welcome friends 💋 A B & C','encoded line breaks normalize without removing emoji');
$seo=new App\Http\Controllers\SeoDiscoveryController;
$request=Request::create('/admin/seo-discovery','POST',['enabled'=>0,'schema_enabled'=>1]);$request->files->set('share_image_upload',photoUpload($fixtures));$seo->saveSite($request);
albumCheck(SiteBranding::exists('share'),'uploaded social artwork persists');
$meta=SeoDiscovery::siteMeta();albumCheck(!str_contains($meta['description'],'&#13;') && str_contains($meta['description'],'💋'),'automatic home metadata is clean');
albumCheck(str_contains($meta['og_image'],'/social-preview/site.jpg?v='),'automatic social image uses versioned public JPEG instead of SVG');
$public=new App\Http\Controllers\SocialPreviewController;
foreach([$public->site(),$public->profile((string)$user->id)] as $response){$info=getimagesizefromstring($response->getContent());albumCheck($response->headers->get('Content-Type')==='image/jpeg' && $info[0]===1200 && $info[1]===630,'public share endpoint returns real 1200x630 JPEG');}
$before=SocialPreview::url();$seo->refresh();albumCheck($before!==SocialPreview::url() && SeoDiscovery::settings()['enabled']===false,'refresh changes share cache key without enabling search overrides');
$html=view('components.seo.social-head',['seo'=>SeoDiscovery::siteMeta()])->render();albumCheck(substr_count($html,'property="og:image"')===1 && str_contains($html,'image/jpeg') && str_contains($html,'1200'),'server-side rich-link metadata includes image dimensions');
$view=view('components.config.seo-discovery',['errors'=>new Illuminate\Support\ViewErrorBag])->render();albumCheck(str_contains($view,'Refresh saved preview') && str_contains($view,'Message card preview'),'SEO refresh and message card controls render');
DB::table('users')->where('id',$user->id)->update(['block'=>'yes']);try{$public->profile((string)$user->id);throw new RuntimeException('Blocked profile exposed');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){albumCheck($e->getStatusCode()===404,'blocked profile share image returns 404');}
$seo->saveSite(Request::create('/admin/seo-discovery','POST',['enabled'=>0,'schema_enabled'=>1,'remove_share_image'=>1]));albumCheck(!SiteBranding::exists('share'),'share artwork removal persists');
foreach(['saveHomeAlbum','refreshSeoPreview'] as $name){$middleware=app('router')->getRoutes()->getByName($name)->gatherMiddleware();albumCheck(in_array('auth',$middleware,true)&&in_array('admin',$middleware,true),'admin protection for '.$name);}
