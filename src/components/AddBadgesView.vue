<template>
  <div class="admin-badges-container">
    <div class="page-title">
      <h2>Badge Management</h2>
      <p>Create and distribute new achievement rewards for platform users</p>
    </div>

    

    <div class="split-layout">
      <section class="form-card">
        <h3>Add New Custom Badge</h3>
        <form @submit.prevent="submitBadge">
          
          <div class="form-group">
            <label>Badge Name</label>
            <input 
              type="text" 
              v-model="form.name" 
              placeholder="e.g., Eco Commuter, Green Chef" 
              required 
            />
          </div>

          <div class="form-group">
            <label>Description / Requirement Message</label>
            <textarea 
              v-model="form.description" 
              placeholder="e.g., Logged over 50km using public transport" 
              rows="3" 
              required
            ></textarea>
          </div>

          <div class="form-row">
            <div class="form-group flex-1">
              <label>Badge Image / Emoji</label>
              <select v-model="form.icon" required>
                <option value="" disabled>Select Icon</option>
                <option value="🥇">🥇 Gold Medal</option>
                <option value="🥈">🥈 Silver Medal</option>
                <option value="🥉">🥉 Bronze Medal</option>
                <option value="🏃">🏃 Runner</option>
                <option value="🚲">🚲 Bicycle</option>
                <option value="🥗">🥗 Salad</option>
                <option value="🔋">🔋 Battery</option>
                <option value="🌲">🌲 Tree</option>
                <option value="✨">✨ Sparkles</option>
                <option value="🔥">🔥 Fire</option>
                <option value="💡">💡 Idea</option>
              </select>
            </div>

            <div class="form-group flex-1">
              <label>Target Activity Category</label>
              <select v-model="form.categoryRule" required>
                <option value="" disabled>Select category rule</option>
                <option v-for="cat in categories" :key="cat" :value="cat">
                  {{ cat }} Logs Requirement
                </option>
              </select>
            </div>
          </div>

          <div class="form-group" v-if="form.categoryRule">
            <label>Target Activity Types <span class="label-hint">(optional — leave all unchecked to count entire category)</span></label>
            <div class="types-loading" v-if="loadingTypes">Loading activity types...</div>
            <div class="type-checkbox-list" v-else-if="activityTypes.length">
              <label
                v-for="type in activityTypes"
                :key="type.id"
                class="type-checkbox-item"
                :class="{ selected: form.activityTypeIds.includes(type.id) }"
              >
                <input
                  type="checkbox"
                  :value="type.id"
                  v-model="form.activityTypeIds"
                />
                {{ type.name }}
              </label>
            </div>
            <div class="types-empty" v-else>No specific activity types found for this category.</div>
          </div>

          <div class="form-group">
            <label>Minimum Threshold Value (e.g. Total km / counts / kWh)</label>
            <input 
              type="number" 
              v-model.number="form.thresholdValue" 
              placeholder="e.g., 50" 
              min="1" 
              required 
            />
          </div>

          <div v-if="error" class="error-message">
            ⚠️ {{ error }}
          </div>

          <button 
            type="submit" 
            class="submit-btn" 
            :disabled="loading"
          >
            {{ loading ? 'Creating Badge...' : 'Save & Publish Badge' }}
          </button>
        </form>
      </section>

      <section class="preview-card">
        <h3>Live Preview</h3>
        <div class="badge-preview-wrapper">
          <div class="preview-badge-icon">
            {{ form.icon || '🏆' }}
          </div>
          <h4 class="preview-badge-name">{{ form.name || 'Your New Badge Name' }}</h4>
          <span class="preview-category-tag" v-if="form.categoryRule">
            Rule: {{ form.categoryRule }}
          </span>
          <p class="preview-badge-desc">
            {{ form.description || 'Provide a brief description detailing how end-users unlock this achievement milestone.' }}
          </p>
          <div class="preview-threshold" v-if="form.thresholdValue">
            Requires at least <strong>{{ form.thresholdValue }} units</strong> accumulated.
          </div>
        </div>
      </section>
    </div>
  </div>

  <div class="badges-list-section">
      <div class="section-header">
        <h3 class="section-title">All Badges</h3>
        <span class="badge-count">{{ allBadges.length }} total</span>
      </div>
      <div class="badges-grid" v-if="allBadges.length">
        <div class="badge-item" v-for="badge in allBadges" :key="badge.id">
          <div class="badge-item-icon">{{ badge.icon }}</div>
          <div class="badge-item-info">
            <div class="badge-item-name">{{ badge.name }}</div>
            <div class="badge-item-meta" v-if="badge.category_rule">
              <span class="badge-tag">{{ badge.category_rule }}</span>
              <span class="badge-tag types" v-if="badge.activity_type_ids">{{ badge.activity_type_ids }}</span>
              <span class="badge-threshold" v-if="badge.threshold_value">≥ {{ badge.threshold_value }} units</span>
            </div>
            <div class="badge-item-desc">{{ badge.description }}</div>
          </div>
          <button class="delete-btn" @click="deleteBadge(badge)" title="Delete badge">✕</button>
        </div>
      </div>
      <div class="badges-empty" v-else>No badges found.</div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { activityAPI, badgeAPI } from '../services/api'

