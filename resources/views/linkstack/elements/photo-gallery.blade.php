@php($gallery = \App\Support\ProfileGallery::get($userinfo->id))
@if(!empty($gallery['enabled']) && count($gallery['photos']))
<section class="biolync-gallery" aria-label="Photo gallery">
<h2>Photo gallery</h2>
<div class="biolync-gallery-columns">
@foreach($gallery['photos'] as $index => $photo)
<figure class="biolync-gallery-photo">
<a href="{{ asset($photo['path']) }}" target="_blank" rel="noopener" data-gallery-photo>
<img src="{{ asset($photo['path']) }}" alt="{{ $photo['caption'] ?: 'Photo '.($index + 1) }}" width="{{ $photo['width'] }}" height="{{ $photo['height'] }}" loading="lazy" decoding="async">
</a>
@if(!empty($photo['caption']))<figcaption>{{ $photo['caption'] }}</figcaption>@endif
</figure>
@endforeach
</div>
<dialog class="biolync-gallery-dialog" aria-label="Photo viewer">
<button type="button" class="biolync-gallery-close" aria-label="Close photo viewer">Close ×</button>
<img alt="">
<p class="biolync-gallery-caption"></p>
</dialog>
</section>
<style>
.biolync-gallery {width:100%;max-width:600px;margin:32px auto;text-align:left;}
.biolync-gallery h2 {text-align:center;font-size:1.6rem;}
.biolync-gallery-columns {display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;align-items:start;}
.biolync-gallery-photo {min-width:0;margin:0;}
.biolync-gallery-photo a {display:block;}
.biolync-gallery-photo img {display:block;width:100%;height:auto;border-radius:10px;}
.biolync-gallery-photo figcaption {font-size:1.2rem;line-height:1.4;padding:6px 2px;overflow-wrap:anywhere;}
.biolync-gallery-dialog {box-sizing:border-box;width:calc(100% - 32px);max-width:1000px;max-height:90vh;max-height:90dvh;padding:16px;border:0;border-radius:12px;background:#151826;color:#fff;}
.biolync-gallery-dialog::backdrop {background:rgba(0,0,0,.85);}
.biolync-gallery-dialog img {display:block;width:100%;height:auto;max-height:70vh;max-height:70dvh;object-fit:contain;}
.biolync-gallery-close {display:block;margin:0 0 12px auto;min-height:44px;padding:8px 16px;color:#fff;background:#30364a;border:1px solid #8590ab;border-radius:8px;cursor:pointer;}
.biolync-gallery-caption {margin:12px 0 0;overflow-wrap:anywhere;}
@media(max-width:767px){.biolync-gallery-columns {grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;}}
</style>
<script>
(()=>{
 const section=document.querySelector('.biolync-gallery'),dialog=section.querySelector('dialog');
 if(typeof dialog.showModal!=='function') return;
 section.querySelectorAll('[data-gallery-photo]').forEach(link=>link.addEventListener('click',event=>{
  event.preventDefault();
  const original=link.querySelector('img'),image=dialog.querySelector('img');
  image.src=link.href;image.alt=original.alt;
  dialog.querySelector('p').textContent=link.closest('figure').querySelector('figcaption')?.textContent || '';
  dialog.showModal();
 }));
 dialog.querySelector('button').addEventListener('click',()=>dialog.close());
 dialog.addEventListener('click',event=>{if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();}});
})();
</script>
@endif
