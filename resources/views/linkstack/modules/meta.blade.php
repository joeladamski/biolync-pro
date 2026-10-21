<meta charset="utf-8">

{{-- Fediverse rel="me" links --}}
@php
  $relMe = "mastodon, firefish, streams, friendica";
  $relMeList = explode(', ', $relMe);
@endphp

@foreach($links as $link)
  @if(in_array($link->name, $relMeList))
    <link href="{{$link->link}}" rel="me">
  @endif
@endforeach

@if(\App\Support\SeoDiscovery::settings()['enabled'])
  @include('components.seo.profile-head')
@else
@if(env('CUSTOM_META_TAGS') == 'true')
  @include('layouts.meta')
@else
  <meta name="description" content="{{ strip_tags($userinfo->littlelink_description) }}">
  <meta name="author" content="{{ $userinfo->name }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
@endif

@include('components.seo.social-head', ['seo' => \App\Support\SeoDiscovery::profileMeta($userinfo)])

@if(config('advanced-config.linkstack_title') != '' and env('HOME_URL') === '')
<title>{{ $userinfo->name }} {{ config('advanced-config.linkstack_title') }}</title>
@elseif(env('CUSTOM_META_TAGS') == 'true' and config('advanced-config.title') != '')
<title>{{ config('advanced-config.title') }}</title>
@elseif(env('HOME_URL') != '')
<title>{{ $userinfo->name }}</title>
@else
<title>{{ $userinfo->name }} 🔗 {{ config('app.name') }} </title>
@endif

@endif

@include('components.favicon')
@include('components.favicon-extension')

@include('components.profile-icons')

@include('layouts.analytics')
@if(\App\Support\AiDiscovery::publishedProfile($userinfo->id))
<link rel="describedby" href="{{ route('aiProfileSummary', ['littlelink' => $userinfo->littlelink_name]) }}">
@endif
