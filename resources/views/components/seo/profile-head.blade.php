@php($seo = \App\Support\SeoDiscovery::profileMeta($userinfo))
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="author" content="{{ $userinfo->name }}">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
@include('components.seo.social-head')
@if(\App\Support\SeoDiscovery::settings()['schema_enabled'])
<script type="application/ld+json">{!! json_encode(\App\Support\SeoDiscovery::profileSchema($userinfo), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
