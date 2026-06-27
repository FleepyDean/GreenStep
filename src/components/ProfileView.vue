<template>
  <div class="profile-container">
    <div v-if="!isAuthenticated" class="auth-box">
      <div v-if="authMode === 'login'">
        <h3>Welcome Back</h3>
        <p class="auth-subtitle">Sign in to sync your local footprint metrics securely</p>
        
        <div v-if="error" class="error-banner">
          {{ error }}
        </div>
        
        <form @submit.prevent="handleLogin">
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" v-model="loginForm.email" placeholder="name@campus.utm.my" required />
          </div>
          <div class="form-group">
            <label>Secure Password</label>
            <input type="password" v-model="loginForm.password" placeholder="••••••••" required />
          </div>
          <button type="submit" class="submit-btn" :disabled="loading">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>
        </form>
        
        <p class="auth-toggle-link">
          Don't have an account? <span @click="authMode = 'register'">Register account</span>
        </p>
      </div>

      <div v-else>
        <h3>Create Profile</h3>
        <p class="auth-subtitle">Join GreenStep to protect daily tracking milestones</p>
        
        <div v-if="error" class="error-banner">
          {{ error }}
        </div>
        
        <form @submit.prevent="handleRegister">
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" v-model="registerForm.name" placeholder="Farish Danial" required />
          </div>
          <div class="form-group">
            <label>Email Address</label>
            <input type="email" v-model="registerForm.email" placeholder="farish@utm.my" required />
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" v-model="registerForm.password" placeholder="••••••••" required />
          </div>
          <button type="submit" class="submit-btn" :disabled="loading">
            {{ loading ? 'Registering...' : 'Complete Registration' }}
          </button>
        </form>
        
        <p class="auth-toggle-link">
          Already have an account? <span @click="authMode = 'login'">Login here</span>
        </p>
      </div>
    </div>

    <div v-else class="profile-layout-grid">
      <section class="user-identity-card">
        <div class="avatar-sphere">👤</div>
        <h3>{{ userProfile.name }}</h3>
        <p class="user-email-text">{{ userProfile.email }}</p>
        <span class="user-role-badge">{{ userProfile.role }}</span>
        <button class="logout-trigger-btn" @click="handleLogout">Logout</button>
      </section>

      <section class="gamification-showcase-panel">
        <h3>🏆 Earned Digital Badges & Milestones</h3>
        
        <div v-if="loadingBadges" class="loading-placeholder">
          Syncing milestones with the database...
        </div>

        <div v-else class="scrollable-badges-box">
          <div class="badges-display-grid">
            <div 
              v-for="badge in userBadges" 
              :key="badge.id" 
              :class="['badge-item-node', { 'locked-badge': !badge.unlocked }]"
            >
              <span class="badge-medallion">
                {{ badge.unlocked ? (badge.icon || '🏅') : '🔒' }}
              </span>
              <h4>{{ badge.title }}</h4>
              <p>{{ badge.criterion }}</p>
              <span :class="['badge-status-stamp', badge.unlocked ? 'status-unlocked' : 'status-locked']">
                {{ badge.unlocked ? 'Unlocked' : 'Locked' }}
              </span>
            </div>
            
            <div v-if="userBadges.length === 0" class="empty-state-notice">
              No milestone profiles configured in backend system.
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { badgeAPI } from '../services/api' 

const authStore = useAuthStore()
const { toast } = useToast()
const authMode = ref('login')
const error = ref(null)
const loading = ref(false)
const loadingBadges = ref(false)

const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({ name: '', email: '', password: '' })

const isAuthenticated = computed(() => authStore.isAuthenticated)
const userProfile = computed(() => authStore.user || {
  name: 'Guest',
  email: '',
  role: 'end-user'
})

const userBadges = ref([])

const fetchUserBadges = async () => {
  if (!authStore.isAuthenticated) return
  
  loadingBadges.value = true
  try {
    const response = await badgeAPI.getUserBadges()
    const data = response.data 
    
    userBadges.value = data.map(badge => {
      let dynamicIcon = '🏅'
      if (badge.id === 1) dynamicIcon = '🚲'
      if (badge.id === 2) dynamicIcon = '🥗'
      if (badge.id === 3) dynamicIcon = '⚡'
      if (badge.id === 4) dynamicIcon = '♻️'
      if (badge.id === 5) dynamicIcon = '💡'
      if (badge.id === 6) dynamicIcon = '🔥'
      if (badge.id === 7) dynamicIcon = '🛠️'
      if (badge.id === 8) dynamicIcon = '🌿'
      if (badge.id === 9) dynamicIcon = '👑'

      return {
        id: badge.id,
        title: badge.name, 
        criterion: badge.criteria_description || 'Eco tracker milestone requirement.', 
        icon: dynamicIcon, 
        unlocked: badge.unlocked === 1 || badge.unlocked === true
      }
    })
  } catch (err) {
    console.error('Network failure connecting to backend tables:', err)
  } finally {
    loadingBadges.value = false
  }
}

const handleLogin = async () => {
  error.value = null
  loading.value = true
  
  const result = await authStore.login(loginForm.email, loginForm.password)
  
  if (result.success) {
    loginForm.email = ''
    loginForm.password = ''
    toast.success('Welcome back!')
    await fetchUserBadges()
  } else {
    error.value = result.message
    toast.error(result.message)
  }
  
  loading.value = false
}

