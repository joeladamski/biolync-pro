const {chromium}=require('playwright');
const fs=require('fs');
(async()=>{
 const source=fs.readFileSync('resources/views/home.blade.php','utf8');
 const css=source.match(/<style>\s*(\.home-layout[\s\S]*?)<\/style>/)[1];
 const styles=['hope-ui.min.css','custom.min.css','dark.min.css','customizer.min.css','rtl.min.css'].map(file=>fs.readFileSync('assets/css/'+file,'utf8')).join('\n');
 const browser=await chromium.launch({headless:true});
 const page=await browser.newPage();
 try {
  const previewClass=source.match(/class="([^"]*col-md-6[^"]*home-preview)"/)[1];
  const copyClass=source.match(/class="([^"]*col-md-6[^"]*home-copy)"/)[1];
  const navClass=source.match(/<nav class="([^"]*)"/)[1];
  const footerClass=source.match(/<footer class="([^"]*)"/)[1];
  for(const [width,height] of [[320,844],[375,844],[390,844],[844,390],[390,844],[932,430],[430,844],[768,844],[1440,900],[1440,480]]) {
   await page.setViewportSize({width,height});
   await page.setContent(`<style>${styles}${css}</style><nav class="${navClass}" style="height:60px">Navigation</nav><div class="wrapper d-flex"><section class="login-content"><div class="row m-0 align-items-center home-layout"><div id="message" class="${copyClass}"><div class="card card-transparent auth-card shadow-none d-flex mb-0"><div class="card-body text-center"><div style="height:150px">Logo</div><h1>BioLync.Pro</h1><p class="lead">${'Your home base for everything you want to share. '.repeat(8)}</p><button id="login">Log in</button></div></div></div><div class="${previewClass}"><div class="d-flex align-items-center h-100"><div class="card-body"><div class="iframe-container"><iframe title="Preview" srcdoc="Demo"></iframe></div></div></div></div></div></section></div><footer class="${footerClass}" style="height:60px">Footer</footer>`);
   const result=await page.evaluate(()=>{
    const rect=s=>document.querySelector(s).getBoundingClientRect();
    const f=rect('iframe'),c=rect('.iframe-container'),n=rect('nav'),m=rect('#message'),p=rect('.home-preview'),footer=rect('footer'),login=rect('#login');
    return {width:f.width,height:f.height,left:f.left,right:f.right,top:f.top,bottom:f.bottom,navBottom:n.bottom,messageTop:m.top,messageLeft:m.left,previewLeft:p.left,containerHeight:c.height,footerTop:footer.top,loginBottom:login.bottom,overflow:document.documentElement.scrollWidth>innerWidth};
   });
   if(result.overflow || result.left<0 || result.right>width || result.top<result.navBottom || result.footerTop<Math.max(result.bottom,result.loginBottom)) throw Error(JSON.stringify({width,height,result}));
   if(width<768 && (result.height>520.1 || result.messageTop<result.bottom || Math.abs(result.left-(width-result.width)/2)>1)) throw Error(JSON.stringify({width,height,result}));
   if(width>=768 && result.previewLeft>=result.messageLeft) throw Error('Desktop preview must be left of message');
   if(Math.abs(result.containerHeight-result.height)>1) throw Error('Scaled preview leaves mismatched space');
   await page.evaluate(()=>window.scrollTo(0,document.documentElement.scrollHeight));
   const reachable=await page.locator('footer').evaluate(f=>f.getBoundingClientRect().bottom<=innerHeight+1);
   if(!reachable)throw Error('Footer cannot be reached by scrolling');
   console.log('PASS homepage portrait/landscape geometry',width,height);
  }
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
