const {chromium}=require('playwright');
const fs=require('fs'),path=require('path');
(async()=>{
 const browser=await chromium.launch({headless:true});
 try {
  for(const width of [320,375,390,767,768,1440]) {
   const page=await browser.newPage({viewport:{width,height:900}});
   await page.route('**/assets/profile-gallery/**',async route=>{
    const filename=path.basename(new URL(route.request().url()).pathname);
    const local=path.resolve('assets/profile-gallery',filename);
    if(fs.existsSync(local)) await route.fulfill({path:local}); else await route.fulfill({status:404,body:''});
   });
   await page.goto('file://'+path.resolve('test-output/gallery.html'));
   await page.evaluate(()=>{const g=document.querySelector('.biolync-gallery-columns');g.append(g.lastElementChild.cloneNode(true));});
   // One deliberately removed fixture photo remains a layout placeholder, so use the surviving landscape.
   await page.locator('.biolync-gallery-photo img').first().waitFor();
   const result=await page.evaluate(()=>{
    const gallery=document.querySelector('.biolync-gallery-columns'),img=gallery.querySelector('img'),r=img.getBoundingClientRect();
    return {display:getComputedStyle(gallery).display,columns:getComputedStyle(gallery).gridTemplateColumns.split(' ').length,items:[...gallery.querySelectorAll('figure')].map(f=>{const r=f.getBoundingClientRect();return {left:r.left,top:r.top,bottom:r.bottom};}),ratio:r.width/r.height,expected:Number(img.getAttribute('width'))/Number(img.getAttribute('height')),overflow:document.documentElement.scrollWidth>innerWidth,left:r.left,right:r.right};
   });
   if(result.display!=='grid'||result.columns!==(width<768?2:3)||result.overflow||result.left<0||result.right>width||Math.abs(result.ratio-result.expected)>.01)throw Error(JSON.stringify({width,result}));
   const count=width<768?2:3;
   for(let i=1;i<count;i++)if(Math.abs(result.items[i].top-result.items[0].top)>1||result.items[i].left<=result.items[i-1].left)throw Error('Photos must read left to right');
   if(result.items[count].top<Math.max(...result.items.slice(0,count).map(r=>r.bottom))-1||Math.abs(result.items[count].left-result.items[0].left)>1)throw Error('Next photo must start the next row');
   await page.locator('[data-gallery-photo]').first().click();
   if(!await page.locator('dialog').evaluate(d=>d.open))throw Error('Viewer did not open');
   await page.keyboard.press('Escape');
   if(await page.locator('dialog').evaluate(d=>d.open))throw Error('Escape did not close viewer');
   console.log('PASS gallery layout and viewer',width);await page.close();
  }
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
