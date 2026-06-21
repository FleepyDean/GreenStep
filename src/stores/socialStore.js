import { defineStore } from 'pinia'
import { ref } from 'vue'
import { socialAPI } from '@/services/api'

export const useSocialStore = defineStore('social', () => {
  const friends = ref([])
  const pendingRequests = ref([])
  const leaderboard = ref([])
  const leaderboardFilter = ref('global')
  const loading = ref(false)
  const error = ref(null)

  async function fetchFriends() {
    loading.value = true
    error.value = null
    try {
      const response = await socialAPI.getFriends()
      friends.value = response.data.friends || []
      pendingRequests.value = response.data.pending_requests || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch friends'
      console.error('fetchFriends error:', err)
    } finally {
      loading.value = false
    }
  }

  async function sendFriendRequest(identifier) {
    loading.value = true
    error.value = null
    try {
      const response = await socialAPI.sendFriendRequest({ identifier })
      await fetchFriends()
      return { success: true, message: response.data.message }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to send friend request'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function respondToRequest(requestId, status) {
    loading.value = true
    error.value = null
    try {
      const response = await socialAPI.updateFriendRequest(requestId, { status })
      await fetchFriends()
      return { success: true, message: response.data.message }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to respond to request'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function fetchLeaderboard(filter = 'global') {
    loading.value = true
    error.value = null
    leaderboardFilter.value = filter
    try {
      const response = await socialAPI.getLeaderboard(filter)
      leaderboard.value = response.data.leaderboard || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch leaderboard'
      console.error('fetchLeaderboard error:', err)
    } finally {
      loading.value = false
    }
  }

  return {
    friends,
    pendingRequests,
    leaderboard,
    leaderboardFilter,
    loading,
    error,
    fetchFriends,
    sendFriendRequest,
    respondToRequest,
    fetchLeaderboard
  }
})
