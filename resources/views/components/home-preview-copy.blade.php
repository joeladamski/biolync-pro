@php($previewCopy = \App\Support\HomeUi::previewCopy())
@if($previewCopy['title'] !== '' || $previewCopy['tagline'] !== '' || $previewCopy['description'] !== '')
<div class="home-preview-copy">
@if($previewCopy['title'] !== '')<h2>{{ $previewCopy['title'] }}</h2>@endif
@if($previewCopy['tagline'] !== '')<p class="home-preview-tagline">{{ $previewCopy['tagline'] }}</p>@endif
@if($previewCopy['description'] !== '')<p class="home-preview-description">{{ $previewCopy['description'] }}</p>@endif
</div>
@endif
