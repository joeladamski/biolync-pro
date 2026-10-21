@php($homeAlbum = \App\Support\HomeAlbum::settings())
<section class="border rounded p-3 mt-4" id="homepage-album-settings">
<h3>Photo album beneath the spotlight description</h3>
<p>Up to 24 photos. Wide (580px), inline (250px), or a custom width. Photos wrap and center automatically, with 15px corners. JPEG, PNG or WebP; up to 8 MB per photo. Save a few uploads at a time if your host limits request size.</p>
@if(session('home_album_saved'))<div class="alert alert-success">Homepage album saved.</div>@endif
<form method="POST" action="{{ route('saveHomeAlbum') }}" enctype="multipart/form-data" id="home-album-form">
@csrf
<input type="hidden" name="album_enabled" value="0">
<label class="mb-3"><input type="checkbox" name="album_enabled" value="1" @checked($homeAlbum['enabled'])> Show album on the homepage</label>
<div id="home-album-rows">
@foreach($homeAlbum['items'] as $index=>$photo)@include('components.config.home-album-row')@endforeach
</div>
<template id="home-album-row-template">@include('components.config.home-album-row',['index'=>'__INDEX__','photo'=>[]])</template>
<button class="btn btn-outline-primary" id="home-album-add" type="button">Add photo</button>
<button class="btn btn-primary" type="submit">Save homepage album</button>
<p class="mt-2" id="home-album-status" role="status"></p>
</form>
</section>
<script src="{{ asset('assets/js/home-album.js') }}" defer></script>
