@php($seo = \App\Support\SeoDiscovery::profileMeta($userinfo))
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="author" content="{{ $userinfo->name }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:type" content="profile">
<meta property="og:title" content="{{ $seo['og_title'] }}">
<meta property="og:description" content="{{ $seo['og_description'] }}">
<meta property="og:image" content="{{ $seo['og_image'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['og_title'] }}">
<meta name="twitter:description" content="{{ $seo['og_description'] }}">
<meta name="twitter:image" content="{{ $seo['og_image'] }}">
@if(\App\Support\SeoDiscovery::settings()['schema_enabled'])
<script type="application/ld+json">{!! json_encode(\App\Support\SeoDiscovery::profileSchema($userinfo), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
