@php
$vip = \App\Models\UserData::getData($userinfo->id, 'vip_profile');
$vip = is_array($vip) ? $vip : [];
$vipEnabled = $userinfo->role === 'vip' && ($vip['enabled'] ?? true);
$grantedAt = !empty($vip['granted_at']) ? \Illuminate\Support\Carbon::parse($vip['granted_at']) : null;
$vipDays = $grantedAt ? max(0, $grantedAt->startOfDay()->diffInDays(now()->startOfDay())) : 0;
$displayName = $userinfo->name ?: $userinfo->littlelink_name;
@endphp
@if($vipEnabled)
<button type="button" class="pk-vip-badge" data-pk-vip-open>VIP 💋</button>
<div class="pk-vip-backdrop" data-pk-vip-modal hidden>
  <section class="pk-vip-sheet" role="dialog" aria-modal="true">
    <button type="button" class="pk-vip-close" data-pk-vip-close aria-label="Close">×</button>
    <div class="pk-vip-seal">💋</div>
    <div class="pk-vip-eyebrow">PINKKISS.LOVE VIP</div>
    <h2>{{ $displayName }}</h2>
    <p class="pk-vip-headline">{{ trim((string)($vip['headline'] ?? '')) ?: $displayName . ' has earned a place in the PinkKiss.Love VIP community.' }}</p>
    <p>{{ trim((string)($vip['message'] ?? '')) ?: 'Their profile represents the personality, creativity, and presence we want VIP to stand for across PinkKiss.Love.' }}</p>
    <div class="pk-vip-tenure">💋 VIP FOR {{ number_format($vipDays) }} {{ \Illuminate\Support\Str::plural('DAY', $vipDays) }} AND COUNTING</div>
    @if(!empty($vip['cta_url']))<a class="pk-vip-cta" href="{{ $vip['cta_url'] }}">{{ $vip['cta_label'] ?: 'Learn About PinkKiss VIP' }}</a>@endif
  </section>
</div>
<style>
.pk-vip-badge{display:inline-flex;vertical-align:middle;margin-left:8px;padding:5px 10px;border:1px solid #FF5DD3;border-radius:999px;background:linear-gradient(135deg,#33003B,#FF5DD3);color:#fff;font-size:.68rem;font-weight:900;letter-spacing:.08em;cursor:pointer}
.pk-vip-backdrop{position:fixed;inset:0;z-index:100001;background:rgba(11,4,14,.76);display:flex;align-items:center;justify-content:center;padding:18px}.pk-vip-backdrop[hidden]{display:none}
.pk-vip-sheet{position:relative;width:min(520px,100%);padding:34px 30px;background:linear-gradient(160deg,#33003B,#19001e);color:#fff;border:1px solid rgba(255,93,211,.4);border-radius:26px;text-align:center}.pk-vip-close{position:absolute;right:14px;top:9px;border:0;background:none;color:#fff;font-size:2rem}.pk-vip-seal{width:92px;height:92px;margin:0 auto 16px;border-radius:50%;display:grid;place-items:center;background:#fff;border:4px solid #FF5DD3;font-size:3rem}.pk-vip-eyebrow{font-size:.78rem;letter-spacing:.18em;color:#ff9ee2;font-weight:800}.pk-vip-sheet h2{color:#fff}.pk-vip-headline{font-weight:700}.pk-vip-tenure{margin:22px 0;padding:12px;border-top:1px solid rgba(255,255,255,.15);border-bottom:1px solid rgba(255,255,255,.15);font-size:.83rem;font-weight:900}.pk-vip-cta{display:block;margin-top:18px;padding:12px 18px;background:#FF5DD3;color:#160019!important;border-radius:999px;text-decoration:none;font-weight:800}
@media(max-width:600px){.pk-vip-backdrop{align-items:flex-end;padding:0}.pk-vip-sheet{border-radius:26px 26px 0 0}}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){var modal=document.querySelector('[data-pk-vip-modal]');var close=function(){if(modal){modal.hidden=true;document.body.style.overflow=''}};document.querySelectorAll('[data-pk-vip-open]').forEach(function(b){b.addEventListener('click',function(){if(modal){modal.hidden=false;document.body.style.overflow='hidden'}})});document.querySelectorAll('[data-pk-vip-close]').forEach(function(b){b.addEventListener('click',close)});});
</script>
@endif
