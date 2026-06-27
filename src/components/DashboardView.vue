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
        <div class="chart-header">
          <h4>{{ trendMode === 'weekly' ? 'Weekly Trend' : 'Monthly Trend' }}</h4>
          <div class="toggle-container">
            <button 
              :class="['toggle-btn', { active: trendMode === 'weekly' }]" 
              @click="trendMode = 'weekly'"
            >
              Week
            </button>
            <button 
              :class="['toggle-btn', { active: trendMode === 'monthly' }]" 
              @click="trendMode = 'monthly'"
            >
              Month
            </button>
          </div>
        </div>

        <div class="chart-container">
          <WeeklyTrendChart 
            v-if="trendMode === 'weekly'"
            :weekly-data="weeklyTrend" 
            :key="'wk-' + weeklyTrend.join(',')"
          />
          <MonthlyTrendChart 
            v-else
            :monthly-data="monthlyTrend" 
            :key="'mo-' + monthlyTrend.join(',')"
          />
        </div>
      </div>

      <div class="chart-box">
        <h4>Category Breakdown</h4>
        <div class="chart-container">
          <CategoryBreakdownChart 
            :breakdown-data="categoryData" 
            :key="Object.values(categoryData).join(',')"
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { tipAPI, dashboardAPI } from '../services/api'
import WeeklyTrendChart from '../components/WeeklyTrendChart.vue'
import MonthlyTrendChart from '../components/MonthlyTrendChart.vue'
import CategoryBreakdownChart from '../components/CategoryBreakdownChart.vue'

// Component & View States
const loading = ref(true)
const dailyTip = ref(null)
const trendMode = ref('weekly') // Tracks active chart view context

// Dashboard Primary Metrics Representation
const metrics = ref({
  todayFootprint: 0,
  weeklyTotal: 0,
  dailyStreak: 0,
  badgesCount: 0
})

// Chart Reactive State Variables
const weeklyTrend = ref([0, 0, 0, 0, 0, 0, 0])
const monthlyTrend = ref([0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0])
const categoryData = ref({ Transport: 0, Diet: 0, Energy: 0, Recycling: 0 })

// Session Authenticated Context Trace
const user = JSON.parse(localStorage.getItem('user') || '{}')

const fetchDashboardDetails = async () => {
  try {
    loading.value = true

    // SECURITY GUARD LAYER
    if (!user || !user.id) {
      console.error("Dashboard mount blocked: Active user context is missing or unauthenticated.")
      loading.value = false
      return
    }

    // 1. Fetch dynamic eco tip highlight
    const tipResponse = await tipAPI.getRandom()
    if (tipResponse && tipResponse.data) {
      dailyTip.value = tipResponse.data.success ? tipResponse.data.data : tipResponse.data
    }

    // 2. Query metric dataset aggregation pipelines 
    const response = await dashboardAPI.getMetrics(user.id)

    if (response && response.data && response.data.success) {
      // Unpack raw analytics values 
      metrics.value = response.data.data
      
      // Assign standard weekly tracking data points 
      if (response.data.data.weeklyTrendArray) {
        weeklyTrend.value = response.data.data.weeklyTrendArray
      }
      
      // Map standalone monthly integers into an accumulating progressive sequence
      if (response.data.data.monthlyTrendArray) {
        const rawMonthlyData = response.data.data.monthlyTrendArray
        
        let runningTotal = 0
        monthlyTrend.value = rawMonthlyData.map(monthValue => {
          runningTotal += monthValue
          return runningTotal
        })
      }
      
      // Update distribution category parameters
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

onMounted(() => {
  if (user && user.id) {
    fetchDashboardDetails()
  } else {
    console.warn('Dashboard view execution halted: Active user identity trace unavailable.')
    loading.value = false
  }
})
</script>

<style scoped>
/* Chart Structural Grid Header & Selection Switch Layout Styling */
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.chart-header h4 {
  margin: 0;
}

.toggle-container {
  display: flex;
  background-color: #f1f3f4;
  padding: 3px;
  border-radius: 20px;
}

.toggle-btn {
  border: none;
  background: none;
  padding: 5px 12px;
  font-size: 12px;
  font-weight: 500;
  color: #54656f;
  cursor: pointer;
  border-radius: 15px;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  color: #00a884;
}

.toggle-btn.active {
  background-color: #ffffff;
  color: #00a884;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-box {
  flex: 1;
  min-width: 300px; /* Safely safeguards chart aspect ratios against layout collapse */
}
</style>