<template>
  <div class="profile-container">
    <div v-if="!isAuthenticated" class="auth-box">
      <div v-if="authMode === 'login'">
        <h3>Welcome Back</h3>
        <p class="auth-subtitle">Sign in to sync your local footprint metrics securely</p>
        
        <div v-if="error" style="padding: 10px; margin-bottom: 15px; background: #ff6b6b; color: white; border-radius: 5px;">
          {{ error }}
        </div>
        
        <form @submit.prevent="handleLogin">
          <div class="form-group" style="text-align: left;">
            <label>Email Address</label>
            <input type="email" v-model="loginForm.email" placeholder="name@campus.utm.my" required />
          </div>
          <div class="form-group" style="text-align: left;">
            <label>Secure Password</label>
            <input type="password" v-model="loginForm.password" placeholder="••••••••" required />
          </div>
          <button type="submit" class="submit-btn" style="margin-top: 1rem;" :disabled="loading">
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
        
        <div v-if="error" style="padding: 10px; margin-bottom: 15px; background: #ff6b6b; color: white; border-radius: 5px;">
          {{ error }}
        </div>
        
        <form @submit.prevent="handleRegister">
          <div class="form-group" style="text-align: left;">
            <label>Full Name</label>
            <input type="text" v-model="registerForm.name" placeholder="Farish Danial" required />
          </div>
          <div class="form-group" style="text-align: left;">
            <label>Email Address</label>
            <input type="email" v-model="registerForm.email" placeholder="farish@utm.my" required />
          </div>
          <div class="form-group" style="text-align: left;">
            <label>Password (Encrypted using Bcrypt via API)</label>
            <input type="password" v-model="registerForm.password" placeholder="••••••••" required />
          </div>
          <button type="submit" class="submit-btn" style="margin-top: 1rem;" :disabled="loading">
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
        <div class="badges-display-grid">
          <div 
            v-for="badge in userBadges" 
            :key="badge.id" 
            :class="['badge-item-node', { 'locked-badge': !badge.unlocked }]"
          >
            <span class="badge-medallion">{{ badge.unlocked ? badge.icon : '🔒' }}</span>
            <h4>{{ badge.title }}</h4>
            <p>{{ badge.criterion }}</p>
            <span :class="['badge-status-stamp', badge.unlocked ? 'status-unlocked' : 'status-locked']">
              {{ badge.unlocked ? 'Unlocked' : 'Locked' }}
            </span>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const authMode = ref('login')
const error = ref(null)
const loading = ref(false)

const loginForm = reactive({ email: '', password: '' })
const registerForm = reactive({ name: '', email: '', password: '' })

const isAuthenticated = computed(() => authStore.isAuthenticated)
const userProfile = computed(() => authStore.user || {
  name: 'Guest',
  email: '',
  role: 'end-user'
})

// Gamification Logic Engine Payload Mappings (FR-iv)
const userBadges = ref([
  {
    id: 1,
    title: 'Green Commuter',
    criterion: 'Logged over 50 kilometers in non-combustion alternative transit.',
    icon: '🚲',
    unlocked: true
  },
  {
    id: 2,
    title: 'Conscious Eater',
    criterion: 'Logged a streak of 7 consecutive plant-based dinner choices.',
    icon: '🥗',
    unlocked: true
  },
  {
    id: 3,
    title: 'Carbon Neutral Master',
    criterion: 'Successfully drop total cumulative footprint values below regional averages.',
    icon: '⚡',
    unlocked: false
  }
])

const handleLogin = async () => {
  error.value = null
  loading.value = true
  
  const result = await authStore.login(loginForm.email, loginForm.password)
  
  if (result.success) {
    loginForm.email = ''
    loginForm.password = ''
  } else {
    error.value = result.message
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
  } else {
    error.value = result.message
  }
  
  loading.value = false
}

const handleLogout = () => {
  authStore.logout()
  loginForm.email = ''
  loginForm.password = ''
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await authStore.fetchProfile()
  }
})
</script>