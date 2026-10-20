<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $error){fwrite(STDERR,(string)$error);exit(1);});

use App\Http\Middleware\AuditUserChanges;
use App\Support\ActivityAudit;
use App\Support\ActivityMonitoring;
use App\Support\PublicSiteStyle;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function auditUploadCheck($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS upload audit: $message\n";}
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:',
    'cache.default'=>'array','session.driver'=>'array','linkstack.disable_random_user_ids'=>'true',
    'app.key'=>'base64:'.base64_encode(str_repeat('x',32)),
    'estate-media.profile_path'=>sys_get_temp_dir().'/upload-audit-'.bin2hex(random_bytes(6))]);
Storage::fake('local');DB::purge();
Schema::create('users',function($t){$t->id();$t->string('name');$t->string('email');$t->string('role')->default('admin');$t->timestamps();});
(require base_path('database/migrations/2026_10_09_130000_create_activity_logs_table.php'))->up();
$user=App\Models\User::create(['name'=>'Owner','email'=>'owner@example.test']);Auth::setUser($user);
ActivityMonitoring::save(['enabled'=>true,'discord_enabled'=>false]);
$source=UploadedFile::fake()->image('source.png',32,32);
$temporary=tempnam(sys_get_temp_dir(),'real-upload-');copy($source->getPathname(),$temporary);
$size=filesize($temporary);
// Use the real UploadedFile implementation: Laravel's testing File overrides getSize().
$upload=new UploadedFile($temporary,'background.png','image/png',UPLOAD_ERR_OK,true);
$request=Request::create('/admin/public-site-styles','POST',array_merge(PublicSiteStyle::defaults(),['password'=>'never-log-this']));
$request->files->set('background_image_upload',$upload);
$request->setUserResolver(fn()=>$user);
$route=(new Route('POST','admin/public-site-styles',fn()=>null))->name('savePublicSiteStyles');
$request->setRouteResolver(fn()=>$route);
app()->instance('request',$request);
$response=(new AuditUserChanges)->handle($request,fn($request)=>(new App\Http\Controllers\PublicSiteStyleController)->save($request));
auditUploadCheck($response->getStatusCode()===302,'real upload completes with redirect when monitoring is enabled');
auditUploadCheck(!is_file($temporary),'controller moves the temporary upload');
$path=PublicSiteStyle::backgroundPath(PublicSiteStyle::settings()['background_image']);
auditUploadCheck(is_file($path),'background image and setting survive audited upload');
$log=ActivityLog::firstOrFail();$metadata=$log->changes['background_image_upload'];
auditUploadCheck($metadata['file']==='background.png' && $metadata['size']===$size && $metadata['mime']==='image/png','audit keeps file metadata captured before move');
auditUploadCheck($log->changes['password']==='[REDACTED]','snapshot retains sensitive-field redaction');
clearstatcache(true,$temporary);
$failed=false;try{$upload->getSize();}catch(Throwable $error){$failed=true;}
auditUploadCheck($failed,'post-move file-size access reproduces the original failure');
$safe=ActivityAudit::sanitize(['nested'=>['upload'=>$upload]]);
auditUploadCheck($safe['nested']['upload']['size']===null,'direct sanitization tolerates moved uploads too');
// Logging errors after a successful change must not replace the response.
Schema::drop('activity_logs');Schema::create('activity_logs',fn($t)=>$t->id());
$plain=Request::create('/admin/public-site-styles','POST',['title'=>'Saved change']);
$plain->setUserResolver(fn()=>$user);$plain->setRouteResolver(fn()=>$route);app()->instance('request',$plain);
$saved=(new AuditUserChanges)->handle($plain,fn()=>response('Saved',200));
auditUploadCheck($saved->getStatusCode()===200 && $saved->getContent()==='Saved','audit storage failure preserves successful response');
$propagated=false;
try{(new AuditUserChanges)->handle($plain,function(){throw new RuntimeException('Controller failure');});}
catch(RuntimeException $error){$propagated=$error->getMessage()==='Controller failure';}
auditUploadCheck($propagated,'controller failures remain visible');
PublicSiteStyle::reset();
