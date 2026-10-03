<h2 class="mb-4 card-header">Homepage UI Controls</h2>
<p>Edit the labels and destinations in the default phone preview. Button styles and icons stay as configured in Advanced Config.</p>
@if(session('home_ui_saved'))<div class="alert alert-success">Homepage settings saved.</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(config('advanced-config.use_custom_buttons') != 'true')
<div class="alert alert-info">Enable use_custom_buttons in Advanced Config to display these buttons.</div>
@endif
<form method="POST" action="{{ route('editHomeUi') }}">
  @csrf
  @php($previewCopy = \App\Support\HomeUi::previewCopy())
  <fieldset class="border rounded p-3 mb-4">
    <legend class="float-none w-auto px-2 fs-5">Text beneath the phone preview</legend>
    <p>Optional text for the preview column. Leave a field blank to hide it.</p>
    <label for="preview-title" class="form-label">Title</label>
    <input id="preview-title" class="form-control mb-3" name="preview_copy[title]" maxlength="120" value="{{ old('preview_copy.title', $previewCopy['title']) }}">
    <label for="preview-tagline" class="form-label">Tagline</label>
    <input id="preview-tagline" class="form-control mb-3" name="preview_copy[tagline]" maxlength="200" value="{{ old('preview_copy.tagline', $previewCopy['tagline']) }}">
    <label for="preview-description" class="form-label">Description</label>
    <textarea id="preview-description" class="form-control" name="preview_copy[description]" maxlength="2000" rows="4">{{ old('preview_copy.description', $previewCopy['description']) }}</textarea>
  </fieldset>
  @foreach(\App\Support\HomeUi::buttons() as $index => $button)
  <fieldset class="border rounded p-3 mb-3">
    <legend class="float-none w-auto px-2 fs-5">Button {{ $index + 1 }} — {{ $button['button'] }}</legend>
    <label for="home-title-{{ $index }}" class="form-label">Label</label>
    <input id="home-title-{{ $index }}" class="form-control mb-3" name="buttons[{{ $index }}][title]" value="{{ old('buttons.'.$index.'.title', $button['title']) }}" maxlength="100" required>
    <label for="home-link-{{ $index }}" class="form-label">Destination URL</label>
    <input id="home-link-{{ $index }}" class="form-control" type="url" name="buttons[{{ $index }}][link]" value="{{ old('buttons.'.$index.'.link', $button['link']) }}" maxlength="2048" placeholder="https://" required>
  </fieldset>
  @endforeach
  <button class="btn btn-primary" type="submit">Save homepage settings</button>
</form>
