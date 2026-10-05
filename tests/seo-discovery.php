<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e){fwrite(STDERR,(string)$e);exit(1);});
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','session.driver'=>'array','app.key'=>'base64:'.base64_encode(str_repeat('x',32))]);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use App\Models\User;
use App\Support\SeoDiscovery;

DB::purge();
Storage::fake('local');

Schema::create('users',function($t){
    $t->id();$t->string('name');$t->string('email');$t->string('password');
    $t->string('littlelink_name')->nullable();$t->text('littlelink_description')->nullable();
    $t->text('image')->nullable();$t->string('block')->default('no');$t->string('role')->default('user');$t->timestamps();
});
Schema::create('pages',function($t){$t->id();$t->text('home_message');});
DB::table('pages')->insert(['home_message'=>'Public home message']);

function seoCheck($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS SEO Discovery: $message\n";}

$user=User::create([
    'name'=>'Roxy Vega',
    'email'=>'private@example.test',
    'password'=>'secret',
    'littlelink_name'=>'roxy',
    'littlelink_description'=>'Miami photographer and creator.',
]);

$c=new App\Http\Controllers\SeoDiscoveryController;

$c->saveSite(Request::create('/admin/seo-discovery','POST',[
    'enabled'=>1,
    'title'=>'Pink Kiss Love',
    'description'=>'Creator discovery platform',
    'robots'=>'index,follow',
    'schema_enabled'=>1,
]));

seoCheck(SeoDiscovery::settings()['enabled']===true,'global SEO enabled');
$site=SeoDiscovery::siteMeta();
seoCheck($site['title']==='Pink Kiss Love' && $site['description']==='Creator discovery platform','site overrides resolve');

$c->saveProfile(Request::create('/admin/edit-user/1/seo-discovery','POST',[
    'indexable'=>1,
    'title'=>'Roxy Vega | Photographer',
]),$user->id);

$user->refresh();
$profile=SeoDiscovery::profileMeta($user);
seoCheck($profile['title']==='Roxy Vega | Photographer','profile title override resolves');
seoCheck($profile['description']==='Miami photographer and creator.','blank profile description stays automatic');

$c->saveProfile(Request::create('/admin/edit-user/1/seo-discovery','POST',['indexable'=>0]),$user->id);
$user->refresh();
seoCheck(SeoDiscovery::profileMeta($user)['robots']==='noindex,nofollow','profile can be excluded from indexing');

$c->saveProfile(Request::create('/admin/edit-user/1/seo-discovery','POST',['indexable'=>1,'reset'=>1]),$user->id);
$user->refresh();
seoCheck(SeoDiscovery::profileSettings($user)['title']==='','profile reset clears overrides');

foreach(['saveSeoSite','saveSeoProfile'] as $name){
    $middleware=app('router')->getRoutes()->getByName($name)->gatherMiddleware();
    seoCheck(in_array('auth',$middleware,true)&&in_array('admin',$middleware,true),'admin protection for '.$name);
}
