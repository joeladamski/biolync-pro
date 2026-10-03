@php($gallery = \App\Support\ProfileGallery::get(Auth::id()))
<div class="card mb-4"><div class="card-body">
<h4>Photo Gallery <span class="badge bg-primary">VIP</span></h4>
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p>Share up to 12 photos below your links. Three columns on desktop, two on mobile; photos keep their original proportions. Your header, avatar and gallery are saved separately.</p>
@if(session('gallery_success'))<div class="alert alert-success">{{ session('gallery_success') }}</div>@endif
<form action="{{ route('savePhotoGallery') }}" method="POST" enctype="multipart/form-data">
@csrf
<input type="hidden" name="enabled" value="0">
<label class="d-block mb-3"><input type="checkbox" name="enabled" value="1" @if(old('enabled', $gallery['enabled'] ?? false)) checked @endif> Show gallery on my public profile</label>
<div class="row">
@foreach($gallery['photos'] as $index => $photo)
<div class="col-6 col-md-4 mb-3">
<img src="{{ asset($photo['path']) }}" alt="Gallery photo {{ $index + 1 }}" loading="lazy" style="width:100%;height:150px;object-fit:contain">
<label class="form-label" for="caption-{{ $photo['id'] }}">Caption / image description</label>
<input class="form-control mb-2" id="caption-{{ $photo['id'] }}" name="captions[{{ $photo['id'] }}]" maxlength="200" value="{{ old('captions.'.$photo['id'], $photo['caption'] ?? '') }}">
<label for="position-{{ $photo['id'] }}" class="form-label">Display order</label>
<input type="number" class="form-control mb-2" id="position-{{ $photo['id'] }}" name="positions[{{ $photo['id'] }}]" min="1" max="12" value="{{ old('positions.'.$photo['id'], $index + 1) }}">
<label><input type="checkbox" name="remove[]" value="{{ $photo['id'] }}"> Remove photo</label>
</div>
@endforeach
</div>
<label class="form-label" for="gallery-photos">Add photos</label>
<input id="gallery-photos" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="form-control mb-2">
<p class="small">JPEG, PNG or WebP. Up to 2 MB per photo and 6000 pixels per side. Convert iPhone HEIC photos to JPEG before uploading. Uploaded files have public URLs; hiding the gallery does not revoke direct links.</p>
<button class="btn btn-primary" type="submit">Save gallery</button>
</form>
</div></div>
