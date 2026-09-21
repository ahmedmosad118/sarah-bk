import axios from 'axios';

// Calculate dynamic API base URL
const getApiBaseUrl = () => {
  if (typeof window !== 'undefined' && window.SARH_BASE_URL) {
    try {
      const url = new URL(window.SARH_BASE_URL);
      const pathname = url.pathname.replace(/\/+$/, '');
      return `${pathname}/api`;
    } catch (e) {
      // Ignore
    }
  }

  if (typeof window !== 'undefined' && window.location.pathname.includes('/public')) {
    const idx = window.location.pathname.indexOf('/public');
    const prefix = window.location.pathname.substring(0, idx + 7).replace(/\/+$/, '');
    return `${prefix}/api`;
  }

  return '/api';
};

const api = axios.create({
  baseURL: getApiBaseUrl(),
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
});

// Request Interceptor: Attach Token & Tenant Context
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('sarh_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  // Tenant slug from local storage or query / host
  const tenantSlug = localStorage.getItem('sarh_tenant_slug');
  if (tenantSlug) {
    config.headers['X-Tenant-Slug'] = tenantSlug;
  }

  return config;
}, (error) => {
  return Promise.reject(error);
});

// Response Interceptor: Handle Global Auth Expiry
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('sarh_token');
      localStorage.removeItem('sarh_user');
      // Redirection handled by router guard
    }
    return Promise.reject(error);
  }
);

export default api;
