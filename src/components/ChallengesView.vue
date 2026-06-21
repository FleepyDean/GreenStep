<template>
  <div class="challenges-container">
    <div class="page-title">
      <h2>🏆 Eco Challenges</h2>
      <p>Join community actions and reduce your carbon footprint together</p>
    </div>

    <button
      v-if="isAdmin"
      class="create-challenge-btn"
      @click="showCreateModal = true"
    >
      + Create New Challenge
    </button>

    <div class="filter-tab-bar">
      <button :class="['tab-btn', currentTab === 'all' ? 'active-tab' : '']" @click="currentTab = 'all'">
        All Challenges
      </button>
      <button :class="['tab-btn', currentTab === 'joined' ? 'active-tab' : '']" @click="currentTab = 'joined'">
        Joined ({{ joinedCount }})
      </button>
    </div>

    <div v-if="challengeStore.loading" class="empty-state">
      <p>Loading challenges...</p>
    </div>

    <div v-else-if="filteredChallenges.length === 0" class="empty-state">
      <p v-if="currentTab === 'joined'">You haven't joined any challenges yet.</p>
      <p v-else>No challenges available right now.</p>
    </div>

    <div v-else class="challenges-grid">
      <div class="challenge-card" v-for="item in filteredChallenges" :key="item.id">
        <div class="status-badge-row">
          <span class="active-pill">Active</span>
          <span v-if="item.has_joined" class="joined-pill">✓ Joined</span>
          <span class="member-count">👥 {{ item.member_count }} members</span>
        </div>

        <h4>{{ item.name }}</h4>
        <p class="challenge-desc">{{ item.description }}</p>

        <div class="meta-stats-list">
          <div class="meta-row">
            <span class="meta-label">Target Reduction:</span>
            <span class="meta-val font-green">{{ item.target_co2_reduction }} kg CO₂</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Duration:</span>
            <span class="meta-val">{{ item.duration_days }} days</span>
          </div>
          <div class="meta-row">
            <span class="meta-label">Timeline:</span>
            <span class="meta-val">{{ formatDate(item.start_date) }} - {{ formatDate(item.end_date) }}</span>
          </div>
        </div>

        <div class="action-btn-group">
          <button class="secondary-action-btn">View Details</button>
          <button
            v-if="!isAdmin"
            :class="['primary-action-btn', item.has_joined ? 'leave-action-btn' : '']"
            @click="toggleJoin(item)"
          >
            {{ item.has_joined ? 'Leave Challenge' : 'Join Challenge' }}
          </button>
        </div>
      </div>
    </div>

    <CreateChallengeModal
      :show="showCreateModal"
      @close="showCreateModal = false"
      @submit="handleCreate"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useChallengeStore } from '@/stores/challengeStore'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import CreateChallengeModal from './CreateChallengeModal.vue'

const challengeStore = useChallengeStore()
const authStore = useAuthStore()
const { toast } = useToast()

const currentTab = ref('all')
const showCreateModal = ref(false)

const isAdmin = computed(() => authStore.user?.role === 'admin')

const filteredChallenges = computed(() => {
  if (currentTab.value === 'joined') {
    return challengeStore.challenges.filter(c => c.has_joined)
  }
  return challengeStore.challenges
})

const joinedCount = computed(() => {
  return challengeStore.challenges.filter(c => c.has_joined).length
})

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

async function toggleJoin(item) {
  if (item.has_joined) {
    const result = await challengeStore.leaveChallenge(item.id)
    if (result.success) {
      toast.success(result.message)
    } else {
      toast.error(result.message)
    }
  } else {
    const result = await challengeStore.joinChallenge(item.id)
    if (result.success) {
      toast.success(result.message)
    } else {
      toast.error(result.message)
    }
  }
}

async function handleCreate(challengeData) {
  const result = await challengeStore.createChallenge(challengeData)
  if (result.success) {
    toast.success(result.message)
    showCreateModal.value = false
  } else {
    toast.error(result.message)
  }
}

onMounted(() => {
  challengeStore.fetchChallenges()
})
</script>

<style scoped>
.create-challenge-btn {
  width: 100%;
  padding: 0.85rem 1.5rem;
  border: none;
  border-radius: 10px;
  background: #10b981;
  color: #ffffff;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 1rem;
}

.create-challenge-btn:hover {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #94a3b8;
}
</style>