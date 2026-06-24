<template>
  <div class="challenge-details-container">
    <div v-if="isLoading" class="loading-state">
      <p>Loading challenge details...</p>
    </div>

    <div v-else-if="!challenge" class="error-state">
      <p>Challenge not found.</p>
      <button class="back-btn" @click="goBack">← Back to Challenges</button>
    </div>

    <div v-else class="details-content">
      <button class="back-btn" @click="goBack">← Back to Challenges</button>

      <div class="details-header">
        <h2>{{ challenge.name }}</h2>
        <div class="header-badges">
          <span :class="[
            'status-badge',
            challenge.is_active ? 'active' : challenge.is_upcoming ? 'upcoming' : 'completed'
          ]">
            {{ challenge.is_active ? 'Active' : challenge.is_upcoming ? 'Upcoming' : 'Completed' }}
          </span>
          <span v-if="challenge.has_joined" class="joined-badge">✓ Joined</span>
        </div>
      </div>

      <section class="details-card">
        <h3>About this Challenge</h3>
        <p class="challenge-description">{{ challenge.description }}</p>
      </section>

      <section class="details-card">
        <h3>Timeline & Goals</h3>
        <div class="meta-grid">
          <div class="meta-item">
            <span class="meta-icon">📅</span>
            <div>
              <p class="meta-label">Start Date</p>
              <p class="meta-value">{{ formatDate(challenge.start_date) }}</p>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon">🏁</span>
            <div>
              <p class="meta-label">End Date</p>
              <p class="meta-value">{{ formatDate(challenge.end_date) }}</p>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon">⏱️</span>
            <div>
              <p class="meta-label">Duration</p>
              <p class="meta-value">{{ challenge.duration_days }} days</p>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon">🎯</span>
            <div>
              <p class="meta-label">Target Reduction</p>
              <p class="meta-value font-green">{{ challenge.target_co2_reduction }} kg CO₂</p>
            </div>
          </div>
          <div class="meta-item">
            <span class="meta-icon">🏷️</span>
            <div>
              <p class="meta-label">Target Category</p>
              <p class="meta-value">{{ challenge.target_category }}</p>
            </div>
          </div>
          <div v-if="challenge.target_activity_type_name" class="meta-item">
            <span class="meta-icon">⚡</span>
            <div>
              <p class="meta-label">Target Activity</p>
              <p class="meta-value font-green">{{ challenge.target_activity_type_name }}</p>
            </div>
          </div>
        </div>
      </section>

      <section v-if="challenge.has_joined && !isAdmin" class="details-card">
        <h3>Your Contribution</h3>
        <p class="progress-description">
          Your personal CO₂ reduction from
          <strong>{{ challenge.target_activity_type_name || challenge.target_category }}</strong>
          activities during this challenge.
        </p>
        <div class="user-contribution-stat">
          <span class="contribution-value">{{ (challenge.user_progress || 0).toFixed(2) }} kg</span>
          <span class="contribution-label">CO₂ reduced</span>
        </div>
      </section>

      <section class="details-card">
        <h3>Community Progress</h3>
        <p class="progress-description">
          Total CO₂ reduction from
          <strong>{{ challenge.target_activity_type_name || challenge.target_category }}</strong>
          activities logged by members during the challenge.
        </p>
        <div class="progress-section">
          <div class="progress-bar-wrapper">
            <div class="progress-bar-track">
              <div
                class="progress-bar-fill"
                :style="{ width: progressPercentage + '%' }"
              ></div>
            </div>
            <span class="progress-percentage">{{ progressPercentage }}%</span>
          </div>
          <div class="progress-stats">
            <div class="progress-stat">
              <span class="stat-label">Current</span>
              <span class="stat-value">{{ challenge.current_progress.toFixed(2) }} kg</span>
            </div>
            <div class="progress-stat">
              <span class="stat-label">Target</span>
              <span class="stat-value font-green">{{ challenge.target_co2_reduction }} kg</span>
            </div>
          </div>
        </div>
      </section>

      <section class="details-card">
        <h3>Participants ({{ challenge.member_count }})</h3>
        <div v-if="challenge.member_count === 0" class="empty-participants">
          No one has joined this challenge yet. Be the first!
        </div>
        <div v-else class="participants-list">
          <div
            v-for="member in challenge.members"
            :key="member.user_id"
            :class="['participant-item', { 'is-you': member.is_current_user }]"
          >
            <span class="participant-avatar">👤</span>
            <div class="participant-info">
              <span class="participant-name">
                {{ member.name }}
                <span v-if="member.is_current_user" class="you-tag">You</span>
              </span>
              <span class="participant-email">{{ member.email }}</span>
            </div>
          </div>
        </div>
      </section>

      <div class="action-area">
        <button
          v-if="!isAdmin"
          :class="['action-btn', challenge.has_joined ? 'leave-btn' : 'join-btn']"
          @click="toggleJoin"
        >
          {{ challenge.has_joined ? 'Leave Challenge' : 'Join Challenge' }}
        </button>
        <div v-else class="admin-actions">
          <button class="admin-edit-btn" @click="openEditModal">
            ✏️ Edit Challenge
          </button>
          <button class="admin-delete-btn" @click="deleteChallenge">
            🗑️ Delete Challenge
          </button>
        </div>
      </div>

      <CreateChallengeModal
        :show="showEditModal"
        :challenge="challenge"
        @close="closeEditModal"
        @submit="handleEditSubmit"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { challengeAPI } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import CreateChallengeModal from './CreateChallengeModal.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const { toast } = useToast()

