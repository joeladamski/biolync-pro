const {chromium}=require('playwright');
const fs=require('fs');
(async()=>{
 const source=fs.readFileSync('resources/views/home.blade.php','utf8');
 const css=source.match(/<style>\s*(\.home-layout[\s\S]*?)<\/style>/)[1];
 const bootstrap=['hope-ui.min.css','custom.min.css','dark.min.css','customizer.min.css','rtl.min.css'].map(file=>fs.readFileSync('assets/css/'+file,'utf8')).join('\n');
 const browser=await chromium.launch({headless:true});
 try {
  for(const width of [320,375,390,430,768,1440]) {
   const page=await browser.newPage({viewport:{width,height:844}});
   await page.setContent(`<style>${bootstrap}${css}</style><nav class="navbar fixed-top home-nav" style="height:60px">Navigation</nav><div class="row m-0 home-layout"><div id="message" class="col-md-6 order-2 order-md-1">Home message</div><div class="col-md-6 order-1 order-md-2 home-preview"><div><div class="card-body"><div class="iframe-container"><iframe title="Preview" srcdoc="Demo"></iframe></div></div></div></div></div>`);
   const result=await page.evaluate(()=>{
    const rect=s=>document.querySelector(s).getBoundingClientRect();
    const f=rect('iframe'),c=rect('.iframe-container'),n=rect('nav'),m=rect('#message');
    return {width:f.width,height:f.height,left:f.left,right:f.right,top:f.top,navBottom:n.bottom,messageTop:m.top,containerHeight:c.height,overflow:document.documentElement.scrollWidth>innerWidth};
   });
   if(result.overflow || result.left<0 || result.right>width) throw Error(JSON.stringify({width,result}));
   if(width<768 && (result.top<result.navBottom+19 || result.height>520.1 || result.messageTop<result.top+result.height || Math.abs(result.left-(width-result.width)/2)>1 || Math.abs(result.containerHeight-result.height)>1)) throw Error(JSON.stringify({width,result}));
   console.log('PASS homepage geometry',width);
   await page.close();
  }
 } finally {await browser.close();}
})().catch(e=>{console.error(e);process.exit(1)});
