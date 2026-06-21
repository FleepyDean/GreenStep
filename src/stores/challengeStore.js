import { defineStore } from 'pinia'
import { ref } from 'vue'
import { challengeAPI } from '@/services/api'

export const useChallengeStore = defineStore('challenge', () => {
  const challenges = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchChallenges() {
    loading.value = true
    error.value = null
    try {
      const response = await challengeAPI.getChallenges()
      challenges.value = response.data.challenges || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch challenges'
      console.error('fetchChallenges error:', err)
    } finally {
      loading.value = false
    }
  }

  async function joinChallenge(id) {
    error.value = null
    try {
      const response = await challengeAPI.joinChallenge(id)
      const challenge = challenges.value.find(c => c.id === id)
      if (challenge) {
        challenge.has_joined = true
        challenge.member_count += 1
      }
      return { success: true, message: response.data.message }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to join challenge'
      error.value = message
      return { success: false, message }
    }
  }

  async function leaveChallenge(id) {
    error.value = null
    try {
      const response = await challengeAPI.leaveChallenge(id)
      const challenge = challenges.value.find(c => c.id === id)
      if (challenge) {
        challenge.has_joined = false
        challenge.member_count = Math.max(0, challenge.member_count - 1)
      }
      return { success: true, message: response.data.message }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to leave challenge'
      error.value = message
      return { success: false, message }
    }
  }

  async function createChallenge(challengeData) {
    error.value = null
    try {
      const response = await challengeAPI.createChallenge(challengeData)
      if (response.data.challenge) {
        challenges.value.unshift(response.data.challenge)
      }
      return { success: true, message: response.data.message }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to create challenge'
      error.value = message
      return { success: false, message }
    }
  }

  return {
    challenges,
    loading,
    error,
    fetchChallenges,
    joinChallenge,
    leaveChallenge,
    createChallenge
  }
})
