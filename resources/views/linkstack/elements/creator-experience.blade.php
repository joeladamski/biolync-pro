@php
$audio = \App\Models\UserData::getData($userinfo->id, 'profile_audio');
$audio = is_array($audio) ? $audio : [];
$live = \App\Models\UserData::getData($userinfo->id, 'live_cta');
$live = is_array($live) ? $live : [];
$fx = \App\Models\UserData::getData($userinfo->id, 'vip_emoji_fx');
$fx = is_array($fx) ? $fx : [];
@endphp

@if(($live['enabled'] ?? false) && !empty($live['url']))
@php
$liveLabel = $live['label'] ?? 'LIVE TONIGHT';
$liveInvitation = $live['invitation'] ?? 'Catch me here tonight';
$liveDirect = ($live['click_behavior'] ?? 'preview') === 'direct';
@endphp
<div class="pk-live-wrap">
  @if($liveDirect)
  <a class="pk-live-kiss" href="{{ $live['url'] }}" target="_blank" rel="noopener noreferrer nofollow">
    <span class="pk-kiss-mark">💋</span><span><strong>{{ $liveLabel }}</strong><small>{{ $liveInvitation }}</small></span>
  </a>
  @else
  <button class="pk-live-kiss" type="button" data-pk-live-open>
    <span class="pk-kiss-mark">💋</span><span><strong>{{ $liveLabel }}</strong><small>{{ $liveInvitation }}</small></span>
  </button>
  <div class="pk-live-backdrop" data-pk-live-modal hidden>
    <section class="pk-live-sheet" role="dialog" aria-modal="true" aria-labelledby="pk-live-title">
      <button class="pk-live-close" type="button" data-pk-live-close aria-label="Close">×</button>
      <div class="pk-live-emblem">💋</div>
      <h2 id="pk-live-title">{{ $liveLabel }}</h2>
      <p class="pk-live-invite">{{ $liveInvitation }}</p>
      @if(!empty($live['venue']))<h3>{{ $live['venue'] }}</h3>@endif
      @if(!empty($live['start_at']))
      <p class="pk-live-time">{{ \Illuminate\Support\Carbon::parse($live['start_at'])->format('M j · g:i A') }}@if(!empty($live['end_at'])) – {{ \Illuminate\Support\Carbon::parse($live['end_at'])->format('g:i A') }}@endif</p>
      @endif
      @if(!empty($live['description']))<p>{{ $live['description'] }}</p>@endif
      <a class="pk-live-go" href="{{ $live['url'] }}" target="_blank" rel="noopener noreferrer nofollow">💋 VISIT ME HERE TONIGHT</a>
    </section>
  </div>
  @endif
</div>
@endif

@if(($audio['enabled'] ?? false) && !empty($audio['url']))
<div class="pk-audio-card" data-pk-audio-player>
  @if(!empty($audio['cover_url']))<img class="pk-audio-cover" src="{{ $audio['cover_url'] }}" alt="" loading="lazy">@endif
  <div class="pk-audio-main">
    <div class="pk-audio-meta">
      <strong>{{ $audio['title'] ?: 'Featured Audio' }}</strong>
      @if(!empty($audio['artist']))<small>{{ $audio['artist'] }}</small>@endif
    </div>
    <div class="pk-audio-controls">
      <button type="button" data-pk-audio-toggle aria-label="Play audio">▶</button>
      <input type="range" min="0" max="100" value="0" step="0.1" data-pk-audio-progress aria-label="Track progress">
      <span data-pk-audio-time>0:00</span>
    </div>
    <audio preload="metadata" src="{{ $audio['url'] }}" data-pk-audio></audio>
  </div>
</div>
@endif

