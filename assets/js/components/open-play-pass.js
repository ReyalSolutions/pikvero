(() => {
 const esc=v=>{const el=document.createElement('span');el.textContent=v??'';return el.innerHTML;};
 window.showOpenPlayPass=async params=>{
  const page=document.createElement('section');page.className='op-event-details op-checkin-page';page.innerHTML='<header><button type="button" aria-label="Back"><i class="bi bi-chevron-left"></i></button><strong>Event Check-in</strong><span></span></header><main><div class="op-event-content"><div class="op-loading-card"><div class="op-loading-image op-skeleton"></div><div class="op-loading-lines"><span class="op-skeleton"></span><span class="op-skeleton"></span></div></div></div></main>';document.body.append(page);document.body.classList.add('op-event-details-view');
  page.querySelector('header button').onclick=()=>{page.remove();if(!document.querySelector('.op-event-details'))document.body.classList.remove('op-event-details-view');};
  try{
   const res=await Api.get('/pikvero/api/customer/open-play.php',{action:'pass',...params});if(!res.success)throw Error(res.message);if(!page.isConnected)return;const p=res.data;
   const date=new Date(p.session_date+'T12:00:00').toLocaleDateString('en',{month:'short',day:'numeric',year:'numeric'});
   const time=v=>new Date('2000-01-01T'+v).toLocaleTimeString('en',{hour:'numeric',minute:'2-digit'});
   const used=p.checkin_status==='checked_in';const eligible=!p.is_expired&&!used&&p.payment_status==='paid'&&p.session_status!=='cancelled';
   const status=p.is_expired?'Expired':used?'Checked In':p.session_status==='cancelled'?'Cancelled':p.payment_status==='refunded'?'Refunded':p.payment_status!=='paid'?'Payment Pending':p.is_event_day?'Check-in Ready':'Upcoming';
   page.querySelector('main').innerHTML='<div class="op-checkin-top"><div class="op-checkin-ticket"><h1>'+esc(p.session_title)+'</h1><p>'+esc(date)+' · '+esc(time(p.start_time)+' – '+time(p.end_time))+'</p><p>'+esc(p.facility_name)+'</p><p><i class="bi bi-grid"></i> Court: '+esc(p.court_name||'Not assigned')+'</p><div class="op-pass-qr"></div><small>Pass #OP-'+Number(p.id)+'</small><span class="op-checkin-status '+(eligible?'':'inactive')+'"><i class="bi bi-'+(eligible?'check-circle-fill':'info-circle')+'"></i> '+status+'</span></div></div><div class="op-checkin-instructions"><h2>Check-in Instructions</h2><ol><li>Show this QR code to the organizer or staff at the court.</li><li>Get your wristband or mark for participation.</li><li>Enjoy the game!</li></ol><p><i class="bi bi-info-circle"></i> This pass is for the event shown above. Staff verify your registration and check-in status.</p></div>';
   if(eligible&&typeof QRCode!=='undefined'){new QRCode(page.querySelector('.op-pass-qr'),{text:location.origin+'/pikvero/public/open-play-receipt.php?registration_id='+Number(p.id),width:200,height:200,correctLevel:QRCode.CorrectLevel.M});}else{page.querySelector('.op-pass-qr').textContent=eligible?'QR unavailable. Show your pass ID to staff.':status;}
  }catch(e){if(page.isConnected)page.querySelector('main').innerHTML='<p class="op-event-content" role="status">'+esc(e.message||'Unable to load your pass. Please try again.')+'</p>';}
 };
})();
