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

    <div class="page-title" style="margin-top: 1rem;">
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const selectedCategory = ref('All')
const categories = ['All', 'Transport', 'Diet', 'Energy', 'Recycling']

// Randomize or select top-tier row as daily feature highlight choice
const dailyFeaturedTip = ref({
  title: 'Use a reusable water bottle',
  body: 'Save approximately 156 plastic bottles per year by using a reusable water bottle instead of single-use disposable containers.',
  category: 'Recycling'
})

const tipsLibrary = ref([
  {
    id: 1,
    title: 'Use a reusable water bottle',
    body: 'Save approximately 156 plastic bottles per year by using a reusable water bottle instead of disposable ones.',
    category: 'Recycling'
  },
  {
    id: 2,
    title: 'Take shorter showers',
    body: 'Reducing shower duration by just 2 minutes saves up to 10 gallons of domestic water and reduces water-heating energy demands.',
    category: 'Energy'
  },
  {
    id: 3,
    title: 'Meatless Mondays',
    body: 'Going plant-based just one day per week saves over 1,200 lbs of carbon dioxide equivalent emissions annually.',
    category: 'Diet'
  },
  {
    id: 4,
    title: 'Buy local produce',
    body: 'Support local agricultural supplies to heavily drop freight shipping engine fuel emissions.',
    category: 'Diet'
  },
  {
    id: 5,
    title: 'Use public transport',
    body: 'Taking a bus or passenger train reduces individual transit carbon footprints by up to 75% compared to private car commuting.',
    category: 'Transport'
  },
  {
    id: 6,
    title: 'Recycle properly',
    body: 'Rinse out contaminated food containers thoroughly before submitting them to sorting bins to protect waste streams.',
    category: 'Recycling'
  }
])

const filteredTips = computed(() => {
  if (selectedCategory.value === 'All') return tipsLibrary.value
  return tipsLibrary.value.filter(tip => tip.category === selectedCategory.value)
})

const getCategoryClass = (cat) => {
  return `pill-${cat.toLowerCase()}`
}
</script>