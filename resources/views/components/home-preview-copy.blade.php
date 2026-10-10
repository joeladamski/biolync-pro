@php($previewCopy = \App\Support\HomeUi::previewCopy())
@if($previewCopy['title'] !== '' || $previewCopy['tagline'] !== '' || $previewCopy['description'] !== '')
<div class="home-preview-copy">
@if($previewCopy['title'] !== '')<h2>{{ $previewCopy['title'] }}</h2>@endif
@if($previewCopy['tagline'] !== '')<p class="home-preview-tagline">{{ $previewCopy['tagline'] }}</p>@endif
@if($previewCopy['description'] !== '')<div class="home-preview-description pk-editorial">{!! \App\Support\RichText::render($previewCopy['description'], $previewCopy['description_format']) !!}</div>@endif
</div>
@endif
