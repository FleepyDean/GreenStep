<template>
  <div class="activity-container">

    <!-- Tab Navigation -->
    <div class="tab-navigation">
      <button 
        :class="['tab-btn', { active: activeTab === 'log' }]" 
        @click="activeTab = 'log'"
      >
        Activity Log
      </button>
      <button 
        :class="['tab-btn', { active: activeTab === 'track' }]" 
        @click="activeTab = 'track'"
      >
        Activity Track
      </button>
    </div>

    <!-- Activity Log Tab -->
    <div v-if="activeTab === 'log'" class="tab-content">
      <div class="split-layout">
        <slot>
          <section class="form-card">
            <h3>Add New Activity</h3>
            <form @submit.prevent="submitActivity">
              <div class="form-group">
                <label>Category</label>
                <select v-model="form.category" required>
                  <option value="" disabled>Select category</option>
                  <option value="Transport">Transport</option>
                  <option value="Diet">Diet/Food Choice</option>
                  <option value="Energy">Energy</option>
                  <option value="Recycling">Recycling</option>
                </select>
              </div>

              <div class="form-group">
                <label>Activity Type</label>
                <select v-model="form.type" :disabled="!form.category" required>
                  <option value="" disabled>Select type</option>
                  <option v-for="opt in typeOptions" :key="opt.id" :value="opt.name">
                    {{ opt.name }} ({{ opt.unit }})
                  </option>
                </select>
              </div>

              <div class="form-group">
                <label>Amount (units / km / kWh)</label>
                <input type="number" v-model.number="form.amount" placeholder="Enter amount" min="0" step="any" required />
              </div>

              <div class="form-group">
                <label>Date</label>
                <input type="date" v-model="form.date" required />
              </div>

              <button type="submit" class="submit-btn">Log Activity</button>
            </form>
          </section>
        </slot>
      </div>

      
      <footer class="guidelines-section" style="margin-top: 30px;">
        <h3>📋 Activity Guidelines</h3>
        <div class="guidelines-grid">
          <div class="guide-item">
            <h5>🚲 Transport</h5>
            <p>Log your total travel distance in different transport modes.</p>
          </div>
          <div class="guide-item">
            <h5>🥗 Diet</h5>
            <p>Track your meals. Red meat heavily affects footprint calculations.</p>
          </div>
          <div class="guide-item">
            <h5>🔌 Energy</h5>
            <p>Monitor domestic electricity and appliance energy consumption.</p>
          </div>
          <div class="guide-item">
            <h5>♻️ Recycling</h5>
            <p>Log recycling efforts to reduce waste production totals.</p>
          </div>
        </div>
      </footer>
    </div>

    <!-- Activity Track Tab -->
    <div v-else class="tab-content">
      <!-- Compact Filter Card -->
      <section class="filter-card">
        <div class="filter-inner">
          <!-- Date Range -->
          <div class="filter-group">
            <label class="filter-label">📅 Date Range</label>
            <div class="date-range-row">
              <input type="date" v-model="filters.startDate" />
              <span class="dash">→</span>
              <input type="date" v-model="filters.endDate" />
            </div>
          </div>

          <!-- Divider -->
          <div class="filter-divider"></div>

          <!-- Category Options -->
          <div class="filter-group">
            <label class="filter-label">🏷️ Category</label>
            <div class="category-options">
              <label class="category-radio">
                <input type="radio" v-model="filters.category" value="" />
                <span>All</span>
              </label>
              <label class="category-radio">
                <input type="radio" v-model="filters.category" value="Transport" />
                <span>🚗 Transport</span>
              </label>
              <label class="category-radio">
                <input type="radio" v-model="filters.category" value="Diet" />
                <span>🍽️ Diet</span>
              </label>
              <label class="category-radio">
                <input type="radio" v-model="filters.category" value="Energy" />
                <span>⚡ Energy</span>
              </label>
              <label class="category-radio">
                <input type="radio" v-model="filters.category" value="Recycling" />
                <span>♻️ Recycling</span>
              </label>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="filter-actions">
            <button class="btn-reset" @click="clearFilters" :disabled="historyLoading">Reset</button>
            <button class="btn-apply" @click="handleApplyFilters" :disabled="historyLoading">Apply</button>
          </div>
        </div>
      </section>

      <!-- Summary Stats Bar -->
      <div v-if="!historyLoading && !historyError && historyActivities.length > 0" class="summary-bar">
        <div class="summary-item">
          <span class="summary-value">{{ historyActivities.length }}</span>
          <span class="summary-label">Activities</span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
          <span class="summary-value">{{ Number(historyTotalFootprint).toFixed(2) }}</span>
          <span class="summary-label">kg CO₂e Total</span>
        </div>
        <div class="summary-divider"></div>
        <div class="summary-item">
          <span class="summary-value">{{ (historyTotalFootprint / historyActivities.length).toFixed(2) }}</span>
          <span class="summary-label">kg CO₂e Avg</span>
        </div>
      </div>

      <!-- History List -->
      <section class="history-list-section">
        <!-- Loading State -->
        <div v-if="historyLoading" class="state-box">
          <div class="spinner"></div>
          <p>Loading history...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="historyError" class="state-box error">
          <span class="state-icon">⚠️</span>
          <p>{{ historyError }}</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="historyActivities.length === 0" class="state-box">
          <span class="state-icon">📭</span>
          <p>No activities found</p>
          <p class="state-subtext">Try adjusting your filters</p>
        </div>

        <!-- Activity Cards -->
        <div v-else class="history-cards">
          <div class="history-card" v-for="log in historyActivities" :key="log.id">
            <div class="history-card-icon" :class="getCategoryClass(log.category)">
              {{ getCategoryIcon(log.category) }}
            </div>
            <div class="history-card-body">
              <div class="history-card-top">
                <h4>{{ log.activity_name }}</h4>
                <span class="category-pill" :class="getCategoryClass(log.category)">{{ log.category }}</span>
              </div>
              <div class="history-card-meta">
                <span class="meta-amount">{{ log.amount }} {{ log.unit }}</span>
                <span class="meta-dot">·</span>
                <span class="meta-date">{{ formatDate(log.logged_on) }}</span>
              </div>
            </div>
            <div class="history-card-right">
              <div class="carbon-badge">{{ Number(log.carbon_footprint).toFixed(2) }}<small>kg CO₂e</small></div>
              <button class="delete-btn-icon" @click="removeHistoryLog(log.id)" :disabled="historyLoading" title="Delete">🗑️</button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { activityAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

// Tab state
const activeTab = ref('log')

// Activity Log form
const form = reactive({
  category: '',
  type: '',
  activityTypeId: null,
  amount: null,
  date: new Date().toISOString().substr(0, 10)
})

// Activity Track filters
const filters = reactive({
  startDate: '',
  endDate: '',
  category: ''
})

// Activity Log data
const activityTypes = ref([])
const activities = ref([])
const totalFootprint = ref(0)
const loading = ref(false)
const error = ref(null)

// Activity Track data
const historyActivities = ref([])
const historyTotalFootprint = ref(0)
const historyLoading = ref(false)
const historyError = ref(null)

const typeOptions = computed(() => {
  if (!form.category) return []
  return activityTypes.value
    .filter(type => type.category === form.category)
    .map(type => ({
      id: type.id,
      name: type.name,
      unit: type.unit
    }))
})

watch(() => form.type, (newType) => {
  const selected = activityTypes.value.find(t => t.name === newType)
  form.activityTypeId = selected?.id || null
})

async function loadActivityTypes() {
  try {
    const response = await activityAPI.getTypes()
    if (response.data.success) {
      const types = response.data.activity_types
      activityTypes.value = Object.values(types).flat()
    }
  } catch (err) {
    console.error('Failed to load activity types:', err)
    error.value = 'Failed to load activity types'
  }
}

async function loadTodayActivities() {
  try {
    loading.value = true
    error.value = null
    const response = await activityAPI.getTodayActivities()
    if (response.data.success) {
      activities.value = response.data.activities
      totalFootprint.value = response.data.total_footprint || 0
    }
  } catch (err) {
    console.error('Failed to load activities:', err)
    if (err.response?.status === 401) {
      error.value = 'Please login to view activities'
      setTimeout(() => router.push('/profile'), 2000)
    } else {
      error.value = err.response?.data?.message || 'Failed to load activities. Please try again.'
    }
  } finally {
    loading.value = false
  }
}

async function submitActivity() {
  if (!form.activityTypeId || !form.amount) {
    error.value = 'Please fill in all fields'
    toast.error('Please fill in all fields')
    return
  }

  try {
    loading.value = true
    error.value = null
    
    const response = await activityAPI.logActivity({
      activity_type_id: form.activityTypeId,
      amount: form.amount,
      date: form.date
    })

    if (response.data.success) {
      form.category = ''
      form.type = ''
      form.activityTypeId = null
      form.amount = null
      form.date = new Date().toISOString().substr(0, 10)
      
      await loadTodayActivities()
      await loadHistory()
      toast.success('Activity logged successfully!')
    }
  } catch (err) {
    console.error('Failed to log activity:', err)
    error.value = err.response?.data?.message || 'Failed to log activity'
    toast.error('Failed to log activity')
  } finally {
    loading.value = false
  }
}

async function removeLog(id) {
  if (!confirm('Are you sure you want to delete this activity?')) return
  
  try {
    loading.value = true
    await activityAPI.deleteActivity(id)
    await loadTodayActivities()
    await loadHistory()
    toast.success('Activity deleted')
  } catch (err) {
    console.error('Failed to delete activity:', err)
    error.value = 'Failed to delete activity'
    toast.error('Failed to delete activity')
  } finally {
    loading.value = false
  }
}

// Activity Track functions
function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('en-MY', { 
    weekday: 'short', 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric' 
  })
}