const challenge = ref(null)
const showEditModal = ref(false)
const isLoading = ref(true)

const isAdmin = computed(() => authStore.user?.role === 'admin')

const progressPercentage = computed(() => {
  if (!challenge.value) return 0
  const target = challenge.value.target_co2_reduction
  if (target <= 0) return 0
  const pct = (challenge.value.current_progress / target) * 100
  return Math.min(Math.round(pct), 100)
})


async function loadDetails() {
  try {
    isLoading.value = true
    const response = await challengeAPI.getChallengeDetails(route.params.id)
    challenge.value = response.data.challenge || null
  } catch (err) {
    console.error('Failed to load challenge details:', err)
    toast.error(err.response?.data?.message || 'Failed to load challenge details')
    challenge.value = null
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

function goBack() {
  router.push('/challenges')
}

function openEditModal() {
  showEditModal.value = true
}

function closeEditModal() {
  showEditModal.value = false
}

async function handleEditSubmit(challengeData) {
  if (!challenge.value) return
  try {
    const response = await challengeAPI.updateChallenge(challenge.value.id, challengeData)
    toast.success(response.data.message || 'Challenge updated')
    closeEditModal()
    await loadDetails()
  } catch (err) {
    console.error('Failed to update challenge:', err)
    toast.error(err.response?.data?.message || 'Failed to update challenge')
  }
}

async function deleteChallenge() {
  if (!challenge.value) return
  if (!confirm(`Are you sure you want to delete "${challenge.value.name}"? This action cannot be undone.`)) return
  try {
    const response = await challengeAPI.deleteChallenge(challenge.value.id)
    toast.success(response.data.message || 'Challenge deleted')
    router.push('/community')
  } catch (err) {
    console.error('Failed to delete challenge:', err)
    toast.error(err.response?.data?.message || 'Failed to delete challenge')
  }
}

async function toggleJoin() {
  if (!challenge.value) return
  const id = challenge.value.id

  try {
    if (challenge.value.has_joined) {
      const response = await challengeAPI.leaveChallenge(id)
      challenge.value.has_joined = false
      challenge.value.member_count = Math.max(0, challenge.value.member_count - 1)
      if (Array.isArray(challenge.value.members)) {
        challenge.value.members = challenge.value.members.filter(m => !m.is_current_user)
      }
      toast.success(response.data.message || 'Left challenge')
    } else {
      const response = await challengeAPI.joinChallenge(id)
      challenge.value.has_joined = true
      challenge.value.member_count += 1
      if (authStore.user && Array.isArray(challenge.value.members)) {
        challenge.value.members.push({
          user_id: authStore.user.id,
          name: authStore.user.name,
          email: authStore.user.email,
          joined_at: new Date().toISOString(),
          is_current_user: true
        })
      }
      toast.success(response.data.message || 'Joined challenge')
    }
  } catch (err) {
    console.error('Failed to toggle join:', err)
    toast.error(err.response?.data?.message || 'Failed to update challenge')
  }
}

onMounted(async () => {
  await loadDetails()
})
</script>

<style scoped>
.challenge-details-container {
  padding: 1rem;
  max-width: 700px;
  margin: 0 auto;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #54656F;
}

.back-btn {
  background: none;
  border: none;
  color: #25D366;
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0.5rem 0;
  margin-bottom: 1rem;
  transition: opacity 0.2s ease;
}

.back-btn:hover {
  opacity: 0.8;
}

.details-header {
  margin-bottom: 1.5rem;
}

.details-header h2 {
  font-size: 1.5rem;
  color: #111B21;
  margin-bottom: 0.75rem;
  font-weight: 700;
}

.header-badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-badge.active {
  background: rgba(0, 168, 132, 0.15);
  color: #25D366;
  border: 1px solid rgba(0, 168, 132, 0.15);
}

.status-badge.upcoming {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  border: 1px solid rgba(59, 130, 246, 0.25);
}

.status-badge.completed {
  background: rgba(94, 100, 110, 0.15);
  color: #54656F;
  border: 1px solid rgba(94, 100, 110, 0.25);
}

.joined-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  border-radius: 4px;
  background: #00A884;
  color: #FFFFFF;
}

.details-card {
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.details-card h3 {
  font-size: 1.1rem;
  color: #25D366;
  margin-bottom: 1rem;
  font-weight: 600;
}

.challenge-description {
  color: #54656F;
  line-height: 1.6;
  font-size: 0.95rem;
}

.meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.meta-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.meta-label {
  font-size: 0.75rem;
  color: #8696A0;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin: 0;
}

.meta-value {
  font-size: 0.95rem;
  color: #111B21;
  font-weight: 600;
  margin: 0;
}

.font-green {
  color: #25D366;
}

.progress-description {
  font-size: 0.85rem;
  color: #54656F;
  margin: -0.25rem 0 0.75rem;
  line-height: 1.5;
}

.progress-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.progress-bar-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-bar-track {
  flex: 1;
  height: 12px;
  background: #F0F2F5;
  border-radius: 6px;
  overflow: hidden;
  border: 1px solid #E9EDEF;
}

.progress-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #00A884, #25D366);
  border-radius: 6px;
  transition: width 0.5s ease;
  min-width: 2px;
}

.user-contribution-stat {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.contribution-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0EA5E9;
}

.contribution-label {
  font-size: 0.9rem;
  color: #8696A0;
}

.progress-percentage {
  font-size: 0.9rem;
  font-weight: 700;
  color: #25D366;
  min-width: 40px;
  text-align: right;
}

.progress-stats {
  display: flex;
  justify-content: space-between;
}

.progress-stat {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.stat-label {
  font-size: 0.75rem;
  color: #8696A0;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.stat-value {
  font-size: 1rem;
  color: #111B21;
  font-weight: 600;
}

.empty-participants {
  color: #8696A0;
  font-size: 0.9rem;
  text-align: center;
  padding: 1.5rem 0;
}

.participants-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.participant-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #F0F2F5;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
}

.participant-item.is-you {
  border-color: #00A884;
  background: rgba(0, 168, 132, 0.08);
}

.participant-avatar {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.participant-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.participant-name {
  font-size: 0.9rem;
  color: #111B21;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.you-tag {
  font-size: 0.65rem;
  padding: 0.15rem 0.4rem;
  background: #00A884;
  color: #FFFFFF;
  border-radius: 6px;
  font-weight: 700;
  text-transform: uppercase;
}

.participant-email {
  font-size: 0.8rem;
  color: #54656F;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.action-area {
  padding-top: 0.5rem;
  padding-bottom: 2rem;
}

.admin-actions {
  display: flex;
  gap: 0.75rem;
}

.admin-edit-btn,
.admin-delete-btn {
  flex: 1;
  padding: 1rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.admin-edit-btn {
  background: rgba(59, 130, 246, 0.15);
  color: #60a5fa;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.admin-edit-btn:hover {
  background: rgba(59, 130, 246, 0.25);
}

.admin-delete-btn {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.admin-delete-btn:hover {
  background: #ef4444;
  color: #FFFFFF;
}

.action-btn {
  width: 100%;
  padding: 1rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.join-btn {
  background: #00A884;
  color: #FFFFFF;
}

.join-btn:hover {
  background: #008069;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 168, 132, 0.2);
}

.leave-btn {
  background: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.leave-btn:hover {
  background: #ef4444;
  color: #FFFFFF;
}

@media (max-width: 480px) {
  .meta-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .details-header h2 {
    font-size: 1.25rem;
  }
}
</style>
