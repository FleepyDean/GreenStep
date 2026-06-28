<template>
  <div class="activity-container">

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
                  <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
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
                <input type="date" v-model="form.date" :max="todayStr" required />
              </div>

              <div class="form-group">
                <label>Photo <span class="optional-tag">Optional</span></label>
                <div
                  class="photo-upload-area"
                  :class="{ 'has-preview': photoPreview }"
                  @click="triggerFileInput"
                  @dragover.prevent
                  @drop.prevent="onPhotoDrop"
                >
                  <img v-if="photoPreview" :src="photoPreview" class="photo-preview" alt="Activity photo preview" />
                  <div v-else class="photo-placeholder">
                    <span class="photo-icon">📷</span>
                    <span class="photo-hint">Tap to upload a photo</span>
                  </div>
                  <button v-if="photoPreview" type="button" class="photo-remove-btn" @click.stop="removePhoto">✕</button>
                </div>
                <button type="button" class="webcam-btn" @click.stop="openWebcam">
                  📷 Use Camera
                </button>
                <input
                  ref="fileInputRef"
                  type="file"
                  accept="image/*"
                  class="hidden-file-input"
                  @change="onPhotoSelected"
                />
              </div>

              <button type="submit" class="submit-btn" :disabled="loading">{{ loading ? 'Logging...' : 'Log Activity' }}</button>
            </form>
          </section>
        </slot>

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
    </div>

    <div v-else class="tab-content">
      <section class="filter-card">
        <div class="filter-inner">
          <div class="filter-group">
            <label class="filter-label">📅 Date Range</label>
            <div class="date-range-row">
              <input type="date" v-model="filters.startDate" />
              <span class="dash">→</span>
              <input type="date" v-model="filters.endDate" />
            </div>
          </div>

          <div class="filter-divider"></div>

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

          <div class="filter-actions">
            <button class="btn-reset" @click="clearFilters" :disabled="historyLoading">Reset</button>
            <button class="btn-apply" @click="handleApplyFilters" :disabled="historyLoading">Apply</button>
          </div>
        </div>
      </section>

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

      <section class="history-list-section">
        <div v-if="historyLoading" class="state-box">
          <div class="spinner"></div>
          <p>Loading history...</p>
        </div>

        <div v-else-if="historyError" class="state-box error">
          <span class="state-icon">⚠️</span>
          <p>{{ historyError }}</p>
        </div>

        <div v-else-if="historyActivities.length === 0" class="state-box">
          <span class="state-icon">📭</span>
          <p>No activities found</p>
          <p class="state-subtext">Try adjusting your filters</p>
        </div>

        <div v-else class="history-cards">
          <div class="history-card" v-for="log in historyActivities" :key="log.id" @click="openDetail(log)">
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
                <span v-if="log.photo_url" class="meta-dot">·</span>
                <span v-if="log.photo_url" class="photo-indicator">📷</span>
              </div>
            </div>
            <div class="history-card-right">
              <div class="carbon-badge">{{ Number(log.carbon_footprint).toFixed(2) }}<small>kg CO₂e</small></div>
              <button class="delete-btn-icon" @click.stop="removeHistoryLog(log.id)" :disabled="historyLoading" title="Delete">🗑️</button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="detailActivity" class="detail-overlay" @click.self="closeDetail">
        <div class="detail-sheet">
          <div class="detail-header">
            <div class="detail-title-row">
              <span class="detail-cat-icon" :class="getCategoryClass(detailActivity.category)">{{ getCategoryIcon(detailActivity.category) }}</span>
              <div>
                <h3 class="detail-name">{{ detailActivity.activity_name }}</h3>
                <span class="category-pill" :class="getCategoryClass(detailActivity.category)">{{ detailActivity.category }}</span>
              </div>
            </div>
            <button class="detail-close" @click="closeDetail">✕</button>
          </div>

          <img v-if="detailActivity.photo_url" :src="resolvePhotoUrl(detailActivity.photo_url)" class="detail-photo" alt="Activity photo" />
          <div v-else class="detail-no-photo">
            <span>📷</span>
            <p>No photo attached</p>
          </div>

          <div class="detail-stats">
            <div class="detail-stat">
              <span class="detail-stat-label">Amount</span>
              <span class="detail-stat-value">{{ detailActivity.amount }} {{ detailActivity.unit }}</span>
            </div>
            <div class="detail-stat">
              <span class="detail-stat-label">CO₂e</span>
              <span class="detail-stat-value co2-value">{{ Number(detailActivity.carbon_footprint).toFixed(2) }} kg</span>
            </div>
            <div class="detail-stat">
              <span class="detail-stat-label">Date</span>
              <span class="detail-stat-value">{{ formatDate(detailActivity.logged_on) }}</span>
            </div>
          </div>

          <button
            class="detail-delete-btn"
            @click="deleteFromDetail"
            :disabled="historyLoading"
          >
            🗑️ Delete Activity
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>

  <Teleport to="body">
    <Transition name="modal-fade">
      <div v-if="showWebcam" class="detail-overlay" @click.self="closeWebcam">
        <div class="webcam-sheet">
          <div class="webcam-header">
            <h3>Take a Photo</h3>
            <button class="detail-close" @click="closeWebcam">✕</button>
          </div>
          <div class="webcam-preview-wrap">
            <video ref="videoRef" class="webcam-video" autoplay playsinline muted></video>
          </div>
          <div class="webcam-actions">
            <button type="button" class="webcam-capture-btn" @click="captureFromWebcam">
              <span class="capture-ring"></span>
            </button>
          </div>
          <canvas ref="canvasRef" class="hidden-canvas"></canvas>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch, nextTick } from 'vue'
import { activityAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { useToast } from '@/composables/useToast'
import { eventBus } from '@/utils/eventBus'

const router = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

// Helper function to safely get local today string formatted as YYYY-MM-DD
function getLocalDateString() {
  const today = new Date()
  const year = today.getFullYear()
  const month = String(today.getMonth() + 1).padStart(2, '0')
  const day = String(today.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const todayStr = getLocalDateString()

// Tab state
const activeTab = ref('log')

// Activity Log form
const form = reactive({
  category: '',
  type: '',
  activityTypeId: null,
  amount: null,
  date: todayStr
})

const photoFile = ref(null)
const photoPreview = ref(null)
const fileInputRef = ref(null)

const showWebcam = ref(false)
const videoRef = ref(null)
const canvasRef = ref(null)
let webcamStream = null

function triggerFileInput() {
  fileInputRef.value?.click()
}

function onPhotoSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  setPhoto(file)
}

function onPhotoDrop(e) {
  const file = e.dataTransfer.files?.[0]
  if (!file || !file.type.startsWith('image/')) return
  setPhoto(file)
}

function setPhoto(file) {
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

function removePhoto() {
  photoFile.value = null
  photoPreview.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

async function openWebcam() {
  showWebcam.value = true
  await nextTick()
  try {
    webcamStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
    if (videoRef.value) {
      videoRef.value.srcObject = webcamStream
      videoRef.value.play()
    }
  } catch (err) {
    toast.error('Camera access denied or not available')
    showWebcam.value = false
  }
}

function closeWebcam() {
  if (webcamStream) {
    webcamStream.getTracks().forEach(t => t.stop())
    webcamStream = null
  }
  showWebcam.value = false
}

function captureFromWebcam() {
  if (!videoRef.value || !canvasRef.value) return
  const video = videoRef.value
  const canvas = canvasRef.value
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  canvas.getContext('2d').drawImage(video, 0, 0)
  canvas.toBlob((blob) => {
    if (!blob) return
    const file = new File([blob], 'webcam_capture.jpg', { type: 'image/jpeg' })
    setPhoto(file)
    closeWebcam()
  }, 'image/jpeg', 0.92)
}

// Activity Track filters
const filters = reactive({
  startDate: '',
  endDate: '',
  category: ''
})

// Activity Log data
const categories = ref([])
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

async function loadCategories() {
  try {
    const response = await activityAPI.getCategories()
    if (response.data.success) {
      categories.value = response.data.categories || []
    }
  } catch (err) {
    console.error('Failed to load categories:', err)
  }
}

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

    let response
    if (photoFile.value) {
      const fd = new FormData()
      fd.append('activity_type_id', form.activityTypeId)
      fd.append('amount', form.amount)
      fd.append('date', form.date)
      fd.append('photo', photoFile.value)
      response = await activityAPI.logActivityWithPhoto(fd)
    } else {
      response = await activityAPI.logActivity({
        activity_type_id: form.activityTypeId,
        amount: form.amount,
        date: form.date
      })
    }

    if (response.data.success) {
      form.category = ''
      form.type = ''
      form.activityTypeId = null
      form.amount = null
      form.date = getLocalDateString()
      removePhoto()

      await loadTodayActivities()
      await loadHistory()
      eventBus.emit('activity-logged')
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
    eventBus.emit('activity-logged')
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

const detailActivity = ref(null)

function openDetail(log) {
  detailActivity.value = log
}

function closeDetail() {
  detailActivity.value = null
}

function resolvePhotoUrl(url) {
  if (!url) return ''
  if (url.startsWith('http')) return url
  return 'http://localhost:8080' + url
}

async function deleteFromDetail() {
  if (!detailActivity.value) return
  const id = detailActivity.value.id
  closeDetail()
  await removeHistoryLog(id)
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
  
  await loadCategories()
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
  border-bottom: 2px solid #E9EDEF;
  padding-bottom: 10px;
}

.tab-btn {
  padding: 10px 24px;
  background: transparent;
  border: 2px solid #00A884;
  color: #00A884;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
}

.tab-btn:hover {
  background: rgba(0, 168, 132, 0.1);
}

.tab-btn.active {
  background: #00A884;
  color: #FFFFFF;
}

/* ============= FILTER CARD ============= */
.filter-card {
  background: #FFFFFF;
  border-radius: 6px;
  padding: 24px;
  border: 1px solid #E9EDEF;
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
  color: #54656F;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-divider {
  height: 1px;
  background: #E9EDEF;
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
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 8px;
  color: #111B21;
  font-size: 14px;
  color-scheme: light;
}

.date-range-row input:focus {
  outline: none;
  border-color: #00A884;
}

.dash {
  color: #8696A0;
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
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.category-radio:hover {
  border-color: #00A884;
}

.category-radio input[type="radio"] {
  display: none;
}

.category-radio span {
  font-size: 13px;
  color: #54656F;
  font-weight: 500;
}

.category-radio input[type="radio"]:checked + span {
  color: #00A884;
  font-weight: 600;
}

.category-radio:has(input[type="radio"]:checked) {
  background: rgba(0, 168, 132, 0.1);
  border-color: #00A884;
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
  border: 1px solid #E9EDEF;
  border-radius: 25px;
  color: #54656F;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-reset:hover:not(:disabled) {
  border-color: #8696A0;
  color: #d1d5db;
}

.btn-apply {
  flex: 1;
  padding: 11px 20px;
  background: #00A884;
  border: none;
  border-radius: 25px;
  color: #FFFFFF;
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
  background: linear-gradient(135deg, #FFFFFF, #F0F2F5);
  border: 1px solid #E9EDEF;
  border-radius: 6px;
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
  color: #00A884;
}

.summary-label {
  font-size: 12px;
  color: #8696A0;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-divider {
  width: 1px;
  height: 36px;
  background: #E9EDEF;
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
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
}

.state-box p {
  margin: 8px 0 0;
  color: #8696A0;
  font-size: 15px;
}

.state-box.error p {
  color: #ef4444;
}

.state-subtext {
  font-size: 13px !important;
  color: #8696A0 !important;
  margin-top: 4px !important;
}

.state-icon {
  font-size: 40px;
  margin-bottom: 8px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #E9EDEF;
  border-top-color: #00A884;
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
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  padding: 16px 20px;
  transition: all 0.2s ease;
}

.history-card:hover {
  border-color: #D1D7DB;
  transform: translateX(4px);
}

/* Category Icon */
.history-card-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  font-size: 22px;
  flex-shrink: 0;
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
}

.history-card-icon.cat-transport { background: rgba(59, 130, 246, 0.1); border-color: rgba(59, 130, 246, 0.3); }
.history-card-icon.cat-diet { background: rgba(245, 158, 11, 0.1); border-color: rgba(245, 158, 11, 0.3); }
.history-card-icon.cat-energy { background: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.3); }
.history-card-icon.cat-recycling { background: rgba(0, 168, 132, 0.1); border-color: rgba(0, 168, 132, 0.2); }

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
  color: #111B21;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.category-pill {
  font-size: 11px;
  padding: 3px 10px;
  border-radius: 6px;
  font-weight: 500;
  white-space: nowrap;
  background: #E9EDEF;
  color: #00A884;
}

.category-pill.cat-transport { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.category-pill.cat-diet { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.category-pill.cat-energy { background: rgba(234, 179, 8, 0.15); color: #facc15; }
.category-pill.cat-recycling { background: rgba(0, 168, 132, 0.15); color: #25D366; }

.history-card-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #8696A0;
}

.meta-dot {
  color: #E9EDEF;
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
  color: #8696A0;
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

/* ============= ACTIVITY DETAIL MODAL ============= */
.detail-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1300;
  display: flex;
  align-items: flex-end;
  justify-content: center;
}

.detail-sheet {
  background: #FFFFFF;
  width: 100%;
  max-width: 520px;
  border-radius: 20px 20px 0 0;
  padding: 1.25rem 1.25rem calc(1.5rem + env(safe-area-inset-bottom, 0px));
  max-height: 90vh;
  overflow-y: auto;
}

.detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.detail-title-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.detail-cat-icon {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  flex-shrink: 0;
}

.detail-cat-icon.cat-transport { background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.3); }
.detail-cat-icon.cat-diet      { background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); }
.detail-cat-icon.cat-energy    { background: rgba(234,179,8,0.1);  border-color: rgba(234,179,8,0.3); }
.detail-cat-icon.cat-recycling { background: rgba(0,168,132,0.1);  border-color: rgba(0,168,132,0.2); }

.detail-name {
  font-size: 1.05rem;
  font-weight: 700;
  color: #111B21;
  margin: 0 0 0.3rem;
}

.detail-close {
  background: #F0F2F5;
  border: none;
  width: 30px;
  height: 30px;
  border-radius: 50%;
  font-size: 0.9rem;
  color: #54656F;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s;
}

.detail-close:hover {
  background: #E9EDEF;
}

.detail-photo {
  width: 100%;
  border-radius: 12px;
  max-height: 280px;
  object-fit: cover;
  margin-bottom: 1rem;
  border: 1px solid #E9EDEF;
}

.detail-no-photo {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  height: 100px;
  background: #F0F2F5;
  border: 1px dashed #D1D7DB;
  border-radius: 12px;
  margin-bottom: 1rem;
  color: #8696A0;
  font-size: 0.82rem;
}

.detail-no-photo span {
  font-size: 1.75rem;
  opacity: 0.4;
}

.detail-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.detail-stat {
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 10px;
  padding: 0.75rem 0.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  text-align: center;
}

.detail-stat-label {
  font-size: 0.7rem;
  color: #8696A0;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  font-weight: 600;
}

.detail-stat-value {
  font-size: 0.95rem;
  color: #111B21;
  font-weight: 700;
}

.detail-stat-value.co2-value {
  color: #ef4444;
}

.detail-delete-btn {
  width: 100%;
  padding: 0.85rem;
  border: 1px solid rgba(239, 68, 68, 0.3);
  background: rgba(239, 68, 68, 0.07);
  color: #ef4444;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.detail-delete-btn:hover:not(:disabled) {
  background: #ef4444;
  color: #FFFFFF;
}

.detail-delete-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Photo indicator on card */
.photo-indicator {
  font-size: 0.75rem;
}

/* Modal transition */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.22s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-fade-enter-active .detail-sheet,
.modal-fade-leave-active .detail-sheet {
  transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1);
}
.modal-fade-enter-from .detail-sheet,
.modal-fade-leave-to .detail-sheet {
  transform: translateY(100%);
}

/* Make history cards feel clickable */
.history-card {
  cursor: pointer;
}

/* ============= WEBCAM ============= */
.webcam-btn {
  width: 100%;
  margin-top: 0.5rem;
  padding: 0.6rem;
  border: 1px solid #D1D7DB;
  border-radius: 8px;
  background: #F0F2F5;
  color: #54656F;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}

.webcam-btn:hover {
  background: #E9EDEF;
  border-color: #00A884;
  color: #00A884;
}

.webcam-sheet {
  background: #111B21;
  width: 100%;
  max-width: 520px;
  border-radius: 20px 20px 0 0;
  padding: 1.25rem 1.25rem calc(2rem + env(safe-area-inset-bottom, 0px));
  max-height: 92vh;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.webcam-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.webcam-header h3 {
  color: #FFFFFF;
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.webcam-preview-wrap {
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  background: #000;
  aspect-ratio: 4/3;
  display: flex;
  align-items: center;
  justify-content: center;
}

.webcam-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.webcam-actions {
  display: flex;
  justify-content: center;
  padding: 0.5rem 0;
}

.webcam-capture-btn {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: #FFFFFF;
  border: 4px solid rgba(255,255,255,0.4);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.1s ease, background 0.15s;
  position: relative;
}

.webcam-capture-btn:active {
  transform: scale(0.92);
  background: #E9EDEF;
}

.capture-ring {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #FFFFFF;
  border: 2px solid #D1D7DB;
  display: block;
}

.hidden-canvas {
  display: none;
}

/* ============= PHOTO UPLOAD ============= */
.optional-tag {
  font-size: 0.72rem;
  color: #8696A0;
  font-weight: 400;
  margin-left: 4px;
  text-transform: lowercase;
}

.hidden-file-input {
  display: none;
}

.photo-upload-area {
  position: relative;
  width: 100%;
  min-height: 120px;
  border: 2px dashed #D1D7DB;
  border-radius: 10px;
  background: #F0F2F5;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  transition: border-color 0.2s ease, background 0.2s ease;
  -webkit-tap-highlight-color: transparent;
}

.photo-upload-area:hover,
.photo-upload-area:active {
  border-color: #00A884;
  background: rgba(0, 168, 132, 0.04);
}

.photo-upload-area.has-preview {
  border-style: solid;
  border-color: #E9EDEF;
  min-height: 180px;
}

.photo-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  pointer-events: none;
}

.photo-icon {
  font-size: 2rem;
}

.photo-hint {
  font-size: 0.82rem;
  color: #8696A0;
  text-align: center;
}

.photo-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  min-height: 180px;
  max-height: 260px;
}

.photo-remove-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.55);
  color: #FFFFFF;
  border: none;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  transition: background 0.15s;
}

.photo-remove-btn:hover {
  background: rgba(239, 68, 68, 0.85);
}

/* ============= SPLIT LAYOUT ============= */
.split-layout {
  width: 100%;
  max-width: 100%;
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
    gap: 6px;
    flex-wrap: nowrap;
  }

  .date-range-row input {
    min-width: 0;
    padding: 8px 10px;
    font-size: 13px;
  }

  .date-range-row input::-webkit-calendar-picker-indicator {
    transform: scale(0.8);
  }
  
  .dash {
    font-size: 14px;
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