/**
 * Pikvero Push Notifications Helper
 * Polls for unread notifications and triggers native browser push notifications & Toast popups
 * if the user has enabled Push Notifications in their Account Settings.
 */
const PushNotifier = {
  checkInterval: null,

  init() {
    if (!('Notification' in window)) return;
    
    // Check for unread push notifications every 15 seconds
    this.poll();
    if (this.checkInterval) clearInterval(this.checkInterval);
    this.checkInterval = setInterval(() => this.poll(), 15000);
  },

  async poll() {
    try {
      if (typeof Api === 'undefined') return;

      // Only poll for unread notifications if user is logged in
      const user = (typeof AuthHelper !== 'undefined') ? AuthHelper.currentUser : null;
      if (!user) return;

      const res = await Api.get('/pikvero/api/customer/notifications.php?action=get_unread', {}, { showToast: false, ignoreUnauthorized: true });
      if (res && res.success && res.data && Array.isArray(res.data.notifications) && res.data.notifications.length > 0) {
        res.data.notifications.forEach(n => {
          this.triggerNotification(n.title, n.message);
        });
      }
    } catch (e) {
      // Ignore poll errors for guests or unauthorized states
    }
  },

  triggerNotification(title, message) {
    // 1. In-app Toast Popup
    if (typeof Toast !== 'undefined') {
      Toast.info(title, message);
    }

    // 2. Native Browser Push Notification
    if ('Notification' in window && Notification.permission === 'granted') {
      try {
        new Notification(title, {
          body: message,
          icon: '/pikvero/assets/images/logo.png',
          badge: '/pikvero/assets/images/logo.png'
        });
      } catch (e) {}
    }
  }
};

document.addEventListener('DOMContentLoaded', () => {
  PushNotifier.init();
});