function getCategoryIcon(category) {
  const icons = {
    'Transport': '🚗',
    'Diet': '🍽️',
    'Energy': '⚡',
    'Recycling': '♻️'
  }
  return icons[category] || '📝'
}

function getCategoryClass(category) {
  const classes = {
    'Transport': 'cat-transport',
    'Diet': 'cat-diet',
    'Energy': 'cat-energy',
    'Recycling': 'cat-recycling'
  }
  return classes[category] || ''
}

async function loadHistory(params = {}) {
  try {
    historyLoading.value = true
    historyError.value = null
    
    const response = await activityAPI.getActivities(params)
    
    if (response.data.success) {
      historyActivities.value = response.data.activities || []
      // Calculate total from activities
      const total = historyActivities.value.reduce((sum, activity) => {
        return sum + (parseFloat(activity.carbon_footprint) || 0)
      }, 0)
      historyTotalFootprint.value = total
    }
  } catch (err) {
    console.error('Failed to load history:', err)
    if (err.response?.status === 401) {
      historyError.value = 'Please login to view history'
      setTimeout(() => router.push('/profile'), 2000)
    } else {
      historyError.value = err.response?.data?.message || 'Failed to load activity history'
    }
  } finally {
    historyLoading.value = false
  }
}

async function applyFilters() {
  const params = {}
  if (filters.startDate) params.start_date = filters.startDate
  if (filters.endDate) params.end_date = filters.endDate
  if (filters.category) params.category = filters.category
  
  await loadHistory(params)
}

