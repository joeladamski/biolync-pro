@php
$header = \App\Models\UserData::getData(Auth::id(), 'profile_header');
$header = is_array($header) ? $header : [];
@endphp
<div class="card mb-4"><div class="card-body">
<h4>Public profile header</h4>
<p>Optional image or silent video cover. Displayed up to 600px wide with your avatar overlapping the lower edge.</p>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('profileHeader') }}" method="post" enctype="multipart/form-data">
@csrf
<label for="header-media">Image or video</label>
<input class="form-control mb-3" id="header-media" name="header_media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
<p>JPEG, PNG, WebP, MP4 or WebM, up to 10MB. Recommended: 1200 × 440 image or a 5–12 second silent clip. Leave blank to keep the current media.</p>
<label for="header-poster">Video poster image</label>
<input class="form-control mb-3" id="header-poster" name="header_poster" type="file" accept="image/jpeg,image/png,image/webp">
<p>Optional fallback image, up to 2MB.</p>
<label for="header-position">Crop position</label>
<select class="form-control mb-3" id="header-position" name="header_position">
@foreach(['center' => 'Center', 'top' => 'Top', 'bottom' => 'Bottom'] as $value => $label)
<option value="{{ $value }}" @selected(old('header_position', $header['position'] ?? 'center') === $value)>{{ $label }}</option>
@endforeach
</select>
@if(!empty($header['media']))<p>Current header: {{ ($header['type'] ?? '') === 'video' ? 'Video' : 'Image' }}</p>@endif
<div class="mb-3"><input id="remove-header" type="checkbox" name="remove_header" value="1"> <label for="remove-header">Remove header and poster</label></div>
<button class="btn btn-primary" type="submit">Save header</button>
</form>
</div></div>
