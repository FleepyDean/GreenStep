<template>
  <div class="tips-container">
    <div class="page-title">
      <h2>💡 Eco Tips</h2>
      <p>Discover actionable habits to systematically shrink your carbon footprints</p>
    </div>

    <div class="featured-tip-banner">
      <div class="featured-badge">✨ Today's Featured Tip</div>
      <h3>{{ dailyFeaturedTip.title }}</h3>
      <p>{{ dailyFeaturedTip.body }}</p>
    </div>

    <!-- Premium Admin Panel Toggle Area -->
    <div v-if="user?.role === 'admin'" class="admin-panel">
      <button 
        class="toggle-panel-btn" 
        @click="showAdminForm = !showAdminForm"
      >
        {{ showAdminForm ? '− Close Panel' : '＋ Add a Tip' }}
      </button>

      <Transition name="fade-slide">
        <div v-if="showAdminForm" class="form-card">
          <h3>New Eco Tip</h3>

          <div class="form-row">
            <input
              v-model="newTip.title"
              placeholder="Tip Title"
              class="tip-input"
            >
            <select
              v-model="newTip.category"
              class="tip-select"
            >
              <option disabled value="">Category</option>
              <option>Transport</option>
              <option>Diet</option>
              <option>Energy</option>
              <option>Recycling</option>
              <option>General</option>
            </select>
          </div>

          <textarea
            v-model="newTip.body"
            placeholder="Tip Description"
            class="tip-textarea"
          ></textarea>

          <input
            v-model="newTip.source_url"
            placeholder="Source URL (optional)"
            class="tip-input"
          >

          <div class="action-row">
            <button class="minimal-btn" @click="addTip">
              Publish Tip
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <div class="page-title" style="margin-top: 2rem;">
      <h3>Browse by Category</h3>
    </div>

    <div class="category-filter-chips">
      <button 
        v-for="cat in categories" 
        :key="cat"
        :class="['chip-btn', selectedCategory === cat ? 'chip-active' : '']"
        @click="selectedCategory = cat"
      >
        {{ cat }}
      </button>
    </div>

    <div class="tips-gallery-grid">
      <div class="tip-library-card" v-for="tip in filteredTips" :key="tip.id">
        <div class="tip-card-meta">
          <span :class="['pill-tag', getCategoryClass(tip.category)]">{{ tip.category }}</span>
        </div>
        <h4>{{ tip.title }}</h4>
        <p>{{ tip.body }}</p>
        <button
          v-if="isAdmin"
          class="delete-btn"
          @click="deleteTip(tip.id)"
        >
          🗑 Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { tipAPI } from '../services/api'

const selectedCategory = ref('All')
const showAdminForm = ref(false) 
const user = JSON.parse(localStorage.getItem('user'))

const isAdmin = computed(() => {
    return user?.role === 'admin'
})

const categories = ['All', 'Transport', 'Diet', 'Energy', 'Recycling']
const dailyFeaturedTip = ref({})
const tipsLibrary = ref([])

const loadTips = async () => {
  try {
    const featured = await tipAPI.getRandom()
    dailyFeaturedTip.value = featured.data

    const all = await tipAPI.getAll()
    tipsLibrary.value = all.data
  } catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  loadTips()
})

const filteredTips = computed(() => {
  if (selectedCategory.value === 'All') return tipsLibrary.value
  return tipsLibrary.value.filter(tip => tip.category === selectedCategory.value)
})

const newTip = ref({
    title: '',
    body: '',
    category: 'General',
    source_url: ''
})

const addTip = async () => {
    try {
        await tipAPI.create(newTip.value)
        newTip.value = {
            title: '',
            body: '',
            category: 'General',
            source_url: ''
        }
        showAdminForm.value = false 
        loadTips()
    } catch (err) {
        console.error(err)
    }
}

const deleteTip = async (id) => {
    if (!confirm("Delete this tip?")) return
    await tipAPI.delete(id)
    loadTips()
}

const getCategoryClass = (cat) => {
  return `pill-${cat.toLowerCase()}`
}
</script>

<style scoped>
/* Admin Architecture Framework */
.admin-panel {
  margin: 1.5rem 0;
}

.toggle-panel-btn {
  background: transparent;
  border: none;
  color: #00A884;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  padding: 0.5rem 0;
  letter-spacing: 0.02em;
  transition: color 0.2s ease;
}

.toggle-panel-btn:hover {
  color: #008069;
}

/* Premium Green Card Styling */
.form-card {
  background: #F0F2F5; 
  border: 1px solid rgba(0, 168, 132, 0.15);
  border-radius: 6px;
  padding: 2rem;
  margin-top: 1rem;
  box-shadow: 0 20px 40px rgba(4, 13, 10, 0.5);
}

.form-card h3 {
  font-size: 1.15rem;
  font-weight: 600;
  color: #f8fafc; 
  margin-bottom: 1.5rem;
  letter-spacing: -0.01em;
}

.form-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1rem;
}

/* Semi-transparent Glass Inputs */
.tip-input,
.tip-select,
.tip-textarea {
  width: 100%;
  padding: 0.8rem 1.1rem;
  margin-bottom: 1.2rem;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  background: #F0F2F5;
  font-size: 0.9rem;
  color: #111B21;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  box-sizing: border-box;
}

.tip-select option {
  background: #F0F2F5;
  color: #111B21;
}

.tip-input:focus,
.tip-select:focus,
.tip-textarea:focus {
  outline: none;
  border-color: rgba(0, 168, 132, 0.4); 
  background: #FFFFFF;
  box-shadow: 0 0 0 4px rgba(0, 168, 132, 0.08); 
}

.tip-input::placeholder,
.tip-textarea::placeholder {
  color: #4b5f58;
}

.tip-textarea {
  min-height: 110px;
  resize: vertical;
}

/* Premium Contrast Action Button */
.action-row {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.4rem;
}

.minimal-btn {
  padding: 0.7rem 1.5rem;
  border: none;
  border-radius: 6px;
  background: #00A884; 
  color: #040d0a;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.minimal-btn:hover {
  background: #25D366;
  transform: translateY(-1px);
  box-shadow: 0 8px 20px rgba(0, 168, 132, 0.15);
}

/* Animation Micro-interactions */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* Standalone Delete Button Overhaul */
.delete-btn {
  background: transparent;
  color: #54656F;
  border: 1px solid #E9EDEF;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  margin-top: 15px;
  transition: all 0.2s ease;
}

.delete-btn:hover {
  background: #fef2f2;
  color: #ef4444;
  border-color: #fee2e2;
}
</style>