async function handleApplyFilters() {
  await applyFilters()
}

function clearFilters() {
  filters.startDate = ''
  filters.endDate = ''
  filters.category = ''
  loadHistory()
}

async function removeHistoryLog(id) {
  if (!confirm('Are you sure you want to delete this activity?')) return
  
  try {
    historyLoading.value = true
    await activityAPI.deleteActivity(id)
    // Reload current filtered view
    await applyFilters()
    toast.success('Activity deleted')
  } catch (err) {
    console.error('Failed to delete activity:', err)
    historyError.value = 'Failed to delete activity'
    toast.error('Failed to delete activity')
  } finally {
    historyLoading.value = false
  }
}

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/profile')
    return
  }
  
  await loadActivityTypes()
  await loadTodayActivities()
  await loadHistory() // Load initial history for Activity Track tab
})
</script>

<style scoped>
/* Tab Navigation Styles */
.tab-navigation {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
  border-bottom: 2px solid #1a3d2e;
  padding-bottom: 10px;
}

.tab-btn {
  padding: 10px 24px;
  background: transparent;
  border: 2px solid #10b981;
  color: #10b981;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.tab-btn:hover {
  background: rgba(16, 185, 129, 0.1);
}

.tab-btn.active {
  background: #10b981;
  color: #fff;
}

/* ============= FILTER CARD ============= */
.filter-card {
  background: #0f1c17;
  border-radius: 14px;
  padding: 24px;
  border: 1px solid #1a3d2e;
}

