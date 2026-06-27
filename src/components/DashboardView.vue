<template>
  <div class="dashboard-wrapper">
    <div class="dashboard-title">
      <h2>🌱 Your Carbon Dashboard</h2>
      <p>Track your environmental impact and progress</p>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <span class="card-icon">📊</span>
        <h3 class="value font-green">{{ metrics.todayFootprint ? metrics.todayFootprint.toFixed(2) : '0.00' }} kg</h3>
        <p class="label">Today's Footprint</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">📝</span>
        <h3 class="value font-green">{{ metrics.weeklyTotal ? metrics.weeklyTotal.toFixed(2) : '0.00' }} kg</h3>
        <p class="label">Weekly Total</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">🔥</span>
        <h3 class="value font-orange">{{ metrics.dailyStreak || 0 }}</h3>
        <p class="label">Daily Streak</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">🏆</span>
        <h3 class="value font-gold">{{ metrics.badgesCount || 0 }}</h3>
        <p class="label">Badges Earned</p>
      </div>
    </div>

    <div class="charts-layout">
      <div class="chart-box">
        <h4>Weekly Trend</h4>
        <div class="chart-container">
          <WeeklyTrendChart 
            :weekly-data="weeklyTrend" 
            :key="weeklyTrend.join(',')"
          />
        </div>
      </div>
      <div class="chart-box">
        <h4>Category Breakdown</h4>
        <div class="chart-container">
          <CategoryBreakdownChart 
            :breakdown-data="categoryData" 
            :key="JSON.stringify(categoryData)"
          />
        </div>
      </div>
    </div>

    <div class="eco-tip-card" v-if="dailyTip">
      <div class="tip-header">💡 Today's Eco Tip</div>
      <div class="tip-content">
        <h5>{{ dailyTip.title }}</h5>
        <p>{{ dailyTip.body }}</p>
      </div>
    </div>

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
          by <strong>{{ goal.projection.end_date.split('-').reverse().join('-') }}</strong>.
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
          </div>
        </div>
      </div>
    </section>

    <!-- Carbon Offset -->
    <div class="offset-card">

      <div class="offset-icon">
        🌍
      </div>

      <h2>Offset Your Carbon Footprint</h2>

      <p class="offset-text">
        You've already produced
        <strong>{{ metrics.weeklyTotal.toFixed(2) }} kg CO₂</strong>
        this week.
      </p>

      <p class="offset-small">
        If you cannot reduce it immediately,
        you can support certified environmental
        projects to compensate your emissions.
      </p>

      <a
        href="https://www.wwf.org.my/how_you_can_help/donate_now/combat_climate_change/"
        target="_blank"
        class="offset-button"
      >
        🌱 Offset My Carbon
      </a>

      <div class="verified">
        ✓ Verified Non-profit Conservation Organization in Malaysia
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
// 🔄 FIXED: Cleanly import the operational API wrappers from your services layer
import { tipAPI, dashboardAPI, goalAPI } from '../services/api'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { eventBus } from '@/utils/eventBus'
import WeeklyTrendChart from '../components/WeeklyTrendChart.vue'
import CategoryBreakdownChart from '../components/CategoryBreakdownChart.vue'

// Base structural tracking references
const loading = ref(true)
const dailyTip = ref(null)

const metrics = ref({
  todayFootprint: 0,
  weeklyTotal: 0,
  dailyStreak: 0,
  badgesCount: 0
})

const weeklyTrend = ref([0, 0, 0, 0, 0, 0, 0])
const categoryData = ref({ Transport: 0, Diet: 0, Energy: 0, Recycling: 0 })

// Auth and goal setup
const authStore = useAuthStore()
const { toast } = useToast()

const goal = ref({ goal: {}, projection: null })
const goalLoading = ref(false)
const goalUpdating = ref(false)
const goalForm = reactive({
  target_reduction_percent: 20,
  baseline_footprint: 15
})

// Safely parse user out of session storage
const user = JSON.parse(localStorage.getItem('user') || '{}')

