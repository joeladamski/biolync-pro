<?php
namespace App\Support;

use App\Models\User;

class SocialPreview
{
    public static function source(?User $user=null): ?string
    {
        $paths=$user ? [RuntimeMedia::path(findAvatar($user->id))] : [SiteBranding::path('share')];
        $paths[]=SiteBranding::path('avatar');$paths[]=SiteBranding::path('favicon');
        foreach($paths as $path) {
            if(!$path || !is_file($path) || is_link($path) || filesize($path)>8*1024*1024)continue;
            $info=@getimagesize($path);
            if($info && in_array($info['mime'],['image/png','image/jpeg','image/webp','image/gif'],true) && $info[0]*$info[1]<=16000000)return $path;
        }
        return null;
    }

    public static function url(?User $user=null): string
    {
        $source=self::source($user);
        $version=substr(hash('sha256',($source ? hash_file('sha256',$source) : config('app.name')).'|'.($user?->name ?? '').'|'.(SeoDiscovery::settings()['share_revision'] ?? '')),0,16);
        return ($user ? route('socialProfileImage',['user'=>$user->id]) : route('socialSiteImage')).'?v='.$version;
    }

    public static function jpeg(?User $user=null): string
    {
        $source=self::source($user);$image=$source ? ProfileIcon::raster($source) : false;
        $canvas=imagecreatetruecolor(1200,630);
        imagefill($canvas,0,0,imagecolorallocate($canvas,51,0,59));
        try {
            if($image) {
                $w=imagesx($image);$h=imagesy($image);
                // Preserve the complete artwork; pad portraits instead of cutting off faces or logos.
                $scale=min(1200/$w,630/$h);$dw=(int)round($w*$scale);$dh=(int)round($h*$scale);
                imagecopyresampled($canvas,$image,(int)((1200-$dw)/2),(int)((630-$dh)/2),0,0,$dw,$dh,$w,$h);
            } else {
                $name=substr(preg_replace('/[^\x20-\x7E]/','',SeoDiscovery::text($user?->name ?: config('app.name'))),0,40) ?: 'BioLync';
                $label=imagecreatetruecolor(400,40);imagefill($label,0,0,imagecolorallocate($label,51,0,59));
                imagestring($label,5,(int)((400-9*strlen($name))/2),10,$name,imagecolorallocate($label,255,255,255));
                imagecopyresampled($canvas,$label,0,255,0,0,1200,120,400,40);imagedestroy($label);
            }
            ob_start();try{imagejpeg($canvas,null,88);return ob_get_contents();}finally{ob_end_clean();}
        } finally {if($image)imagedestroy($image);imagedestroy($canvas);}
    }
}
