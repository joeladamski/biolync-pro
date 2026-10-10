const {chromium} = require('playwright');
const fs = require('fs');
const path = require('path');
const http = require('http');
const assert = require('assert');
const root = path.resolve(__dirname, '..');
const server = http.createServer((req, res) => {
  if (req.url === '/favicon.ico') { res.writeHead(204);res.end();return; }
  if (req.method === 'POST') { res.writeHead(200, {'Content-Type':'text/html'}); res.end('<title>Saved</title><h1>Saved</h1>'); return; }
  const file = path.join(root, req.url === '/' ? 'test-output/ui-rich-text.html' : req.url);
  if (!file.startsWith(root) || !fs.existsSync(file)) { res.writeHead(404);res.end();return; }
  res.setHeader('Content-Type', file.endsWith('.css') ? 'text/css' : file.endsWith('.js') ? 'application/javascript' : 'text/html; charset=utf-8');
  let content=fs.readFileSync(file);
  if(file.endsWith('.html')) content=content.toString().replace(/http:\/\/localhost(?=\/)/g, 'http://'+req.headers.host);
  res.end(content);
});
(async () => {
  await new Promise(r=>server.listen(0, '127.0.0.1', r));
  const url = `http://127.0.0.1:${server.address().port}`;
  const browser = await chromium.launch();
  try {
    for (const width of [390,1440]) {
      const page = await browser.newPage({viewport:{width,height:1000}});
      const errors=[]; page.on('pageerror', e=>errors.push(e.message));
      page.on('console', msg=>{if(msg.type()==='error')errors.push(msg.text())});
      await page.goto(url);
      assert.equal(await page.title(),'Admin content editor QA');
      await page.waitForFunction(()=>document.querySelector('#home-message').dataset.editorReady);
      assert.equal(await page.locator('.jodit-container').count(),2, 'only visible forms initialize');
      assert(await page.locator('.jodit-wysiwyg').first().innerHTML().then(s=>s.includes('Be found 💋')));
      assert.equal(await page.locator('.jodit-wysiwyg').first().evaluate(el=>getComputedStyle(el).backgroundColor), 'rgb(35, 40, 56)', 'editor matches dark admin background');
      await page.screenshot({path:`test-output/rich-text-${width}.png`,fullPage:false});
      assert(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth + 1), 'editor fits viewport');
      await page.locator('summary').filter({hasText:'Edit existing page'}).click();
      await page.waitForFunction(()=>document.querySelectorAll('.jodit-container').length===3);
      await page.locator('#show-ui').click();
      await page.waitForFunction(()=>document.querySelector('#preview-description').dataset.editorReady);
      assert.equal(await page.locator('.jodit-container').count(),4, 'tab and collapsed page editors initialize once');
      const field = page.locator('#page-body-new');
      const form = field.locator('xpath=ancestor::form');
      const editor = form.locator('.jodit-container');
      // Use the actual contenteditable, toolbar, and source controls.
      const input = editor.locator('.jodit-wysiwyg');
      await input.fill('A new page 💋');
      await input.selectText();
      await editor.locator('[data-ref="bold"]').click();
      assert(await input.innerHTML().then(s=>/<(strong|b)>/.test(s)), 'toolbar applies bold');
      await editor.locator('[data-ref="source"]').click();
      const source = editor.locator('.jodit-source__mirror');
      await source.fill('<h2>Saved heading 💋</h2><p><strong>Saved bold</strong></p>');
      await page.locator('#page-title-new').fill('Saved page');
      await page.locator('#page-slug-new').fill('saved-page');
      const submitted = page.waitForRequest(r=>r.method()==='POST');
      await form.getByRole('button',{name:'Save page',exact:true}).click();
      const request = await submitted;
      const body = new URLSearchParams(request.postData());
      assert(body.get('body').includes('Saved heading 💋') && body.get('body').includes('<strong>Saved bold</strong>'), 'source edits synchronize before submission');
      assert.equal(body.get('body_format'),'html');
      assert.equal(errors.length,0,errors.join('\n'));
      console.log(`PASS rich editor at ${width}px: render, toolbar, deferred editors, source-mode form submission, console, no overflow`);
      await page.close();
    }
    // Asset failure retains a normal textarea rather than losing the content field.
    const page=await browser.newPage();
    await page.route('**/assets/vendor/jodit/jodit.min.js', route=>route.fulfill({status:200,contentType:'application/javascript',body:''}));
    await page.goto(url);
    assert(await page.locator('#home-message').isVisible());
    await page.locator('#home-message').fill('<p>Fallback edit</p>');
    const submitted = page.waitForRequest(r=>r.method()==='POST');
    await page.getByRole('button',{name:'Save home',exact:true}).click();
    assert.equal(new URLSearchParams((await submitted).postData()).get('message'),'<p>Fallback edit</p>');
    console.log('PASS editor asset failure: textarea remains editable and submits');
  } finally { await browser.close(); server.close(); }
})().catch(e=>{console.error(e);server.close();process.exit(1)});
