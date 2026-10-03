<h2 class="mb-4 card-header">Homepage UI Controls</h2>
<p>Edit the labels and destinations in the default phone preview. Button styles and icons stay as configured in Advanced Config.</p>
@if(session('home_ui_saved'))<div class="alert alert-success">Homepage links saved.</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(config('advanced-config.use_custom_buttons') != 'true')
<div class="alert alert-info">Enable use_custom_buttons in Advanced Config to display these buttons.</div>
@endif
<form method="POST" action="{{ route('editHomeUi') }}">
  @csrf
  @foreach(\App\Support\HomeUi::buttons() as $index => $button)
  <fieldset class="border rounded p-3 mb-3">
    <legend class="float-none w-auto px-2 fs-5">Button {{ $index + 1 }} — {{ $button['button'] }}</legend>
    <label for="home-title-{{ $index }}" class="form-label">Label</label>
    <input id="home-title-{{ $index }}" class="form-control mb-3" name="buttons[{{ $index }}][title]" value="{{ old('buttons.'.$index.'.title', $button['title']) }}" maxlength="100" required>
    <label for="home-link-{{ $index }}" class="form-label">Destination URL</label>
    <input id="home-link-{{ $index }}" class="form-control" type="url" name="buttons[{{ $index }}][link]" value="{{ old('buttons.'.$index.'.link', $button['link']) }}" maxlength="2048" placeholder="https://" required>
  </fieldset>
  @endforeach
  <button class="btn btn-primary" type="submit">Save homepage links</button>
</form>
