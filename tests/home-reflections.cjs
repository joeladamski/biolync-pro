// Browser plugin not available; Playwright checks actual Blade output and the production animation script.
const {chromium}=require('playwright'),fs=require('fs'),path=require('path'),http=require('http'),assert=require('assert');
const root=process.cwd(),fixtures=path.join(root,'test-output/reflections');
const server=http.createServer((req,res)=>{
 const name=new URL(req.url,'http://localhost').pathname;
 if(name==='/favicon.ico'){res.writeHead(204);return res.end();}
 let file=name.startsWith('/assets/')?path.join(root,name):path.join(fixtures,name);
 if(![root+path.sep,fixtures+path.sep].some(p=>file.startsWith(p))||!fs.existsSync(file)){res.writeHead(404);return res.end();}
 res.setHeader('Content-Type',file.endsWith('.css')?'text/css':file.endsWith('.js')?'application/javascript':file.endsWith('.svg')?'image/svg+xml':'text/html;charset=utf-8');
 let data=fs.readFileSync(file);if(file.endsWith('.html'))data=data.toString().replaceAll('http://localhost','http://'+req.headers.host);
 res.end(data);
});
(async()=>{
 await new Promise(r=>server.listen(0,'127.0.0.1',r));const base='http://127.0.0.1:'+server.address().port;
 const browser=await chromium.launch();
 try{
  for(const width of [390,1440]) for(const mode of ['no-preference','reduce']){
   const page=await browser.newPage({viewport:{width,height:1000},reducedMotion:mode}),errors=[];
   page.on('pageerror',e=>errors.push(e.message));
   await page.route('https://fonts.googleapis.com/**',r=>r.fulfill({contentType:'text/css',body:''}));
   await page.goto(base+'/metalqueen.one.html');assert.equal(await page.title(),'Metal Queen One');
   assert.equal(await page.locator('.metal-reflection').count(),0,'MetalQueen has no reflections on either section');
   assert.equal(await page.locator('script[src*="metal-home-reflections"]').count(),0,'MetalQueen does not load the animation');
   await page.goto(base+'/biolync.pro.html');assert.equal(await page.title(),'BioLync');
   assert.equal(await page.locator('.metal-reflection').count(),1);assert.equal(await page.locator('.home-copy .metal-reflection').count(),0,'BioLync message card has no reflection');
   assert.equal(await page.locator('.iframe-container .metal-reflection[data-direction="counterclockwise"]').count(),1);
   const beam=page.locator('.metal-reflection-beam');
   const animation=await beam.evaluate(el=>el.getAnimations().map(a=>({frames:a.effect.getKeyframes().map(f=>f.transform),timing:a.effect.getTiming()})));
   if(mode==='reduce')assert.equal(animation.length,0,'reduced motion stops animation');
   else {assert.equal(animation.length,1);assert.deepEqual(animation[0].frames,['rotate(0deg)','rotate(-360deg)']);assert.equal(animation[0].timing.iterations,2);assert.equal(animation[0].timing.duration,12000);await beam.evaluate(el=>{el.getAnimations()[0].pause();el.getAnimations()[0].currentTime=3000;});}
   assert.equal(await page.locator('.metal-reflection').evaluate(el=>getComputedStyle(el).pointerEvents),'none');
   await page.frameLocator('iframe').getByRole('button',{name:'Open profile link'}).click();assert.equal(await page.frameLocator('iframe').getByRole('button').innerText(),'Opened','overlay does not block profile interaction');
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'frame does not overflow');
   if(mode==='no-preference')await page.screenshot({path:path.join(root,'test-output','device-reflection-'+width+'.png'),fullPage:true});
   assert.deepEqual(errors,[]);await page.close();console.log('PASS device reflection',width,mode);
  }
 }finally{await browser.close();server.close();}
})().catch(e=>{console.error(e);server.close();process.exitCode=1;});
