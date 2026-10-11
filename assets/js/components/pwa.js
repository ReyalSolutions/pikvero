(() => {
 if(window.pikveroPwaLoaded)return;window.pikveroPwaLoaded=true;
 // Online-only shortcuts: no manifest requests or worker registration.
 document.querySelectorAll('link[rel="manifest"]').forEach(link=>link.remove());
 const scriptPath=new URL(document.currentScript.src).pathname;
 const base=location.hostname==='pikvero.wuaze.com'?'':scriptPath.slice(0,scriptPath.indexOf('/assets/'));
 if('serviceWorker' in navigator){
  navigator.serviceWorker.getRegistrations().then(registrations=>Promise.all(registrations.filter(registration=>{
   const worker=registration.active||registration.waiting||registration.installing;
   return worker && new URL(worker.scriptURL).origin===location.origin && /\/service-worker\.js$/.test(new URL(worker.scriptURL).pathname);
  }).map(registration=>registration.unregister()))).catch(()=>{});
 }
 if('caches' in window)caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('pikvero-pwa-')).map(key=>caches.delete(key)))).catch(()=>{});
 const ios=/iPad|iPhone|iPod/.test(navigator.userAgent)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1);
 const android=/Android/i.test(navigator.userAgent);
 if(!ios&&!android)return;
 if(matchMedia('(display-mode: standalone)').matches||navigator.standalone===true)return;
 try{if(sessionStorage.getItem('pikvero-shortcut-dismissed')==='1')return;}catch(e){}
 const banner=document.createElement('div');
 banner.style.cssText='position:fixed;left:12px;right:12px;bottom:calc(86px + env(safe-area-inset-bottom));z-index:1000;max-width:460px;margin:auto;display:flex;align-items:center;gap:10px;background:white;color:#214134;border:1px solid #d8e8de;border-radius:12px;padding:12px;box-shadow:0 4px 20px #003d2d25;font-family:system-ui';
 banner.innerHTML='<img src="'+base+'/assets/images/pwa/icon-192.png" alt="" width="36" height="36"><span style="flex:1;font-size:13px"><strong>Pikvero shortcut</strong><br><small>Quick access · Internet required</small></span><button type="button" style="border:0;background:#08783e;color:white;padding:10px;border-radius:8px">How to add</button><button type="button" aria-label="Dismiss shortcut suggestion" style="border:0;background:none;font-size:20px;color:#647d6e">×</button>';
 document.body.append(banner);
 banner.querySelectorAll('button')[1].onclick=()=>{banner.remove();try{sessionStorage.setItem('pikvero-shortcut-dismissed','1');}catch(e){}};
 banner.querySelector('button').onclick=()=>{
  const dialog=document.createElement('dialog');dialog.setAttribute('aria-label','Add Pikvero to your home screen');
  dialog.style.cssText='border:1px solid #d8e8de;border-radius:14px;max-width:340px;padding:22px;font-family:system-ui;color:#214134;line-height:1.6';
  dialog.innerHTML='<h3 style="margin:0 0 10px;font-size:18px">Add Pikvero shortcut</h3><p style="font-size:14px">'+(ios?'Open this page in Safari. Tap Share, choose Add to Home Screen, then tap Add.':'Open this page in Chrome. Tap the three-dot menu, choose Add to Home screen or Install and create shortcut, then choose Create shortcut and confirm.')+'</p><p style="font-size:12px;color:#647d6e">The shortcut opens this website. An internet connection is required. Menu names may vary by browser.</p><button type="button" style="border:0;border-radius:8px;background:#08783e;color:white;padding:10px 16px">Got it</button>';
  document.body.append(dialog);dialog.querySelector('button').onclick=()=>dialog.close();dialog.onclose=()=>dialog.remove();dialog.showModal();
 };
})();
