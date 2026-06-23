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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
// 🔄 FIXED: Cleanly import the operational API wrappers from your services layer
import { tipAPI, dashboardAPI } from '../services/api'
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

onMounted(() => {
  if (user && user.id) {
    fetchDashboardDetails()
  } else {
    console.warn('Dashboard view execution halted: Active user identity trace unavailable.')
    loading.value = false
  }
})
</script>


