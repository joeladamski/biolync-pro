<section class="pk-admin-profile-preview border rounded p-3" aria-label="User profile preview">
    <h3 class="h5">Profile preview</h3>
    <p class="mb-2">{{ $user->name }} · {{ strtoupper($user->role) }}</p>
    @if($user->littlelink_name && $user->block !== 'yes')
        @php($profileUrl = url('/@' . $user->littlelink_name))
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-sm btn-outline-primary" href="{{ $profileUrl }}" target="_blank" rel="noopener">Open profile</a>
            <button class="btn btn-sm btn-outline-secondary" type="button" id="pk-refresh-user-preview">Refresh preview</button>
        </div>
        <iframe id="pk-user-preview" title="{{ $user->name }} profile preview" src="{{ $profileUrl }}" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox" loading="lazy"></iframe>
        <p class="form-text mt-2 mb-0">Preview shows saved changes. Save to update it.</p>
    @else
        <p class="mb-0">{{ $user->block === 'yes' ? 'This profile is blocked from public view.' : 'Set a Page URL and save to preview this profile.' }}</p>
    @endif
</section>
<style>
.pk-admin-profile-preview {position:sticky;top:24px;}
.pk-admin-profile-preview iframe {display:block;width:100%;max-width:390px;height:650px;margin:0 auto;border:8px solid #10121a;border-radius:24px;background:#151826;}
@media(max-width:1199px) {.pk-admin-profile-preview {position:static;}}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('pk-refresh-user-preview')?.addEventListener('click', () => {
        const frame = document.getElementById('pk-user-preview');
        if (frame) frame.src = frame.getAttribute('src');
    });
});
</script>
