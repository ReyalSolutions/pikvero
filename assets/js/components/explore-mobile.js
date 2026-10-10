(() => {
 if(!document.body.classList.contains('player-court-search')||!matchMedia('(max-width:768px)').matches)return;
 const escape=value=>{const el=document.createElement('span');el.textContent=value??'';return el.innerHTML;};
 let filter='all',savedOnly=false,lastCourts=[];let saved=[];try{saved=JSON.parse(localStorage.getItem('pikvero_saved_facilities')||'[]');if(!Array.isArray(saved))saved=[];}catch(e){}
 const header=document.createElement('header');header.className='explore-mobile-header';header.innerHTML='<button type="button" aria-label="Back"><i class="bi bi-chevron-left"></i></button><strong>Courts</strong><button type="button" aria-label="Show saved facilities" aria-pressed="false"><i class="bi bi-bookmark"></i></button>';
 document.querySelector('.search-hero-inner').prepend(header);header.querySelector('button').onclick=()=>location.href='/pikvero/public/customer/dashboard.php';header.querySelector('button:last-child').onclick=event=>{savedOnly=!savedOnly;event.currentTarget.setAttribute('aria-pressed',String(savedOnly));event.currentTarget.querySelector('i').className='bi bi-bookmark'+(savedOnly?'-fill':'');if(savedOnly){filter='all';areaBounds=null;resetSearchFilters();chips.querySelectorAll('button').forEach(chip=>chip.classList.toggle('active',chip.dataset.exploreFilter==='all'));}renderExploreCourts(lastCourts);};
 const chips=document.getElementById('quick-chips-container');chips.innerHTML=['All','Indoor','Outdoor','With Pro Shop','Café'].map((name,i)=>'<button type="button" class="quick-chip-pill '+(!i?'active':'')+'" data-explore-filter="'+['all','indoor','outdoor','shop','cafe'][i]+'">'+name+'</button>').join('');
 chips.querySelectorAll('button').forEach(button=>button.onclick=()=>{filter=button.dataset.exploreFilter;chips.querySelectorAll('button').forEach(chip=>chip.classList.toggle('active',chip===button));document.getElementById('filter-type').value=['indoor','outdoor'].includes(filter)?filter:'';loadCourts();});
 const map=document.createElement('div');map.className='explore-mobile-map';map.innerHTML='<div class="explore-map-canvas" aria-label="Interactive court map"></div><button type="button" class="explore-map-search"><i class="bi bi-arrow-repeat"></i> Search this area</button><button type="button" class="explore-map-locate" aria-label="Use my location"><i class="bi bi-crosshair"></i></button>';document.querySelector('.results-main-wrap').prepend(map);
 const filterDialog=document.createElement('dialog');filterDialog.className='explore-filter-modal';filterDialog.setAttribute('aria-label','Filter courts');filterDialog.innerHTML='<header><strong>Filters</strong><button type="button" aria-label="Close filters">×</button></header>';
 const drawer=document.getElementById('filter-drawer');drawer.classList.add('is-expanded');filterDialog.append(drawer);document.body.append(filterDialog);
 drawer.querySelectorAll('input,select,textarea').forEach(field=>field.setAttribute('form','filter-form'));
 const filterToggle=document.getElementById('toggle-filter-btn');
 function closeFilters(){filterDialog.close();filterToggle.setAttribute('aria-expanded','false');}
 window.toggleFilterDrawer=()=>{if(filterDialog.open){closeFilters();return;}filterDialog.showModal();filterToggle.setAttribute('aria-expanded','true');};
 filterDialog.querySelector('header button').onclick=closeFilters;
 filterDialog.addEventListener('close',()=>filterToggle.setAttribute('aria-expanded','false'));
 filterDialog.addEventListener('click',event=>{if(event.target===filterDialog){const bounds=filterDialog.getBoundingClientRect();if(event.clientX<bounds.left||event.clientX>bounds.right||event.clientY<bounds.top||event.clientY>bounds.bottom)closeFilters();}});
 const apply=drawer.querySelector('.btn-apply-filters');apply.type='button';apply.onclick=()=>{updateActiveFilterCount();loadCourts();closeFilters();};
 drawer.querySelector('.btn-reset-filters').onclick=event=>{event.preventDefault();filter='all';areaBounds=null;resetSearchFilters();chips.querySelectorAll('button').forEach(chip=>chip.classList.remove('active'));chips.querySelector('[data-explore-filter="all"]').classList.add('active');};
 const leaflet=L.map(map.querySelector('.explore-map-canvas'),{scrollWheelZoom:false}).setView([12.8,122.5],5);
 L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'}).addTo(leaflet);
 const markers=L.layerGroup().addTo(leaflet),coordinates=new Map(),pending=new Set();let userLocation=null,userMarker=null,areaBounds=null,centered=false,geoQueue=Promise.resolve(),locating=false,locationLabel='Tap Use my location to see distance';
 let coordinateCache={};try{coordinateCache=JSON.parse(localStorage.getItem('pikvero_facility_coordinates')||'{}');}catch(e){}
 function locateFacility(c){
  const key=[c.address,c.city,c.province,'Philippines'].filter(Boolean).join(', ');const cached=coordinateCache[key];
  if(cached&&Date.now()-cached.timestamp<30*86400000){coordinates.set(c.facility_id,cached.point);return;}
  if(pending.has(key))return;pending.add(key);
  geoQueue=geoQueue.then(async()=>{try{
   const endpoint=window.EXPLORE_GEOCODER_URL||'https://photon.komoot.io/api/';
   const response=await fetch(endpoint+'?'+new URLSearchParams({q:key,limit:'3',bbox:'116,4,127,22'}));if(!response.ok)return;
   const result=await response.json();const feature=result.features?.find(item=>item.properties?.countrycode?.toLowerCase()==='ph');if(!feature)return;
   const [lng,lat]=feature.geometry.coordinates;const point=[lat,lng];coordinates.set(c.facility_id,point);coordinateCache[key]={point,timestamp:Date.now()};try{localStorage.setItem('pikvero_facility_coordinates',JSON.stringify(coordinateCache));}catch(e){}
   renderExploreCourts(lastCourts);
  }catch(e){}finally{await new Promise(resolve=>setTimeout(resolve,1100));}});
 }
 map.querySelector('.explore-map-search').onclick=()=>{areaBounds=leaflet.getBounds();renderExploreCourts(lastCourts);};
 function requestLocation(){
  if(locating)return;
  if(!window.isSecureContext){locationLabel='HTTPS is required for location';renderExploreCourts(lastCourts);Toast.info('Secure connection required','Open this site using HTTPS on your phone. Location access is blocked on HTTP network addresses, even when device location is on.');return;}
  if(!navigator.geolocation){locationLabel='Location unavailable';renderExploreCourts(lastCourts);Toast.info('Location unavailable','Your browser does not support location access.');return;}
  locating=true;locationLabel='Getting your location…';renderExploreCourts(lastCourts);
  let finished=false;
  const watchdog=setTimeout(()=>failure({code:3}),45000);
  const success=position=>{if(finished)return;finished=true;clearTimeout(watchdog);locating=false;userLocation=[position.coords.latitude,position.coords.longitude];if(userMarker)leaflet.removeLayer(userMarker);userMarker=L.circleMarker(userLocation,{radius:7,color:'#fff',weight:2,fillColor:'#1677ff',fillOpacity:1}).addTo(leaflet).bindTooltip('Your location');leaflet.setView(userLocation,12);areaBounds=null;centered=true;renderExploreCourts(lastCourts);};
  const failure=error=>{if(finished)return;finished=true;clearTimeout(watchdog);locating=false;locationLabel=error.code===1?'Allow location access to see distance':'Location unavailable · Tap to retry';renderExploreCourts(lastCourts);Toast.info('Location unavailable',error.code===1?'Allow location for this site in your browser and for your browser in phone settings, then retry.':error.code===3?'Location request timed out. Check phone location services and tap Use my location again.':'Turn on device location services and enable location for your browser, then retry.');};
  navigator.geolocation.getCurrentPosition(success,error=>{if(finished)return;if(error.code===1){failure(error);return;}navigator.geolocation.getCurrentPosition(success,failure,{enableHighAccuracy:true,timeout:30000,maximumAge:60000});},{enableHighAccuracy:false,timeout:12000,maximumAge:60000});
 }
 map.querySelector('.explore-map-locate').onclick=requestLocation;
 const locationPrompt=document.createElement('button');locationPrompt.type='button';locationPrompt.className='explore-location-prompt';locationPrompt.innerHTML='<i class="bi bi-crosshair"></i> Use my location to see distances';map.after(locationPrompt);locationPrompt.onclick=requestLocation;
 window.renderExploreCourts=courts=>{
  locationPrompt.hidden=!!userLocation;
  locationPrompt.disabled=locating;locationPrompt.innerHTML='<i class="bi bi-crosshair"></i> '+(locating?'Getting your location…':'Use my location to see distances');
  lastCourts=courts;
  const amenityText=c=>JSON.stringify(c.amenities||[]).toLowerCase();
  const matching=courts.filter(c=>(!savedOnly||saved.includes(String(c.facility_id)))&&(filter!=='shop'||/shop|equipment|rental/.test(amenityText(c)))&&(filter!=='cafe'||/caf[eé]|refreshment|food|drink/.test(amenityText(c)))&&(!areaBounds||(coordinates.has(c.facility_id)&&areaBounds.contains(coordinates.get(c.facility_id)))));
  const facilities=[...new Map(matching.map(c=>[c.facility_id,c])).values()];
  [...new Map(courts.map(c=>[c.facility_id,c])).values()].forEach(locateFacility);
  markers.clearLayers();facilities.forEach(c=>{const point=coordinates.get(c.facility_id);if(!point)return;const marker=L.circleMarker(point,{radius:7,color:'#fff',weight:2,fillColor:'#08783e',fillOpacity:1}).addTo(markers);const popup=document.createElement('div');popup.textContent=c.facility_name;const button=document.createElement('button');button.type='button';button.textContent='Show facility';button.onclick=()=>document.querySelector('[data-explore-facility="'+Number(c.facility_id)+'"]')?.scrollIntoView({behavior:'smooth',block:'center'});popup.append(document.createElement('br'),button);marker.bindPopup(popup);});
  if(!centered&&markers.getLayers().length){leaflet.fitBounds(L.featureGroup(markers.getLayers()).getBounds(),{padding:[20,20],maxZoom:13});centered=true;}
  const grid=document.getElementById('courts-results-grid');grid.replaceChildren();
  facilities.forEach(c=>{
   const card=document.createElement('article');card.className='explore-facility-card';card.dataset.exploreFacility=c.facility_id;const id=String(c.facility_id);const active=saved.includes(id);
   const amenities=[...new Set((c.amenities||[]).map(a=>a.name).filter(Boolean))];
   const tags=[c.court_type,...amenities.slice(0,3)].filter(Boolean);
   if(amenities.length>3)tags.push('+'+(amenities.length-3));
   const point=coordinates.get(c.facility_id);c.distance_km=userLocation&&point?(leaflet.distance(userLocation,point)/1000):null;
   card.innerHTML='<button type="button" class="explore-facility-open"><img loading="lazy" decoding="async" src="'+escape(c.images?.[0]?.image_path||c.image_path||'/pikvero/assets/images/logo.png')+'" alt="'+escape(c.facility_name)+'"><span><strong>'+escape(c.facility_name)+'</strong>'+(c.avg_rating?'<small class="explore-rating">★ '+Number(c.avg_rating).toFixed(1)+' ('+Number(c.total_reviews||0)+')</small>':'')+'<small>'+escape(c.city||'')+'</small><small class="explore-distance"><i class="bi bi-geo-alt"></i> '+escape(c.distance_km!==null?(Number(c.distance_km)<1?'~'+Math.round(Number(c.distance_km)*1000)+' m away':'~'+Number(c.distance_km).toFixed(1)+' km away'):(userLocation?'Distance unavailable':locationLabel))+'</small><span class="explore-facility-tags">'+tags.map(tag=>'<span>'+escape(tag)+'</span>').join('')+'</span></span></button><button type="button" class="explore-save" aria-label="Save '+escape(c.facility_name)+'" aria-pressed="'+active+'"><i class="bi bi-heart'+(active?'-fill':'')+'"></i></button>';
   card.querySelector('img').onerror=function(){this.onerror=null;this.src='/pikvero/assets/images/logo.png';};card.querySelector('.explore-facility-open').onclick=()=>openFacilityBooking(c.facility_id,c.id);
   card.querySelector('.explore-save').onclick=()=>{saved=saved.includes(id)?saved.filter(value=>value!==id):[...saved,id];try{localStorage.setItem('pikvero_saved_facilities',JSON.stringify(saved));}catch(e){}renderExploreCourts(lastCourts);};grid.append(card);
  });
  if(!facilities.length)grid.innerHTML='<div class="explore-empty">'+(['indoor','outdoor'].includes(filter)?'No court listed':'No matching facilities. Try another filter.')+'</div>';
 };
 if(navigator.permissions)navigator.permissions.query({name:'geolocation'}).then(permission=>{if(permission.state==='granted')requestLocation();permission.onchange=()=>{if(permission.state==='granted')requestLocation();};}).catch(()=>{});
})();
