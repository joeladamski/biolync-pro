@php
    $header = \App\Models\UserData::getData($userinfo->id, 'profile_header');
    $header = is_array($header) ? $header : [];
    $validPath = function ($path) use ($userinfo) {
        return is_string($path) && preg_match('#^assets/profile-media/' . $userinfo->id . '_[a-f0-9]{32}\.(jpg|jpeg|png|webp|mp4|webm)$#', $path) && file_exists(\App\Support\RuntimeMedia::path($path));
    };
    $hasHeader = $validPath($header['media'] ?? null);
    $position = in_array($header['position'] ?? '', ['top', 'center', 'bottom']) ? $header['position'] : 'center';
@endphp
<style>
body {padding-top:0!important;}
html, body {background-color:var(--biolync-bg, var(--bgColor, #151826));}
.container.biolync-profile {box-sizing:border-box;width:100%!important;max-width:none!important;padding:0!important;margin:0!important;}
.biolync-profile > .row {box-sizing:border-box;width:100%!important;margin:0!important;padding:0!important;}
.biolync-profile > .row > .biolync-profile-column {box-sizing:border-box;width:100%!important;max-width:none!important;margin:0!important;padding:0 8px!important;}
.biolync-cover { position:relative; box-sizing:border-box;width:100%;max-width:100%;height:220px;margin:0;padding:0;left:auto;transform:none; border-radius:0 0 20px 20px; overflow:hidden; background:var(--biolync-bg-art, #263342); }
.biolync-cover img,.biolync-cover video { width:100%;height:100%;object-fit:cover;object-position:var(--cover-position);display:block; }
.biolync-avatar { position:relative;width:150px;margin:0 auto; }
.biolync-avatar #avatar { box-sizing:border-box;width:150px!important;height:150px!important;min-width:150px!important;min-height:150px!important;object-fit:cover; }
.biolync-cover + .biolync-avatar { width:150px;margin-top:-56px; }
.biolync-cover + .biolync-avatar #avatar { box-sizing:border-box;width:150px!important;height:150px!important;min-width:150px!important;min-height:150px!important;border-radius:50%;border:4px solid var(--biolync-bg, #263342); }
.biolync-cover-toggle { position:absolute;right:12px;top:12px;z-index:1;width:auto!important;min-height:0!important;padding:6px 12px!important;background:#17212eee!important;color:white!important;border:1px solid white!important;border-radius:8px;cursor:pointer; }
@media(max-width:600px) { .biolync-cover {height:190px;} }
.biolync-profile .button { box-sizing:border-box;max-width:100%; }
.biolync-profile .social-icon-div { padding:0!important;margin:0!important; }
.biolync-profile .social-icon { padding:5px 10px!important; }
</style>
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
    @elseif($hasHeader)
        <img src="{{ asset($header['media']) }}" alt="Profile cover" fetchpriority="high">
    @else
        <img src="{{ asset('assets/images/dashboard/top-header-overlay.png') }}" alt="" fetchpriority="high">
    @endif
</div>
