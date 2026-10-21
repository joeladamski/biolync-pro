(() => {
 const form=document.getElementById('home-album-form');if(!form)return;
 const rows=document.getElementById('home-album-rows'),add=document.getElementById('home-album-add');
 let index=rows.children.length;
 add.addEventListener('click',()=>{
  if(rows.children.length>=24){document.getElementById('home-album-status').textContent='The album supports up to 24 photos.';return;}
  rows.insertAdjacentHTML('beforeend',document.getElementById('home-album-row-template').innerHTML.replaceAll('__INDEX__',String(index++)));
  rows.lastElementChild.querySelector('input[type=file]').focus();
 });
 form.addEventListener('change',e=>{
  if(e.target.matches('[data-album-preset]') && e.target.value!=='custom')e.target.closest('fieldset').querySelector('[data-album-width]').value=e.target.value;
  if(e.target.matches('[data-album-width]'))e.target.closest('fieldset').querySelector('[data-album-preset]').value=['250','580'].includes(e.target.value)?e.target.value:'custom';
 });
 form.addEventListener('click',e=>{if(e.target.matches('[data-album-discard]'))e.target.closest('fieldset').remove();});
})();
