<fieldset class="border rounded p-3 mb-3 album-admin-row">
<legend class="float-none w-auto px-2 fs-5">Photo</legend>
<input type="hidden" name="album[{{ $index }}][id]" value="{{ $photo['id'] ?? '' }}">
@if(!empty($photo['image']))<img src="{{ asset($photo['image']) }}" alt="Saved album photo" style="max-width:150px;max-height:100px;border-radius:15px" class="mb-3">@endif
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Upload or replace image<input class="form-control" type="file" name="album[{{ $index }}][image]" accept="image/jpeg,image/png,image/webp"></label></div>
<div class="col-md-3"><label class="form-label">Width preset<select class="form-select" data-album-preset><option value="580" @selected(($photo['width'] ?? 250)==580)>Wide — 580px</option><option value="250" @selected(($photo['width'] ?? 250)==250)>Inline — 250px</option><option value="custom" @selected(!in_array($photo['width'] ?? 250,[580,250]))>Custom</option></select></label></div>
<div class="col-md-3"><label class="form-label">Width (120–580px)<input class="form-control" type="number" name="album[{{ $index }}][width]" data-album-width min="120" max="580" value="{{ $photo['width'] ?? 250 }}" required></label></div>
<div class="col-md-6"><label class="form-label">Image description (alt text)<input class="form-control" name="album[{{ $index }}][alt]" maxlength="200" value="{{ $photo['alt'] ?? '' }}"></label></div>
<div class="col-md-6"><label class="form-label">Caption (optional)<input class="form-control" name="album[{{ $index }}][caption]" maxlength="200" value="{{ $photo['caption'] ?? '' }}"></label></div>
<div class="col-md-6"><label class="form-label">Destination URL (optional)<input class="form-control" type="url" name="album[{{ $index }}][url]" maxlength="2048" placeholder="https://" value="{{ $photo['url'] ?? '' }}"></label></div>
<div class="col-md-3"><label class="form-label">Logo<select class="form-select" name="album[{{ $index }}][icon]">@foreach(\App\Support\HomeAlbum::icons() as $value=>$label)<option value="{{ $value }}" @selected(($photo['icon'] ?? '')===$value)>{{ $label }}</option>@endforeach</select></label></div>
<div class="col-md-3"><label class="form-label">Display order<input class="form-control" type="number" min="0" max="999" name="album[{{ $index }}][order]" value="{{ $photo['order'] ?? $index }}"></label></div>
</div>
<label class="me-3 mt-3"><input type="checkbox" name="album[{{ $index }}][new_tab]" value="1" @checked($photo['new_tab'] ?? true)> Open link in a new tab</label>
<label class="mt-3"><input type="checkbox" name="album[{{ $index }}][remove]" value="1"> Remove this photo when saved</label>
<button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-album-discard>Discard row</button>
</fieldset>
