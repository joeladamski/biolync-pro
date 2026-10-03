@php
    $header = \App\Models\UserData::getData($userinfo->id, 'profile_header');
    $header = is_array($header) ? $header : [];
    $validPath = function ($path) use ($userinfo) {
        return is_string($path) && preg_match('#^assets/profile-media/' . $userinfo->id . '_[a-f0-9]{32}\.(jpg|jpeg|png|webp|mp4|webm)$#', $path) && file_exists(base_path($path));
    };
    $hasHeader = $validPath($header['media'] ?? null);
    $position = in_array($header['position'] ?? '', ['top', 'center', 'bottom']) ? $header['position'] : 'center';
@endphp
<style>
.biolync-profile > .row > .biolync-profile-column { margin-top:0!important; padding-top:24px!important; }
.biolync-cover { position:relative; width:min(600px,calc(100vw - 24px)); height:220px; left:50%; transform:translateX(-50%); border-radius:20px; overflow:hidden; background:var(--biolync-bg-art, #263342); }
.biolync-cover img,.biolync-cover video { width:100%;height:100%;object-fit:cover;object-position:var(--cover-position);display:block; }
.biolync-cover + .biolync-avatar { position:relative;margin-top:-56px; }
.biolync-cover + .biolync-avatar #avatar { width:112px!important;height:112px!important;min-width:112px!important;border-radius:50%;border:4px solid var(--biolync-bg, #263342); }
.biolync-cover-toggle { position:absolute;right:12px;top:12px;z-index:1;width:auto!important;min-height:0!important;padding:6px 12px!important;background:#17212eee!important;color:white!important;border:1px solid white!important;border-radius:8px;cursor:pointer; }
@media(max-width:600px) { .biolync-cover {height:190px;} }
</style>
@if($hasHeader)
<div class="biolync-cover" style="--cover-position:{{ $position }}">
    @if(($header['type'] ?? '') === 'video')
        <video id="biolync-header-video" muted loop playsinline preload="none" @if($validPath($header['poster'] ?? null)) poster="{{ asset($header['poster']) }}" @endif aria-label="Profile header video">
            <source src="{{ asset($header['media']) }}">
        </video>
        <button class="biolync-cover-toggle" id="biolync-video-toggle" type="button">Play video</button>
        <script>
        (() => {
            const video = document.getElementById('biolync-header-video');
            const toggle = document.getElementById('biolync-video-toggle');
            video.addEventListener('play', () => { toggle.textContent = 'Pause video'; });
            video.addEventListener('pause', () => { toggle.textContent = 'Play video'; });
            toggle.addEventListener('click', () => { if (video.paused) video.play().catch(() => {}); else video.pause(); });
            if (!matchMedia('(prefers-reduced-motion: reduce)').matches) video.play().catch(() => {});
        })();
        </script>
    @else
        <img src="{{ asset($header['media']) }}" alt="Profile cover" fetchpriority="high">
    @endif
</div>
@endif
