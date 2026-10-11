const BASE = new URL('./',self.location.href).pathname.replace(/\/$/,'');
const CACHE = 'pikvero-pwa-v2-'+BASE;
const OFFLINE = BASE+'/public/offline.html';
const ICONS = [BASE+'/assets/images/pwa/icon-192.png',BASE+'/assets/images/pwa/icon-512.png'];
self.addEventListener('install', event => {
 event.waitUntil(caches.open(CACHE).then(cache => cache.addAll([OFFLINE,...ICONS])));
});
self.addEventListener('activate', event => {
 event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('pikvero-pwa-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
 if(event.request.method !== 'GET')return;
 const url=new URL(event.request.url);
 if(url.origin !== self.location.origin || !url.pathname.startsWith(BASE+'/'))return;
 // Keep account data, bookings, payments and API responses on the network.
 if(event.request.mode === 'navigate'){
  event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
 }else if([OFFLINE,...ICONS].includes(url.pathname)){
  event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request)));
 }
});
