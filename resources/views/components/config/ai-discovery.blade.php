@php($aiSettings = \App\Support\AiDiscovery::settings())
<h2 class="mb-4 card-header">AI Discovery</h2>
<p>Publish automatically generated llms.txt and llms-full.txt files using public content. Profile-specific settings are under Admin → Users → Edit User. These files provide context; they do not verify identities or guarantee AI discovery.</p>
@if(session('ai_success'))<div class="alert alert-success">{{ session('ai_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('saveAiSite') }}">
@csrf
<input type="hidden" name="enabled" value="0">
<label class="d-block mb-3"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $aiSettings['enabled']))> Enable public AI Discovery files</label>
<label for="ai-intro" class="form-label">Site introduction override (optional)</label>
<textarea id="ai-intro" name="introduction" class="form-control mb-3" rows="4" maxlength="2000">{{ old('introduction', $aiSettings['introduction']) }}</textarea>
<label for="ai-context" class="form-label">Additional public context (optional)</label>
<textarea id="ai-context" name="context" class="form-control mb-3" rows="5" maxlength="5000">{{ old('context', $aiSettings['context']) }}</textarea>
<p>Blank introduction uses the current home message. Full output includes published site pages and profiles explicitly enabled by an admin. Private account fields are excluded.</p>
<button class="btn btn-primary" type="submit">Save AI Discovery</button>
<button class="btn btn-outline-secondary" type="submit" name="reset" value="1">Reset text to automatic</button>
</form>
<h3 class="mt-4">Generated previews</h3>
<p>Previews are visible here even when publication is off.</p>
<details class="mb-3"><summary>llms.txt</summary><pre class="border rounded p-3" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:400px;overflow:auto">{{ \App\Support\AiDiscovery::siteText() }}</pre></details>
<details><summary>llms-full.txt</summary><pre class="border rounded p-3" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:400px;overflow:auto">{{ \App\Support\AiDiscovery::siteText(true) }}</pre></details>
@if($aiSettings['enabled'])<p class="mt-3"><a href="{{ route('aiSiteSummary') }}" target="_blank" rel="noopener">View public llms.txt</a> · <a href="{{ route('aiSiteFull') }}" target="_blank" rel="noopener">View public full file</a></p>@endif
