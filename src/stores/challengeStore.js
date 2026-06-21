import { defineStore } from 'pinia'
import { ref } from 'vue'
import { challengeAPI } from '@/services/api'

export const useChallengeStore = defineStore('challenge', () => {
  const challenges = ref([])
  const currentChallengeDetails = ref(null)
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

  async function updateChallenge(id, challengeData) {
    error.value = null
    try {
      const response = await challengeAPI.updateChallenge(id, challengeData)
      const index = challenges.value.findIndex(c => c.id === id)
      if (index !== -1) {
        challenges.value[index] = { ...challenges.value[index], ...challengeData }
      }
      if (currentChallengeDetails.value && currentChallengeDetails.value.id === id) {
        currentChallengeDetails.value = { ...currentChallengeDetails.value, ...challengeData }
      }
      return { success: true, message: response.data.message }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to update challenge'
      error.value = message
      return { success: false, message }
    }
  }

  async function deleteChallenge(id) {
    error.value = null
    try {
      const response = await challengeAPI.deleteChallenge(id)
      challenges.value = challenges.value.filter(c => c.id !== id)
      return { success: true, message: response.data.message }
    } catch (err) {
      const message = err.response?.data?.message || 'Failed to delete challenge'
      error.value = message
      return { success: false, message }
    }
  }

  async function fetchChallengeDetails(id) {
    loading.value = true
    error.value = null
    try {
      const response = await challengeAPI.getChallengeDetails(id)
      currentChallengeDetails.value = response.data.challenge || null
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch challenge details'
      console.error('fetchChallengeDetails error:', err)
    } finally {
      loading.value = false
    }
  }

  return {
    challenges,
    currentChallengeDetails,
    loading,
    error,
    fetchChallenges,
    fetchChallengeDetails,
    joinChallenge,
    leaveChallenge,
    createChallenge,
    updateChallenge,
    deleteChallenge
  }
})