const handleRegister = async () => {
  error.value = null
  loading.value = true
  
  const result = await authStore.register(
    registerForm.name,
    registerForm.email,
    registerForm.password
  )
  
  if (result.success) {
    registerForm.name = ''
    registerForm.email = ''
    registerForm.password = ''
    toast.success('Account created successfully!')
    await fetchUserBadges()
  } else {
    error.value = result.message
    toast.error(result.message)
  }
  
  loading.value = false
}

const handleLogout = () => {
  authStore.logout()
  userBadges.value = []
  loginForm.email = ''
  loginForm.password = ''
  toast.info('Logged out successfully')
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await authStore.fetchProfile()
    await fetchUserBadges()
  }
})
</script>

<style scoped>
/* 📌 CORE STRUCTURE LAYOUT STYLES */
.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

/* 🔐 SECURED AUTH BOX DESIGN FRAMEWORK */
.auth-box {
  max-width: 450px;
  margin: 3rem auto;
  background: #ffffff;
  padding: 2.5rem;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
  border: 1px solid #f1f5f9;
  text-align: center;
}
.auth-box h3 {
  font-size: 1.5rem;
  color: #1e293b;
  margin-bottom: 0.5rem;
  font-weight: 700;
}
.auth-subtitle {
  color: #64748b;
  font-size: 0.9rem;
  margin-bottom: 2rem;
}
.form-group {
  margin-bottom: 1.25rem;
  text-align: left;
}
.form-group label {
  display: block;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.5rem;
}
.form-group input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s ease;
  box-sizing: border-box;
}
.form-group input:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}
.submit-btn {
  width: 100%;
  padding: 0.85rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 1rem;
}
.submit-btn:hover {
  background: #059669;
}
.submit-btn:disabled {
  background: #a7f3d0;
  cursor: not-allowed;
}
.auth-toggle-link {
  margin-top: 1.5rem;
  font-size: 0.9rem;
  color: #64748b;
}
.auth-toggle-link span {
  color: #10b981;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}
.error-banner {
  padding: 10px 14px;
  margin-bottom: 20px;
  background: #ef4444;
  color: white;
  border-radius: 6px;
  font-size: 0.9rem;
  text-align: left;
}

/* 📱 MOBILE VIEW FIRST FLUID LAYOUT */
.profile-layout-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  align-items: stretch;
}
.user-identity-card {
  background: #ffffff;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  border: 1px solid #f1f5f9;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
}
.avatar-sphere {
  font-size: 3rem;
  margin-bottom: 1rem;
}
.user-email-text {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0.25rem 0 1rem 0;
}
.user-role-badge {
  display: inline-block;
  background: #ecfdf5;
  color: #065f46;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  margin-bottom: 1.5rem;
}
.logout-trigger-btn {
  display: block;
  width: 100%;
  padding: 0.65rem;
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fee2e2;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.logout-trigger-btn:hover {
  background: #fee2e2;
}
.gamification-showcase-panel {
  background: #ffffff;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
}
.gamification-showcase-panel h3 {
  font-size: 1.15rem;
  color: #1e293b;
  margin-top: 0;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 0.75rem;
}
.scrollable-badges-box {
  max-height: 520px;
  overflow-y: auto;
  padding-right: 6px;
}
.badges-display-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
  gap: 1rem;
}
.badge-item-node {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1.25rem 1rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  transition: transform 0.2s, box-shadow 0.2s;
}
.badge-item-node:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.04);
}
.badge-medallion {
  font-size: 2.25rem;
  margin-bottom: 0.75rem;
}
.badge-item-node h4 {
  margin: 0 0 0.5rem 0;
  font-size: 0.95rem;
  color: #334155;
  font-weight: 600;
}
.badge-item-node p {
  margin: 0 0 1rem 0;
  font-size: 0.75rem;
  color: #64748b;
  line-height: 1.3;
  flex-grow: 1;
}
.badge-status-stamp {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  text-transform: uppercase;
}
.status-unlocked {
  background: #e6f4ea;
  color: #137333;
}
.status-locked {
  background: #f1f3f4;
  color: #5f6368;
}
.locked-badge {
  background: #f8fafc;
  border-style: dashed;
  opacity: 0.75;
}
.loading-placeholder, .empty-state-notice {
  text-align: center;
  padding: 3rem;
  color: #64748b;
  font-size: 0.95rem;
}

/* 🖥️ DESKTOP RESPONSIVE BREAKPOINT UPGRADES (>=768px Width) */
@media (min-width: 768px) {
  .profile-layout-grid {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 2rem;
    align-items: start;
  }
  .scrollable-badges-box {
    max-height: 440px; /* Snaps perfectly alignment-balanced with identity card */
  }
  .badges-display-grid {
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 1.25rem;
  }
}

/* Custom Webkit Scrollbar Controls */
.scrollable-badges-box::-webkit-scrollbar {
  width: 6px;
}
.scrollable-badges-box::-webkit-scrollbar-track {
  background: #f8fafc;
  border-radius: 10px;
}
.scrollable-badges-box::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
.scrollable-badges-box::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>