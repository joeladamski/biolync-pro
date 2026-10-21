<?php
namespace App\Support;

use Illuminate\Support\Facades\Storage;

class HomeAlbum
{
    public static function icons(): array
    {
        return [''=>'No logo','instagram'=>'Instagram','facebook'=>'Facebook','twitter'=>'X / Twitter','tiktok'=>'TikTok','youtube'=>'YouTube','linkedin'=>'LinkedIn','pinterest'=>'Pinterest','snapchat'=>'Snapchat','discord'=>'Discord','website'=>'Website'];
    }

    public static function settings(): array
    {
        $saved=json_decode(Storage::disk('local')->get('home-album.json') ?? '{}',true);
        return array_replace(['enabled'=>true,'items'=>[]],is_array($saved)?$saved:[]);
    }

    public static function path(?string $logical): ?string
    {
        return $logical && preg_match('#^assets/home-album/[a-f0-9]{32}\.(jpg|png|webp)$#D',$logical) ? RuntimeMedia::path($logical) : null;
    }

    public static function items(): array
    {
        $settings=self::settings();
        if (!$settings['enabled']) return [];
        return array_values(array_filter($settings['items'],fn($item)=>($path=self::path($item['image'] ?? null)) && is_file($path)));
    }

    /** Persist images and their references under one lock; only delete old files after publication. */
    public static function save(array $rows, array $uploads, bool $enabled): void
    {
        $disk=Storage::disk('local');$lockPath=$disk->path('home-album.lock');
        if(!is_dir(dirname($lockPath)))mkdir(dirname($lockPath),0750,true);
        $lock=fopen($lockPath,'c');
        if(!$lock)throw new \RuntimeException('Cannot open homepage album lock.');
        $created=[];$temporary=null;
        try {
            if(!flock($lock,LOCK_EX))throw new \RuntimeException('Cannot lock homepage album.');
            $old=self::settings()['items'];$known=array_column($old,null,'id');$items=[];
            foreach($rows as $index=>$row) {
                if(!empty($row['remove']))continue;
                $id=$row['id'] ?? '';
                $previous=$known[$id] ?? null;
                if($id!=='' && !$previous)throw \Illuminate\Validation\ValidationException::withMessages(['album'=>'This album changed in another tab. Refresh before saving.']);
                $logical=$previous['image'] ?? null;
                if(isset($uploads[$index])) {
                    $upload=$uploads[$index];
                    $extension=$upload->extension()==='jpeg'?'jpg':$upload->extension();
                    $logical='assets/home-album/'.bin2hex(random_bytes(16)).'.'.$extension;
                    $upload->move(RuntimeMedia::path('assets/home-album'),basename($logical));
                    $created[]=self::path($logical);
                }
                if(!$logical)continue;
                $items[]=['id'=>$previous['id'] ?? bin2hex(random_bytes(8)),'image'=>$logical,
                    'width'=>(int)$row['width'],'alt'=>trim($row['alt'] ?? ''),'caption'=>trim($row['caption'] ?? ''),
                    'url'=>trim($row['url'] ?? ''),'new_tab'=>!empty($row['new_tab']),'icon'=>$row['icon'] ?? '',
                    'order'=>(int)($row['order'] ?? $index)];
            }
            usort($items,fn($a,$b)=>$a['order']<=>$b['order']);
            $temporary='home-album.'.bin2hex(random_bytes(8)).'.tmp';
            if(!$disk->put($temporary,json_encode(['enabled'=>$enabled,'items'=>$items],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)) || !$disk->move($temporary,'home-album.json'))throw new \RuntimeException('Cannot save homepage album.');
            $created=[];
            $keep=array_column($items,'image');
            foreach($old as $item)if(!in_array($item['image'],$keep,true) && ($path=self::path($item['image'])) && is_file($path))@unlink($path);
        } finally {
            foreach($created as $path)if($path && is_file($path))@unlink($path);
            if($temporary)$disk->delete($temporary);
            flock($lock,LOCK_UN);fclose($lock);
        }
    }
}
