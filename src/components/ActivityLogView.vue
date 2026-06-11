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
                <option v-for="opt in typeOptions[form.category]" :key="opt" :value="opt">
                  {{ opt }}
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
          <span class="refresh-badge">Refresh</span>
        </div>

        <div class="logs-list">
          <div class="log-item" v-for="log in activities" :key="log.id">
            <div class="log-details">
              <h4>{{ log.type }} <span class="category-pill">{{ log.category }}</span></h4>
              <p class="unit-breakdown">{{ log.amount }} units logged</p>
            </div>
            <button class="delete-btn" @click="removeLog(log.id)">Delete</button>
          </div>
        </div>

        <div class="logs-footer-total">
          <span>Today's Total Avoided:</span>
          <strong>107.24 kg CO₂e</strong>
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
import { ref, reactive } from 'vue'

const form = reactive({
  category: '',
  type: '',
  amount: null,
  date: new Date().toISOString().substr(0, 10)
})

const typeOptions = {
  Transport: ['Bus / Train travel', 'Car (Petrol)', 'Bicycle commute'],
  Diet: ['Red Meat Meal', 'Vegetarian Meal', 'Vegan Meal'],
  Energy: ['Electricity Usage', 'Air Conditioner usage'],
  Recycling: ['Paper Recycling', 'Plastic Recycling']
}

const activities = ref([
  { id: 1, category: 'Recycling', type: 'Paper Recycling', amount: 5 },
  { id: 2, category: 'Energy', type: 'Electricity', amount: 12 },
  { id: 3, category: 'Diet', type: 'Red Meat Meal', amount: 1 },
  { id: 4, category: 'Transport', type: 'Car (Petrol)', amount: 15 }
])

const submitActivity = () => {
  if (!form.category || !form.type || !form.amount) return
  activities.value.unshift({
    id: Date.now(),
    category: form.category,
    type: form.type,
    amount: form.amount
  })
  form.category = ''
  form.type = ''
  form.amount = null
}

const removeLog = (id) => {
  activities.value = activities.value.filter(item => item.id !== id)
}
</script>