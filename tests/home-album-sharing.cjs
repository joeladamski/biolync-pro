// Browser plugin not available; use Playwright against Blade-rendered fixtures with production CSS.
const {chromium}=require('playwright'),fs=require('fs'),path=require('path'),http=require('http'),assert=require('assert');
const root=process.cwd(),fixtures=process.env.ALBUM_UI_DIR||'/tmp/biolync-album-ui';
const server=http.createServer((req,res)=>{
 if(req.url==='/favicon.ico'){res.writeHead(204);return res.end();}
 if(req.method==='POST'){res.writeHead(200,{'Content-Type':'text/html'});return res.end('<title>Album saved QA</title><h1>Saved</h1>');}
 const name=new URL(req.url,'http://localhost').pathname;
 let file=name.startsWith('/assets/')?path.join(root,name):path.join(fixtures,name);
 if(name.startsWith('/assets/home-album/'))file=path.join(fixtures,path.basename(name));
 if(![root+path.sep,fixtures+path.sep].some(p=>file.startsWith(p))||!fs.existsSync(file)){res.writeHead(404);return res.end();}
 res.setHeader('Content-Type',file.endsWith('.css')?'text/css':file.endsWith('.js')?'application/javascript':file.endsWith('.png')?'image/png':file.endsWith('.svg')?'image/svg+xml':'text/html;charset=utf-8');
 let data=fs.readFileSync(file);if(file.endsWith('.html'))data=data.toString().replaceAll('http://localhost','http://'+req.headers.host);
 res.end(data);
});
(async()=>{
 await new Promise(r=>server.listen(0,'127.0.0.1',r));const base='http://127.0.0.1:'+server.address().port;
 const browser=await chromium.launch();
 try{
 for(const width of [320,390,1440]){
  const page=await browser.newPage({viewport:{width,height:1100}}),errors=[];
  page.on('pageerror',e=>errors.push(e.message));page.on('console',m=>{if(m.type()==='error')errors.push(m.text());});
  await page.goto(base+'/album.html');assert.equal(await page.title(),'BioLync album QA');
  await page.locator('.home-album-image').evaluateAll(async images=>{await Promise.all(images.map(img=>img.decode()));});
  const boxes=await page.locator('.home-album-photo').evaluateAll(els=>els.map(e=>{const r=e.getBoundingClientRect();return {x:r.x,y:r.y,width:r.width};}));
  assert(Math.abs(boxes[0].width-Math.min(580,width-20))<=1,'wide photo fits screen');
  assert.equal(Math.round(boxes[1].width),250);assert.equal(Math.round(boxes[2].width),250);
  if(width===1440){assert.equal(boxes[1].y,boxes[2].y,'250px photos share row');assert(Math.abs((boxes[1].x+boxes[2].x+250)/2-width/2)<=1,'inline pair centers');}
  else assert(boxes[2].y>boxes[1].y,'inline photos wrap on narrow screens');
  assert.equal(await page.locator('.home-album-link').first().evaluate(e=>getComputedStyle(e).borderRadius),'15px');
  assert.equal(await page.locator('.home-album-logo').first().evaluate(e=>getComputedStyle(e).top),'5px');
  assert.equal(await page.locator('.home-album-logo').first().evaluate(e=>getComputedStyle(e).left),'5px');
  assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'album has no horizontal overflow');
  await page.route('https://example.test/**',r=>r.fulfill({contentType:'text/html',body:'<title>Creator destination</title><h1>Creator</h1>'}));
  const popupPromise=page.waitForEvent('popup');await page.locator('.home-album-link').first().click();const popup=await popupPromise;await popup.waitForLoadState();assert.equal(await popup.title(),'Creator destination');assert.equal(await popup.evaluate(()=>window.opener),null);await popup.close();
  await page.screenshot({path:path.join(fixtures,'album-'+width+'.png'),fullPage:true});
  await page.goto(base+'/admin.html');assert.equal(await page.title(),'BioLync album settings QA');
  const count=await page.locator('.album-admin-row').count();await page.getByRole('button',{name:'Add photo',exact:true}).click();assert.equal(await page.locator('.album-admin-row').count(),count+1);
  const added=page.locator('.album-admin-row').last();await added.locator('[data-album-preset]').selectOption('580');assert.equal(await added.locator('[data-album-width]').inputValue(),'580');
  await added.locator('[data-album-width]').fill('350');await added.locator('[data-album-width]').press('Tab');assert.equal(await added.locator('[data-album-preset]').inputValue(),'custom');
  await added.locator('input[type=file]').setInputFiles(path.join(fixtures,'album.png'));await added.locator('select[name$="[icon]"]').selectOption('youtube');
  await added.getByRole('button',{name:'Discard row'}).click();assert.equal(await page.locator('.album-admin-row').count(),count);
  await page.screenshot({path:path.join(fixtures,'album-settings-'+width+'.png'),fullPage:true});
  const submitted=page.waitForRequest(r=>r.method()==='POST');await page.getByRole('button',{name:'Save homepage album',exact:true}).click();const request=await submitted;assert(request.headers()['content-type'].includes('multipart/form-data'));assert(request.postData().includes('album_enabled'));
  assert.deepEqual(errors,[]);await page.close();console.log('PASS album browser',width,'wrapping, links, badges, presets and multipart save');
 }
 }finally{await browser.close();server.close();}
})().catch(e=>{console.error(e);server.close();process.exitCode=1;});