const router = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

const loading = ref(false)
const error = ref(null)
const categories = ref([])
const activityTypes = ref([])
const loadingTypes = ref(false)
const allBadges = ref([])

const form = reactive({
  name: '',
  description: '',
  icon: '',
  categoryRule: '',
  activityTypeIds: [],
  thresholdValue: null
})

async function loadCategories() {
  try {
    const response = await activityAPI.getCategories()
    if (response.data?.success) {
      categories.value = response.data.categories || []
    } else {
      categories.value = response.data || []
    }
  } catch (err) {
    console.error('Failed to load activity metrics reference:', err)
    toast.error('Failed to fetch activity configuration options')
  }
}

async function loadActivityTypes(category) {
  activityTypes.value = []
  form.activityTypeIds = []
  if (!category) return
  loadingTypes.value = true
  try {
    const response = await activityAPI.getTypesByCategory(category)
    activityTypes.value = response.data?.activity_types || []
  } catch (err) {
    console.error('Failed to load activity types:', err)
  } finally {
    loadingTypes.value = false
  }
}

watch(() => form.categoryRule, (newCat) => {
  loadActivityTypes(newCat)
})

async function loadAllBadges() {
  try {
    const response = await badgeAPI.getAllBadges()
    allBadges.value = response.data?.badges || []
  } catch (err) {
    console.error('Failed to load badges:', err)
  }
}

async function deleteBadge(badge) {
  if (!confirm(`Delete badge "${badge.name}"?`)) return
  try {
    await badgeAPI.deleteBadge(badge.id)
    toast.success(`Badge "${badge.name}" deleted`)
    await loadAllBadges()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Failed to delete badge')
  }
}

async function submitBadge() {
  if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
    toast.error('Unauthorized administration configuration update payload dropped')
    return
  }

  loading.value = true
  error.value = null

  try {
    // Cleaner approach invoking updated backend middleware mappings
    const response = await badgeAPI.createBadge({
      name: form.name,
      description: form.description,
      icon: form.icon,
      category_rule: form.categoryRule,
      activity_type_ids: form.activityTypeIds,
      threshold_value: form.thresholdValue
    })

    if (response.data?.success || response.status === 201 || response.status === 200) {
      toast.success('Custom Admin Badge compiled and deployed safely!')
      
      // Reset form options cleanly
      form.name = ''
      form.description = ''
      form.icon = ''
      form.categoryRule = ''
      form.activityTypeIds = []
      form.thresholdValue = null
      activityTypes.value = []
      await loadAllBadges()
    }
  } catch (err) {
    console.error('Failed to save badge schema configuration:', err)
    error.value = err.response?.data?.message || 'Server error occurred mapping custom admin badge object parameters'
    toast.error('Could not preserve badge configuration rules details')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  // Route check fallback optimization parameters
  if (!authStore.isAuthenticated || authStore.user?.role !== 'admin') {
    toast.error('Access Denied: Admin level account verification parameters failed.')
    router.push('/dashboard')
    return
  }
  await Promise.all([loadCategories(), loadAllBadges()])
})
</script>

<style scoped>
.admin-badges-container {
  padding: 20px;
  animation: fadeIn 0.3s ease;
}

.page-title {
  margin-bottom: 24px;
}
.page-title h2 {
  margin: 0 0 4px;
  color: #111B21;
}
.page-title p {
  margin: 0;
  color: #54656F;
  font-size: 14px;
}

.badges-list-section {
  background: #fff;
  border: 1px solid #E9EDEF;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
}

.section-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  color: #111B21;
  margin: 0;
}

.badge-count {
  font-size: 12px;
  background: #F0F2F5;
  color: #54656F;
  padding: 3px 10px;
  border-radius: 12px;
  font-weight: 600;
}

.badges-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.badge-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px 14px;
  background: #F0F2F5;
  border-radius: 10px;
  border: 1px solid #E9EDEF;
}

.badge-item-icon {
  font-size: 2rem;
  line-height: 1;
  flex-shrink: 0;
}

