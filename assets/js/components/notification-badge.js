(() => {
 if(window.NotificationBadge)return;
 const style=document.createElement('style');style.textContent='.notification-bell-link{position:relative!important}.notification-unread-count{position:absolute;top:0;right:0;min-width:17px;height:17px;display:flex;align-items:center;justify-content:center;padding:0 4px;border-radius:999px;background:#e84d5b;color:white;font:700 10px/1 system-ui;box-shadow:0 0 0 2px white;pointer-events:none}.notification-unread-count[hidden]{display:none}';document.head.append(style);
 let count=0,busy=false;
 function render(){document.querySelectorAll('a[href*="/notifications"]').forEach(link=>{if(!link.querySelector('.bi-bell,.bi-bell-fill'))return;link.classList.add('notification-bell-link');let badge=link.querySelector('.notification-unread-count');if(!badge){badge=document.createElement('span');badge.className='notification-unread-count';badge.setAttribute('aria-hidden','true');link.append(badge);}const value=count>99?'99+':String(count);if(badge.textContent!==value)badge.textContent=value;badge.hidden=!count;const label=count?'Notifications, '+count+' unread':'Notifications';if(link.getAttribute('aria-label')!==label)link.setAttribute('aria-label',label);});}
 async function refresh(){if(busy||document.hidden)return;busy=true;try{const response=await fetch('/pikvero/api/customer/inbox.php?view=unread_count',{cache:'no-store',credentials:'same-origin'});if(!response.ok)return;const result=await response.json();if(result.success){count=Math.max(0,Number(result.data?.count)||0);render();}}catch(e){}finally{busy=false;}}
 window.NotificationBadge={refresh};
 new MutationObserver(render).observe(document.body,{childList:true,subtree:true});
 window.addEventListener('notifications-read',refresh);window.addEventListener('focus',refresh);document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh();});
 render();refresh();setInterval(refresh,60000);
})();
