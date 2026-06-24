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

      <section class="goal-projection-card">
        <h3>🎯 Carbon Reduction Goal</h3>

        <div v-if="goalLoading" class="goal-loading">
          <p>Loading your projection...</p>
        </div>

        <div v-else-if="goal.projection" class="goal-body">
          <div class="goal-header-row">
            <div class="goal-target">
              <span class="goal-label">Target</span>
              <span class="goal-value">{{ goal.goal.target_reduction_percent }}% in {{ goal.goal.goal_duration_days }} days</span>
            </div>
            <span :class="['goal-status-badge', goal.projection.on_track ? 'on-track' : 'off-track']">
              {{ goal.projection.on_track ? 'On Track' : 'Off Track' }}
            </span>
          </div>

          <div class="progress-wrapper">
            <div class="progress-bar-bg">
              <div
                class="progress-bar-fill"
                :style="{ width: `${Math.max(0, Math.min(100, goal.projection.progress_percent))}%` }"
              ></div>
            </div>
            <div class="progress-meta">
              <span>{{ goal.projection.progress_percent }}% of target</span>
              <span>{{ goal.projection.days_elapsed }} / {{ goal.projection.duration_days }} days</span>
            </div>
          </div>

          <div class="projection-stats">
            <div class="stat-box">
              <span class="stat-label">Current Pace</span>
              <span class="stat-value">{{ goal.projection.average_pace_per_day }} kg/day</span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Projected Total</span>
              <span class="stat-value">{{ goal.projection.projected_total_savings }} kg</span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Target Savings</span>
              <span class="stat-value">{{ goal.projection.target_total_savings }} kg</span>
            </div>
            <div class="stat-box">
              <span class="stat-label">Needed Pace</span>
              <span class="stat-value">{{ goal.projection.needed_daily_pace }} kg/day</span>
            </div>
          </div>

          <p class="goal-insight">
            At your current pace you are projected to reduce
            <strong>{{ goal.projection.projected_reduction_percent }}%</strong>
            by <strong>{{ goal.projection.end_date }}</strong>.
          </p>

          <div class="goal-editor">
            <h4>Customize Goal</h4>
            <div class="goal-form-row">
              <div class="form-group">
                <label>Target Reduction (%)</label>
                <input type="number" v-model.number="goalForm.target_reduction_percent" min="1" max="100" step="1" />
              </div>
              <div class="form-group">
                <label>Daily Baseline (kg CO₂)</label>
                <input type="number" v-model.number="goalForm.baseline_footprint" min="0" step="0.1" />
              </div>
            </div>
            <div class="goal-actions">
              <button class="update-goal-btn" @click="updateGoal" :disabled="goalUpdating">
                {{ goalUpdating ? 'Updating...' : 'Update Goal' }}
              </button>
              <button class="reset-goal-btn" @click="resetGoal" :disabled="goalUpdating">
                Start New Cycle
              </button>
            </div>
          </div>
        </div>
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
import { useToast } from '@/composables/useToast'
import { goalAPI } from '@/services/api'

const authStore = useAuthStore()
const { toast } = useToast()
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
const goal = ref({ goal: {}, projection: null })
const goalLoading = ref(false)
const goalUpdating = ref(false)
const goalForm = reactive({
  target_reduction_percent: 20,
  baseline_footprint: 15
})

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
    toast.success('Welcome back!')
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
  } else {
    error.value = result.message
    toast.error(result.message)
  }
  
  loading.value = false
}

const handleLogout = () => {
  authStore.logout()
  loginForm.email = ''
  loginForm.password = ''
  toast.info('Logged out successfully')
}

const fetchGoal = async () => {
  if (!authStore.isAuthenticated) return
  try {
    goalLoading.value = true
    const response = await goalAPI.getGoal()
    if (response.data.success) {
      goal.value = {
        goal: response.data.goal || {},
        projection: response.data.projection || null
      }
      goalForm.target_reduction_percent = response.data.goal.target_reduction_percent ?? 20
      goalForm.baseline_footprint = response.data.goal.baseline_footprint ?? 15
    }
  } catch (err) {
    console.error('Failed to fetch goal:', err)
  } finally {
    goalLoading.value = false
  }
}

