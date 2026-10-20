// Browser plugin not available; use the installed Playwright runner.
const {chromium}=require('playwright');
const fs=require('fs'), path=require('path'),http=require('http'),assert=require('assert');
const root=process.cwd(), fixtures=process.env.BIOLYNC_UI_DIR || '/tmp/biolync-content-ui';
const server=http.createServer((req,res)=>{
 if(req.url==='/favicon.ico'){res.writeHead(204);return res.end();}
 if(req.method==='POST'){res.writeHead(200,{'Content-Type':'text/html'});return res.end('<title>Saved</title><h1>Saved</h1>');}
 let name=new URL(req.url,'http://localhost').pathname;
 if(name.includes('/studio/linkparamform_part/'))name='/'+name.split('/').at(-2)+'.html';
 let file=name.startsWith('/assets/')?path.join(root,name):path.join(fixtures,name);
 if(name.includes('/assets/img/background-img/0_landing'))file=path.join(fixtures,'background.png');
 if(![root+path.sep,fixtures+path.sep].some(p=>file.startsWith(p)) || !fs.existsSync(file)){res.writeHead(404);return res.end();}
 res.setHeader('Content-Type',file.endsWith('.css')?'text/css':file.endsWith('.js')?'application/javascript':file.endsWith('.png')?'image/png':'text/html;charset=utf-8');
 let data=fs.readFileSync(file);if(file.endsWith('.html'))data=data.toString().replaceAll('http://localhost','http://'+req.headers.host);
 res.end(data);
});
(async()=>{
 await new Promise(r=>server.listen(0,'127.0.0.1',r));
 const base='http://127.0.0.1:'+server.address().port;
 const browser=await chromium.launch();
 try{
  for(const width of [390,1440]){
   const page=await browser.newPage({viewport:{width,height:1000}}),errors=[];
   page.on('pageerror',e=>errors.push(e.message));page.on('console',msg=>{if(msg.type()==='error')errors.push(msg.text())});
   await page.route('https://fonts.googleapis.com/**',route=>route.fulfill({contentType:'text/css',body:''}));
   await page.goto(base+'/styles.html');assert.equal(await page.title(),'BioLync style QA');
   await page.locator('#pk-surface-opacity').fill('25');
   assert.equal(await page.locator('#pk-style-preview-card').evaluate(el=>getComputedStyle(el).backgroundColor),'rgba(255, 255, 255, 0.25)');
   assert.equal(await page.locator('#pk-style-preview-title').innerText(),'BioLync');
   await page.locator('#pk-background-image').setInputFiles(path.join(fixtures,'background.png'));
   assert(await page.locator('#pk-style-preview').evaluate(el=>getComputedStyle(el).backgroundImage.includes('blob:')));
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'style form fits mobile');
   await page.screenshot({path:path.join(fixtures,'styles-'+width+'.png'),fullPage:false});
   await page.goto(base+'/editors.html');assert.equal(await page.title(),'BioLync editor QA');
   await page.waitForFunction(()=>document.querySelectorAll('.jodit-container').length===2);
   await page.locator('.doSelectLinkType[data-typeid=heading]').click();
   await page.waitForFunction(()=>document.querySelector('#block-heading')?.dataset.editorReady==='true');
   assert.equal(await page.locator('.jodit-container').count(),2,'old block editor is destroyed');
   await page.locator('.doSelectLinkType[data-typeid=text]').click();
   await page.waitForFunction(()=>document.querySelector('#block-text')?.dataset.editorReady==='true');
   assert.equal(await page.locator('.jodit-container').count(),2,'switching blocks does not duplicate editors');
   await page.locator('#my-form .jodit-wysiwyg').fill('Formatted creator text');
   await page.locator('#my-form .jodit-wysiwyg').press('ControlOrMeta+a');
   await page.locator('#my-form [data-ref=bold]').click();
   assert(await page.locator('#my-form .jodit-wysiwyg').innerHTML().then(s=>/<(strong|b)>/.test(s)),'toolbar formats text');
   await page.locator('#my-form [data-ref=source]').click();
   await page.locator('#my-form .jodit-source__mirror').fill('<p><strong>Save and add more 💋</strong></p>');
   await page.screenshot({path:path.join(fixtures,'editors-'+width+'.png'),fullPage:false});
   const submission=page.waitForRequest(r=>r.method()==='POST');
   await page.getByRole('button',{name:'Save and Add More',exact:true}).click();
   const request=await submission, values=new URLSearchParams(request.postData());
   assert.equal(values.get('param'),'add_more');assert(values.get('text').includes('<strong>Save and add more 💋</strong>'),'source editor synchronizes on native submit');
   await page.goto(base+'/landing.html');
   assert.equal(await page.title(),'BioLync landing QA');
   assert(await page.locator('body').evaluate(el=>getComputedStyle(el).backgroundImage.includes('0_landing')),'landing image renders');
   const frame=page.frameLocator('iframe');
   assert.equal(await frame.locator('html').evaluate(el=>getComputedStyle(el).scrollbarColor),'rgb(80, 82, 94) rgb(24, 24, 32)');
   await page.screenshot({path:path.join(fixtures,'landing-'+width+'.png'),fullPage:false});
   assert.deepEqual(errors,[],'no browser runtime or console errors');
   await page.close();
   console.log('PASS browser: styles, background preview, editor lifecycle, Save and Add More, dark scrollbar at '+width);
  }
 }finally{await browser.close();server.close();}
})().catch(error=>{console.error(error);server.close();process.exitCode=1;});
