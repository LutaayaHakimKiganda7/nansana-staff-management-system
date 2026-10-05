(function(){
  if(!('serviceWorker' in navigator)||!window.MGTMQueue) return;
  var Q=MGTMQueue, meta=function(n){ var m=document.querySelector('meta[name="'+n+'"]'); return m?m.content:''; }, base=meta('app-base');
  function esc(s){ return String(s).replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];}); }
  navigator.serviceWorker.register(base+'/sw.js').catch(function(){});
  var panel,syncing=false;
  function badge(n){ var b=document.getElementById('offcount'); if(b){ b.hidden=!n; b.textContent=n; } }
  function refreshCount(){ return Q.all().then(function(a){ badge(a.length); return a; }); }
  function sync(){
    if(syncing||!navigator.onLine) return Promise.resolve(); syncing=true; var done=0;
    return Q.all().then(function(items){ var chain=Promise.resolve(); items.forEach(function(it){ if(it.status==='failed') return;
      chain=chain.then(function(){ var fd=new FormData(); it.fields.forEach(function(p){ fd.append(p[0],p[0]==='_csrf'?meta('csrf-token'):p[1]); });
        return fetch(it.url,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){
          if(r.url.indexOf('auth/login')>-1) throw {login:true};
          if(/r=(ledger%2Fprofile|ledger\/profile|schools%2Findex|schools\/index)/.test(r.url)){ done++; return Q.remove(it.id); }
          return r.text().then(function(h){ var d=new DOMParser().parseFromString(h,'text/html').querySelector('.alert.danger'); it.status='failed'; it.error=d?d.textContent.trim():('The server refused this change ('+r.status+').'); return Q.put(it); }); }); }); }); return chain; })
    .catch(function(e){ if(e&&e.login) alert('Sign in again, then press "Send now".'); }).then(function(){ syncing=false; return refreshCount(); }).then(function(){ if(done) toast(done+' offline change(s) sent.'); render(); });
  }
  function toast(t){ var d=document.createElement('div'); d.className='alert ok offtoast'; d.textContent=t; document.body.appendChild(d); setTimeout(function(){d.remove();},4000); }
  function prime(btn,out){
    btn.disabled=true; out.textContent='Preparing…';
    fetch(base+'/index.php?r=offline/manifest',{credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){ var urls=j.urls,i=0,ok=0;
      function next(){ if(i>=urls.length) return; var u=urls[i++]; out.textContent='Saving '+i+' of '+urls.length+'…';
        return fetch(u,{credentials:'same-origin',headers:{'X-Offline-Prime':'1'}}).then(function(r){ if(r.ok) ok++; }).catch(function(){}).then(next); }
      return Promise.all([next(),next(),next(),next()]).then(function(){ localStorage.setItem('mgtm_primed',new Date().toISOString()); out.textContent='Saved '+ok+' pages for offline use.'; }); })
    .catch(function(){ out.textContent='Could not prepare offline data. Check your connection.'; }).then(function(){ btn.disabled=false; });
  }
  function render(){ if(!panel||panel.hidden) return; Q.all().then(function(items){
    var last=localStorage.getItem('mgtm_primed');
    panel.querySelector('.offbody').innerHTML='<p>'+(navigator.onLine?'<span class="pill active">Online</span>':'<span class="pill disabled">Offline</span>')+'</p>'+
      '<h3>Offline data</h3><p class="muted">'+(last?'Last saved '+esc(new Date(last).toLocaleString()):'Nothing saved yet.')+'</p><button class="btn" id="offprime">Save for offline use</button> <span id="offprog" class="muted"></span>'+
      '<h3>Changes waiting to send ('+items.length+')</h3><ul class="feed">'+items.map(function(it){ return '<li><div><b>'+esc(it.label)+'</b><small>'+(it.status==='failed'?'Failed: '+esc(it.error||''):'Waiting')+'</small></div><button class="link" data-d="'+it.id+'">Discard</button></li>'; }).join('')+(items.length?'':'<li class="empty">None.</li>')+'</ul>'+
      (items.length?'<button class="btn primary" id="offsend">Send now</button>':'');
    panel.querySelector('#offprime').onclick=function(){ prime(this,panel.querySelector('#offprog')); };
    var s=panel.querySelector('#offsend'); if(s) s.onclick=function(){ items.forEach(function(i){ if(i.status==='failed'){ i.status='waiting'; Q.put(i); } }); sync(); };
    panel.querySelectorAll('[data-d]').forEach(function(b){ b.onclick=function(){ if(confirm('Discard this unsent change?')) Q.remove(+b.dataset.d).then(refreshCount).then(render); }; }); }); }
  function openPanel(){ if(!panel){ panel=document.createElement('div'); panel.className='offpanel card'; panel.innerHTML='<div class="row between"><h2>Work offline</h2><button class="link" id="offclose">Close</button></div><div class="offbody"></div>'; document.body.appendChild(panel); panel.querySelector('#offclose').onclick=function(){panel.hidden=true;}; } panel.hidden=false; render(); }
  document.addEventListener('click',function(e){ if(e.target.closest('#offopen')) openPanel(); });
  document.addEventListener('submit',function(e){ var f=e.target; if(!f.action||f.action.indexOf('auth%2Flogout')<0&&f.action.indexOf('auth/logout')<0) return; e.preventDefault();
    Q.all().then(function(a){ if(a.length&&!confirm(a.length+' unsent offline change(s) will be lost if you sign out. Sign out anyway?')) return; return Q.clear().then(function(){ if(navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage('clear'); localStorage.removeItem('mgtm_primed'); f.submit(); }); }); },true);
  window.addEventListener('online',function(){ sync(); render(); }); window.addEventListener('offline',function(){ render(); });
  refreshCount().then(function(a){ if(a.length&&navigator.onLine) sync(); });
})();
