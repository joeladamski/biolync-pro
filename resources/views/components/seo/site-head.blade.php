@php($seo = \App\Support\SeoDiscovery::siteMeta())
<title>{{ $seo['title'] }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
@include('components.seo.social-head')
@if(\App\Support\SeoDiscovery::settings()['schema_enabled'])
<script type="application/ld+json">{!! json_encode(\App\Support\SeoDiscovery::siteSchema(), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
