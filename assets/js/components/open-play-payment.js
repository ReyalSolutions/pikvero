(() => {
 const esc=v=>{const el=document.createElement('span');el.textContent=v??'';return el.innerHTML;};
 const money=v=>'₱'+Number(v).toFixed(2);
 window.showOpenPlayPayment=s=>{
  let method=feeSettings.qrph_enabled?'qrph':'cash',stage='payment',busy=false;
  const page=document.createElement('section');page.className='op-event-details op-join-page';document.body.append(page);document.body.classList.add('op-event-details-view');
  const close=()=>{page.remove();document.body.classList.remove('op-event-details-view');};
  function render(){
   const base=Number(s.fee_per_player),fees=method==='cash'?0:Math.round(base*Number(feeSettings.platform_fee_pct))/100+Math.round(base*Number(feeSettings.paymongo_fee_pct))/100;
   const label=method==='qrph'?'QR Ph':'Pay at Counter';
   const summary='<div class="op-join-session"><img src="'+esc(s.photos?.[0]?.image_path||s.facility_image||'/pikvero/assets/images/logo.png')+'" alt="'+esc(s.facility_name)+'"><div><strong>'+esc(s.title)+'</strong><small>'+esc(s.facility_name)+'</small><small>'+esc(s.session_date)+' · '+esc(s.start_time.slice(0,5)+' – '+s.end_time.slice(0,5))+'</small></div></div>';
   page.innerHTML='<header><button type="button" aria-label="Back"><i class="bi bi-chevron-left"></i></button><strong>'+(stage==='payment'?'Payment Method':'Confirm Registration')+'</strong><span></span></header><main>'+ (stage==='review'?'<div class="op-payment-review"><i class="bi bi-calendar-check"></i><h2>Review and Confirm</h2><p>Please double-check your event details before confirming.</p></div>':'')+summary+'<div class="op-event-content">'+(stage==='payment'?'<div class="op-payment-options">'+(feeSettings.qrph_enabled?'<label><i class="bi bi-qr-code"></i><span><strong>QR Ph</strong><small>Scan to pay with your bank or e-wallet</small></span><input type="radio" name="op-payment" value="qrph" '+(method==='qrph'?'checked':'')+'></label>':'')+'<label><i class="bi bi-cash-stack"></i><span><strong>Pay at Counter</strong><small>Pay when you arrive</small></span><input type="radio" name="op-payment" value="cash" '+(method==='cash'?'checked':'')+'></label></div>':'<h2>Payment Method</h2><div class="op-join-summary">'+label+'</div>')+'<h2>Payment Details</h2><div class="op-join-summary"><div><span>Event Fee</span><strong>'+money(base)+'</strong></div><div><span>Service fees</span><strong>'+money(fees)+'</strong></div><div class="op-join-total"><span>Total Amount</span><strong>'+money(base+fees)+'</strong></div></div>'+(stage==='review'?'<label class="op-payment-terms"><input type="checkbox"> <span>I agree to the <a href="/pikvero/public/terms.php" target="_blank" rel="noopener">Terms and Conditions</a> and <a href="/pikvero/public/terms.php" target="_blank" rel="noopener">Booking Policy</a>.</span></label>':'')+'<div class="op-join-footer"><button class="op-event-join" type="button">'+(stage==='payment'?'Continue':method==='cash'?'Confirm Registration':'Confirm and Pay')+'</button></div></div></main>';
   page.querySelector('header button').onclick=()=>{if(busy)return;if(stage==='review'){stage='payment';render();}else{close();showOpenPlayJoin(s);}};
   page.querySelectorAll('[name="op-payment"]').forEach(input=>input.onchange=()=>{method=input.value;render();});
   const button=page.querySelector('.op-event-join');if(stage==='review'){button.disabled=true;page.querySelector('.op-payment-terms input').onchange=e=>{button.disabled=!e.target.checked;};}
   button.onclick=async()=>{
    if(stage==='payment'){stage='review';render();return;}if(busy)return;busy=true;button.disabled=true;button.textContent='Processing…';
    try{
     const res=await Api.post('/pikvero/api/customer/open-play.php?action=join',{session_id:s.id,player_name:document.getElementById('join-player-name').value.trim(),player_phone:document.getElementById('join-player-phone').value.trim(),payment_method:method});
     if(!res.success)throw Error(res.message||'Unable to register');
     if(res.data?.checkout_url){location.href=res.data.checkout_url;return;}
     close();Toast.success('Registration Confirmed!','Your Open Play pass is ready.');switchTab('my-passes');
    }catch(e){Toast.error('Registration failed',e.message||'Please try again.');busy=false;button.disabled=false;button.textContent=method==='cash'?'Confirm Registration':'Confirm and Pay';}
   };
  }
  render();
 };
})();

