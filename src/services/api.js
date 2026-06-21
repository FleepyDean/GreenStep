import axios from 'axios'

const API_BASE_URL = 'http://localhost:8080/api'

const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('user')
      window.location.href = '/profile'
    }
    return Promise.reject(error)
  }
)

export const authAPI = {
  register: (data) => api.post('/auth/register', data),
  login: (data) => api.post('/auth/login', data),
  getProfile: () => api.get('/auth/me')
}

export const activityAPI = {
  getTypes: () => api.get('/activity-types'),
  getCategories: () => api.get('/activity-types/categories'),
  getTypesByCategory: (category) => api.get(`/activity-types/category/${category}`),
  
  logActivity: (data) => api.post('/activities', data),
  getActivities: (params) => api.get('/activities', { params }),
  getTodayActivities: () => api.get('/activities/today'),
  getStats: (params) => api.get('/activities/stats', { params }),
  deleteActivity: (id) => api.delete(`/activities/${id}`)
}

export const tipAPI = {
  getRandom: () => api.get('/tips/random'),
  getAll: () => api.get('/tips'),
  create: (data) => api.post('/tips', data),
  delete: (id) => api.delete(`/tips/${id}`)
}

export default api
