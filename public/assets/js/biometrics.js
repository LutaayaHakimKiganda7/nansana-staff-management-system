(function(){
  var root=document.getElementById('bio'); if(!root) return; var D=root.dataset, consent=document.getElementById('consent'), msg=document.getElementById('msg');
  function say(t,ok){ msg.hidden=false; msg.className='alert '+(ok?'ok':'danger'); msg.textContent=t; }
  function post(slot,fd){
    if(!consent||!consent.checked){ say('Tick the consent box first.'); return Promise.reject(); }
    fd.append('_csrf',D.csrf); fd.append('teacher_id',D.teacher); fd.append('slot',slot); fd.append('consent','1');
    return fetch(D.save,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.text();}).then(function(t){ var j; try{ j=JSON.parse(t); }catch(e){ throw new Error(t.slice(0,150)||'Server error'); } if(j.error) throw new Error(j.error); return j; });
  }
  function summary(j){ document.getElementById('nfp').textContent=j.fp; document.getElementById('nface').textContent=j.face?'captured':'missing'; if(j.reset) say('Verification was reset because biometrics changed.',true); }
  function showFinger(card,j){ var i=card.querySelector('img'); i.src=D.image+j.id+'&t='+Date.now(); i.hidden=false; card.classList.add('done'); card.querySelector('.st').textContent='Captured'; card.querySelector('.del').hidden=false; summary(j); }
  function fail(e){ if(e&&e.message) say(e.message); }
  document.querySelectorAll('.finger').forEach(function(card){
    var slot=card.dataset.slot, file=card.querySelector('input[type=file]');
    file.addEventListener('change',function(){ if(!file.files[0]) return; var fd=new FormData(); fd.append('file',file.files[0]); post(slot,fd).then(function(j){ showFinger(card,j); say('Saved.',true); }).catch(fail); file.value=''; });
    card.querySelector('.del').addEventListener('click',function(){ if(!confirm('Remove this fingerprint?')) return; var fd=new FormData(); fd.append('_csrf',D.csrf); fd.append('teacher_id',D.teacher); fd.append('slot',slot);
      fetch(D.remove,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(j){ card.classList.remove('done'); card.querySelector('img').hidden=true; card.querySelector('.st').textContent='Empty'; card.querySelector('.del').hidden=true; summary(j); }).catch(fail); });
    var scan=card.querySelector('.scan');
    if(window.MGTMFingerprint&&window.MGTMFingerprint.capture){ scan.hidden=false; document.getElementById('scannernote').textContent='Scanner connected. Use Scan, or upload an image instead.';
      scan.addEventListener('click',function(){ say('Place the finger on the scanner…',true); window.MGTMFingerprint.capture(slot).then(function(r){ var fd=new FormData(); fd.append('image',r.image); if(r.template) fd.append('template',r.template); if(r.quality!=null) fd.append('quality',r.quality); return post(slot,fd); }).then(function(j){ showFinger(card,j); say('Fingerprint saved.',true); }).catch(function(e){ fail(e||{message:'Scan failed.'}); }); }); }
  });
  var cam=document.getElementById('cam'),snap=document.getElementById('snap'),start=document.getElementById('camstart'),img=document.getElementById('faceimg'),stream=null;
  function saveFace(dataUrl,file){ var fd=new FormData(); if(file) fd.append('file',file); else fd.append('image',dataUrl); post('face',fd).then(function(j){ img.src=D.image+j.id+'&t='+Date.now(); img.hidden=false; summary(j); say('Face saved.',true); }).catch(fail); }
  start.addEventListener('click',function(){ if(!navigator.mediaDevices){ say('The camera needs a secure (https) connection.'); return; }
    navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:640},height:{ideal:480}}}).then(function(s){ stream=s; cam.srcObject=s; cam.hidden=false; img.hidden=true; snap.hidden=false; start.hidden=true; }).catch(function(){ say('The camera could not be opened. Allow camera access or upload a photo.'); }); });
  snap.addEventListener('click',function(){ var c=document.createElement('canvas'),w=Math.min(480,cam.videoWidth||480); c.width=w; c.height=Math.round(w*(cam.videoHeight||360)/(cam.videoWidth||480)); c.getContext('2d').drawImage(cam,0,0,c.width,c.height);
    saveFace(c.toDataURL('image/jpeg',0.85)); stream&&stream.getTracks().forEach(function(t){t.stop();}); cam.hidden=true; snap.hidden=true; start.hidden=false; });
  document.getElementById('facefile').addEventListener('change',function(e){ if(e.target.files[0]) saveFace(null,e.target.files[0]); e.target.value=''; });
})();
