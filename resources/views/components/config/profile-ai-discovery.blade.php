@php($profileAi = \App\Support\AiDiscovery::profileSettings($user))
<section id="ai-discovery" class="border rounded p-3 mt-4">
<h3>AI Discovery</h3>
<p>Admin-only controls for this user's public llms.txt and llms-full.txt. Normal profile edits update the generated content; overrides remain until reset.</p>
@if(session('ai_success'))<div class="alert alert-success">{{ session('ai_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('saveAiProfile', ['id' => $user->id]) }}" method="POST">
@csrf
<input type="hidden" name="enabled" value="0">
<label class="d-block mb-3"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $profileAi['enabled']))> Include this profile in public AI Discovery</label>
<label for="profile-ai-summary" class="form-label">Summary override (optional)</label>
<textarea id="profile-ai-summary" name="summary" class="form-control mb-3" rows="4" maxlength="2000">{{ old('summary', $profileAi['summary']) }}</textarea>
<label for="profile-ai-context" class="form-label">Additional public context (optional)</label>
<textarea id="profile-ai-context" name="context" class="form-control mb-3" rows="5" maxlength="5000">{{ old('context', $profileAi['context']) }}</textarea>
<p>Leave the summary blank to use the public bio. Global publication must also be enabled in Admin → Config → AI Discovery. Blocked profiles always return 404. Account email, password, role and private settings are excluded.</p>
<button class="btn btn-primary" type="submit">Save profile AI Discovery</button>
<button class="btn btn-outline-secondary" type="submit" name="reset" value="1">Reset text to automatic</button>
</form>
<h4 class="mt-4">Generated previews</h4>
<details class="mb-3"><summary>llms.txt</summary><pre class="border rounded p-3" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:400px;overflow:auto">{{ \App\Support\AiDiscovery::profileText($user) }}</pre></details>
<details><summary>llms-full.txt</summary><pre class="border rounded p-3" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:400px;overflow:auto">{{ \App\Support\AiDiscovery::profileText($user, true) }}</pre></details>
@if(\App\Support\AiDiscovery::publishedProfile($user->id))<p class="mt-3"><a href="{{ route('aiProfileSummary', ['littlelink' => $user->littlelink_name]) }}" target="_blank" rel="noopener">View public file</a> · <a href="{{ route('aiProfileFull', ['littlelink' => $user->littlelink_name]) }}" target="_blank" rel="noopener">View public full file</a></p>@endif
</section>
