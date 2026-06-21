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
    <div v-if="user?.role === 'admin'" class="admin-panel">

  <div class="form-card">

    <h3>➕ Add New Eco Tip</h3>

    <input
      v-model="newTip.title"
      placeholder="Tip Title"
      class="tip-input"
    >

    <textarea
      v-model="newTip.body"
      placeholder="Tip Description"
      class="tip-textarea"
    ></textarea>

    <select
      v-model="newTip.category"
      class="tip-input"
    >
      <option>Transport</option>
      <option>Diet</option>
      <option>Energy</option>
      <option>Recycling</option>
      <option>General</option>
    </select>

    <input
      v-model="newTip.source_url"
      placeholder="Source URL (optional)"
      class="tip-input"
    >

    <button
      class="green-btn"
      @click="addTip"
    >
      + Add Tip
    </button>

  </div>

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
const user = JSON.parse(localStorage.getItem('user'))

const isAdmin = computed(() => {
    return user?.role === 'admin'
})
const categories = ['All', 'Transport', 'Diet', 'Energy', 'Recycling']
const loadTips = async () => {
  try {

    // Today's random tip
    const featured = await tipAPI.getRandom()
    dailyFeaturedTip.value = featured.data

    // All tips
    const all = await tipAPI.getAll()
    tipsLibrary.value = all.data

  } catch (err) {
    console.error(err)
  }
}

onMounted(() => {
  loadTips()
})

// Randomize or select top-tier row as daily feature highlight choice
const dailyFeaturedTip = ref({})

const tipsLibrary = ref([])

const filteredTips = computed(() => {
  if (selectedCategory.value === 'All') return tipsLibrary.value
  return tipsLibrary.value.filter(tip => tip.category === selectedCategory.value)
})

const newTip = ref({
    title:'',
    body:'',
    category:'General',
    source_url:''
})

const addTip = async()=>{

    try{

        await tipAPI.create(newTip.value)

        newTip.value={
            title:'',
            body:'',
            category:'General',
            source_url:''
        }

        loadTips()

    }catch(err){

        console.error(err)

    }

}

const deleteTip = async(id)=>{

    if(!confirm("Delete this tip?")) return

    await tipAPI.delete(id)

    loadTips()

}

const getCategoryClass = (cat) => {
  return `pill-${cat.toLowerCase()}`
}
</script>

<style scoped>

.admin-panel{
    margin-bottom:30px;
}

.form-card{

    background:#1f3e35;

    border-radius:20px;

    padding:30px;

    margin:25px 0;

    box-shadow:0 12px 30px rgba(0,0,0,.08);

    border-top:5px solid #43a047;

}

.form-card h3{

    font-size:24px;

    color:#2e7d32;

    margin-bottom:25px;

    text-align:center;

}

.tip-input,
.tip-textarea{

    width:100%;

    padding:15px;

    margin-bottom:18px;

    border:1px solid #dfe5df;

    border-radius:12px;

    background:#f8fbf8;

    font-size:15px;

    transition:.25s;

    box-sizing:border-box;

}

.tip-input:focus,
.tip-textarea:focus{

    outline:none;

    border-color:#43a047;

    background:white;

    box-shadow:0 0 0 4px rgba(67,160,71,.15);

}

.tip-textarea{

    min-height:120px;

    resize:vertical;

}

.green-btn{

    width:100%;

    padding:15px;

    border:none;

    border-radius:12px;

    background:linear-gradient(135deg,#43a047,#2e7d32);

    color:white;

    font-size:17px;

    font-weight:700;

    cursor:pointer;

    transition:.25s;

}

.green-btn:hover{

    transform:translateY(-2px);

    box-shadow:0 10px 20px rgba(67,160,71,.3);

}

.delete-btn{

    background:#ef5350;

    color:white;

    border:none;

    padding:10px 18px;

    border-radius:8px;

    cursor:pointer;

    margin-top:15px;

}

.delete-btn:hover{

    background:#d32f2f;

}

</style>