<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function(Throwable $e) { fwrite(STDERR, (string)$e); exit(1); });

use App\Support\ProfileIcon;
use App\Support\RuntimeMedia;
use App\Support\SiteBranding;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function iconCheck($ok, $message) {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS profile icons: $message\n";
}
$root = sys_get_temp_dir().'/profile-icons-'.bin2hex(random_bytes(6));
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:',
    'estate-media.profile_path'=>$root.'/media','estate-media.branding_path'=>$root.'/branding']);
DB::purge();
Schema::create('users', function($t) {
    $t->id(); $t->string('name'); $t->string('littlelink_name'); $t->string('block')->nullable();
});
DB::table('users')->insert(['id'=>987654321,'name'=>'Creator','littlelink_name'=>'creator','block'=>'no']);
$user = User::findOrFail(987654321);
$path = RuntimeMedia::path('assets/img').'/987654321_icon.png';
$image = imagecreatetruecolor(300, 100);
imagefill($image, 0, 0, imagecolorallocate($image,255,0,0));
imagefilledrectangle($image,100,0,199,99,imagecolorallocate($image,0,255,0));
imagepng($image,$path); imagedestroy($image);
iconCheck(ProfileIcon::source($user)===$path,'persistent profile avatar takes priority');
$oldUrl=ProfileIcon::url($user,180);
$controller = new App\Http\Controllers\ProfileIconController;
foreach (['32','180'] as $size) {
    $response=$controller->show((string)$user->id,$size);
    $data=$response->getContent(); $info=getimagesizefromstring($data);
    iconCheck($response->headers->get('Content-Type')==='image/png' && $info[0]===(int)$size && $info[1]===(int)$size,'endpoint returns square PNG at '.$size);
    $decoded=imagecreatefromstring($data);
    iconCheck((imagecolorat($decoded,0,0)&0xFFFFFF)===0x00FF00,'wide avatars are center cropped rather than stretched');
    imagedestroy($decoded);
}
$image=imagecreatetruecolor(80,80);imagefill($image,0,0,imagecolorallocate($image,0,0,255));
imagepng($image,$path);imagedestroy($image);clearstatcache();
iconCheck(ProfileIcon::url($user,180)!==$oldUrl,'avatar replacement changes icon URL');
$html=view('components.profile-icons',['userinfo'=>$user])->render();
iconCheck(substr_count($html,'rel="icon"')===1 && str_contains($html,'rel="apple-touch-icon"') && str_contains($html,'180x180'),'profile head declares both tab and Apple touch icons');
iconCheck(str_contains($html,htmlspecialchars(ProfileIcon::url($user,180),ENT_QUOTES,'UTF-8')),'touch icon points to this profile');
unlink($path);
@mkdir($root.'/branding',0750,true);
$image=imagecreatetruecolor(40,40);imagepng($image,$root.'/branding/favicon.png');imagedestroy($image);
iconCheck(ProfileIcon::source($user)===SiteBranding::path('favicon'),'missing avatar falls back to estate branding');
$png=ProfileIcon::png(null,180);$info=getimagesizefromstring($png);
iconCheck($info[0]===180 && $info['mime']==='image/png','SVG-only estates still have a raster fallback');
foreach (['yes',''] as $state) {
    if($state==='yes') DB::table('users')->where('id',$user->id)->update(['block'=>'yes']);
    else DB::table('users')->where('id',$user->id)->update(['block'=>'no','littlelink_name'=>'']);
    try { $controller->show((string)$user->id,'180'); throw new RuntimeException('Hidden profile icon exposed'); }
    catch(Symfony\Component\HttpKernel\Exception\HttpException $e) { iconCheck($e->getStatusCode()===404,'hidden profile icon returns 404'); }
}
try { $controller->show((string)$user->id,'9999'); throw new RuntimeException('Unbounded size accepted'); }
catch(Symfony\Component\HttpKernel\Exception\HttpException $e) { iconCheck($e->getStatusCode()===404,'unbounded dimensions rejected'); }
$route=app('router')->getRoutes()->getByName('profileIcon');
iconCheck($route && $route->matches(Illuminate\Http\Request::create('/profile-icons/987654321/180.png')),'public PNG route resolves');

$jpeg=$root.'/phone.jpg';
$image=imagecreatetruecolor(120,80);
$colors=[[240,20,20],[20,240,20],[20,20,240],[240,240,20]];
foreach($colors as $i=>$rgb) imagefilledrectangle($image,($i%2)*60,(int)($i/2)*40,($i%2)*60+59,(int)($i/2)*40+39,imagecolorallocate($image,...$rgb));
ob_start();imagejpeg($image,null,100);$original=ob_get_clean();imagedestroy($image);
$expected=[1=>[0,1,2,3],2=>[1,0,3,2],3=>[3,2,1,0],4=>[2,3,0,1],5=>[0,2,1,3],6=>[2,0,3,1],7=>[3,1,2,0],8=>[1,3,0,2]];
foreach(['little','big'] as $endian) foreach($expected as $orientation=>$corners) {
    $short=$endian==='little'?'v':'n';$long=$endian==='little'?'V':'N';
    $exif="Exif\0\0".($endian==='little'?'II':'MM').pack($short,42).pack($long,8).pack($short,1)
        .pack($short,0x112).pack($short,3).pack($long,1).pack($short,$orientation)."\0\0".pack($long,0);
    file_put_contents($jpeg,substr($original,0,2)."\xFF\xE1".pack('n',strlen($exif)+2).$exif.substr($original,2));
    $icon=imagecreatefromstring(ProfileIcon::png($jpeg,180));
    foreach([[30,30],[150,30],[30,150],[150,150]] as $i=>$point) {
        $pixel=imagecolorat($icon,...$point);
        $actual=[($pixel>>16)&255,($pixel>>8)&255,$pixel&255];
        foreach($actual as $channel=>$value) iconCheck(abs($value-$colors[$corners[$i]][$channel])<20,'JPEG orientation '.$orientation.' '.$endian.' corner '.$i.' channel '.$channel);
    }
    imagedestroy($icon);
}
file_put_contents($jpeg,"\xFF\xD8\xFF\xE1\x00\x20Exif\0\0II");
$info=getimagesizefromstring(ProfileIcon::png($jpeg,180));
iconCheck($info[0]===180,'malformed JPEG metadata falls back safely');
