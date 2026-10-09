@php
$header = \App\Models\UserData::getData(Auth::id(), 'profile_header');
$header = is_array($header) ? $header : [];
$validHeader = !empty($header['media'])
    && preg_match('#^assets/profile-media/' . Auth::id() . '_[a-f0-9]{32}\\.(jpg|jpeg|png|webp|mp4|webm)$#', $header['media'])
    && file_exists(base_path($header['media']));
$defaultHeader = 'assets/images/dashboard/top-header-overlay.png';
@endphp
<div class="card mb-4">
  <div class="card-body">
    <h4>Public Profile Header</h4>
    <p>Choose the cover visitors see above your profile. If you do not upload one, PinkKiss.Love uses the system header shown below.</p>

    <div class="mb-4" style="max-width:600px">
      @if($validHeader && ($header['type'] ?? '') === 'video')
        <video src="{{ asset($header['media']) }}" controls muted playsinline preload="metadata" @if(!empty($header['poster'])) poster="{{ asset($header['poster']) }}" @endif style="width:100%;height:200px;object-fit:cover;border-radius:12px"></video>
      @elseif($validHeader)
        <img src="{{ asset($header['media']) }}" alt="Current profile cover" style="width:100%;height:200px;object-fit:cover;object-position:{{ $header['position'] ?? 'center' }};border-radius:12px">
      @else
        <img src="{{ asset($defaultHeader) }}" alt="PinkKiss.Love default profile cover" style="width:100%;height:200px;object-fit:cover;object-position:center;border-radius:12px">
        <div class="form-text mt-2">Default PinkKiss.Love header currently in use.</div>
      @endif
    </div>

    <form action="{{ route('profileHeader') }}" method="post" enctype="multipart/form-data">
      @csrf
      <label class="form-label" for="header-media">Image or video</label>
      <input class="form-control mb-2" id="header-media" name="header_media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm">
      <p class="form-text">JPEG, PNG, WebP, MP4 or WebM, up to 10MB. Recommended: 1200 × 440 image or a 5–12 second silent clip.</p>

      <label class="form-label" for="header-poster">Video poster image</label>
      <input class="form-control mb-2" id="header-poster" name="header_poster" type="file" accept="image/jpeg,image/png,image/webp">
      <p class="form-text">Optional video fallback image, up to 2MB.</p>

      <label class="form-label" for="header-position">Crop position</label>
      <select class="form-control mb-3" id="header-position" name="header_position">
        @foreach(['center' => 'Center', 'top' => 'Top', 'bottom' => 'Bottom'] as $value => $label)
        <option value="{{ $value }}" @selected(old('header_position', $header['position'] ?? 'center') === $value)>{{ $label }}</option>
        @endforeach
      </select>

      @if($validHeader)
      <div class="form-check mb-3">
        <input id="remove-header" class="form-check-input" type="checkbox" name="remove_header" value="1">
        <label class="form-check-label" for="remove-header">Remove my custom header and restore the PinkKiss.Love default</label>
      </div>
      @endif

      <button class="btn btn-primary" type="submit">Save header</button>
    </form>
  </div>
</div>
