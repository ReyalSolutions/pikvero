(() => {
 const mobile=()=>matchMedia('(max-width:768px)').matches;
 let photoGesture=null;
 let swipedPhoto=null;
 document.addEventListener('touchstart',event=>{
  const carousel=event.target.closest('.facility-photo-carousel');
  photoGesture=mobile()&&carousel&&event.touches.length===1?{carousel,x:event.touches[0].clientX,y:event.touches[0].clientY}:null;
 },{passive:true});
 document.addEventListener('touchend',event=>{
  if(!photoGesture)return;
  const {carousel,x,y}=photoGesture;photoGesture=null;
  const touch=event.changedTouches[0];if(!touch)return;
  const dx=touch.clientX-x,dy=touch.clientY-y;
  if(Math.abs(dx)<35||Math.abs(dx)<=Math.abs(dy))return;
  const total=Number(carousel.dataset.photoCount);if(total<2)return;
  navCardCarousel(null,carousel.dataset.carouselId,dx<0?1:-1,total);
  swipedPhoto=carousel;
  setTimeout(()=>{if(swipedPhoto===carousel)swipedPhoto=null;},500);
 },{passive:true});
 document.addEventListener('touchcancel',()=>{photoGesture=null;},{passive:true});
 document.addEventListener('click',event=>{
  if(swipedPhoto&&swipedPhoto.contains(event.target)){event.preventDefault();event.stopImmediatePropagation();swipedPhoto=null;}
 },true);
 function setView(schedule){document.body.classList.toggle('facility-schedule-view',schedule);document.getElementById('facility-view-title').textContent=schedule?'Select schedule':'Facility details';window.scrollTo({top:0,behavior:'instant'});}
 document.getElementById('facility-view-back').onclick=()=>{if(document.body.classList.contains('facility-schedule-view'))setView(false);else history.length>1?history.back():location.href='/pikvero/public/customer/search.php';};
 const favorite=document.getElementById('facility-favorite-btn');
 const favoriteKey='pikvero_saved_facilities';
 const facilityId=new URLSearchParams(location.search).get('id')||'';
 let savedFacilities=[];
 try{savedFacilities=JSON.parse(localStorage.getItem(favoriteKey)||'[]');if(!Array.isArray(savedFacilities))savedFacilities=[];}catch(e){}
 function syncFavorite(){const active=savedFacilities.includes(facilityId);favorite.setAttribute('aria-pressed',String(active));favorite.querySelector('i').className='bi '+(active?'bi-heart-fill':'bi-heart');}
 favorite.onclick=()=>{savedFacilities=savedFacilities.includes(facilityId)?savedFacilities.filter(id=>id!==facilityId):[...savedFacilities,facilityId];try{localStorage.setItem(favoriteKey,JSON.stringify(savedFacilities));}catch(e){}syncFavorite();};
 syncFavorite();
 document.getElementById('courts-list').addEventListener('click',event=>{if(event.target.closest('.court-item')&&mobile())setView(true);});
 const originalScroll=window.scrollToCourts;window.scrollToCourts=()=>{if(mobile())setView(true);else originalScroll();};
 function setSection(section){document.body.dataset.facilitySection=section;document.querySelectorAll('[data-fac-section]').forEach(b=>b.classList.toggle('active',b.dataset.facSection===section));}
 document.querySelectorAll('[data-fac-section]').forEach(button=>button.onclick=()=>setSection(button.dataset.facSection));
 setSection('overview');
 const col=document.getElementById('court-selection-col');const filters=document.createElement('div');filters.className='facility-court-filters';filters.innerHTML='<button class="active" data-type="">All</button><button data-type="indoor">Indoor</button><button data-type="outdoor">Outdoor</button>';col.insertBefore(filters,document.getElementById('courts-list'));filters.querySelectorAll('button').forEach(button=>button.onclick=()=>{filters.querySelectorAll('button').forEach(b=>b.classList.toggle('active',b===button));document.querySelectorAll('.court-item').forEach(card=>card.hidden=!!button.dataset.type&&card.dataset.courtType!==button.dataset.type);document.getElementById('facility-filter-empty')?.remove();const hasCourts=!!document.querySelector('.court-item:not([hidden])');if(!hasCourts){const empty=document.createElement('p');empty.id='facility-filter-empty';empty.className='facility-filter-empty';empty.textContent='No court listed';document.getElementById('courts-list').append(empty);}const bookingButton=col.querySelector('.facility-book-now');if(bookingButton)bookingButton.hidden=!hasCourts;});
 const bookNow=document.createElement('button');bookNow.type='button';bookNow.className='facility-book-now';bookNow.textContent='Book Now';bookNow.onclick=()=>{document.querySelector('.court-item:not([hidden])')?.click();};col.append(bookNow);
 const picker=document.getElementById('booking-date-picker');const week=document.createElement('div');week.className='facility-week';picker.after(week);
 function renderWeek(){week.replaceChildren();const base=new Date((picker.value||new Date().toLocaleDateString('en-CA'))+'T12:00:00');for(let i=-3;i<=3;i++){const d=new Date(base);d.setDate(base.getDate()+i);const date=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');const button=document.createElement('button');button.type='button';button.className=i===0?'active':'';button.innerHTML='<small>'+d.toLocaleDateString('en',{weekday:'short'})+'</small><strong>'+d.getDate()+'</strong>';button.disabled=date<new Date().toLocaleDateString('en-CA');button.onclick=()=>{picker.value=date;$(picker).trigger('change');renderWeek();};week.append(button);}}
 picker.addEventListener('change',renderWeek);renderWeek();
 const slots=document.getElementById('time-slots-grid');const legend=document.createElement('div');legend.className='facility-slot-legend';legend.innerHTML='<span>● Available</span><span>● Booked</span><span>● Selected</span>';slots.before(legend);
 const duration=document.createElement('div');duration.className='facility-duration';duration.innerHTML='<h3>Duration</h3><div><button data-hours="1" aria-pressed="true">1 hour</button><button data-hours="2" aria-pressed="false">2 hours</button><button data-hours="3" aria-pressed="false">3 hours</button></div>';slots.after(duration);let hours=1;
 const customButton=document.createElement('button');customButton.type='button';customButton.textContent='Custom';customButton.setAttribute('aria-pressed','false');duration.querySelector('div').append(customButton);
 const customField=document.createElement('label');customField.className='facility-custom-hours';customField.hidden=true;customField.innerHTML='Custom hours<input type="number" min="1" max="24" step="1" inputmode="numeric" placeholder="Enter hours" aria-label="Custom booking hours">';duration.append(customField);
 const customInput=customField.querySelector('input');
 function applyHours(value){if(!Number.isInteger(value)||value<1||value>24){Toast.warning('Invalid duration','Enter a whole number from 1 to 24 hours.');return false;}hours=value;clearSelectedSlots();return true;}
 duration.querySelectorAll('[data-hours]').forEach(button=>button.onclick=()=>{applyHours(Number(button.dataset.hours));customField.hidden=true;duration.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));});
 customButton.onclick=()=>{customField.hidden=false;customInput.value=String(hours);duration.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b===customButton)));customInput.focus();};
 customInput.addEventListener('change',()=>applyHours(Number(customInput.value)));
 customInput.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();customInput.blur();}});
 const originalToggle=window.toggleSlotSelection;window.toggleSlotSelection=function(key,court,date,start,end,price){if(!mobile())return originalToggle(...arguments);if(!customField.hidden&&(!customInput.validity.valid||Number(customInput.value)!==hours)){if(!applyHours(Number(customInput.value)))return;}const available=[...slots.querySelectorAll('.slot-btn.available')];const index=available.findIndex(b=>b.dataset.slotKey===key);const chosen=available.slice(index,index+hours);const parts=chosen.map(b=>b.dataset.slotKey.split('-'));if(chosen.length!==hours||parts.some((p,i)=>i&&p[0]!==parts[i-1][1])){Toast.error('Time unavailable','Choose a start time with '+hours+' consecutive available hours.');return;}clearSelectedSlots();chosen.forEach((b,i)=>originalToggle(b.dataset.slotKey,court,date,parts[i][0],parts[i][1],Number(b.dataset.slotPrice)));};
 const originalConfirm=window.confirmMultiSlotBookingModal;window.confirmMultiSlotBookingModal=function(){if(mobile()&&!customField.hidden&&(!customInput.validity.valid||Number(customInput.value)!==hours)){Toast.warning('Choose your duration','Enter valid custom hours, then select a start time.');return;}const selected=Object.values(selectedSlotsMap).sort((a,b)=>a.startTime.localeCompare(b.startTime));if(selected.some((s,i)=>i&&String(s.startTime).slice(0,5)!==String(selected[i-1].endTime).slice(0,5))){Toast.error('Select consecutive hours','Choose adjacent time slots for one reservation.');return;}originalConfirm();};
})();