.badge-item-info {
  flex: 1;
  min-width: 0;
}

.badge-item-name {
  font-weight: 700;
  font-size: 14px;
  color: #111B21;
  margin-bottom: 4px;
}

.badge-item-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 4px;
}

.badge-tag {
  font-size: 11px;
  background: rgba(0, 168, 132, 0.12);
  color: #00A884;
  padding: 2px 8px;
  border-radius: 10px;
  font-weight: 600;
}

.badge-tag.types {
  background: rgba(99, 102, 241, 0.1);
  color: #6366f1;
}

.badge-threshold {
  font-size: 11px;
  color: #54656F;
  background: #fff;
  border: 1px solid #E9EDEF;
  padding: 2px 8px;
  border-radius: 10px;
}

.badge-item-desc {
  font-size: 12.5px;
  color: #54656F;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.delete-btn {
  flex-shrink: 0;
  background: none;
  border: 1px solid #E9EDEF;
  color: #ef4444;
  width: 30px;
  height: 30px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}

.delete-btn:hover {
  background: rgba(239, 68, 68, 0.08);
  border-color: #ef4444;
}

.badges-empty {
  font-size: 13px;
  color: #8696A0;
  text-align: center;
  padding: 20px;
}

.split-layout {
  display: flex;
  gap: 24px;
  align-items: flex-start;
}

.form-card {
  flex: 1.5;
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 12px;
  padding: 24px;
}

.form-card h3 {
  margin: 0 0 20px;
  color: #111B21;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 18px;
}

.form-row {
  display: flex;
  gap: 16px;
}

.flex-1 {
  flex: 1;
}

label {
  font-size: 13px;
  font-weight: 600;
  color: #54656F;
}

input, select, textarea {
  padding: 11px 14px;
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 8px;
  color: #111B21;
  font-size: 14px;
}

input:focus, select:focus, textarea:focus {
  outline: none;
  border-color: #00A884;
}

.types-loading, .types-empty {
  font-size: 13px;
  color: #8696A0;
  padding: 10px 12px;
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 8px;
}

.label-hint {
  font-size: 11px;
  font-weight: 400;
  color: #8696A0;
}

.type-checkbox-list {
  display: flex;
  flex-direction: column;
  gap: 6px;
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid #E9EDEF;
  border-radius: 8px;
  padding: 8px;
  background: #F0F2F5;
}

.type-checkbox-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 13.5px;
  color: #111B21;
  cursor: pointer;
  transition: background 0.15s;
  border: 1px solid transparent;
}

.type-checkbox-item:hover {
  background: #E9EDEF;
}

.type-checkbox-item.selected {
  background: rgba(0, 168, 132, 0.1);
  border-color: #00A884;
  color: #00A884;
  font-weight: 600;
}

.type-checkbox-item input[type="checkbox"] {
  width: 15px;
  height: 15px;
  accent-color: #00A884;
  padding: 0;
  margin: 0;
  border: none;
  background: transparent;
  flex-shrink: 0;
}

.error-message {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  padding: 10px;
  border-radius: 6px;
  font-size: 13px;
  margin-bottom: 16px;
}

.submit-btn {
  width: 100%;
  padding: 12px;
  background: #00A884;
  border: none;
  color: #FFFFFF;
  border-radius: 25px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.submit-btn:hover:not(:disabled) {
  background: #0d9f6e;
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Preview Card Styles */
.preview-card {
  flex: 1;
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 12px;
  padding: 24px;
  position: sticky;
  top: 20px;
}

.preview-card h3 {
  margin: 0 0 20px;
  color: #111B21;
}

.badge-preview-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: #F0F2F5;
  border: 2px dashed #D1D7DB;
  border-radius: 12px;
  padding: 30px 20px;
}

.preview-badge-icon {
  font-size: 4rem;
  margin-bottom: 12px;
  line-height: 1;
}

.preview-badge-name {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 700;
  color: #111B21;
}

.preview-category-tag {
  font-size: 11px;
  background: rgba(0, 168, 132, 0.15);
  color: #00A884;
  padding: 4px 12px;
  border-radius: 12px;
  font-weight: 600;
  margin-bottom: 12px;
}

.preview-badge-desc {
  font-size: 13.5px;
  color: #54656F;
  margin: 0 0 14px;
  line-height: 1.4;
}

.preview-threshold {
  font-size: 12px;
  color: #8696A0;
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  padding: 6px 12px;
  border-radius: 6px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
  .split-layout {
    flex-direction: column;
  }
  .form-row {
    flex-direction: column;
    gap: 0;
  }
  .preview-card {
    position: static;
    width: 100%;
  }
}
</style>