<template>
  <div class="challenges-container">
    <div class="page-title">
      <h2>🏆 Eco Challenges</h2>
      <p>Join community actions and reduce your carbon footprint together</p>
    </div>

    <button v-if="isAdmin" class="create-challenge-btn" @click="showCreateModal = true">
      + Create New Challenge
    </button>

    <div class="filter-tab-bar">
      <button v-if="!isAdmin" :class="['tab-btn', currentTab === 'all' ? 'active-tab' : '']" @click="currentTab = 'all'">
        All Challenges
      </button>
      <button v-if="!isAdmin" :class="['tab-btn', currentTab === 'joined' ? 'active-tab' : '']" @click="currentTab = 'joined'">
        Joined ({{ joinedCount }})
      </button>
    </div>

    <div v-if="isLoading" class="empty-state">
      <p>Loading challenges...</p>
    </div>

    <div v-else-if="filteredChallenges.length === 0" class="empty-state">
      <p v-if="currentTab === 'joined'">You haven't joined any challenges yet.</p>
      <p v-else>No challenges available right now.</p>
    </div>

    <div v-else class="challenges-grid">
      <div class="challenge-card" v-for="item in filteredChallenges" :key="item.id">
        <div class="status-badge-row">
          <span :class="['status-pill', item.is_active ? 'active-pill' : item.is_upcoming ? 'upcoming-pill' : 'completed-pill']">
            {{ item.is_active ? 'Active' : item.is_upcoming ? 'Upcoming' : 'Completed' }}
          </span>
          <span v-if="item.has_joined" class="joined-pill">✓ Joined</span>
          <span class="member-count">👥 {{ item.member_count }} members</span>
          <div v-if="isAdmin" class="admin-card-actions">
            <button class="icon-btn edit-icon-btn" @click.stop="openEditModal(item)" title="Edit">✏️</button>
            <button class="icon-btn delete-icon-btn" @click.stop="deleteItem(item)" title="Delete">🗑️</button>
          </div>
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
          <div class="meta-row">
            <span class="meta-label">Members:</span>
            <span class="meta-val">
              {{ item.member_count }}
              <span v-if="item.member_limit">/ {{ item.member_limit }}</span>
              <span v-if="item.is_full" class="full-badge">Full</span>
            </span>
          </div>
        </div>

        <div class="action-btn-group">
          <button class="secondary-action-btn" @click="viewDetails(item.id)">View Details</button>
          <button
            v-if="!isAdmin"
            :class="['primary-action-btn', item.has_joined ? 'leave-action-btn' : '']"
            :disabled="item.is_full && !item.has_joined"
            @click="toggleJoin(item)"
          >
            {{ item.has_joined ? 'Leave Challenge' : (item.is_full ? 'Full' : 'Join Challenge') }}
          </button>
        </div>
      </div>
    </div>

    <CreateChallengeModal
      :show="showCreateModal"
      :challenge="editingChallenge"
      @close="closeModal"
      @submit="handleModalSubmit"
    />
  </div>
</template>



<script setup>

import { ref, computed, onMounted } from 'vue'

import { useRouter } from 'vue-router'

import { challengeAPI } from '@/services/api'

import { useAuthStore } from '@/stores/auth'

import { useToast } from '@/composables/useToast'

import CreateChallengeModal from './CreateChallengeModal.vue'



const router = useRouter()



const authStore = useAuthStore()

const { toast } = useToast()



const challenges = ref([])

const currentTab = ref('all')

const showCreateModal = ref(false)

const editingChallenge = ref(null)

const isLoading = ref(true)



const isAdmin = computed(() => authStore.user?.role === 'admin')



const filteredChallenges = computed(() => {

  const list = Array.isArray(challenges.value) ? challenges.value : []

  if (currentTab.value === 'joined') {

    return list.filter(c => c.has_joined)

  }

  return list

})



const joinedCount = computed(() => {

  const list = Array.isArray(challenges.value) ? challenges.value : []

  return list.filter(c => c.has_joined).length

})



async function loadChallenges() {

  try {

    isLoading.value = true

    const response = await challengeAPI.getChallenges()

    challenges.value = response.data.challenges || []

  } catch (err) {

    console.error('Failed to load challenges:', err)

    toast.error(err.response?.data?.message || 'Failed to load challenges')

  } finally {

    isLoading.value = false

  }

}