// Mobile schedule presentation uses the existing court and booking state.
(() => {
 const isMobile=()=>matchMedia('(max-width:768px)').matches;
 const picker=document.getElementById('booking-date-picker');
 const header=document.querySelector('.time-slots-header-wrap');
 const calendar=document.createElement('div');calendar.className='facility-calendar';
 calendar.innerHTML='<strong></strong><div><button type="button" aria-label="Previous week">‹</button><button type="button" aria-label="Next week">›</button></div>';
 header.prepend(calendar);
 const selected=document.createElement('div');selected.className='facility-schedule-court';
 selected.innerHTML='<h3>Select Court</h3><button type="button" aria-label="Change court"><img alt=""><span><strong></strong><small></small></span><i class="bi bi-chevron-right"></i></button>';
 header.after(selected);
 selected.querySelector('button').onclick=()=>{document.body.classList.remove('facility-schedule-view');document.getElementById('facility-view-title').textContent='Facility details';document.querySelector('[data-fac-section="courts"]').click();};
 function syncCourt(){const card=document.querySelector('.court-item.selected');if(!card)return;selected.querySelector('img').src=card.querySelector('img')?.src||'/pikvero/assets/images/logo.png';selected.querySelector('strong').textContent=card.dataset.courtName;selected.querySelector('small').textContent=card.querySelector('div:last-child>div:last-child>span')?.textContent||card.dataset.courtType;}
 new MutationObserver(syncCourt).observe(document.getElementById('courts-list'),{subtree:true,attributes:true,attributeFilter:['class']});
 function syncMonth(){calendar.querySelector('strong').textContent=new Date(picker.value+'T12:00:00').toLocaleDateString('en',{month:'long',year:'numeric'});}
 picker.addEventListener('change',syncMonth);syncMonth();
 calendar.querySelectorAll('button').forEach((button,i)=>button.onclick=()=>{const date=new Date(picker.value+'T12:00:00');date.setDate(date.getDate()+(i?7:-7));const value=date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0');if(value<picker.min)return;picker.value=value;$(picker).trigger('change');picker.dispatchEvent(new Event('change'));});
 const legend=document.querySelector('.facility-slot-legend');const title=document.createElement('h3');title.className='facility-slot-title';title.textContent='Select Time Slot';legend.before(title);
 const grid=document.getElementById('time-slots-grid');
 function decorate(){if(!isMobile())return;grid.querySelectorAll('.facility-slot-period').forEach(el=>el.remove());let period='';grid.querySelectorAll('.slot-btn').forEach(button=>{const hour=Number(button.dataset.slotKey?.slice(0,2));const group=hour<12?'Morning':hour<18?'Afternoon':'Evening';if(group!==period){const label=document.createElement('h4');label.className='facility-slot-period';label.textContent=group;button.before(label);period=group;}button.querySelector('strong').textContent=button.dataset.slotStart;});}
 const originalUpdate=window.updateSlotsUI;window.updateSlotsUI=function(){originalUpdate();decorate();};
 const footer=document.createElement('div');footer.className='facility-schedule-footer';footer.innerHTML='<button type="button">Continue</button>';footer.querySelector('button').onclick=()=>confirmMultiSlotBookingModal();document.getElementById('time-slots-col').append(footer);
})();

(() => {
 const escape=value=>{const el=document.createElement('span');el.textContent=value??'';return el.innerHTML;};
 const money=value=>'₱'+Number(value).toLocaleString('en-PH',{minimumFractionDigits:Number.isInteger(Number(value))?0:2,maximumFractionDigits:2});
 window.showMobileBookingSummary=(booking,draft={})=>{
  document.querySelector('.facility-booking-summary')?.remove();
  const data=window.facilityBookingData||{};const facility=data.facility||{};
  const court=(data.courts||[]).find(c=>Number(c.id)===Number(booking.courtId))||{};
  const rentals=(data.products||[]).filter(p=>p.type==='rental'&&p.status==='active'&&Number(p.facility_id)===Number(facility.id)).slice(0,20);
  const quantities=new Map((draft.addons||[]).map(item=>[item.product_id,item.quantity]));const previousFocus=document.activeElement;const previousOverflow=document.body.style.overflow;
  const panel=document.createElement('section');panel.className='facility-booking-summary';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Booking Summary');
  const image=facility.images?.[0]?.image_path||court.images?.[0]?.image_path||'/pikvero/assets/images/logo.png';
  const date=new Date(booking.date+'T12:00:00').toLocaleDateString('en',{weekday:'short',month:'short',day:'numeric',year:'numeric'});
  const detailRow=(icon,label,value,small='')=>`<div class="booking-detail-row"><i class="bi bi-${icon}" aria-hidden="true"></i><span>${label}</span><strong>${escape(value)}${small?'<small>'+escape(small)+'</small>':''}</strong></div>`;
  panel.innerHTML=`<header><button type="button" aria-label="Back to schedule">‹</button><strong>Booking Summary</strong><span></span></header><div class="booking-summary-content"><div class="booking-summary-details"><div class="booking-summary-venue"><img src="${escape(image)}" alt="${escape(facility.name)}"><div><strong>${escape(facility.name)}</strong><small><i class="bi bi-geo-alt"></i> ${escape([facility.address,facility.city].filter(Boolean).join(', '))}</small>${facility.average_rating?'<small class="booking-venue-rating">★ '+escape(facility.average_rating)+' '+(facility.review_count?'('+escape(facility.review_count)+' reviews)':'')+'</small>':''}</div></div>${detailRow('person-badge','Court',booking.courtName,[court.court_type,court.surface_type?.replaceAll('_',' ')].filter(Boolean).join(' · '))}${detailRow('calendar-event','Date',date)}${detailRow('clock','Time',formatTime12h(booking.earliestStart)+' – '+formatTime12h(booking.latestEnd))}${detailRow('clock-history','Duration',booking.totalHours+' hour'+(booking.totalHours===1?'':'s'))}<div class="booking-detail-row booking-summary-price"><i class="bi bi-cash-stack"></i><span>Price</span><strong>${money(booking.totalPrice)}</strong></div></div><div class="booking-summary-addons"><h3>Add-ons <span>(Optional)</span></h3>${rentals.length?rentals.map(p=>`<div class="booking-addon-row" data-product-id="${Number(p.id)}"><i class="bi bi-bag-plus" aria-hidden="true"></i><span>${escape(p.name)}</span><small>${money(p.price)}</small><button type="button" data-step="-1" aria-label="Remove ${escape(p.name)}" disabled>−</button><output>0</output><button type="button" data-step="1" aria-label="Add ${escape(p.name)}">+</button></div>`).join(''):'<p>No add-ons available for this facility.</p>'}</div><div class="booking-summary-total"><strong>Total Amount</strong><output>${money(booking.totalPrice)}</output></div><label class="booking-summary-notes">Notes <span>(Optional)</span><textarea maxlength="1000" placeholder="Add special requests or notes..."></textarea></label><div class="booking-summary-action"><button type="button">Proceed to Payment</button></div></div>`;
  document.body.append(panel);document.body.style.overflow='hidden';panel.querySelector('textarea').value=draft.notes||'';
  function close(){panel.remove();document.body.style.overflow=previousOverflow;previousFocus?.focus();}
  panel.querySelector('header button').onclick=close;
  panel.addEventListener('keydown',event=>{if(event.key==='Escape'){event.preventDefault();close();}if(event.key==='Tab'){const controls=[...panel.querySelectorAll('button:not(:disabled),textarea')];const first=controls[0],last=controls.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});
  const getTotal=()=>booking.totalPrice+rentals.reduce((sum,p)=>sum+Number(p.price)*(quantities.get(Number(p.id))||0),0);
  panel.querySelectorAll('.booking-addon-row button').forEach(button=>button.onclick=()=>{const row=button.closest('.booking-addon-row');const id=Number(row.dataset.productId);const quantity=(quantities.get(id)||0)+Number(button.dataset.step);if(quantity<0||quantity>20){Toast.warning('Add-on limit','You can select up to 20 of each add-on.');return;}quantities.set(id,quantity);row.querySelector('output').textContent=quantity;row.querySelector('[data-step="-1"]').disabled=quantity===0;row.querySelector('[data-step="1"]').disabled=quantity===20;panel.querySelector('.booking-summary-total output').textContent=money(getTotal());});
  panel.querySelector('.booking-summary-action button').onclick=()=>{
   const extras={notes:panel.querySelector('textarea').value.trim(),addons:[...quantities].filter(([,quantity])=>quantity>0).map(([product_id,quantity])=>({product_id,quantity}))};
   const total=getTotal();
   close();
   window.showMobileBookingPayment(booking,extras,total);
  };
  panel.querySelectorAll('.booking-addon-row').forEach(row=>{const quantity=quantities.get(Number(row.dataset.productId))||0;row.querySelector('output').textContent=quantity;row.querySelector('[data-step="-1"]').disabled=quantity===0;row.querySelector('[data-step="1"]').disabled=quantity===20;});
  panel.querySelector('.booking-summary-total output').textContent=money(getTotal());
  panel.querySelector('header button').focus();
 };
})();

(() => {
 const escape=value=>{const el=document.createElement('span');el.textContent=value??'';return el.innerHTML;};
 const money=value=>'₱'+Number(value).toLocaleString('en-PH',{minimumFractionDigits:Number.isInteger(Number(value))?0:2,maximumFractionDigits:2});
 window.showMobileBookingPayment=(booking,extras,subtotal,selectedChannel)=>{
  const data=window.facilityBookingData||{},facility=data.facility||{};
  const methods=[{id:'qrph',name:'QR Ph',description:'Scan QR to pay with your bank or e-wallet',icon:'qr-code',brand:'qrph'},{id:'cash',name:'Pay at Counter',description:'Pay at the facility when you arrive',icon:'cash-stack',brand:'cash'}];
  const enabled=[...(data.paymentMethods||[]).filter(id=>id==='qrph'),'cash'];
  let channel=selectedChannel||enabled[0]||'';
  const gateway=Number(window.paymongoConfiguredFeePct??0);
  const platformFee=Math.round(subtotal*Number(window.platformFeePct??0))/100;
  const gatewayFee=window.passGatewayFee===true?Math.round(subtotal*gateway)/100:0;
  const serviceFees=platformFee+gatewayFee;
  const getTotal=()=>channel==='cash'?subtotal:subtotal+serviceFees;
  const panel=document.createElement('section');panel.className='facility-booking-summary facility-payment-flow';panel.setAttribute('role','dialog');panel.setAttribute('aria-modal','true');panel.setAttribute('aria-label','Payment Method');
  const previousOverflow=document.body.style.overflow;document.body.style.overflow='hidden';document.body.append(panel);
  let bookingReference=null,busy=false;
  const method=()=>methods.find(item=>item.id===channel);
  function close(){panel.remove();document.body.style.overflow=previousOverflow;}
  function header(title){return '<header><button type="button" aria-label="Back">‹</button><strong>'+title+'</strong><span></span></header>';}
  function totalRow(){return '<div class="booking-summary-total"><strong>Total Amount</strong><output>'+money(getTotal())+'</output></div>';}
  function feeRows(){return channel==='cash'?'': '<div class="booking-payment-fees"><div><span>Booking & add-ons</span><strong>'+money(subtotal)+'</strong></div><div><span>Service fees</span><strong>'+money(serviceFees)+'</strong></div></div>';}
  function brand(item){return '<span class="booking-payment-brand '+item.brand+'" aria-hidden="true">'+(item.id==='gcash'?'G':('<i class="bi bi-'+item.icon+'"></i>'))+'</span>';}
  function renderPayment(){
   panel.setAttribute('aria-label','Payment Method');
   panel.innerHTML=header('Payment Method')+'<div class="booking-summary-content"><div class="booking-payment-methods">'+methods.map(item=>'<label class="booking-payment-method '+(!enabled.includes(item.id)?'unavailable':'')+'">'+brand(item)+'<span><strong>'+item.name+'</strong><small>'+item.description+(!enabled.includes(item.id)?' · Unavailable':'')+'</small></span><input type="radio" name="mobile-payment-channel" value="'+item.id+'" '+(item.id===channel?'checked ':'')+(!enabled.includes(item.id)?'disabled':'')+'></label>').join('')+'</div><h3 class="booking-payment-detail-title">Payment Details</h3><div class="booking-payment-details"><span><strong>'+escape(facility.name)+'</strong><small>'+escape(booking.courtName)+' · '+escape(new Date(booking.date+'T12:00:00').toLocaleDateString('en',{month:'short',day:'numeric',year:'numeric'}))+'</small></span><strong>'+money(subtotal)+'</strong></div>'+feeRows()+totalRow()+'<div class="booking-summary-action"><button type="button" '+(!channel?'disabled':'')+'>Pay with '+escape(method()?.name||'selected method')+'</button></div></div>';
   panel.querySelector('header button').onclick=()=>{close();showMobileBookingSummary(booking,extras);};
   panel.querySelectorAll('input[name="mobile-payment-channel"]').forEach(input=>input.onchange=()=>{channel=input.value;renderPayment();});
   panel.querySelector('.booking-summary-action button').onclick=renderConfirm;
   if(channel==='cash')panel.querySelector('.booking-summary-action button').textContent='Continue with Pay at Counter';
   panel.querySelector('header button').focus();
  }
  function renderConfirm(){
   const court=(data.courts||[]).find(c=>Number(c.id)===Number(booking.courtId))||{};
   const image=facility.images?.[0]?.image_path||court.images?.[0]?.image_path||'/pikvero/assets/images/logo.png';
   panel.setAttribute('aria-label','Confirm Booking');
   panel.innerHTML=header('Confirm Booking')+'<div class="booking-summary-content"><div class="booking-review-intro"><span><i class="bi bi-calendar-check"></i><i class="bi bi-check-circle-fill"></i></span><h2>Review and Confirm</h2><p>Please double-check your booking details<br>before confirming.</p></div><div class="booking-review-venue"><img src="'+escape(image)+'" alt="'+escape(facility.name)+'"><div><strong>'+escape(facility.name)+'</strong><small>'+escape(booking.courtName)+' · '+escape(court.court_type||'')+'</small><small><i class="bi bi-calendar-event"></i> '+escape(new Date(booking.date+'T12:00:00').toLocaleDateString('en',{weekday:'short',month:'short',day:'numeric',year:'numeric'}))+'</small><small><i class="bi bi-clock"></i> '+escape(formatTime12h(booking.earliestStart)+' – '+formatTime12h(booking.latestEnd))+' ('+booking.totalHours+' hour'+(booking.totalHours===1?'':'s')+')</small></div></div><h3 class="booking-payment-detail-title">Payment Method</h3><button type="button" class="booking-review-method">'+brand(method())+'<span><strong>'+method().name+'</strong><small>'+method().description+'</small></span></button>'+feeRows()+totalRow()+'<label class="booking-review-terms"><input type="checkbox"><span>I agree to the <a href="/pikvero/public/terms.php" target="_blank" rel="noopener">Terms and Conditions</a> and <a href="/pikvero/public/terms.php" target="_blank" rel="noopener">Booking Policy</a>.</span></label><div class="booking-summary-action"><button type="button" disabled>'+(channel==='cash'?'Confirm Reservation':'Confirm and Pay')+'</button></div></div>';
   panel.querySelector('header button').onclick=renderPayment;panel.querySelector('.booking-review-method').onclick=renderPayment;
   const button=panel.querySelector('.booking-summary-action button');
   panel.querySelector('.booking-review-terms input').onchange=event=>{button.disabled=!event.target.checked;};
   button.onclick=async()=>{
    if(busy||!panel.querySelector('.booking-review-terms input').checked)return;
    busy=true;button.disabled=true;button.textContent='Preparing payment…';panel.querySelectorAll('header button,.booking-review-method,.booking-review-terms input').forEach(el=>el.disabled=true);
    try{
     if(!bookingReference){const response=await $.ajax({url:'/pikvero/api/customer/bookings.php',method:'POST',contentType:'application/json',dataType:'json',data:JSON.stringify({court_id:booking.courtId,date:booking.date,start_time:booking.earliestStart,end_time:booking.latestEnd,payment_method:channel==='cash'?'cash':'online',...extras})});if(!response.success)throw new Error(response.message||'Could not create booking.');bookingReference=response.data.booking_reference;}
     if(channel==='cash'){location.href='/pikvero/public/customer/bookings.php?confirmed=1&booking_ref='+encodeURIComponent(bookingReference);return;}
     const checkout=await $.ajax({url:'/pikvero/api/payments/booking-checkout.php',method:'POST',contentType:'application/json',dataType:'json',data:JSON.stringify({booking_reference:bookingReference,facility_id:facility.id,payment_channel:channel})});
     if(!checkout.success||!checkout.data?.checkout_url)throw new Error(checkout.message||'Could not open payment checkout.');
     location.href=checkout.data.checkout_url;
    }catch(error){Toast.error('Payment unavailable',error.responseJSON?.message||error.message||'Please try again.');busy=false;button.disabled=false;button.textContent='Retry Payment';panel.querySelectorAll('header button,.booking-review-method,.booking-review-terms input').forEach(el=>el.disabled=false);}
   };
   panel.querySelector('header button').focus();
  }
  panel.addEventListener('keydown',event=>{if(event.key==='Escape'&&!busy){event.preventDefault();close();showMobileBookingSummary(booking,extras);}if(event.key==='Tab'){const controls=[...panel.querySelectorAll('button:not(:disabled),input:not(:disabled),a')];const first=controls[0],last=controls.at(-1);if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}}});
  renderPayment();
 };
})();
