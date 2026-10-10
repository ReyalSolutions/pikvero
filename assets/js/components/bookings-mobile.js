(() => {
 const escape=value=>{const el=document.createElement('span');el.textContent=value??'';return el.innerHTML;};
 window.initMobileBookings=()=>{
  const root=document.createElement('section');root.className='mobile-bookings';root.innerHTML='<header><button type="button" aria-label="Back"><i class="bi bi-chevron-left"></i></button><strong>My Bookings</strong><span></span></header><div class="mobile-bookings-content"><div class="mobile-bookings-tabs" role="tablist" aria-label="Reservations"><button type="button" role="tab" aria-selected="true" data-view="upcoming">Upcoming</button><button type="button" role="tab" aria-selected="false" data-view="past">Past</button></div><div class="mobile-bookings-list" role="tabpanel" aria-live="polite"></div><button type="button" class="mobile-bookings-more" hidden>See more</button></div>';
  document.querySelector('main').prepend(root);
  root.querySelector('header button').onclick=()=>location.href='/pikvero/public/customer/dashboard.php';
  const list=root.querySelector('.mobile-bookings-list'),more=root.querySelector('.mobile-bookings-more');
  let view='upcoming',offset=0,version=0,loading=false,controller;
  const time=value=>new Date('2000-01-01T'+value).toLocaleTimeString('en',{hour:'numeric',minute:'2-digit'});
  async function load(reset=false){
   if(reset){controller?.abort();version++;offset=0;loading=false;list.replaceChildren();currentBookingsMap={};}
   if(loading)return;loading=true;const requestVersion=version;controller=new AbortController();more.disabled=true;more.textContent='Loading…';list.setAttribute('aria-busy','true');
   const skeletons=Array.from({length:offset?2:6},()=>{
    const card=document.createElement('div');card.className='mobile-booking-card mobile-booking-skeleton';card.setAttribute('aria-hidden','true');
    card.innerHTML='<span class="booking-skeleton-image booking-skeleton-shimmer"></span><span class="mobile-booking-info"><span class="booking-skeleton-line booking-skeleton-shimmer"></span><span class="booking-skeleton-line short booking-skeleton-shimmer"></span><span class="booking-skeleton-line booking-skeleton-shimmer"></span><span class="booking-skeleton-line short booking-skeleton-shimmer"></span><span class="booking-skeleton-badge booking-skeleton-shimmer"></span></span>';
    list.append(card);return card;
   });
   try{
    const params=new URLSearchParams({draw:'1',start:String(offset),length:'6',view,'order[0][column]':'2','order[0][dir]':view==='upcoming'?'asc':'desc'});
    const response=await fetch('/pikvero/api/customer/bookings.php?'+params,{signal:controller.signal});const result=await response.json();
    if(requestVersion!==version)return;if(!response.ok||!Array.isArray(result.data))throw new Error(result.message||'Could not load reservations.');
    skeletons.forEach(card=>card.remove());
    if(!offset)list.replaceChildren();
    result.data.forEach(booking=>{
     currentBookingsMap[booking.id]=booking;
     const card=document.createElement('button');card.type='button';card.className='mobile-booking-card';
     const status=booking.booking_status||'pending';const label=status==='awaiting_payment'?'Pending Payment':status.replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase());
     const date=new Date(booking.booking_date+'T12:00:00').toLocaleDateString('en',{weekday:'short',month:'short',day:'numeric',year:'numeric'});
     card.innerHTML='<img loading="lazy" decoding="async" src="'+escape(booking.facility_image||'/pikvero/assets/images/logo.png')+'" alt="'+escape(booking.facility_name)+'"><span class="mobile-booking-info"><strong>'+escape(booking.facility_name)+'</strong><small>'+escape(booking.court_name)+'</small><small><i class="bi bi-calendar-event"></i> '+escape(date)+'</small><small><i class="bi bi-clock"></i> '+escape(time(booking.start_time)+' – '+time(booking.end_time))+'</small><span class="mobile-booking-status '+escape(status)+'">'+escape(label)+'</span></span><i class="bi bi-chevron-right" aria-hidden="true"></i>';
     const image=card.querySelector('img');image.classList.add('booking-skeleton-shimmer');
     image.onload=()=>image.classList.remove('booking-skeleton-shimmer');
     image.onerror=function(){this.onerror=null;this.classList.remove('booking-skeleton-shimmer');this.src='/pikvero/assets/images/logo.png';};
     if(image.complete&&image.naturalWidth)image.classList.remove('booking-skeleton-shimmer');
     card.onclick=()=>showMobileBookingDetails(booking);list.append(card);
    });
    offset+=result.data.length;more.hidden=offset>=Number(result.recordsFiltered)||!result.data.length;
    if(!offset)list.innerHTML='<div class="mobile-bookings-empty"><i class="bi bi-calendar2-check"></i><strong>No '+view+' reservations</strong><p>Your bookings will appear here.</p>'+(view==='upcoming'?'<a href="/pikvero/public/customer/search.php">Find a court</a>':'')+'</div>';
   }catch(error){if(error.name==='AbortError'||requestVersion!==version)return;if(!offset)list.innerHTML='<div class="mobile-bookings-empty">Could not load reservations. Please try again.</div>';more.hidden=false;Toast.error('Reservations unavailable',error.message||'Please try again.');}
   finally{skeletons.forEach(card=>card.remove());if(requestVersion===version){loading=false;more.disabled=false;more.textContent=offset?'See more':'Retry';list.setAttribute('aria-busy','false');}}
  }
  root.querySelectorAll('[data-view]').forEach(button=>button.onclick=()=>{view=button.dataset.view;root.querySelectorAll('[data-view]').forEach(tab=>tab.setAttribute('aria-selected',String(tab===button)));load(true);});
  more.onclick=()=>load();window.reloadMobileBookings=()=>load(true);load(true);
 };
})();
