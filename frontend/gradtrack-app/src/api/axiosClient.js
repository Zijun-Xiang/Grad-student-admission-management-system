import axios from 'axios';

function safeJson(value) {
  try {
    return JSON.stringify(value);
  } catch {
    return String(value);
  }
}

export function formatAxiosError(error) {
  const status = error?.response?.status;
  const data = error?.response?.data;

  if (data && typeof data === 'object' && data.errors && typeof data.errors === 'object') {
    const lines = [];
    for (const [field, messages] of Object.entries(data.errors)) {
      if (Array.isArray(messages)) {
        for (const message of messages) lines.push(`${field}: ${message}`);
      } else if (messages != null) {
        lines.push(`${field}: ${String(messages)}`);
      }
    }
    const message = lines.length ? lines.join('\n') : (data.message ?? 'Validation error');
    return status ? `[${status}] ${message}` : message;
  }

  if (typeof data === 'string') {
    return status ? `[${status}] ${data}` : data;
  }

  const message =
    data?.message ??
    (data && typeof data === 'object' ? safeJson(data) : null) ??
    error?.message ??
    'Unknown error';

  return status ? `[${status}] ${message}` : message;
}

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  headers: {
    Accept: 'application/json',
  },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  const method = (config.method || 'GET').toUpperCase();
  const url = `${config.baseURL || ''}${config.url || ''}`;
  const hasAuth = Boolean(token);

  console.groupCollapsed(`[API] ${method} ${url}`);
  console.log('Auth header present:', hasAuth);
  console.log('Headers:', config.headers);
  if (config.params) console.log('Params:', config.params);
  if (config.data !== undefined) console.log('Payload:', config.data);
  console.groupEnd();

  return config;
});

api.interceptors.response.use(
  (response) => {
    const method = (response.config?.method || 'GET').toUpperCase();
    const url = `${response.config?.baseURL || ''}${response.config?.url || ''}`;

    console.groupCollapsed(`[API] ${response.status} ${method} ${url}`);
    console.log('Response body:', response.data);
    console.groupEnd();

    return response;
  },
  (error) => {
    const status = error?.response?.status;
    const method = (error.config?.method || 'GET').toUpperCase();
    const url = `${error.config?.baseURL || ''}${error.config?.url || ''}`;

    console.groupCollapsed(`[API] ERROR ${status ?? 'NO_STATUS'} ${method} ${url}`);
    console.log('Error message:', error?.message);
    console.log('Response body:', error?.response?.data);
    console.groupEnd();

    return Promise.reject(error);
  },
);