function formatDate(dateStr) {

  if (!dateStr) return ''

  const d = new Date(dateStr)

  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })

}



function viewDetails(id) {

  router.push(`/challenges/${id}`)

}



async function toggleJoin(item) {

  try {

    const index = challenges.value.findIndex(c => c.id === item.id)

    if (item.has_joined) {

      const response = await challengeAPI.leaveChallenge(item.id)

      challenges.value[index] = { ...item, has_joined: false, member_count: Math.max(0, (item.member_count || 0) - 1) }

      toast.success(response.data.message || 'Left challenge')

    } else {

      const response = await challengeAPI.joinChallenge(item.id)

      challenges.value[index] = { ...item, has_joined: true, member_count: (item.member_count || 0) + 1 }

      toast.success(response.data.message || 'Joined challenge')

    }

  } catch (err) {

    console.error('Failed to toggle join:', err)

    toast.error(err.response?.data?.message || 'Failed to update challenge')

  }

}



function closeModal() {

  showCreateModal.value = false

  editingChallenge.value = null

}



async function handleModalSubmit(challengeData) {

  try {

    if (editingChallenge.value) {

      const response = await challengeAPI.updateChallenge(editingChallenge.value.id, challengeData)

      toast.success(response.data.message || 'Challenge updated')

    } else {

      const response = await challengeAPI.createChallenge(challengeData)

      toast.success(response.data.message || 'Challenge created')

    }

    closeModal()

    await loadChallenges()

  } catch (err) {

    console.error('Failed to save challenge:', err)

    toast.error(err.response?.data?.message || 'Failed to save challenge')

  }

}



function openEditModal(item) {

  editingChallenge.value = item

  showCreateModal.value = true

}



async function deleteItem(item) {

  if (!confirm(`Are you sure you want to delete "${item.name}"?`)) return

  try {

    const response = await challengeAPI.deleteChallenge(item.id)

    toast.success(response.data.message || 'Challenge deleted')

    await loadChallenges()

  } catch (err) {

    console.error('Failed to delete challenge:', err)

    toast.error(err.response?.data?.message || 'Failed to delete challenge')

  }

}



onMounted(async () => {

  await loadChallenges()

})

</script>



<style scoped>

.status-pill {

  font-size: 0.75rem;

  font-weight: 700;

  padding: 0.35rem 0.75rem;

  border-radius: 4px;

  text-transform: uppercase;

  letter-spacing: 0.05em;

  flex-shrink: 0;

}



.status-badge-row {

  display: flex;

  align-items: center;

  gap: 0.5rem;

  flex-wrap: wrap;

}



.admin-card-actions {

  display: flex;

  gap: 0.25rem;

  margin-left: auto;

}



.icon-btn {

  background: transparent;

  border: none;

  font-size: 1rem;

  cursor: pointer;

  padding: 0.3rem;

  border-radius: 6px;

  transition: background 0.2s ease;

}



.icon-btn:hover {

  background: rgba(255, 255, 255, 0.1);

}



.delete-icon-btn:hover {

  background: rgba(239, 68, 68, 0.15);

}



.active-pill {

  background: rgba(0, 168, 132, 0.15);

  color: #25D366;

  border: 1px solid rgba(0, 168, 132, 0.15);

}



.upcoming-pill {

  background: rgba(59, 130, 246, 0.15);

  color: #60a5fa;

  border: 1px solid rgba(59, 130, 246, 0.25);

}



.completed-pill {

  background: rgba(94, 100, 110, 0.15);

  color: #54656F;

  border: 1px solid rgba(94, 100, 110, 0.25);

}



.create-challenge-btn {

  width: 100%;

  padding: 0.85rem 1.5rem;

  border: none;

  border-radius: 6px;

  background: #00A884;

  color: #FFFFFF;

  font-size: 0.95rem;

  font-weight: 600;

  cursor: pointer;

  transition: all 0.2s ease;

  margin-bottom: 1rem;

}



.create-challenge-btn:hover {

  background: #008069;

  transform: translateY(-1px);

  box-shadow: 0 4px 12px rgba(0, 168, 132, 0.2);

}

.full-badge {
  display: inline-block;
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.15rem 0.4rem;
  border-radius: 10px;
  margin-left: 0.4rem;
  text-transform: uppercase;
}

.primary-action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: #8696A0;
}



.empty-state {

  text-align: center;

  padding: 2.5rem 1rem;

  color: #54656F;

}

</style>