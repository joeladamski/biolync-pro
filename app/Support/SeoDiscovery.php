<?php
namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class SeoDiscovery
{
    public static function settings(): array
    {
        $saved=json_decode(Storage::disk('local')->get('seo-discovery.json') ?? '{}',true);
        return array_merge([
            'enabled'=>false,
            'title'=>'',
            'description'=>'',
            'canonical_url'=>'',
            'robots'=>'index,follow',
            'og_title'=>'',
            'og_description'=>'',
            'og_image'=>'',
            'schema_enabled'=>true,
        ],is_array($saved)?$saved:[]);
    }

    public static function saveSettings(array $changes): void
    {
        $disk=Storage::disk('local');$lockPath=$disk->path('seo-discovery.lock');
        if(!is_dir(dirname($lockPath)))mkdir(dirname($lockPath),0755,true);
        $lock=fopen($lockPath,'c');
        if(!$lock || !flock($lock,LOCK_EX))throw new \RuntimeException('Unable to lock SEO Discovery settings.');
        $temp=null;
        try {
            $temp=tempnam(dirname($lockPath),'seo-discovery-');
            $json=json_encode(array_merge(self::settings(),$changes),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
            if($temp===false || file_put_contents($temp,$json)===false || !rename($temp,$disk->path('seo-discovery.json')))throw new \RuntimeException('Unable to save SEO Discovery settings.');
        } finally {
            if($temp && is_file($temp))unlink($temp);
            flock($lock,LOCK_UN);fclose($lock);
        }
    }

    public static function profileSettings(User $user): array
    {
        $metadata=json_decode($user->image ?? '{}',true);
        return array_merge([
            'indexable'=>true,
            'title'=>'',
            'description'=>'',
            'canonical_url'=>'',
            'robots'=>'',
        ],is_array($metadata['seo_discovery'] ?? null)?$metadata['seo_discovery']:[]);
    }

    public static function text($value): string
    {
        $value=preg_replace('#</(?:p|div|li|h[1-6])>|<br\s*/?>#i',"\n",(string)$value);
        $value=html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8');
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$value));
    }

    private static function siteDescription(): string
    {
        $home=\App\Models\Page::first()?->home_message;
        $home=$home==='default' ? __('messages.HOME.MESSAGE') : $home;
        return preg_replace('/\s+/u',' ',self::text($home));
    }

    private static function imageUrl(string $configured=''): string
    {
        if($configured!=='' && filter_var($configured,FILTER_VALIDATE_URL))return $configured;
        if(function_exists('findFile') && file_exists(base_path('assets/linkstack/images/'.findFile('avatar'))))return asset('assets/linkstack/images/'.findFile('avatar'));
        return asset('assets/linkstack/images/logo.svg');
    }

    public static function siteMeta(): array
    {
        $s=self::settings();
        $title=self::text($s['title']) ?: config('app.name');
        $description=self::text($s['description']) ?: self::siteDescription();
        return [
            'title'=>$title,
            'description'=>$description,
            'canonical'=>$s['canonical_url'] ?: url('/'),
            'robots'=>$s['robots'] ?: 'index,follow',
            'og_title'=>self::text($s['og_title']) ?: $title,
            'og_description'=>self::text($s['og_description']) ?: $description,
            'og_image'=>self::imageUrl((string)$s['og_image']),
        ];
    }

    public static function profileMeta(User $user): array
    {
        $global=self::settings();$s=self::profileSettings($user);
        $description=self::text($s['description']) ?: preg_replace('/\s+/u',' ',self::text($user->littlelink_description));
        $title=self::text($s['title']) ?: trim($user->name.' | '.config('app.name'));
        $robots=!$s['indexable'] ? 'noindex,nofollow' : (self::text($s['robots']) ?: ($global['robots'] ?: 'index,follow'));
        $avatar=file_exists(base_path(findAvatar($user->id))) ? url(findAvatar($user->id)) : self::imageUrl((string)$global['og_image']);
        return [
            'title'=>$title,
            'description'=>$description,
            'canonical'=>$s['canonical_url'] ?: url('/@'.rawurlencode($user->littlelink_name)),
            'robots'=>$robots,
            'og_title'=>$title,
            'og_description'=>$description,
            'og_image'=>$avatar,
        ];
    }

    public static function siteSchema(): array
    {
        $m=self::siteMeta();
        return [
            '@context'=>'https://schema.org',
            '@type'=>'WebSite',
            'name'=>$m['title'],
            'url'=>$m['canonical'],
            'description'=>$m['description'],
        ];
    }

    public static function profileSchema(User $user): array
    {
        $m=self::profileMeta($user);
        return [
            '@context'=>'https://schema.org',
            '@type'=>'Person',
            'name'=>$user->name,
            'url'=>$m['canonical'],
            'description'=>$m['description'],
            'image'=>$m['og_image'],
        ];
    }

    public static function diagnostics(?User $user=null): array
    {
        $m=$user ? self::profileMeta($user) : self::siteMeta();
        return [
            'title'=>['ok'=>mb_strlen($m['title'])>0 && mb_strlen($m['title'])<=65,'label'=>'Title is present and within 65 characters'],
            'description'=>['ok'=>mb_strlen($m['description'])>0 && mb_strlen($m['description'])<=170,'label'=>'Description is present and within 170 characters'],
            'canonical'=>['ok'=>filter_var($m['canonical'],FILTER_VALIDATE_URL)!==false,'label'=>'Canonical URL is valid'],
            'robots'=>['ok'=>trim($m['robots'])!=='','label'=>'Robots directive is configured'],
        ];
    }
}
