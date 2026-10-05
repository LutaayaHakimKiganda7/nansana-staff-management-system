(function(g){
  var DB='mgtm-offline',ST='queue';
  function open(){ return new Promise(function(res,rej){ var r=indexedDB.open(DB,1); r.onupgradeneeded=function(){ r.result.createObjectStore(ST,{keyPath:'id',autoIncrement:true}); }; r.onsuccess=function(){res(r.result);}; r.onerror=function(){rej(r.error);}; }); }
  function tx(mode,fn){ return open().then(function(db){ return new Promise(function(res,rej){ var t=db.transaction(ST,mode),req=fn(t.objectStore(ST)); t.oncomplete=function(){ db.close(); res(req&&req.result); }; t.onerror=function(){ rej(t.error); }; }); }); }
  g.MGTMQueue={ add:function(i){return tx('readwrite',function(s){return s.add(i);});}, put:function(i){return tx('readwrite',function(s){return s.put(i);});}, remove:function(id){return tx('readwrite',function(s){return s.delete(id);});},
    all:function(){return tx('readonly',function(s){return s.getAll();}).then(function(r){return r||[];});}, clear:function(){return tx('readwrite',function(s){return s.clear();});} };
})(self);
