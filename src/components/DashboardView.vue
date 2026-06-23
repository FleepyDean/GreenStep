<template>
  <div class="dashboard-wrapper">
    <div class="dashboard-title">
      <h2>🌱 Your Carbon Dashboard</h2>
      <p>Track your environmental impact and progress</p>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <span class="card-icon">📊</span>
        <h3 class="value font-green">107.24 kg</h3>
        <p class="label">Today's Footprint</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">📝</span>
        <h3 class="value font-green">107.24 kg</h3>
        <p class="label">Weekly Total</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">🔥</span>
        <h3 class="value font-orange">30</h3>
        <p class="label">Daily Streak</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">🏆</span>
        <h3 class="value font-gold">1</h3>
        <p class="label">Badges Earned</p>
      </div>
    </div>

    <div class="charts-layout">
      <div class="chart-box">
        <h4>Weekly Trend</h4>
        <WeeklyTrendChart :weekly-data="weeklyTrend" />
      </div>
      <div class="chart-box">
        <h4>Category Breakdown</h4>
        <CategoryBreakdownChart :category-data="categoryData" />
      </div>
    </div>

    <div class="eco-tip-card">
      <div class="tip-header">💡 Today's Eco Tip</div>
      <div class="tip-content">
        <h5>Use a reusable water bottle</h5>
        <p>Save approximately 156 plastic bottles per year by using a reusable water bottle instead of disposable ones.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
import { tipAPI } from '../services/api'
import axios from 'axios'
import WeeklyTrendChart from '../components/WeeklyTrendChart.vue' // Adjust import path if needed
import CategoryBreakdownChart from '../components/CategoryBreakdownChart.vue' // Adjust import path if needed
// Initialize loading state boundaries
const loading = ref(true)
const dailyTip = ref(null)

// Initialize data structural references
const metrics = ref({
  todayFootprint: 0,
  weeklyTotal: 0,
  dailyStreak: 0,
  badgesCount: 0
})

// Monday - Sunday historical values array ref
const weeklyTrend = ref([0, 0, 0, 0, 0, 0, 0])
const categoryData = ref([0, 0, 0, 0, 0])
// Safe check context tracking user details
const user = JSON.parse(localStorage.getItem('user'))

const fetchDashboardDetails = async () => {
  try {
    loading.value = true

    // 1. Fetch dynamic eco tip highlight
    const tipResponse = await tipAPI.getRandom()
    dailyTip.value = tipResponse.data

    // 2. Query data structures from your backend database container
    const response = await axios.get(`http://localhost/GreenStep/api/public/index.php/api/dashboard/${user?.id}`, {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`
      }
    })

    if (response.data && response.data.success) {
      // Unpack analytics summary markers
      metrics.value = response.data.data
      
      // Update chart tracking timeline array state
      if (response.data.data.weeklyTrendArray) {
        weeklyTrend.value = response.data.data.weeklyTrendArray
      }
      if (response.data.data.categoryData) {
        categoryData.value = response.data.data.categoryData
      }
    }
  } catch (error) {
    console.error('Failed to sync full database data context with dashboard elements:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (user?.id) {
    fetchDashboardDetails()
  } else {
    console.warn('Dashboard view execution halted: Active user context unavailable.')
    loading.value = false
  }
})
</script>

