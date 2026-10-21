@php($albumPhotos = \App\Support\HomeAlbum::items())
@if($albumPhotos)
<link rel="stylesheet" href="{{ asset('assets/css/home-album.css') }}">
<section class="home-album" aria-label="Featured photo album">
@foreach($albumPhotos as $photo)
<figure class="home-album-photo" style="--album-width:{{ (int)$photo['width'] }}px">
@if($photo['url'])<a class="home-album-link" href="{{ $photo['url'] }}" @if($photo['new_tab']) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $photo['alt'] ?: ($photo['caption'] ?: 'View featured photo') }}">@else<div class="home-album-link">@endif
<img class="home-album-image" src="{{ asset($photo['image']) }}" alt="{{ $photo['alt'] }}" loading="lazy" decoding="async">
@if($photo['icon'] && array_key_exists($photo['icon'], \App\Support\HomeAlbum::icons()))
<span class="home-album-logo"><img src="{{ asset('assets/linkstack/icons/'.$photo['icon'].'.svg') }}" alt="{{ \App\Support\HomeAlbum::icons()[$photo['icon']] }}"></span>
@endif
@if($photo['url'])</a>@else</div>@endif
@if($photo['caption'])<figcaption>{{ $photo['caption'] }}</figcaption>@endif
</figure>
@endforeach
</section>
@endif
