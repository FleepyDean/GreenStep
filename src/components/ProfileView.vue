<template>
  <div class="profile-container">
    <div v-if="!isAuthenticated" class="auth-box">
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
        
        <div v-if="loadingBadges" style="text-align: center; padding: 2rem; color: #888;">
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
            
            <div v-if="userBadges.length === 0" style="grid-column: 1/-1; text-align: center; color: #777; padding-top: 2rem;">
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
/* 📱 MOBILE VIEW FIRST (Default) */
.profile-layout-grid {
  display: flex;
  flex-direction: column; /* Stack profile card on top of badges on small screens */
  gap: 1.5rem;
  padding: 1rem;
  align-items: stretch;
}

.scrollable-badges-box {
  max-height: 500px; /* Gives mobile viewers plenty of viewport real estate */
  overflow-y: auto;
  padding-right: 6px;
}

/* Fluid responsive grid for the individual badge items themselves */
.badges-display-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); /* Adaptive column reduction */
  gap: 1rem;
}

.gamification-showcase-panel {
  background: #ffffff;
  padding: 1.25rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  border: 1px solid #f1f5f9;
}


/* 🖥️ DESKTOP VIEW UPGRADES (Triggered above 768px wide viewport) */
@media (min-width: 768px) {
  .profile-layout-grid {
    display: grid;
    grid-template-columns: 280px 1fr; /* Snaps back into columns side-by-side */
    gap: 2rem;
    padding: 2rem;
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