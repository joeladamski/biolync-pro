<form action="{{ route('saveFeaturedProfile') }}" method="POST" class="border rounded p-3 mb-4">
@csrf
<label class="form-label" for="featured-user">Featured homepage profile</label>
<select class="form-control mb-3" id="featured-user" name="featured_user_id">
<option value="">Default demo preview</option>
@foreach(\App\Models\User::orderBy('name')->get() as $candidate)
@if(\App\Support\AiDiscovery::publicUser($candidate))
<option value="{{ $candidate->id }}" @selected((string)old('featured_user_id', \App\Support\AiDiscovery::settings()['featured_user_id']) === (string)$candidate->id)>{{ $candidate->name }} — {{ $candidate->littlelink_name }}</option>
@endif
@endforeach
</select>
<p class="small">Shows the selected public profile in the homepage phone preview. Blocked or deleted accounts fall back to the demo. Demo button settings apply when the default demo is selected.</p>
<button class="btn btn-primary" type="submit">Save featured profile</button>
</form>
