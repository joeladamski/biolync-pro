<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Support\SitePages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
Storage::fake('local');
$c = new App\Http\Controllers\SitePageController;
$data=['title'=>'About us','nav_label'=>'About','slug'=>'about','body'=>"<script>alert(1)</script>\nOur story",'published'=>1,'show_in_header'=>1,'order'=>2];
$save=fn($d)=>$c->save(Request::create('/admin/site-pages','POST',$d));
$save($data);
$id=SitePages::all()[0]['id'];
$draft=$data;$draft['slug']='draft';$draft['published']=0;$save($draft);
$hidden=$data;$hidden['slug']='hidden';$hidden['show_in_header']=0;$save($hidden);
$first=$data;$first['slug']='first';$first['order']=0;$save($first);
if(array_column(SitePages::navigation(),'slug')!==['first','about'])throw new RuntimeException('Navigation visibility or order incorrect');
try{$c->show('draft');throw new RuntimeException('Draft accessible');}catch(Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e){}
$html=$c->show('about')->render();
if(str_contains($html,'<script>alert(1)</script>')||!str_contains($html,'&lt;script&gt;')||!str_contains($html,'/pages/first'))throw new RuntimeException('Public content escaping or menu failed');
foreach(['about','terms','../bad'] as $slug){$bad=$data;$bad['slug']=$slug;try{$save($bad);throw new RuntimeException('Invalid or duplicate slug accepted');}catch(Illuminate\Validation\ValidationException $e){}}
if(count(SitePages::all())!==4)throw new RuntimeException('Rejected save changed pages');
$data['id']=$id;$data['title']='Updated';$save($data);
if($c->show('about')->getData()['sitePage']['title']!=='Updated')throw new RuntimeException('Edit did not persist');
$c->delete(Request::create('/admin/site-pages/delete','POST',['id'=>$id]));
try{$c->show('about');throw new RuntimeException('Deleted page accessible');}catch(Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e){}
foreach(['saveSitePage','deleteSitePage'] as $name){$route=app('router')->getRoutes()->getByName($name);foreach(['auth','admin'] as $middleware){if(!in_array($middleware,$route->gatherMiddleware(),true))throw new RuntimeException('Missing admin protection');}}
echo "PASS site pages: persistence, drafts, navigation, escaping, validation, edits, deletion and admin middleware\n";
