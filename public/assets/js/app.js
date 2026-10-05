(function(){
  var s=document.getElementById('side'),b=document.getElementById('burger'),c=document.getElementById('scrim');
  function t(o){ s&&s.classList.toggle('open',o); c&&c.classList.toggle('show',o); }
  b&&b.addEventListener('click',function(){ t(!s.classList.contains('open')); }); c&&c.addEventListener('click',function(){ t(false); });
  document.querySelectorAll('form[data-confirm]').forEach(function(f){ f.addEventListener('submit',function(e){ if(!confirm(f.dataset.confirm)) e.preventDefault(); }); });
})();
(function(){
  var tabs=document.querySelectorAll('.tabs button');
  tabs.forEach(function(b){ b.addEventListener('click',function(){
    tabs.forEach(function(x){ x.classList.toggle('on',x===b); });
    document.querySelectorAll('.tab').forEach(function(p){ p.classList.toggle('on',p.dataset.pane===b.dataset.tab); }); }); });
  var d=document.getElementById('dob'),a=document.getElementById('rage'),y=document.getElementById('ryear');
  function calc(){ if(d&&a&&y&&d.value&&a.value){ y.value=parseInt(d.value.slice(0,4),10)+parseInt(a.value,10); } }
  d&&d.addEventListener('input',calc); a&&a.addEventListener('input',calc);
  var f=document.querySelector('form.tform'); // jump to the first tab containing an invalid field
  f&&f.addEventListener('invalid',function(e){ var p=e.target.closest('.tab'); if(p&&!p.classList.contains('on')){ var b=document.querySelector('.tabs button[data-tab="'+p.dataset.pane+'"]'); b&&b.click(); } },true);
})();
(function(){ var b=document.getElementById('hostinger'); if(!b) return;
  b.addEventListener('click',function(){ document.getElementById('host').value='smtp.hostinger.com'; document.getElementById('port').value='465'; document.getElementById('enc').value='ssl'; });
  var e=document.getElementById('enc'),p=document.getElementById('port');
  e&&e.addEventListener('change',function(){ if(e.value==='ssl') p.value='465'; else if(e.value==='tls') p.value='587'; });
})();
document.addEventListener('click',function(e){ if(e.target.closest('[data-print]')) window.print(); });
document.querySelectorAll('select[data-autosubmit]').forEach(function(s){ s.addEventListener('change',function(){ s.form.submit(); }); });
