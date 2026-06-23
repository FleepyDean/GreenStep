<template>
  <div class="leaderboard-container">
    <div class="page-title">
      <h2>🏆 Leaderboard</h2>
      <p>See how your carbon footprint reduction compares with others</p>
    </div>
 
    <div class="filter-tab-bar">
      <button
        :class="['tab-btn', filter === 'global' ? 'active-tab' : '']"
        @click="setFilter('global')"
      >
        Global
      </button>
      <button
        v-if="!isAdmin()"
        :class="['tab-btn', filter === 'friends' ? 'active-tab' : '']"
        @click="setFilter('friends')"
      >
        Friends Only
      </button>
    </div>
 
    <section class="leaderboard-card">
      <div v-if="socialStore.loading" class="empty-state">
        <p>Loading rankings...</p>
      </div>
 
      <div v-else-if="socialStore.leaderboard.length === 0" class="empty-state">
        <p>No rankings available yet.</p>
        <p class="empty-sub">Start logging activities to appear on the leaderboard.</p>
      </div>
 
      <div v-else class="leaderboard-table-wrapper">
        <table class="leaderboard-table">
          <thead>
            <tr>
              <th class="col-rank">Rank</th>
              <th class="col-user">User</th>
              <th class="col-score">Total CO₂</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="entry in socialStore.leaderboard"
              :key="entry.user_id"
              :class="['leaderboard-row', { 'current-user-row': entry.is_current_user }]"
            >
              <td class="col-rank">
                <span :class="['rank-badge', getRankClass(entry.rank)]">
                  {{ entry.rank }}
                </span>
              </td>
              <td class="col-user">
                <div class="user-cell">
                  <span class="user-avatar">👤</span>
                  <div>
                    <p class="user-name">{{ entry.name }}</p>
                    <p v-if="entry.is_current_user" class="you-badge">You</p>
                  </div>
                </div>
              </td>
              <td class="col-score">
                <span :class="['score-value', getScoreClass(entry.total_footprint)]">
                  {{ formatScore(entry.total_footprint) }} kg
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
 
<script setup>
import { ref, onMounted } from 'vue'
import { useSocialStore } from '@/stores/socialStore'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isAdmin = () => authStore.user?.role === 'admin'
 
const socialStore = useSocialStore()
const filter = ref('global')
 
const setFilter = async (newFilter) => {
  filter.value = newFilter
  await socialStore.fetchLeaderboard(newFilter)
}
 
const formatScore = (value) => {
  const num = Number(value)
  return num > 0 ? `+${num.toFixed(2)}` : num.toFixed(2)
}
 
const getScoreClass = (value) => {
  return Number(value) <= 0 ? 'score-low' : 'score-high'
}
 
const getRankClass = (rank) => {
  if (rank === 1) return 'rank-gold'
  if (rank === 2) return 'rank-silver'
  if (rank === 3) return 'rank-bronze'
  return 'rank-regular'
}
 
onMounted(async () => {
  await socialStore.fetchLeaderboard(filter.value)
})
</script>
 
<style scoped>
.leaderboard-card {
  background: #FFFFFF;
  border: 1px solid #E9EDEF;
  border-radius: 6px;
  padding: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
  overflow: hidden;
}
 
.leaderboard-table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
 
.leaderboard-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}
 
.leaderboard-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  color: #54656F;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #E9EDEF;
}
 
.leaderboard-table td {
  padding: 1rem;
  border-bottom: 1px solid #E9EDEF;
  vertical-align: middle;
}
 
.leaderboard-row:last-child td {
  border-bottom: none;
}
 
.current-user-row {
  background: linear-gradient(90deg, rgba(0, 168, 132, 0.08) 0%, transparent 100%);
  border-left: 3px solid #00A884;
}
 
.col-rank {
  width: 70px;
  text-align: center;
}
 
.col-score {
  text-align: right;
  white-space: nowrap;
}
 
.rank-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  font-weight: 700;
  font-size: 0.85rem;
}
 
.rank-gold {
  background: linear-gradient(135deg, #facc15 0%, #eab308 100%);
  color: #422006;
  box-shadow: 0 0 12px rgba(250, 204, 21, 0.3);
}
 
.rank-silver {
  background: linear-gradient(135deg, #e2e8f0 0%, #54656F 100%);
  color: #0f172a;
}
 
.rank-bronze {
  background: linear-gradient(135deg, #fdba74 0%, #c2410c 100%);
  color: #fff7ed;
}
 
.rank-regular {
  background-color: #E9EDEF;
  color: #54656F;
}
 
.user-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
 
.user-avatar {
  font-size: 1.5rem;
  flex-shrink: 0;
}
 
.user-name {
  color: #111B21;
  font-weight: 600;
}
 
.you-badge {
  font-size: 0.7rem;
  color: #25D366;
  font-weight: 700;
  margin-top: 0.2rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
 
.score-value {
  font-weight: 700;
}
 
.score-low {
  color: #25D366;
}
 
.score-high {
  color: #f87171;
}
 
.empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #54656F;
}
 
.empty-sub {
  font-size: 0.85rem;
  margin-top: 0.5rem;
}
 
@media (max-width: 640px) {
  .leaderboard-card {
    padding: 1rem;
  }
 
  .leaderboard-table td,
  .leaderboard-table th {
    padding: 0.75rem;
  }
}
</style>