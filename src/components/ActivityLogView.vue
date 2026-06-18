<template>
  <div class="activity-container">
    <div class="page-title">
      <h2>📝 Log Your Activity</h2>
      <p>Track your daily carbon footprint actions</p>
    </div>

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

      <section class="logs-card">
        <div class="logs-header">
          <h3>Today's Activities</h3>
          <span class="refresh-badge" @click="loadTodayActivities" style="cursor: pointer">Refresh</span>
        </div>

        <div v-if="loading && activities.length === 0" style="padding: 20px; text-align: center;">
          Loading...
        </div>

        <div v-else-if="error" style="padding: 20px; color: #ff6b6b;">
          {{ error }}
        </div>

        <div v-else-if="activities.length === 0" style="padding: 20px; text-align: center; color: #888;">
          No activities logged today. Start tracking your carbon footprint!
        </div>

        <div v-else class="logs-list">
          <div class="log-item" v-for="log in activities" :key="log.id">
            <div class="log-details">
              <h4>{{ log.activity_name }} <span class="category-pill">{{ log.category }}</span></h4>
              <p class="unit-breakdown">{{ log.amount }} {{ log.unit }} - {{ Number(log.carbon_footprint).toFixed(2) }} kg CO₂e</p>
            </div>
            <button class="delete-btn" @click="removeLog(log.id)" :disabled="loading">Delete</button>
          </div>
        </div>

        <div class="logs-footer-total">
          <span>Today's Total Footprint:</span>
          <strong>{{ Number(totalFootprint).toFixed(2) }} kg CO₂e</strong>
        </div>
      </section>
    </div>

    <footer class="guidelines-section">
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
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { activityAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  category: '',
  type: '',
  activityTypeId: null,
  amount: null,
  date: new Date().toISOString().substr(0, 10)
})

const activityTypes = ref([])
const activities = ref([])
const totalFootprint = ref(0)
const loading = ref(false)
const error = ref(null)

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
    }
  } catch (err) {
    console.error('Failed to log activity:', err)
    error.value = err.response?.data?.message || 'Failed to log activity'
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
  } catch (err) {
    console.error('Failed to delete activity:', err)
    error.value = 'Failed to delete activity'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (!authStore.isAuthenticated) {
    router.push('/profile')
    return
  }
  
  await loadActivityTypes()
  await loadTodayActivities()
})
</script>