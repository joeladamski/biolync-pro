@php($seoSettings = \App\Support\SeoDiscovery::settings())
@php($seoMeta = \App\Support\SeoDiscovery::siteMeta())
<h2 class="mb-4 card-header">SEO Discovery</h2>
<p>Fine-tune how this domain is represented to conventional search engines. SEO Discovery is separate from AI Discovery but both use the same public site and profile content as their source of truth.</p>
@if(session('seo_success'))<div class="alert alert-success">{{ session('seo_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<form method="POST" action="{{ route('saveSeoSite') }}" enctype="multipart/form-data">
@csrf
<input type="hidden" name="enabled" value="0">
<input type="hidden" name="schema_enabled" value="0">
<label class="d-block mb-3"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $seoSettings['enabled']))> Enable managed search metadata</label>

<label for="seo-title" class="form-label">Site title override (optional)</label>
<input id="seo-title" name="title" class="form-control mb-3" maxlength="120" value="{{ old('title', $seoSettings['title']) }}">

<label for="seo-description" class="form-label">Meta description override (optional)</label>
<textarea id="seo-description" name="description" class="form-control mb-3" rows="3" maxlength="500">{{ old('description', $seoSettings['description']) }}</textarea>

<label for="seo-canonical" class="form-label">Canonical URL override (optional)</label>
<input id="seo-canonical" name="canonical_url" type="url" class="form-control mb-3" maxlength="500" value="{{ old('canonical_url', $seoSettings['canonical_url']) }}" placeholder="{{ url('/') }}">

<label for="seo-robots" class="form-label">Default robots directive</label>
<input id="seo-robots" name="robots" class="form-control mb-3" maxlength="120" value="{{ old('robots', $seoSettings['robots']) }}" placeholder="index,follow">

<h3 class="mt-4">Social and message preview</h3>
<p>Open Graph metadata supplies rich links for Messages on iPhone and supported social apps, even when managed search metadata is off. Each app controls whether and when it displays or caches a preview.</p>
<label for="seo-og-title" class="form-label">Open Graph title override (optional)</label>
<input id="seo-og-title" name="og_title" class="form-control mb-3" maxlength="120" value="{{ old('og_title', $seoSettings['og_title']) }}">

<label for="seo-og-description" class="form-label">Open Graph description override (optional)</label>
<textarea id="seo-og-description" name="og_description" class="form-control mb-3" rows="3" maxlength="500">{{ old('og_description', $seoSettings['og_description']) }}</textarea>

<label for="seo-og-image" class="form-label">Open Graph image URL override (optional)</label>
<input id="seo-og-image" name="og_image" type="url" class="form-control mb-3" maxlength="500" value="{{ old('og_image', $seoSettings['og_image']) }}">

<label for="seo-share-upload" class="form-label">Upload share artwork (optional)</label>
<input id="seo-share-upload" type="file" name="share_image_upload" class="form-control mb-2" accept="image/jpeg,image/png,image/webp">
<p>JPEG, PNG or WebP up to 2 MB. A 1200 × 630 image works well. With no URL override, this artwork or the site avatar is published as a public JPEG.</p>
@if(\App\Support\SiteBranding::exists('share'))<label class="d-block mb-3"><input type="checkbox" name="remove_share_image" value="1"> Remove uploaded share artwork</label>@endif
<label class="d-block mb-3"><input type="checkbox" name="schema_enabled" value="1" @checked(old('schema_enabled', $seoSettings['schema_enabled']))> Publish JSON-LD structured data</label>

<p>Blank overrides automatically use the current site name, home message and site artwork. This keeps SEO tuned without creating a second content source.</p>
<button class="btn btn-primary" type="submit">Save SEO Discovery</button>
<button class="btn btn-outline-secondary" type="submit" name="reset" value="1">Reset overrides to automatic</button>
</form>

<div class="d-flex gap-2 flex-wrap mt-3">
<form method="POST" action="{{ route('refreshSeoPreview') }}">@csrf<button class="btn btn-outline-primary" type="submit">Refresh saved preview</button></form>
<a class="btn btn-outline-secondary" href="{{ url('/') }}" target="_blank" rel="noopener noreferrer">Open homepage</a>
</div>
<p class="mt-2">Refresh reloads saved content and changes the image cache key. It cannot clear an app's cached link card. Save any edits first.</p>
<h3 class="mt-4">Message card preview</h3>
<figure class="border rounded overflow-hidden" style="max-width:580px">
<img src="{{ $seoMeta['og_image'] }}" alt="Share artwork preview" style="width:100%;height:auto;display:block" loading="lazy">
<figcaption class="p-3"><small>{{ parse_url($seoMeta['canonical'], PHP_URL_HOST) }}</small><h4>{{ $seoMeta['og_title'] }}</h4><p>{{ $seoMeta['og_description'] }}</p></figcaption>
</figure>
<h3 class="mt-4">Resolved preview</h3>
<dl class="row">
<dt class="col-sm-3">Title</dt><dd class="col-sm-9">{{ $seoMeta['title'] }}</dd>
<dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $seoMeta['description'] }}</dd>
<dt class="col-sm-3">Canonical</dt><dd class="col-sm-9">{{ $seoMeta['canonical'] }}</dd>
<dt class="col-sm-3">Robots</dt><dd class="col-sm-9">{{ $seoMeta['robots'] }}</dd>
</dl>

<h3 class="mt-4">Discovery diagnostics</h3>
<ul class="list-unstyled">
@foreach(\App\Support\SeoDiscovery::diagnostics() as $check)
<li class="mb-2">{{ $check['ok'] ? '✓' : '⚠' }} {{ $check['label'] }}</li>
@endforeach
</ul>
