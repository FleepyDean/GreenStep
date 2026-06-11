<template>
  <div class="challenges-container">
    <div class="page-title">
      <h2>🏆 Eco Challenges</h2>
      <p>Join community actions and reduce your carbon footprint together</p>
    </div>

    <div class="filter-tab-bar">
      <button :class="['tab-btn', currentTab === 'all' ? 'active-tab' : '']" @click="currentTab = 'all'">
        All Challenges
      </button>
      <button :class="['tab-btn', currentTab === 'joined' ? 'active-tab' : '']" @click="currentTab = 'joined'">
        Joined ({{ joinedCount }})
      </button>
    </div>

    <div class="challenges-grid">
      <div class="challenge-card" v-for="item in filteredChallenges" :key="item.id">
        <div class="status-badge-row">
          <span class="active-pill">Active</span>
          <span v-if="item.joined" class="joined-pill">✓ Joined</span>
          <span class="member-count">👥 {{ item.members }} members</span>
        </div>
        
        <h4>{{ item.name }}</h4>
        <p class="challenge-desc">{{ item.description }}</p>

        <div class="meta-stats-list">
          <div class="meta-row">
            <span class="meta-label">Target Reduction:</span>
            <span class="meta-val font-green">{{ item.target }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Duration:</span>
            <span class="meta-val">{{ item.duration }}</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Timeline:</span>
            <span class="meta-val">{{ item.timeline }}</span>
          </div>
        </div>

        <div class="action-btn-group">
          <button class="secondary-action-btn">View Details</button>
          <button 
            :class="['primary-action-btn', item.joined ? 'leave-action-btn' : '']"
            @click="toggleJoin(item)"
          >
            {{ item.joined ? 'Leave Challenge' : 'Join Challenge' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const currentTab = ref('all')

const projectChallenges = ref([
  {
    id: 1,
    name: 'Zero Waste Week',
    description: 'Go one week minimizing plastic wrappers, composting food scraps, and refusing single-use items.',
    target: '20 kg CO₂',
    duration: '7 days',
    timeline: 'May 27, 2026 - Jun 3, 2026',
    members: 42,
    joined: false
  },
  {
    id: 2,
    name: '30-Day Eco Warrior Challenge',
    description: 'Reduce your household electricity footings by shifting to alternative transit modes and vegetarian meal tracks.',
    target: '50 kg CO₂',
    duration: '30 days',
    timeline: 'May 10, 2026 - Jun 10, 2026',
    members: 156,
    joined: true
  },
  {
    id: 3,
    name: 'Green Transport Month',
    description: 'Ditch private combustion vehicles. Rely completely on commuter electric transit systems, cycling, or shared buses.',
    target: '100 kg CO₂',
    duration: '30 days',
    timeline: 'May 1, 2026 - May 31, 2026',
    members: 89,
    joined: false
  }
])

const filteredChallenges = computed(() => {
  if (currentTab.value === 'joined') {
    return projectChallenges.value.filter(c => c.joined)
  }
  return projectChallenges.value
})

const joinedCount = computed(() => {
  return projectChallenges.value.filter(c => c.joined).length
})

const toggleJoin = (item) => {
  item.joined = !item.joined
  item.members = item.joined ? item.members + 1 : item.members - 1
}
</script>