// Browser plugin not available; use the repository's Playwright runner.
const {chromium} = require('playwright');
const fs = require('fs');
const path = require('path');
const http = require('http');
(async () => {
 const root = process.cwd();
 const server = http.createServer((req, res) => {
  const route = req.url.split('?')[0];
  const file = path.join(root, route.startsWith('/@') ? 'test-output/ui-BioLyncMidnight.html' : route);
  if (!file.startsWith(root + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) {res.writeHead(404); return res.end();}
  const type = {'.html':'text/html', '.svg':'image/svg+xml', '.png':'image/png', '.css':'text/css'}[path.extname(file)] || 'application/octet-stream';
  res.setHeader('Content-Type', type);
  let data = fs.readFileSync(file);
  if (type === 'text/html') data = data.toString().replaceAll('http://localhost', `http://127.0.0.1:${server.address().port}`);
  res.end(data);
 });
 await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
 const base = `http://127.0.0.1:${server.address().port}`;
 const browser = await chromium.launch({headless:true});
 const errors = [];
 try {
  for (const theme of ['BioLyncOcean','BioLyncPaper','BioLyncMidnight','BioLyncRose','default']) {
   for (const width of [320,390,768,1440]) {
    const page = await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'});
    page.on('pageerror', e => errors.push(e.message));
    await page.goto(`${base}/test-output/ui-${theme}.html`);
    if (await page.title() !== 'Profile UI QA') throw Error('Wrong profile page');
    const geometry = await page.evaluate(() => {
     const a = document.querySelector('#avatar').getBoundingClientRect();
     const b = document.querySelector('.pk-vip-badge').getBoundingClientRect();
     const icon = getComputedStyle(document.querySelector('.social-icon'));
     return {avatar:a.width,badge:b.width,height:b.height,top:b.top-a.top,right:b.right-a.right,overflow:document.documentElement.scrollWidth>innerWidth,paddingTop:icon.paddingTop,paddingBottom:icon.paddingBottom};
    });
    if (Math.abs(geometry.avatar-128.8)>0.1 || geometry.badge>90 || geometry.height>28 || Math.abs(geometry.top)>2 || geometry.right<0 || geometry.right>16 || geometry.overflow || geometry.paddingTop!=='5px' || geometry.paddingBottom!=='5px') throw Error(JSON.stringify({theme,width,geometry}));
    await page.locator('[data-pk-vip-open]').click();
    if (await page.locator('[data-pk-vip-modal]').getAttribute('hidden') !== null) throw Error('VIP recognition did not open');
    await page.locator('[data-pk-vip-close]').click();
    if (await page.locator('[data-pk-vip-modal]').getAttribute('hidden') === null) throw Error('VIP recognition did not close');
    if (theme==='BioLyncMidnight' && [390,1440].includes(width)) await page.screenshot({path:`test-output/profile-${width}.png`, fullPage:true});
    console.log('PASS profile UI', theme, width, geometry);
    await page.close();
   }
  }
  for (const width of [390,1440]) {
   const page = await browser.newPage({viewport:{width,height:900}});
   page.on('pageerror', e => errors.push(e.message));
   await page.goto(`${base}/test-output/ui-admin.html`);
   await page.locator('#pk-user-preview').waitFor();
   let loads = 0;
   page.on('framenavigated', frame => {if(frame.parentFrame()) loads++;});
   await page.locator('#pk-refresh-user-preview').click();
   await page.waitForFunction(() => document.querySelector('#pk-user-preview').contentWindow !== null);
   await page.waitForTimeout(300);
   if (!loads || await page.title() !== 'Admin preview QA') throw Error('Preview refresh did not reload frame');
   if (await page.evaluate(() => document.documentElement.scrollWidth>innerWidth)) throw Error('Admin preview overflows');
   if (await page.locator('button[name=save_action]').count()!==2) throw Error('Save controls missing');
   await page.screenshot({path:`test-output/admin-${width}.png`,fullPage:true});
   console.log('PASS admin preview refresh', width);
   await page.close();
  }
  if (errors.length) throw Error(errors.join('\n'));
 } finally {await browser.close(); await new Promise(resolve => server.close(resolve));}
})().catch(e => {console.error(e);process.exit(1)});
