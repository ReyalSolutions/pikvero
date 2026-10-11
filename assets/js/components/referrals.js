(() => {
  let data, pending, referralTable;
  const esc = value => { const el=document.createElement('span');el.textContent=value??'';return el.innerHTML; };
  const money = value => '₱'+Number(value||0).toFixed(2);
  const labels={pending_payment:'Waiting for paid package',pending_verification:'Payment verification pending',available:'Available to claim',requested:'Cash claim requested',paid:'Cash paid'};
  async function load() {
    const response=await Api.get('/pikvero/api/referrals.php',location.pathname.includes('/customer/')?{scope:'mine'}:{});
    data=response.data;
    document.getElementById('referral-code').textContent=data.code;
    ['copy-code','copy-link'].forEach(id=>document.getElementById(id).disabled=false);
    document.getElementById('history-title').textContent=data.admin?'Referral rewards & cash claims':'Your referral history';
    document.getElementById('admin-help').hidden=!data.admin;
    const sums={available:0,requested:0,paid:0};
    data.rewards.forEach(r=>{if(r.status in sums)sums[r.status]+=Number(r.bonus_amount);});
    const summaryLabels=location.pathname.includes('/customer/')?{available:'Available',requested:'Pending claim',paid:'Paid'}:labels;
    document.getElementById('referral-summary').innerHTML=Object.entries(sums).map(([status,total])=>'<div class="card-streetside referral-panel"><div>'+esc(summaryLabels[status])+'</div><h2>'+money(total)+'</h2></div>').join('');
    const useDataTable=document.body.classList.contains('admin-referrals') && !!window.jQuery?.fn?.DataTable;
    const rowsHtml=data.rewards.length?data.rewards.map(r=>{
      let action='';
      const independentAdmin=data.admin && ![Number(r.owner_id),Number(r.referrer_id)].includes(Number(window.referralUserId));
      if(independentAdmin&&r.status==='pending_verification')action='<button class="button lime" data-action="verify" data-id="'+r.id+'">Verify payment</button>';
      if(r.status==='available'&&Number(r.referrer_id)===Number(window.referralUserId))action='<button class="button lime" data-action="claim" data-id="'+r.id+'">Claim ₱150</button>';
      if(independentAdmin&&r.status==='requested')action='<button class="button coral" data-action="pay" data-id="'+r.id+'">Record cash payout</button>';
      return '<tr><td data-order="'+esc(r.created_at)+'"><strong>'+esc(r.owner_name)+'</strong><br><small>Referred by '+esc(r.referrer_name)+'</small><br><small>'+esc(r.created_at)+'</small></td><td>'+(r.payment_id?'#'+Number(r.payment_id)+' · '+money(r.package_amount)+'<br><small>'+esc(r.payment_method)+' · '+esc(r.payment_status)+'</small><br><small>'+esc(r.payment_date)+'</small>':'No paid package yet')+'</td><td data-order="'+Number(r.bonus_amount)+'">'+money(r.bonus_amount)+'</td><td><span class="referral-status '+esc(r.status)+'">'+esc(labels[r.status]||r.status)+'</span></td><td>'+action+(r.requested_at?'<small class="ledger-date">Requested: '+esc(r.requested_at)+'</small>':'')+(r.paid_at?'<small class="ledger-date">Paid: '+esc(r.paid_at)+'<br>Reference: '+esc(r.payout_reference)+'</small>':'')+'</td></tr>';
    }).join(''):useDataTable?'':'<tr class="referral-empty"><td colspan="5">No referrals yet. Share your code with an owner to get started.</td></tr>';
    if (useDataTable) {
      if (referralTable) {
        const rows=window.jQuery('<tbody>').html(rowsHtml).children('tr');
        referralTable.clear().rows.add(rows).draw(false);
      } else {
        document.getElementById('referral-rows').innerHTML=rowsHtml;
        referralTable=window.jQuery('#referral-table').DataTable({
          pageLength:10,lengthMenu:[10,25,50,100],order:[[0,'desc']],autoWidth:false,
          dom:'<"referral-table-toolbar"lf>rt<"referral-table-footer"ip>',
          columnDefs:[{targets:4,orderable:false}],
          language:{search:'Search referrals:',searchPlaceholder:'Owner, referrer or status',emptyTable:'No referrals yet.',zeroRecords:'No matching referrals found.'}
        });
      }
    } else document.getElementById('referral-rows').innerHTML=rowsHtml;
  }
  document.addEventListener('DOMContentLoaded',async()=>{
    await NavbarComponent.render('#navbar-container',true);
    const context=await AuthHelper.checkSession();
    window.referralUserId=context?.user?.id;
    SidebarComponent.render('referrals',location.pathname.includes('/customer/')||context?.role==='customer'?'customer':context?.role==='court_owner'?'owner':'admin');
    FooterComponent.render('#footer-container',true);
    try{await load();}catch(e){document.getElementById('referral-rows').innerHTML='<tr><td colspan="5">Unable to load referrals. Refresh to retry.</td></tr>';}
  });
  async function copy(value){try{await navigator.clipboard.writeText(value);Toast.success('Copied','Referral details copied.');}catch(e){Toast.info('Your referral details',value);}}
  document.getElementById('copy-code').onclick=()=>copy(data.code);
  function referralLink(code) {
    const local = ['localhost','127.0.0.1','[::1]'].includes(location.hostname);
    const origin = local ? location.origin : 'https://pikvero.wuaze.com';
    const basePath = local || location.pathname.startsWith('/pikvero/') ? '/pikvero' : '';
    return origin+basePath+'/public/owner-onboarding.php?referral='+encodeURIComponent(code);
  }
  document.getElementById('copy-link').onclick=()=>copy(referralLink(data.code));
  document.getElementById('referral-rows').onclick=event=>{
    const button=event.target.closest('[data-action]');if(!button)return;
    pending={action:button.dataset.action,id:Number(button.dataset.id)};
    const pay=pending.action==='pay',verify=pending.action==='verify';
    document.getElementById('referral-action-title').textContent=pay?'Record ₱150 cash payout':verify?'Verify package payment':'Claim ₱150 cash bonus';
    document.getElementById('referral-action-description').textContent=pay?'Confirm that ₱150 cash has been given to the referrer. Enter the receipt or payout reference.':verify?'Confirm that the listed package payment has been received and its receipt checked. This makes the referral bonus available to claim.':'Send a cash claim request. The payout will be recorded by an administrator.';
    document.getElementById('payout-label').hidden=!pay;
    document.getElementById('payout-reference').required=pay;
    document.getElementById('payout-reference').value='';
    document.getElementById('referral-dialog').showModal();
  };
  document.getElementById('referral-action-form').onsubmit=async event=>{
    event.preventDefault();const button=document.getElementById('referral-confirm');button.disabled=true;
    try{await Api.post('/pikvero/api/referrals.php',{...pending,csrf_token:data.csrf_token,reference:document.getElementById('payout-reference').value});document.getElementById('referral-dialog').close();Toast.success('Saved','Referral claim record updated.');await load();}catch(e){}finally{button.disabled=false;}
  };
})();
