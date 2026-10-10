(() => {
 if(!matchMedia('(max-width:768px)').matches)return;
 const escape=value=>{const el=document.createElement('span');el.textContent=value??'';return el.innerHTML;};
 const main=document.getElementById('page-content');const top=document.createElement('div');top.className='open-play-mobile-top';top.innerHTML='<header><a href="/pikvero/public/customer/dashboard.php" aria-label="Back to home"><i class="bi bi-chevron-left"></i></a><strong>Play</strong><div class="open-play-mobile-header-actions"><button type="button" class="open-play-mobile-pass-icon" aria-label="My Passes" title="My Passes"><i class="bi bi-ticket-perforated"></i></button><button type="button" aria-label="Search sessions"><i class="bi bi-search"></i></button></div></header><div class="open-play-mobile-search" hidden><input type="search" placeholder="Search sessions or facilities" aria-label="Search sessions"></div><div class="open-play-mobile-tabs" role="tablist"><button type="button" role="tab" aria-selected="true" data-kind="open">Open Play</button><button type="button" role="tab" aria-selected="false" data-kind="events">Events</button><button type="button" role="tab" aria-selected="false" data-kind="tournaments">Tournaments</button></div>';
 main.prepend(top);let sessions=[],kind='open',query='';let saved=[];const savedKey='pikvero_saved_open_play';try{saved=JSON.parse(localStorage.getItem(savedKey)||'[]');if(!Array.isArray(saved))saved=[];}catch(e){}
 const passes=document.createElement('button');passes.className='open-play-mobile-passes';passes.type='button';passes.textContent='View My Passes';main.append(passes);passes.onclick=()=>{switchTab('my-passes');top.querySelectorAll('[data-kind]').forEach(button=>button.setAttribute('aria-selected','false'));};
 top.querySelector('.open-play-mobile-pass-icon').onclick=passes.onclick;
 top.querySelector('header button[aria-label="Search sessions"] ').onclick=()=>{const search=top.querySelector('.open-play-mobile-search');search.hidden=!search.hidden;if(!search.hidden)search.querySelector('input').focus();};top.querySelector('input').oninput=event=>{query=event.target.value.trim().toLowerCase();render();};
 top.querySelectorAll('[data-kind]').forEach(button=>button.onclick=()=>{kind=button.dataset.kind;top.querySelectorAll('[data-kind]').forEach(tab=>tab.setAttribute('aria-selected',String(tab===button)));document.getElementById('tab-available').style.display='block';document.getElementById('tab-my-passes').style.display='none';if(kind==='open'){loadAvailableSessions();}else{render();}});
 let visibleCount=6;
 window.renderMobileOpenPlay=data=>{sessions=data;visibleCount=6;render();};
 function render(){
  const grid=document.getElementById('sessions-grid');grid.replaceChildren();
  const items=kind==='open'?sessions.filter(s=>(s.title+' '+s.facility_name).toLowerCase().includes(query)):[];
  if(!items.length){grid.innerHTML='<div class="open-play-mobile-empty">'+(kind==='open'?'No Open Play sessions listed.':kind==='events'?'No events listed.':'No tournaments listed.')+'</div>';return;}
  items.slice(0,visibleCount).forEach(s=>{
   const full=Number(s.registered_players)>=Number(s.max_players)||s.status==='full',joined=Number(s.is_user_registered)>0;
   const level=/beginner/i.test(s.title)?'Beginner':/intermediate/i.test(s.title)?'Intermediate':'All Levels';
   const time=value=>new Date('2000-01-01T'+value).toLocaleTimeString('en',{hour:'numeric',minute:'2-digit'});
   const date=new Date(s.session_date+'T12:00:00').toLocaleDateString('en',{month:'short',day:'numeric',year:'numeric'});
   const card=document.createElement('article');card.className='open-play-mobile-card';
   card.innerHTML='<img loading="lazy" decoding="async" src="'+escape(s.facility_image||'/pikvero/assets/images/logo.png')+'" alt="'+escape(s.facility_name)+'"><div><strong>'+escape(s.title)+'</strong><small><i class="bi bi-people"></i> '+Number(s.registered_players)+'/'+Number(s.max_players)+' players</small><small><i class="bi bi-calendar-event"></i> '+escape(date)+' · '+escape(time(s.start_time)+' – '+time(s.end_time))+'</small><small><i class="bi bi-geo-alt"></i> '+escape(s.facility_name)+'</small><div class="open-play-mobile-card-bottom"><span class="'+level.toLowerCase().replace(' ','-')+'">'+level+'</span><button type="button" class="open-play-mobile-join" '+(full&&!joined?'disabled':'')+'>'+(joined?'Joined':full?'Full':'Join')+'</button></div></div><button type="button" class="open-play-mobile-save" aria-label="Save '+escape(s.title)+'" aria-pressed="'+saved.includes(String(s.id))+'"><i class="bi bi-heart'+(saved.includes(String(s.id))?'-fill':'')+'"></i></button>';
   if(joined)card.querySelector('.open-play-mobile-join').classList.add('is-joined');const details=document.createElement('button');details.type='button';details.className='open-play-see-details';details.textContent='See details';details.onclick=()=>window.showOpenPlayDetails(s.id);card.querySelector('div').append(details);
   card.querySelector('img').onerror=function(){this.onerror=null;this.src='/pikvero/assets/images/logo.png';};card.querySelector('.open-play-mobile-join').onclick=()=>{window.showOpenPlayDetails(s.id);};card.querySelector('.open-play-mobile-save').onclick=()=>{const id=String(s.id);saved=saved.includes(id)?saved.filter(value=>value!==id):[...saved,id];try{localStorage.setItem(savedKey,JSON.stringify(saved));}catch(e){}render();};grid.append(card);
  });
  if(items.length>visibleCount){const more=document.createElement('button');more.type='button';more.className='open-play-mobile-more';more.textContent='See more';more.onclick=()=>{visibleCount+=6;render();};grid.append(more);}
 }
})();





