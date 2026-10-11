/**
 * Global AJAX Utility for Pikvero
 */
const Api = {
  baseUrl: location.origin + '/pikvero',

  async request(url, options = {}) {
    const defaultHeaders = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };

    const config = {
      method: options.method || 'GET',
      credentials: 'same-origin',
      headers: { ...defaultHeaders, ...(options.headers || {}) }
    };
    if (config.method !== 'GET' && typeof AuthHelper !== 'undefined' && AuthHelper.currentUser?.csrf_token) {
      config.headers['X-CSRF-Token'] = AuthHelper.currentUser.csrf_token;
    }

    if (options.data) {
      if (options.data instanceof FormData) {
        delete config.headers['Content-Type'];
        config.body = options.data;
      } else {
        config.body = JSON.stringify(options.data);
      }
    }

    try {
      const response = await fetch(url, config);
      const resData = await response.json();

      if (response.status === 401) {
        if (options.ignoreUnauthorized || options.showToast === false) {
          return { success: false, code: 'UNAUTHORIZED' };
        }
        // Suppress intrusive 401 Toast popups for guest visitors on public landing pages
        return { success: false, code: 'UNAUTHORIZED' };
      }

      if (!response.ok || resData.success === false) {
        const msg = resData.message || 'An unexpected error occurred.';
        if (options.showToast !== false && response.status !== 401) {
          Toast.error('API Error', msg);
        }
        throw new Error(msg);
      }

      return resData;
    } catch (err) {
      if (options.ignoreUnauthorized || err.message === 'Unauthorized access' || err.message === 'Authentication required.') {
        return { success: false, code: 'UNAUTHORIZED' };
      }
      if (options.showToast !== false && !err.message.includes('Authentication required')) {
        console.error('API Request Failed:', err);
      }
      throw err;
    }
  },

  get(url, params = {}, options = {}) {
    const queryString = new URLSearchParams(params).toString();
    const fullUrl = queryString ? `${url}?${queryString}` : url;
    return this.request(fullUrl, { method: 'GET', ...options });
  },

  post(url, data = {}, options = {}) {
    return this.request(url, { method: 'POST', data, ...options });
  }
};