<style>
.pk-live-wrap,.pk-audio-card{width:min(100%,600px);margin:18px auto;}
.pk-live-kiss{width:100%;border:0;display:flex;align-items:center;justify-content:center;gap:12px;padding:15px 22px;text-decoration:none;cursor:pointer;color:#fff;background:linear-gradient(135deg,var(--pk-primary,#33003B),var(--pk-accent,#FF5DD3));border-radius:48% 52% 46% 54% / 58% 45% 55% 42%;box-shadow:0 12px 30px rgba(0,0,0,.22);transform:rotate(-.6deg);}
.pk-live-kiss:hover{transform:rotate(0) translateY(-2px);}
.pk-live-kiss strong,.pk-live-kiss small{display:block;color:inherit}.pk-live-kiss strong{font-size:1.08rem;letter-spacing:.08em}.pk-live-kiss small{margin-top:2px}.pk-kiss-mark{font-size:1.7rem}
.pk-live-backdrop{position:fixed;inset:0;z-index:100000;background:rgba(15,8,20,.72);display:flex;align-items:center;justify-content:center;padding:18px;}
.pk-live-backdrop[hidden]{display:none}
.pk-live-sheet{width:min(520px,100%);position:relative;background:#25002b;color:#fff;border:1px solid rgba(255,93,211,.45);border-radius:24px;padding:32px;text-align:center;box-shadow:0 22px 70px rgba(0,0,0,.5)}
.pk-live-close{position:absolute;right:14px;top:10px;background:none;border:0;color:#fff;font-size:2rem}.pk-live-emblem{font-size:3rem}.pk-live-sheet h2{margin:.25rem 0;color:#fff}.pk-live-invite{color:#ffb9eb;font-weight:700}.pk-live-sheet h3{color:#fff}.pk-live-time{opacity:.84}.pk-live-go{display:block;margin-top:20px;padding:13px 18px;border-radius:999px;background:#FF5DD3;color:#160019!important;text-decoration:none;font-weight:800}
.pk-audio-card{display:flex;gap:14px;align-items:center;padding:14px;background:rgba(20,13,27,.88);color:#fff;border:1px solid rgba(255,255,255,.15);border-radius:var(--pk-card-radius,18px);backdrop-filter:blur(12px);box-shadow:0 10px 28px rgba(0,0,0,.22)}
.pk-audio-cover{width:72px;height:72px;object-fit:cover;border-radius:12px}.pk-audio-main{flex:1;min-width:0}.pk-audio-meta strong,.pk-audio-meta small{display:block}.pk-audio-meta small{opacity:.72}.pk-audio-controls{display:flex;align-items:center;gap:10px;margin-top:9px}.pk-audio-controls button{width:36px;height:36px;border-radius:50%;border:0;background:#FF5DD3}.pk-audio-controls input{flex:1;min-width:80px}.pk-audio-controls span{font-variant-numeric:tabular-nums;font-size:.82rem}
@media(max-width:600px){.pk-live-backdrop{align-items:flex-end;padding:0}.pk-live-sheet{border-radius:24px 24px 0 0;padding:32px 22px 28px}.pk-audio-card{margin-left:10px;margin-right:10px;width:auto}}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.querySelector('[data-pk-live-modal]');
  document.querySelectorAll('[data-pk-live-open]').forEach(btn => btn.addEventListener('click', () => {
    if (modal) { modal.hidden = false; document.body.style.overflow = 'hidden'; }
  }));
  const closeLive = () => { if (modal) { modal.hidden = true; document.body.style.overflow = ''; } };
  document.querySelectorAll('[data-pk-live-close]').forEach(btn => btn.addEventListener('click', closeLive));
  if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeLive(); });

  document.querySelectorAll('[data-pk-audio-player]').forEach(player => {
    const audio = player.querySelector('[data-pk-audio]');
    const toggle = player.querySelector('[data-pk-audio-toggle]');
    const progress = player.querySelector('[data-pk-audio-progress]');
    const time = player.querySelector('[data-pk-audio-time]');
    const fmt = s => Number.isFinite(s) ? Math.floor(s/60)+':'+String(Math.floor(s%60)).padStart(2,'0') : '0:00';
    toggle.addEventListener('click', () => {
      document.querySelectorAll('audio[data-pk-audio]').forEach(other => { if (other !== audio) other.pause(); });
      if (audio.paused) audio.play().catch(()=>{}); else audio.pause();
    });
    audio.addEventListener('play', () => { toggle.textContent='❚❚'; toggle.setAttribute('aria-label','Pause audio'); });
    audio.addEventListener('pause', () => { toggle.textContent='▶'; toggle.setAttribute('aria-label','Play audio'); });
    audio.addEventListener('timeupdate', () => {
      progress.value = audio.duration ? (audio.currentTime/audio.duration)*100 : 0;
      time.textContent = fmt(audio.currentTime);
    });
    progress.addEventListener('input', () => { if (audio.duration) audio.currentTime=(progress.value/100)*audio.duration; });
  });
});
</script>

@if($userinfo->role === 'vip' && ($fx['enabled'] ?? false) && !empty($fx['emojis']))
<div class="pk-emoji-fx" data-pk-emoji-fx aria-hidden="true"></div>
<button class="pk-fx-toggle" type="button" data-pk-fx-toggle>💋 FX</button>
<style>
.pk-emoji-fx{position:fixed;inset:0;z-index:30;overflow:hidden;pointer-events:none}.pk-fx-item{position:absolute;will-change:transform,opacity;animation:pk-rise var(--duration) linear forwards;font-size:var(--size);left:var(--left)}.pk-fx-item.pk-fall{animation-name:pk-fall}
.pk-fx-toggle{position:fixed;right:12px;bottom:12px;z-index:31;border:1px solid rgba(255,255,255,.5);border-radius:999px;padding:7px 11px;background:#33003bcc;color:#fff;font-weight:700}
@keyframes pk-rise{0%{transform:translateY(110vh) translateX(0) rotate(0);opacity:0}10%{opacity:.9}100%{transform:translateY(-15vh) translateX(var(--drift)) rotate(300deg);opacity:0}}
@keyframes pk-fall{0%{transform:translateY(-15vh) translateX(0) rotate(0);opacity:0}10%{opacity:.9}100%{transform:translateY(110vh) translateX(var(--drift)) rotate(300deg);opacity:0}}
@media(prefers-reduced-motion:reduce){.pk-emoji-fx,.pk-fx-toggle{display:none!important}}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const layer=document.querySelector('[data-pk-emoji-fx]'), toggle=document.querySelector('[data-pk-fx-toggle]');
  if(!layer||!toggle||matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const emojis=@json(array_values(array_slice($fx['emojis'],0,3)));
  const direction=@json($fx['direction'] ?? 'rise');
  const intensity=@json($fx['intensity'] ?? 'normal');
  const delay={subtle:1400,normal:700,party:300}[intensity]||700;
  let enabled=localStorage.getItem('pkEmojiFx')!=='off', timer=null;
  const spawn=()=>{
    if(!enabled||layer.childElementCount>=30) return;
    const node=document.createElement('span'); node.className='pk-fx-item'+(direction==='fall'?' pk-fall':'');
    node.textContent=emojis[Math.floor(Math.random()*emojis.length)];
    node.style.setProperty('--left',(Math.random()*96)+'vw');node.style.setProperty('--size',(18+Math.random()*24)+'px');
    node.style.setProperty('--duration',(5+Math.random()*5)+'s');node.style.setProperty('--drift',(-40+Math.random()*80)+'px');
    layer.appendChild(node);node.addEventListener('animationend',()=>node.remove());
  };
  const sync=()=>{toggle.textContent=enabled?'💋 FX':'💋 FX OFF'; if(timer)clearInterval(timer); if(enabled){spawn();timer=setInterval(spawn,delay)}};
  toggle.addEventListener('click',()=>{enabled=!enabled;localStorage.setItem('pkEmojiFx',enabled?'on':'off');sync()});sync();
});
</script>
@endif
