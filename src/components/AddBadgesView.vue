<template>
  <div class="admin-badges-container">
    <div class="page-title">
      <h2>🏆 Badge Management</h2>
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
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
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

const form = reactive({
  name: '',
  description: '',
  icon: '',
  categoryRule: '',
  thresholdValue: null
})

// Fetch dynamic valid categories from your endpoint config rules
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
      threshold_value: form.thresholdValue
    })

    if (response.data?.success || response.status === 201 || response.status === 200) {
      toast.success('Custom Admin Badge compiled and deployed safely!')
      
      // Reset form options cleanly
      form.name = ''
      form.description = ''
      form.icon = ''
      form.categoryRule = ''
      form.thresholdValue = null
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
  await loadCategories()
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