document.querySelectorAll('[data-profile-modal]').forEach(link => link.addEventListener('click', async event => {
  event.preventDefault();
  const dialog = document.getElementById(link.dataset.profileModal);
  dialog.showModal();
  if (dialog.id === 'notifications-modal') {
    const save = document.getElementById('save-profile-notifications'); save.disabled = true;
    try {
      const response = await Api.get('/pikvero/api/customer/notifications.php?action=get_preferences');
      if (!response.success) return;
      document.getElementById('modal-email-notifications').checked = response.data.email_notifications == 1;
      document.getElementById('modal-push-notifications').checked = response.data.push_notifications == 1;
      save.disabled = false;
    } catch (error) { Toast.error('Unable to load settings', 'Please try again.'); }
  }
}));
document.querySelectorAll('.profile-dialog').forEach(dialog => {
  dialog.querySelector('[data-close-dialog]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => { if (event.target === dialog) {const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)dialog.close();} });
});
async function saveProfileSettings(form, url, payload, title) {
  const button = form.querySelector('[type="submit"]'); button.disabled = true;
  try {const response = await Api.post(url, payload); if(response.success) {form.closest('dialog').close();Toast.success(title, 'Your changes have been saved.');}}
  catch(error) {Toast.error('Unable to save settings', 'Please try again.');}
  finally {button.disabled = false;}
}
document.getElementById('profile-preferences-form').addEventListener('submit', event => {event.preventDefault();saveProfileSettings(event.target, '/pikvero/api/customer/profile-preferences.php', {csrf:window.PROFILE_CSRF,playing_level:document.getElementById('modal-playing-level').value}, 'Preferences updated');});
document.getElementById('profile-notifications-form').addEventListener('submit', event => {event.preventDefault();saveProfileSettings(event.target, '/pikvero/api/customer/notifications.php?action=save_preferences', {email_notifications:document.getElementById('modal-email-notifications').checked,push_notifications:document.getElementById('modal-push-notifications').checked}, 'Notifications updated');});

document.getElementById('profile-password-form').addEventListener('submit', async event => {
 event.preventDefault();const form=event.target;const np=document.getElementById('password-new').value,cp=document.getElementById('password-confirm').value;
 if(np!==cp){Toast.error('Passwords do not match','Confirm your new password.');return;}
 await saveProfileSettings(form,'/pikvero/api/customer/update-password.php',{csrf:window.PROFILE_CSRF,current_password:document.getElementById('password-current').value,new_password:np,confirm_password:cp},'Password updated');
 if(!form.closest('dialog').open)form.reset();
});
['bug','contact'].forEach(type=>document.getElementById('profile-'+type+'-form').addEventListener('submit',async event=>{
 event.preventDefault();const form=event.target;
 await saveProfileSettings(form,'/pikvero/api/feedback-reports.php',{report_type:type==='bug'?'bug':'feature_request',category:type==='bug'?'general':'support',priority:'medium',title:(type==='contact'?'[Support] ':'')+document.getElementById(type+'-subject').value.trim(),description:document.getElementById(type+'-description').value.trim()},type==='bug'?'Bug report submitted':'Message submitted');
 if(!form.closest('dialog').open)form.reset();
}));