const fetchDashboardDetails = async () => {
  try {
    loading.value = true

    // 🛡️ SECURITY GUARD LAYER
    if (!user || !user.id) {
      console.error("Dashboard mount blocked: Active user context is missing or unauthenticated.")
      loading.value = false
      return
    }

    // 1. Fetch dynamic eco tip highlight
    const tipResponse = await tipAPI.getRandom()
    if (tipResponse && tipResponse.data) {
      if (tipResponse.data.success) {
        dailyTip.value = tipResponse.data.data
      } else {
        dailyTip.value = tipResponse.data
      }
    }

    // 2. 🔄 FIXED: Query pipeline using the dashboard helper from api.js
    const response = await dashboardAPI.getMetrics(user.id)

    if (response && response.data && response.data.success) {
      // Unpack analytics summary markers
      metrics.value = response.data.data
      
      // Update chart timeline tracking arrays
      if (response.data.data.weeklyTrendArray) {
        weeklyTrend.value = response.data.data.weeklyTrendArray
      }
      
      // Update reactive category metrics representation
      if (response.data.data.categoryBreakdown) {
        categoryData.value = response.data.data.categoryBreakdown
      }
    }
  } catch (error) {
    console.error('Failed to sync database context with dashboard elements:', error)
  } finally {
    loading.value = false
  }
}

const fetchGoal = async () => {
  if (!authStore.isAuthenticated) return
  try {
    goalLoading.value = true
    console.log('Fetching goal...')
    const response = await goalAPI.getGoal()
    console.log('Fetch goal response:', response.data)
    if (response.data.success) {
      goal.value = {
        goal: response.data.goal || {},
        projection: response.data.projection || null
      }
      goalForm.target_reduction_percent = response.data.goal.target_reduction_percent ?? 20
      goalForm.baseline_footprint = response.data.goal.baseline_footprint ?? 15
      console.log('Updated goal from fetch:', goal.value)
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
    console.log('Resetting goal with:', {
      target_reduction_percent: goalForm.target_reduction_percent,
      baseline_footprint: goalForm.baseline_footprint,
      reset_start_date: true
    })
    
    const response = await goalAPI.updateGoal({
      target_reduction_percent: goalForm.target_reduction_percent,
      baseline_footprint: goalForm.baseline_footprint,
      reset_start_date: true
    })
    
    console.log('Reset goal response:', response.data)
    
    if (response.data.success) {
      goal.value = {
        goal: response.data.goal || {},
        projection: response.data.projection || null
      }
      console.log('Updated goal state:', goal.value)
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

const handleActivityLogged = async () => {
  console.log('Activity logged event received, refreshing goal...')
  await fetchGoal()
}

onMounted(async () => {
  if (user && user.id) {
    await fetchDashboardDetails()
    await fetchGoal()
    eventBus.on('activity-logged', handleActivityLogged)
  } else {
    console.warn('Dashboard view execution halted: Active user identity trace unavailable.')
    loading.value = false
  }
})

onUnmounted(() => {
  eventBus.off('activity-logged', handleActivityLogged)
})
</script>

<style scoped>
.goal-projection-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
  margin-top: 1.5rem;
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

/* ============================= */
/* Carbon Offset Card */
/* ============================= */

.offset-card{

margin-top:35px;
padding:30px;

background:linear-gradient(135deg,#1E8E3E,#34A853);

border-radius:20px;

color:white;

text-align:center;

box-shadow:0 12px 35px rgba(0,0,0,.18);

position:relative;

overflow:hidden;

}

/* floating circles */

.offset-card::before{

content:"";

position:absolute;

width:220px;
height:220px;

background:rgba(255,255,255,.08);

border-radius:50%;

top:-80px;
right:-60px;

}

.offset-card::after{

content:"";

position:absolute;

width:140px;
height:140px;

background:rgba(255,255,255,.08);

border-radius:50%;

bottom:-60px;
left:-40px;

}

.offset-icon{

font-size:60px;

margin-bottom:10px;

animation:floatEarth 3s ease-in-out infinite;

}

@keyframes floatEarth{

0%{
transform:translateY(0px);
}

50%{
transform:translateY(-8px);
}

100%{
transform:translateY(0px);
}

}

.offset-card h2{

margin-bottom:15px;

font-size:28px;

}

.offset-text{

font-size:18px;

margin-bottom:12px;

}

.offset-small{

opacity:.9;

line-height:1.6;

margin-bottom:25px;

}

.offset-button{

display:inline-block;

padding:14px 34px;

background:white;

color:#1E8E3E;

font-weight:bold;

border-radius:50px;

text-decoration:none;

transition:.3s;

box-shadow:0 5px 15px rgba(0,0,0,.2);

}

.offset-button:hover{

transform:translateY(-4px) scale(1.05);

box-shadow:0 12px 25px rgba(0,0,0,.25);

}

.verified{

margin-top:20px;

font-size:14px;

opacity:.9;

letter-spacing:1px;

}
</style>


