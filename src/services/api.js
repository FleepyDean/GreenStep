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
      const url = error.config?.url || ''
      // Don't redirect on auth endpoints - let the component handle the error
      if (!url.includes('/auth/login') && !url.includes('/auth/register')) {
        localStorage.removeItem('auth_token')
        localStorage.removeItem('user')
        window.location.href = '/profile'
      }
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
  logActivityWithPhoto: (formData) => api.post('/activities', formData, { headers: { 'Content-Type': 'multipart/form-data' } }),
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

export const socialAPI = {
  getFriends: () => api.get('/friends'),
  sendFriendRequest: (data) => api.post('/friends/request', data),
  updateFriendRequest: (id, data) => api.put(`/friends/request/${id}`, data),
  removeFriend: (id) => api.delete(`/friends/${id}`),
  getLeaderboard: (filter = 'global') => api.get('/leaderboard', { params: { filter } })
}

export const challengeAPI = {
  getChallenges: () => api.get('/challenges'),
  getChallengeDetails: (id) => api.get(`/challenges/${id}/details`),
  createChallenge: (data) => api.post('/challenges', data),
  updateChallenge: (id, data) => api.put(`/challenges/${id}`, data),
  deleteChallenge: (id) => api.delete(`/challenges/${id}`),
  joinChallenge: (id) => api.post(`/challenges/${id}/join`),
  leaveChallenge: (id) => api.delete(`/challenges/${id}/leave`)
}

export const dashboardAPI = {
  getMetrics: (userId) => api.get(`dashboard/${userId}`)
}

export const goalAPI = {
  getGoal: () => api.get('/goal'),
  updateGoal: (data) => api.put('/goal', data)
}

export const emissionFactorAPI = {
  getAll: () => api.get('/activity-types?grouped=false'),
  getCategories: () => api.get('/activity-types/categories'),
  createCategory: (data) => api.post('/admin/categories', data),
  create: (data) => api.post('/admin/activity-types', data),
  update: (id, data) => api.put(`/admin/activity-types/${id}`, data),
  delete: (id) => api.delete(`/admin/activity-types/${id}`)
}

export const adminAPI = {
  getDashboard: () => api.get('/admin/dashboard'),
  getDataset: () => api.get('/admin/dataset')
}

export default api