.filter-inner {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.filter-label {
  font-size: 13px;
  font-weight: 600;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-divider {
  height: 1px;
  background: #1a3d2e;
}

/* Date Range */
.date-range-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.date-range-row input {
  flex: 1;
  padding: 10px 14px;
  background: #0a1510;
  border: 1px solid #1a3d2e;
  border-radius: 8px;
  color: #fff;
  font-size: 14px;
  color-scheme: dark;
}

.date-range-row input:focus {
  outline: none;
  border-color: #10b981;
}

.dash {
  color: #4b5563;
  font-size: 16px;
}

/* Category Pills */
.category-options {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.category-radio {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 8px 16px;
  background: #0a1510;
  border: 1px solid #1a3d2e;
  border-radius: 20px;
  transition: all 0.2s ease;
}

.category-radio:hover {
  border-color: #10b981;
}

.category-radio input[type="radio"] {
  display: none;
}

.category-radio span {
  font-size: 13px;
  color: #9ca3af;
  font-weight: 500;
}

.category-radio input[type="radio"]:checked + span {
  color: #10b981;
  font-weight: 600;
}

.category-radio:has(input[type="radio"]:checked) {
  background: rgba(16, 185, 129, 0.1);
  border-color: #10b981;
}

/* Filter Buttons */
.filter-actions {
  display: flex;
  gap: 12px;
}

.btn-reset {
  flex: 1;
  padding: 11px 20px;
  background: transparent;
  border: 1px solid #374151;
  border-radius: 25px;
  color: #9ca3af;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-reset:hover:not(:disabled) {
  border-color: #6b7280;
  color: #d1d5db;
}

.btn-apply {
  flex: 1;
  padding: 11px 20px;
  background: #10b981;
  border: none;
  border-radius: 25px;
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-apply:hover:not(:disabled) {
  background: #0d9f6e;
  transform: translateY(-1px);
}

.btn-reset:disabled,
.btn-apply:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ============= SUMMARY STATS BAR ============= */
.summary-bar {
  display: flex;
  align-items: center;
  justify-content: space-around;
  background: linear-gradient(135deg, #0f1c17, #0a1510);
  border: 1px solid #1a3d2e;
  border-radius: 14px;
  padding: 18px 24px;
  margin-top: 20px;
}

.summary-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.summary-value {
  font-size: 22px;
  font-weight: 700;
  color: #10b981;
}

.summary-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-divider {
  width: 1px;
  height: 36px;
  background: #1a3d2e;
}

/* ============= HISTORY LIST ============= */
.history-list-section {
  margin-top: 20px;
}

/* State Boxes (Loading / Error / Empty) */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
  background: #0f1c17;
  border: 1px solid #1a3d2e;
  border-radius: 14px;
}

.state-box p {
  margin: 8px 0 0;
  color: #6b7280;
  font-size: 15px;
}

.state-box.error p {
  color: #ef4444;
}

.state-subtext {
  font-size: 13px !important;
  color: #4b5563 !important;
  margin-top: 4px !important;
}

.state-icon {
  font-size: 40px;
  margin-bottom: 8px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #1a3d2e;
  border-top-color: #10b981;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 12px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* History Cards */
.history-cards {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.history-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #0f1c17;
  border: 1px solid #1a3d2e;
  border-radius: 12px;
  padding: 16px 20px;
  transition: all 0.2s ease;
}

.history-card:hover {
  border-color: #2a5d3e;
  transform: translateX(4px);
}

/* Category Icon */
.history-card-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  font-size: 22px;
  flex-shrink: 0;
  background: #0a1510;
  border: 1px solid #1a3d2e;
}

.history-card-icon.cat-transport { background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3); }
.history-card-icon.cat-diet { background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3); }
.history-card-icon.cat-energy { background: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.3); }
.history-card-icon.cat-recycling { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); }

/* Card Body */
.history-card-body {
  flex: 1;
  min-width: 0;
}

.history-card-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 6px;
}

.history-card-top h4 {
  margin: 0;
  font-size: 15px;
  font-weight: 600;
  color: #e5e7eb;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.category-pill {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 12px;
  font-weight: 500;
  white-space: nowrap;
  background: #1a3d2e;
  color: #10b981;
}

.category-pill.cat-transport { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.category-pill.cat-diet { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.category-pill.cat-energy { background: rgba(234, 179, 8, 0.15); color: #facc15; }
.category-pill.cat-recycling { background: rgba(16, 185, 129, 0.15); color: #34d399; }

.history-card-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #6b7280;
}

.meta-dot {
  color: #374151;
}

/* Card Right Section */
.history-card-right {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-shrink: 0;
}

.carbon-badge {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  font-size: 18px;
  font-weight: 700;
  color: #ef4444;
  line-height: 1.1;
}

.carbon-badge small {
  font-size: 10px;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
}

.delete-btn-icon {
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 18px;
  padding: 6px;
  border-radius: 8px;
  transition: all 0.2s ease;
  opacity: 0.5;
}

.delete-btn-icon:hover:not(:disabled) {
  opacity: 1;
  background: rgba(239, 68, 68, 0.1);
}

.delete-btn-icon:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* ============= TAB CONTENT ============= */
.tab-content {
  animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ============= MOBILE RESPONSIVE ============= */
@media (max-width: 768px) {
  .tab-navigation {
    justify-content: center;
  }
  
  .tab-btn {
    padding: 8px 16px;
    font-size: 14px;
  }
  
  .date-range-row {
    flex-direction: column;
    gap: 8px;
  }
  
  .dash {
    display: none;
  }
  
  .category-options {
    gap: 6px;
  }
  
  .category-radio {
    padding: 6px 12px;
  }
  
  .category-radio span {
    font-size: 12px;
  }
  
  .summary-bar {
    padding: 14px 16px;
  }
  
  .summary-value {
    font-size: 18px;
  }
  
  .history-card {
    padding: 14px;
    gap: 12px;
  }
  
  .history-card-icon {
    width: 40px;
    height: 40px;
    font-size: 18px;
  }
  
  .history-card-right {
    gap: 10px;
  }
  
  .carbon-badge {
    font-size: 15px;
  }
}
</style>