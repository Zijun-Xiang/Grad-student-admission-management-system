import axios from 'axios'

export const apiBaseURL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/api'

const api = axios.create({
  baseURL: apiBaseURL,
  withCredentials: true,
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error?.response?.status === 401) {
      localStorage.removeItem('user')
      if (window.location.pathname !== '/') window.location.href = '/'
    }
    return Promise.reject(error)
  },
)

export default api

