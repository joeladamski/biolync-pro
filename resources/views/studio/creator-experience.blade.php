@php
$audio = \App\Models\UserData::getData(Auth::id(), 'profile_audio');
$audio = is_array($audio) ? $audio : [];
$live = \App\Models\UserData::getData(Auth::id(), 'live_cta');
$live = is_array($live) ? $live : [];
$fx = \App\Models\UserData::getData(Auth::id(), 'vip_emoji_fx');
$fx = is_array($fx) ? $fx : [];
$fxEmojis = is_array($fx['emojis'] ?? null) ? $fx['emojis'] : ['💋', '💗', '✨'];
@endphp

<div class="card mt-4 mb-4">
  <div class="card-body">
    <h4 class="mb-2"><i class="bi bi-stars"></i> Creator Experience</h4>
    <p class="text-muted">Featured content that appears above or around your normal profile links.</p>
    <form action="{{ route('saveCreatorExperience') }}" method="post">
      @csrf

      <fieldset class="border rounded p-3 mb-4">
        <legend class="float-none w-auto px-2 fs-5">💋 Featured Live CTA</legend>
        <div class="form-check form-switch mb-3">
          <input type="hidden" name="live_enabled" value="0">
          <input class="form-check-input" type="checkbox" id="live-enabled" name="live_enabled" value="1" @checked(old('live_enabled', $live['enabled'] ?? false))>
          <label class="form-check-label" for="live-enabled">Show featured live button above my links</label>
        </div>
        <label class="form-label" for="live-label">Primary label</label>
        <input class="form-control mb-3" id="live-label" name="live_label" maxlength="40" value="{{ old('live_label', $live['label'] ?? 'LIVE TONIGHT') }}">
        <label class="form-label" for="live-invitation">Invitation</label>
        <input class="form-control mb-3" id="live-invitation" name="live_invitation" maxlength="120" value="{{ old('live_invitation', $live['invitation'] ?? 'Catch me here tonight') }}" list="pk-live-invitations">
        <datalist id="pk-live-invitations">
          <option value="Catch me here tonight">
          <option value="Visit me here tonight">
          <option value="Watch me live">
          <option value="Join me tonight">
          <option value="See me tonight">
        </datalist>
        <label class="form-label" for="live-venue">Venue / platform</label>
        <input class="form-control mb-3" id="live-venue" name="live_venue" maxlength="160" value="{{ old('live_venue', $live['venue'] ?? '') }}" placeholder="Venue, Twitch, YouTube Live…">
        <label class="form-label" for="live-description">Short description</label>
        <textarea class="form-control mb-3" id="live-description" name="live_description" maxlength="600" rows="3">{{ old('live_description', $live['description'] ?? '') }}</textarea>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="live-start">Starts</label>
            <input class="form-control" id="live-start" type="datetime-local" name="live_start_at" value="{{ old('live_start_at', !empty($live['start_at']) ? \Illuminate\Support\Carbon::parse($live['start_at'])->format('Y-m-d\TH:i') : '') }}">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="live-end">Ends</label>
            <input class="form-control" id="live-end" type="datetime-local" name="live_end_at" value="{{ old('live_end_at', !empty($live['end_at']) ? \Illuminate\Support\Carbon::parse($live['end_at'])->format('Y-m-d\TH:i') : '') }}">
          </div>
        </div>
        <label class="form-label" for="live-url">Destination URL</label>
        <input class="form-control mb-3" id="live-url" type="url" name="live_url" value="{{ old('live_url', $live['url'] ?? '') }}" placeholder="https://">
        <label class="form-label" for="live-click-behavior">When tapped</label>
        <select class="form-control" id="live-click-behavior" name="live_click_behavior">
          <option value="preview" @selected(old('live_click_behavior', $live['click_behavior'] ?? 'preview') === 'preview')>Open PinkKiss info panel first</option>
          <option value="direct" @selected(old('live_click_behavior', $live['click_behavior'] ?? 'preview') === 'direct')>Go directly to destination</option>
        </select>
      </fieldset>

      <fieldset class="border rounded p-3 mb-4">
        <legend class="float-none w-auto px-2 fs-5">🎧 Profile Audio</legend>
        <div class="form-check form-switch mb-3">
          <input type="hidden" name="audio_enabled" value="0">
          <input class="form-check-input" type="checkbox" id="audio-enabled" name="audio_enabled" value="1" @checked(old('audio_enabled', $audio['enabled'] ?? false))>
          <label class="form-check-label" for="audio-enabled">Show audio player</label>
        </div>
        <label class="form-label" for="audio-title">Track title</label>
        <input class="form-control mb-3" id="audio-title" name="audio_title" maxlength="120" value="{{ old('audio_title', $audio['title'] ?? '') }}">
        <label class="form-label" for="audio-artist">Artist / label</label>
        <input class="form-control mb-3" id="audio-artist" name="audio_artist" maxlength="120" value="{{ old('audio_artist', $audio['artist'] ?? '') }}">
        <label class="form-label" for="audio-url">Direct audio URL</label>
        <input class="form-control mb-3" id="audio-url" type="url" name="audio_url" value="{{ old('audio_url', $audio['url'] ?? '') }}" placeholder="https://example.com/track.mp3">
        <label class="form-label" for="audio-cover-url">Cover artwork URL (optional)</label>
        <input class="form-control" id="audio-cover-url" type="url" name="audio_cover_url" value="{{ old('audio_cover_url', $audio['cover_url'] ?? '') }}" placeholder="https://example.com/cover.jpg">
      </fieldset>

      @if(Auth::user()->role === 'vip')
      <fieldset class="border rounded p-3 mb-4">
        <legend class="float-none w-auto px-2 fs-5">✨ VIP Emoji FX</legend>
        <div class="form-check form-switch mb-3">
          <input type="hidden" name="emoji_fx_enabled" value="0">
          <input class="form-check-input" type="checkbox" id="emoji-fx-enabled" name="emoji_fx_enabled" value="1" @checked(old('emoji_fx_enabled', $fx['enabled'] ?? false))>
          <label class="form-check-label" for="emoji-fx-enabled">Enable profile emoji effects</label>
        </div>
        <div class="row">
          @for($i=0; $i<3; $i++)
          <div class="col-md-4 mb-3">
            <label class="form-label" for="emoji-{{ $i + 1 }}">Emoji {{ $i + 1 }}</label>
            <input class="form-control" id="emoji-{{ $i + 1 }}" name="emoji_{{ $i + 1 }}" maxlength="12" value="{{ old('emoji_'.($i+1), $fxEmojis[$i] ?? '') }}">
          </div>
          @endfor
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label" for="emoji-direction">Direction</label>
            <select class="form-control" id="emoji-direction" name="emoji_fx_direction">
              <option value="rise" @selected(old('emoji_fx_direction', $fx['direction'] ?? 'rise') === 'rise')>↑ Rise</option>
              <option value="fall" @selected(old('emoji_fx_direction', $fx['direction'] ?? 'rise') === 'fall')>↓ Fall</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label" for="emoji-intensity">Intensity</label>
            <select class="form-control" id="emoji-intensity" name="emoji_fx_intensity">
              @foreach(['subtle'=>'Subtle','normal'=>'Normal','party'=>'Party'] as $value=>$label)
              <option value="{{ $value }}" @selected(old('emoji_fx_intensity', $fx['intensity'] ?? 'normal') === $value)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </fieldset>
      @endif

      <button class="btn btn-primary" type="submit">Save creator experience</button>
    </form>
  </div>
</div>
