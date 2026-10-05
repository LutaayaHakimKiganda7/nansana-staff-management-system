importScripts('assets/js/queue.js');
var V='mgtm-v2',SHELL=['offline.html','assets/css/app.css','assets/img/nmc.jpg','assets/img/icon-192.png','assets/img/icon-512.png','assets/js/app.js','assets/js/queue.js','assets/js/offline.js','manifest.webmanifest'];
var READ=/^(dashboard\/index|teachers\/(index|form)|ledger\/(index|profile)|schools\/(index|form)|retirement\/index)$/, QUEUE=/^(teachers|schools)\/save$/;
self.addEventListener('install',function(e){ e.waitUntil(caches.open(V).then(function(c){return c.addAll(SHELL);}).then(function(){return self.skipWaiting();})); });
self.addEventListener('activate',function(e){ e.waitUntil(caches.keys().then(function(k){return Promise.all(k.filter(function(x){return x!==V;}).map(function(x){return caches.delete(x);}));}).then(function(){return self.clients.claim();})); });
self.addEventListener('message',function(e){ if(e.data==='clear') e.waitUntil(caches.delete(V).then(function(){return caches.open(V);}).then(function(c){return c.addAll(SHELL);})); });
function route(u){ var r=u.searchParams.get('r'); if(r) return r; return /(index\.php|\/)$/.test(u.pathname)?'dashboard/index':''; }
function within(p,ms){ return new Promise(function(res,rej){ var t=setTimeout(function(){rej(new Error('timeout'));},ms); p.then(function(v){clearTimeout(t);res(v);},function(e){clearTimeout(t);rej(e);}); }); }
function netFirst(req){
  return within(fetch(req),8000).then(function(res){ if(res.ok&&!res.redirected){ var cp=res.clone(); caches.open(V).then(function(c){c.put(req,cp);}); } return res; })
    .catch(function(){ return caches.match(req).then(function(m){ return m||caches.match('offline.html'); }); });
}
function swr(req){ return caches.open(V).then(function(c){ return c.match(req).then(function(m){ var n=fetch(req).then(function(r){ if(r.ok) c.put(req,r.clone()); return r; }).catch(function(){return m;}); return m||n; }); }); }
function queued(){ var home=self.registration.scope+'index.php?r=dashboard/index';
  return new Response('<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Saved offline</title><body style="font:16px system-ui;background:#12161b;color:#e8ecf1;display:grid;place-items:center;min-height:100vh;margin:0"><div style="max-width:420px;padding:1.5rem"><h2>Saved on this device</h2><p style="color:#93a0b0">There is no connection right now. Your changes are stored here and will be sent when you are back online. Open "Work offline" in the menu to review them.</p><a style="color:#f0c23b" href="'+home+'">Back to dashboard</a></div>',{status:200,headers:{'Content-Type':'text/html; charset=utf-8'}}); }
function handlePost(req){
  var cp=req.clone(); return fetch(req).catch(function(){ return cp.formData().then(function(fd){ var f=[]; fd.forEach(function(v,k){f.push([k,v]);});
    var t=route(new URL(req.url))==='teachers/save', edit=parseInt(fd.get('id'),10)>0, nm=((fd.get('first_name')||fd.get('name')||'')+' '+(fd.get('surname')||'')).trim();
    return MGTMQueue.add({url:req.url,label:(edit?'Edit ':'New ')+(t?'teacher: ':'school: ')+nm,fields:f,created:Date.now(),status:'waiting'}); }).then(queued); });
}
self.addEventListener('fetch',function(e){
  var req=e.request,u=new URL(req.url); if(u.origin!==location.origin) return;
  if(req.method==='POST'){ if(QUEUE.test(route(u))) e.respondWith(handlePost(req)); return; }
  if(req.method!=='GET') return;
  if(u.pathname.indexOf('/uploads/photos/')>-1||u.pathname.indexOf('/assets/')>-1){ e.respondWith(swr(req)); return; }
  var prime=req.headers.get('X-Offline-Prime'); if(req.mode==='navigate'||prime){
    if(READ.test(route(u))) e.respondWith(netFirst(req)); else if(req.mode==='navigate') e.respondWith(fetch(req).catch(function(){return caches.match('offline.html');})); }
});
