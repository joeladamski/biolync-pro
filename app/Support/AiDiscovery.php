<?php
namespace App\Support;

use App\Models\User;
use App\Models\Link;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;

class AiDiscovery
{
    public static function settings(): array
    {
        $saved=json_decode(Storage::disk('local')->get('ai-discovery.json') ?? '{}',true);
        return array_merge(['enabled'=>false,'introduction'=>'','context'=>'','featured_user_id'=>null],is_array($saved)?$saved:[]);
    }

    public static function saveSettings(array $changes): void
    {
        $disk=Storage::disk('local');$lockPath=$disk->path('ai-discovery.lock');
        if(!is_dir(dirname($lockPath)))mkdir(dirname($lockPath),0755,true);
        $lock=fopen($lockPath,'c');
        if(!$lock || !flock($lock,LOCK_EX))throw new \RuntimeException('Unable to lock AI Discovery settings.');
        $temp=null;
        try {
            $temp=tempnam(dirname($lockPath),'ai-discovery-');
            $json=json_encode(array_merge(self::settings(),$changes),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
            if($temp===false || file_put_contents($temp,$json)===false || !rename($temp,$disk->path('ai-discovery.json')))throw new \RuntimeException('Unable to save AI Discovery settings.');
        } finally {
            if($temp && is_file($temp))unlink($temp);
            flock($lock,LOCK_UN);fclose($lock);
        }
    }

    public static function publicUser($user): bool
    {
        return $user && $user->block !== 'yes' && trim((string)$user->littlelink_name)!=='';
    }

    public static function profileSettings(User $user): array
    {
        $metadata=json_decode($user->image ?? '{}',true);
        return array_merge(['enabled'=>false,'summary'=>'','context'=>''],is_array($metadata['ai_discovery'] ?? null)?$metadata['ai_discovery']:[]);
    }

    public static function publishedProfile($id): bool
    {
        $user=User::find($id);
        return self::settings()['enabled'] && self::publicUser($user) && self::profileSettings($user)['enabled'];
    }

    public static function featuredUrl(): string
    {
        $id=self::settings()['featured_user_id'];$user=$id ? User::find($id) : null;
        return self::publicUser($user) ? self::profileUrl($user) : url('/demo-page');
    }

    public static function profileUrl(User $user): string
    {
        return url('/@'.rawurlencode($user->littlelink_name));
    }

    public static function text($value): string
    {
        $value=preg_replace('#</(?:p|div|li|h[1-6])>|<br\s*/?>#i',"\n",(string)$value);
        $value=html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8');
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$value));
    }

    private static function label($value): string
    {
        return str_replace(['\\','[',']',"\r","\n"],['\\\\','\\[','\\]',' ',' '],self::text($value));
    }

    private static function link($label,$url): string
    {
        return '- ['.self::label($label).']('.str_replace(['(',')',' ',"\r","\n"],['%28','%29','%20','',''],$url).')';
    }

    private static function externalUrl($url): bool
    {
        return is_string($url) && filter_var($url,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($url,PHP_URL_SCHEME) ?? ''),['https','http'],true) && !preg_match('/[\x00-\x20]/',$url);
    }

    public static function profiles(): array
    {
        return User::orderBy('name')->get()->filter(fn($user)=>self::publicUser($user) && self::profileSettings($user)['enabled'])->all();
    }

    public static function profileText(User $user,bool $full=false): string
    {
        $settings=self::profileSettings($user);$base=self::profileUrl($user);
        $bio=self::text($user->littlelink_description);
        $summary=self::text($settings['summary']) ?: preg_replace('/\s+/u',' ',$bio);
        $out='# '.self::label($user->name)."\n\n";
        if($summary!=='')$out.='> '.str_replace("\n","\n> ",$summary)."\n\n";
        if(self::text($settings['context'])!=='')$out.=self::text($settings['context'])."\n\n";
        $out.="## Profile\n\n".self::link('Public profile',$base)."\n".self::link('Full profile context',$base.'/llms-full.txt')."\n";
        if($full && $bio!=='')$out.="\n## Biography\n\n".$bio."\n";
        $links=Link::where('user_id',$user->id)->orderBy('up_link')->orderBy('order')->get();
        $safe=$links->filter(fn($link)=>self::externalUrl($link->link));
        if($safe->count()) {
            $out.="\n## Links\n\n";
            foreach($safe as $link)$out.=self::link($link->title ?: $link->link,$link->link)."\n";
        }
        if($full) {
            $gallery=ProfileGallery::get($user->id);
            if(!empty($gallery['enabled']) && count($gallery['photos'])) {
                $out.="\n## Public gallery\n\n";
                foreach($gallery['photos'] as $index=>$photo)$out.=self::link($photo['caption'] ?: 'Photo '.($index+1),asset($photo['path']))."\n";
            }
        }
        return $out;
    }

    public static function siteText(bool $full=false): string
    {
        $settings=self::settings();$home=Page::first()?->home_message;
        $home=$home==='default' ? __('messages.HOME.MESSAGE') : $home;
        $intro=self::text($settings['introduction']) ?: preg_replace('/\s+/u',' ',self::text($home));
        $out='# '.self::label(config('app.name'))."\n\n";
        if($intro!=='')$out.='> '.str_replace("\n","\n> ",$intro)."\n\n";
        if(self::text($settings['context'])!=='')$out.=self::text($settings['context'])."\n\n";
        $out.="## Site\n\n".self::link('Homepage',url('/'))."\n".self::link('Full site context',url('/llms-full.txt'))."\n";
        foreach(SitePages::all() as $page) {
            if(!$page['published'])continue;
            $out.="\n## ".self::label($page['title'])."\n\n".self::link($page['title'],route('publicSitePage',['slug'=>$page['slug']]))."\n";
            if($full)$out.="\n".self::text($page['body'])."\n";
        }
        $profiles=self::profiles();
        if(count($profiles)) {
            $out.="\n## Profiles\n\n";
            foreach($profiles as $user)$out.=self::link($user->name,self::profileUrl($user).'/llms.txt')."\n";
            if($full)foreach($profiles as $user)$out.="\n---\n\n".self::profileText($user,true);
        }
        return $out;
    }
}
