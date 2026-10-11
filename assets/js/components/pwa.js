(() => {
 if(window.pikveroPwaLoaded)return;window.pikveroPwaLoaded=true;
 if(!window.isSecureContext)return;
 const scriptPath=new URL(document.currentScript.src).pathname;
 const base=location.hostname==='pikvero.wuaze.com'?'':scriptPath.slice(0,scriptPath.indexOf('/assets/'));
 const manifest=document.querySelector('link[rel="manifest"]') || document.head.appendChild(Object.assign(document.createElement('link'),{rel:'manifest'}));
 manifest.href=base+'/manifest.webmanifest';
 if('serviceWorker' in navigator)navigator.serviceWorker.register(base+'/service-worker.js?v=20261011',{scope:base+'/',updateViaCache:'none'}).catch(error=>console.warn('Pikvero service worker unavailable',error));
 let promptEvent=null;
 const standalone=()=>matchMedia('(display-mode: standalone)').matches || navigator.standalone===true;
 const ios=/iPad|iPhone|iPod/.test(navigator.userAgent)||(navigator.platform==='MacIntel'&&navigator.maxTouchPoints>1);
 let dismissed=false;try{dismissed=sessionStorage.getItem('pikvero-install-dismissed')==='1';}catch(e){}
 const banner=document.createElement('div');banner.hidden=true;banner.style.cssText='position:fixed;left:12px;right:12px;bottom:calc(86px + env(safe-area-inset-bottom));z-index:1000;max-width:460px;margin:auto;display:none;align-items:center;gap:10px;background:white;color:#214134;border:1px solid #d8e8de;border-radius:12px;padding:12px;box-shadow:0 4px 20px #003d2d25;font-family:system-ui';
 banner.innerHTML='<img src="'+base+'/assets/images/pwa/icon-192.png" alt="" width="36" height="36"><span style="flex:1;font-size:13px"><strong>Install Pikvero</strong><br><small>Open it like an app on your device</small></span><button type="button" style="border:0;background:#08783e;color:white;padding:10px;border-radius:8px">Install</button><button type="button" aria-label="Dismiss install suggestion" style="border:0;background:none;font-size:20px;color:#647d6e">×</button>';
 document.body.append(banner);
 function show(){if(dismissed||standalone()||document.querySelector('.op-event-details,.booking-confirmed-screen'))return;banner.hidden=false;banner.style.display='flex';}
 function hide(){banner.hidden=true;banner.style.display='none';}
 banner.querySelectorAll('button')[1].onclick=()=>{dismissed=true;hide();try{sessionStorage.setItem('pikvero-install-dismissed','1');}catch(e){}};
 banner.querySelector('button').onclick=async()=>{
  if(promptEvent){const event=promptEvent;promptEvent=null;hide();await event.prompt();await event.userChoice;}
  else if(ios){const dialog=document.createElement('dialog');dialog.style.cssText='border:0;border-radius:12px;max-width:300px;padding:24px;font-family:system-ui;color:#214134';dialog.innerHTML='<h3>Install Pikvero</h3><p>Open Pikvero in Safari, tap Share, then choose Add to Home Screen and tap Add.</p><button type="button">Got it</button>';document.body.append(dialog);dialog.querySelector('button').onclick=()=>dialog.close();dialog.onclose=()=>dialog.remove();dialog.showModal();}
 };
 window.addEventListener('beforeinstallprompt',event=>{event.preventDefault();promptEvent=event;show();});
 window.addEventListener('appinstalled',()=>{promptEvent=null;hide();});
 if(ios&&!standalone())show();
})();
