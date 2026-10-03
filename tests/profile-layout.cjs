const {chromium}=require('playwright');
const path=require('path');
(async()=>{const browser=await chromium.launch({headless:true});
for(const theme of ['Ocean','Paper','Midnight','Rose']) for(const width of [375,390,768,1440]) {
 const page=await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'});
 await page.goto('file://'+path.resolve('test-output/'+theme+'.html'));
 const result=await page.evaluate(()=>{const cover=document.querySelector('.biolync-cover').getBoundingClientRect(),avatar=document.querySelector('#avatar').getBoundingClientRect(),video=document.querySelector('video');return {overflow:document.documentElement.scrollWidth>innerWidth,cover:cover.width,left:cover.left,right:cover.right,overlap:cover.bottom-avatar.top,paused:video.paused,html:getComputedStyle(document.documentElement).backgroundColor,body:getComputedStyle(document.body).backgroundColor};});
 if(result.overflow || result.left<0 || result.right>width || result.cover>600 || result.overlap<50 || !result.paused || result.html!==result.body) throw Error(JSON.stringify({theme,width,result}));
 console.log('PASS layout',theme,width);await page.close();
}await browser.close();})().catch(e=>{console.error(e);process.exit(1)});
