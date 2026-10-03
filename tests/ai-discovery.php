<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e){fwrite(STDERR,(string)$e);exit(1);});
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array','app.key'=>'base64:'.base64_encode(str_repeat('x',32)),'linkstack.disable_random_user_ids'=>'true']);
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserData;
use App\Support\AiDiscovery;
use App\Support\SitePages;
DB::purge();Storage::fake('local');
Schema::create('users',function($t){$t->id();$t->string('name');$t->string('email');$t->string('password');$t->string('littlelink_name')->nullable();$t->text('littlelink_description')->nullable();$t->text('image')->nullable();$t->string('block')->default('no');$t->string('role')->default('user');$t->timestamps();});
Schema::create('links',function($t){$t->id();$t->integer('user_id');$t->text('title');$t->text('link');$t->string('up_link')->default('no');$t->integer('order')->default(0);});
Schema::create('pages',function($t){$t->id();$t->text('home_message');});
DB::table('pages')->insert(['home_message'=>'Public home message']);
function aiCheck($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS AI Discovery: $message\n";}
function aiNotFound($callback){try{$callback();throw new RuntimeException('Expected 404');}catch(Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e){}}
$owner=User::create(['name'=>'Jay','email'=>'SECRET-ACCOUNT-EMAIL','password'=>'SECRET-PASSWORD','littlelink_name'=>'jay','littlelink_description'=>'<p>Public biography</p>']);
$other=User::create(['name'=>'Other','email'=>'other@example.test','password'=>'unused','littlelink_name'=>'other','littlelink_description'=>'UNPUBLISHED-PROFILE']);
$owner->littlelink_description='<p>Public biography</p>';$owner->save();
$other->littlelink_description='UNPUBLISHED-PROFILE';$other->save();
UserData::saveData($owner->id,'profile_header',['position'=>'top']);UserData::saveData($owner->id,'private_note','PRIVATE-NOTE');UserData::saveData($owner->id,'photo_gallery',['enabled'=>false,'photos'=>[]]);
DB::table('links')->insert([['user_id'=>$owner->id,'title'=>'Project','link'=>'https://example.com/project','order'=>0],['user_id'=>$owner->id,'title'=>'Unsafe','link'=>'javascript:alert(1)','order'=>1]]);
SitePages::update(fn($pages)=>[['id'=>'a','title'=>'About','slug'=>'about','body'=>'Public page body','published'=>true,'show_in_header'=>true,'order'=>0],['id'=>'b','title'=>'Draft','slug'=>'draft','body'=>'DRAFT-PAGE-CONTENT','published'=>false,'show_in_header'=>false,'order'=>1]]);
$c=new App\Http\Controllers\AiDiscoveryController;
aiNotFound(fn()=>$c->site());aiNotFound(fn()=>$c->profile('jay'));
aiCheck(str_ends_with(AiDiscovery::featuredUrl(),'/demo-page'),'initial demo fallback');
$c->saveFeatured(Request::create('/admin/featured-profile','POST',['featured_user_id'=>$owner->id]));
aiCheck(str_ends_with(AiDiscovery::featuredUrl(),'/@jay'),'featured public account selected');
$c->saveSite(Request::create('/admin/ai-discovery','POST',['enabled'=>1,'introduction'=>'Platform overview','context'=>'Public platform context']));
aiCheck(AiDiscovery::settings()['featured_user_id']===$owner->id,'AI settings preserve featured selection');
aiNotFound(fn()=>$c->profile('jay'));
$c->saveProfile(Request::create('/admin/edit-user/1/ai-discovery','POST',['enabled'=>1,'summary'=>'Admin summary','context'=>'Admin context']),$owner->id);
$owner->refresh();$short=$c->profile('jay')->getContent();$full=$c->profile('jay',true)->getContent();
aiCheck(str_contains($short,'Admin summary') && str_contains($short,'Admin context') && str_contains($short,'https://example.com/project') && str_contains($full,'Public biography'),'profile overrides, links and full biography generated');
aiCheck(UserData::getData($owner->id,'profile_header')['position']==='top','AI edits preserve header metadata');
aiCheck(UserData::getData($owner->id,'photo_gallery')===['enabled'=>false,'photos'=>[]],'AI edits preserve gallery metadata');
$site=$c->site(true)->getContent();
foreach(['SECRET-ACCOUNT-EMAIL','SECRET-PASSWORD','PRIVATE-NOTE','UNPUBLISHED-PROFILE','DRAFT-PAGE-CONTENT','javascript:','Verified user'] as $private)aiCheck(!str_contains($site,$private),'output excludes '.$private);
aiCheck(str_contains($site,'Public page body') && str_contains($site,'/@jay/llms.txt'),'published pages and opted-in profiles included');
$owner->littlelink_description='<p>Updated biography</p>';$owner->save();
$c->saveProfile(Request::create('/admin/edit-user/1/ai-discovery','POST',['enabled'=>1,'reset'=>1]),$owner->id);
aiCheck(str_contains($c->profile('jay')->getContent(),'Updated biography') && !str_contains($c->profile('jay')->getContent(),'Admin summary'),'reset resumes automatic content');
$owner->block='yes';$owner->save();aiNotFound(fn()=>$c->profile('jay'));
aiCheck(!str_contains($c->site(true)->getContent(),'Updated biography') && str_ends_with(AiDiscovery::featuredUrl(),'/demo-page'),'blocked profile excluded and featured demo restored');
try{$c->saveFeatured(Request::create('/admin/featured-profile','POST',['featured_user_id'=>$owner->id]));throw new RuntimeException('Blocked feature allowed');}catch(Illuminate\Validation\ValidationException $e){}
$owner->block='no';$owner->save();
foreach(['/llms.txt','/llms-full.txt','/@jay/llms.txt','/@jay/llms-full.txt'] as $path){$response=app('router')->dispatch(Request::create($path));aiCheck($response->getStatusCode()===200 && str_starts_with($response->headers->get('Content-Type'),'text/plain') && str_contains($response->headers->get('Cache-Control'),'no-store'),'public route '.$path);if(str_contains($path,'full'))aiCheck(str_contains($response->getContent(),str_starts_with($path,'/@')?'## Biography':'Public page body'),'full route includes expanded content '.$path);}
foreach(['saveAiSite','saveAiProfile','saveFeaturedProfile'] as $name){$middleware=app('router')->getRoutes()->getByName($name)->gatherMiddleware();aiCheck(in_array('auth',$middleware,true)&&in_array('admin',$middleware,true),'admin protection for '.$name);}
$html=view('components.config.profile-ai-discovery',['user'=>$owner->fresh(),'errors'=>new Illuminate\Support\ViewErrorBag])->render();
aiCheck(str_contains($html,'Save profile AI Discovery') && str_contains($html,'/admin/edit-user/'.$owner->id.'/ai-discovery'),'profile editor saves separately through Users');
$c->saveProfile(Request::create('/admin/edit-user/1/ai-discovery','POST',['enabled'=>0]),$owner->id);aiNotFound(fn()=>$c->profile('jay'));
$c->saveSite(Request::create('/admin/ai-discovery','POST',['enabled'=>0,'reset'=>1]));aiNotFound(fn()=>$c->site());
aiCheck(AiDiscovery::settings()['introduction']==='' && AiDiscovery::settings()['featured_user_id']===$owner->id,'global disable and reset preserve homepage feature');

AiDiscovery::saveSettings(['featured_user_id'=>$other->id]);$other->delete();
aiCheck(str_ends_with(AiDiscovery::featuredUrl(),'/demo-page'),'deleted featured account falls back to demo');
try{$c->saveProfile(Request::create('/admin/edit-user/1/ai-discovery','POST',['enabled'=>1,'summary'=>str_repeat('x',2001)]),$owner->id);throw new RuntimeException('Oversized summary allowed');}catch(Illuminate\Validation\ValidationException $e){}
aiCheck(!AiDiscovery::profileSettings($owner->fresh())['enabled'],'invalid edit leaves publication unchanged');