const updateGoal = async () => {
  if (!authStore.isAuthenticated) return
  try {
    goalUpdating.value = true
    const response = await goalAPI.updateGoal({
      target_reduction_percent: goalForm.target_reduction_percent,
      baseline_footprint: goalForm.baseline_footprint
    })
    if (response.data.success) {
      goal.value = {
        goal: response.data.goal || {},
        projection: response.data.projection || null
      }
      toast.success('Goal updated successfully')
    } else {
      toast.error(response.data.message || 'Failed to update goal')
    }
  } catch (err) {
    console.error('Failed to update goal:', err)
    toast.error('Failed to update goal')
  } finally {
    goalUpdating.value = false
  }
}

const resetGoal = async () => {
  if (!authStore.isAuthenticated) return
  try {
    goalUpdating.value = true
    const response = await goalAPI.updateGoal({
      target_reduction_percent: goalForm.target_reduction_percent,
      baseline_footprint: goalForm.baseline_footprint,
      reset_start_date: true
    })
    if (response.data.success) {
      goal.value = {
        goal: response.data.goal || {},
        projection: response.data.projection || null
      }
      toast.success('New goal cycle started')
    } else {
      toast.error(response.data.message || 'Failed to reset goal')
    }
  } catch (err) {
    console.error('Failed to reset goal:', err)
    toast.error('Failed to reset goal')
  } finally {
    goalUpdating.value = false
  }
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await authStore.fetchProfile()
    await fetchGoal()
  }
})
</script>

<style scoped>
.goal-projection-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  grid-column: 1 / -1;
}

.goal-projection-card h3 {
  margin: 0 0 1rem 0;
  font-size: 1.2rem;
  color: #1b3a4b;
}

.goal-loading {
  text-align: center;
  color: #54656f;
  padding: 1rem 0;
}

.goal-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.goal-target {
  display: flex;
  flex-direction: column;
}

.goal-label {
  font-size: 0.75rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.goal-value {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2c3e50;
}

.goal-status-badge {
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
}

.goal-status-badge.on-track {
  background: #d4edda;
  color: #155724;
}

.goal-status-badge.off-track {
  background: #f8d7da;
  color: #721c24;
}

.progress-wrapper {
  margin-bottom: 1.25rem;
}

.progress-bar-bg {
  background: #e9ecef;
  border-radius: 999px;
  height: 12px;
  overflow: hidden;
  margin-bottom: 0.4rem;
}

.progress-bar-fill {
  background: linear-gradient(90deg, #27ae60, #2ecc71);
  height: 100%;
  border-radius: 999px;
  transition: width 0.5s ease;
}

.progress-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  color: #54656f;
}

.projection-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.stat-box {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 0.75rem;
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.75rem;
  color: #7f8c8d;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1rem;
  font-weight: 700;
  color: #1b3a4b;
}

.goal-insight {
  font-size: 0.9rem;
  color: #54656f;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.goal-editor {
  border-top: 1px solid #e9ecef;
  padding-top: 1.25rem;
}

.goal-editor h4 {
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
  color: #1b3a4b;
}

.goal-form-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.goal-form-row .form-group {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.goal-form-row label {
  font-size: 0.8rem;
  color: #7f8c8d;
  margin-bottom: 0.3rem;
}

.goal-form-row input {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 0.95rem;
}

.goal-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.update-goal-btn,
.reset-goal-btn {
  padding: 0.55rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.update-goal-btn:disabled,
.reset-goal-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.update-goal-btn {
  background: #27ae60;
  color: white;
}

.reset-goal-btn {
  background: #e9ecef;
  color: #1b3a4b;
}
</style>