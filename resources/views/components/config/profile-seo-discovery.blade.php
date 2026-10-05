@php($profileSeo = \App\Support\SeoDiscovery::profileSettings($user))
@php($profileSeoMeta = \App\Support\SeoDiscovery::profileMeta($user))
<section id="seo-discovery" class="border rounded p-3 mt-4">
<h3>SEO Discovery</h3>
<p>Admin-only search controls for this public profile. Leave overrides blank to keep the profile synchronized with the user's public name and bio.</p>
@if(session('seo_success'))<div class="alert alert-success">{{ session('seo_success') }}</div>@endif
<form action="{{ route('saveSeoProfile', ['id' => $user->id]) }}" method="POST">
@csrf
<input type="hidden" name="indexable" value="0">
<label class="d-block mb-3"><input type="checkbox" name="indexable" value="1" @checked(old('indexable', $profileSeo['indexable']))> Allow this profile to be indexed</label>

<label for="profile-seo-title" class="form-label">SEO title override (optional)</label>
<input id="profile-seo-title" name="title" class="form-control mb-3" maxlength="120" value="{{ old('title', $profileSeo['title']) }}">

<label for="profile-seo-description" class="form-label">Meta description override (optional)</label>
<textarea id="profile-seo-description" name="description" class="form-control mb-3" rows="3" maxlength="500">{{ old('description', $profileSeo['description']) }}</textarea>

<label for="profile-seo-canonical" class="form-label">Canonical URL override (optional)</label>
<input id="profile-seo-canonical" name="canonical_url" type="url" class="form-control mb-3" maxlength="500" value="{{ old('canonical_url', $profileSeo['canonical_url']) }}" placeholder="{{ url('/@'.$user->littlelink_name) }}">

<label for="profile-seo-robots" class="form-label">Robots override (optional)</label>
<input id="profile-seo-robots" name="robots" class="form-control mb-3" maxlength="120" value="{{ old('robots', $profileSeo['robots']) }}" placeholder="Uses global SEO Discovery directive">

<button class="btn btn-primary" type="submit">Save profile SEO Discovery</button>
<button class="btn btn-outline-secondary" type="submit" name="reset" value="1">Reset overrides to automatic</button>
</form>

<h4 class="mt-4">Resolved preview</h4>
<p><strong>Title:</strong> {{ $profileSeoMeta['title'] }}<br>
<strong>Description:</strong> {{ $profileSeoMeta['description'] }}<br>
<strong>Canonical:</strong> {{ $profileSeoMeta['canonical'] }}<br>
<strong>Robots:</strong> {{ $profileSeoMeta['robots'] }}</p>

<ul class="list-unstyled">
@foreach(\App\Support\SeoDiscovery::diagnostics($user) as $check)
<li class="mb-2">{{ $check['ok'] ? '✓' : '⚠' }} {{ $check['label'] }}</li>
@endforeach
</ul>
</section>
