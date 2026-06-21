<template>
  <div class="friends-container">
    <div class="page-title">
      <h2>🤝 Friends</h2>
      <p>Connect with others and grow your eco community together</p>
    </div>
 
    <section class="friends-card add-friend-card">
      <h3>Add Friend</h3>
      <form class="add-friend-form" @submit.prevent="handleSendRequest">
        <div class="add-friend-input">
          <input
            type="text"
            v-model="searchIdentifier"
            placeholder="Enter email or user ID"
            :disabled="socialStore.loading"
            required
          />
        </div>
        <button type="submit" class="submit-btn" :disabled="socialStore.loading || !searchIdentifier.trim()">
          {{ socialStore.loading ? 'Sending...' : 'Send Request' }}
        </button>
      </form>
    </section>
 
    <div class="filter-tab-bar">
      <button :class="['tab-btn', activeTab === 'friends' ? 'active-tab' : '']" @click="activeTab = 'friends'">
        My Friends ({{ socialStore.friends.length }})
      </button>
      <button :class="['tab-btn', activeTab === 'pending' ? 'active-tab' : '']" @click="activeTab = 'pending'">
        Pending ({{ socialStore.pendingRequests.length }})
      </button>
    </div>
 
    <div v-if="socialStore.loading && !hasLoaded" class="empty-state">
      <p>Loading friends...</p>
    </div>
 
    <section v-else-if="activeTab === 'friends'" class="friends-card">
      <h3>My Friends</h3>
      <div v-if="socialStore.friends.length === 0" class="empty-state">
        <p>You don't have any friends yet.</p>
        <p class="empty-sub">Send a request above to start building your community.</p>
      </div>
      <ul v-else class="friends-list">
        <li v-for="friend in socialStore.friends" :key="friend.friendship_id" class="friend-item">
          <div class="friend-info">
            <span class="friend-avatar">👤</span>
            <div>
              <h4>{{ friend.friend_name }}</h4>
              <p class="friend-email">{{ friend.friend_email }}</p>
            </div>
          </div>
          <span class="friend-status-badge accepted">Friend</span>
        </li>
      </ul>
    </section>
 
    <section v-else class="friends-card">
      <h3>Pending Requests</h3>
      <div v-if="socialStore.pendingRequests.length === 0" class="empty-state">
        <p>No pending friend requests.</p>
      </div>
      <ul v-else class="friends-list">
        <li v-for="request in socialStore.pendingRequests" :key="request.friendship_id" class="friend-item">
          <div class="friend-info">
            <span class="friend-avatar">👤</span>
            <div>
              <h4>{{ request.friend_name }}</h4>
              <p class="friend-email">{{ request.friend_email }}</p>
              <p class="request-direction">
                {{ request.direction === 'received' ? 'Wants to be your friend' : 'Request sent' }}
              </p>
            </div>
          </div>
          <div class="request-actions">
            <template v-if="request.direction === 'received'">
              <button
                class="accept-btn"
                @click="handleRespond(request.friendship_id, 'accepted')"
                :disabled="socialStore.loading"
              >
                Accept
              </button>
              <button
                class="decline-btn"
                @click="handleRespond(request.friendship_id, 'declined')"
                :disabled="socialStore.loading"
              >
                Decline
              </button>
            </template>
            <span v-else class="friend-status-badge pending">Pending</span>
          </div>
        </li>
      </ul>
    </section>
  </div>
</template>
 
<script setup>
import { ref, onMounted, computed } from 'vue'
import { useSocialStore } from '@/stores/socialStore'
import { useToast } from '@/composables/useToast'
 
const socialStore = useSocialStore()
const { toast } = useToast()
 
const activeTab = ref('friends')
const searchIdentifier = ref('')
const hasLoaded = ref(false)
 
const handleSendRequest = async () => {
  const identifier = searchIdentifier.value.trim()
  if (!identifier) return
 
  const result = await socialStore.sendFriendRequest(identifier)
  if (result.success) {
    toast.success(result.message)
    searchIdentifier.value = ''
  } else {
    toast.error(result.message)
  }
}
 
const handleRespond = async (requestId, status) => {
  const result = await socialStore.respondToRequest(requestId, status)
  if (result.success) {
    toast.success(result.message)
  } else {
    toast.error(result.message)
  }
}
 
onMounted(async () => {
  await socialStore.fetchFriends()
  hasLoaded.value = true
})
</script>
 
<style scoped>
.add-friend-card {
  margin-bottom: 1rem;
}
 
.add-friend-card h3 {
  font-size: 1.15rem;
  color: #34d399;
  margin-bottom: 1rem;
}
 
.add-friend-form {
  display: flex;
  gap: 0.75rem;
  align-items: stretch;
}
 
.add-friend-input {
  flex: 1;
}
 
.add-friend-input input {
  width: 100%;
  padding: 0.8rem 1rem;
  border: 1px solid #1c3830;
  border-radius: 8px;
  background-color: #0c1d18;
  font-size: 0.95rem;
  color: #f1f5f9;
  outline: none;
}
 
.add-friend-input input:focus {
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}
 
.add-friend-form .submit-btn {
  width: auto;
  padding: 0.8rem 1.5rem;
}
 
.friends-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
 
.friend-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background-color: #0c1d18;
  border: 1px solid #1c3830;
  border-radius: 10px;
  gap: 1rem;
}
 
.friend-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}
 
.friend-avatar {
  font-size: 1.75rem;
  flex-shrink: 0;
}
 
.friend-info h4 {
  font-size: 0.95rem;
  color: #ffffff;
  margin-bottom: 0.2rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
 
.friend-email {
  font-size: 0.8rem;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
 
.request-direction {
  font-size: 0.75rem;
  color: #34d399;
  margin-top: 0.2rem;
}
 
.friend-status-badge {
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  flex-shrink: 0;
}
 
.friend-status-badge.accepted {
  background-color: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.2);
}
 
.friend-status-badge.pending {
  background-color: rgba(250, 204, 21, 0.15);
  color: #facc15;
  border: 1px solid rgba(250, 204, 21, 0.2);
}
 
.request-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}
 
.accept-btn {
  background-color: #10b981;
  color: white;
  border: none;
  padding: 0.55rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}
 
.accept-btn:hover {
  background-color: #059669;
}
 
.decline-btn {
  background-color: rgba(239, 68, 68, 0.15);
  color: #f87171;
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 0.55rem 0.9rem;
  font-size: 0.82rem;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}
 
.decline-btn:hover {
  background-color: #ef4444;
  color: white;
}
 
.empty-state {
  text-align: center;
  padding: 2.5rem 1rem;
  color: #94a3b8;
}
 
.empty-sub {
  font-size: 0.85rem;
  margin-top: 0.5rem;
}
 
@media (max-width: 640px) {
  .add-friend-form {
    flex-direction: column;
  }
 
  .add-friend-form .submit-btn {
    width: 100%;
  }
 
  .friend-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
 
  .request-actions {
    width: 100%;
  }
 
  .accept-btn,
  .decline-btn {
    flex: 1;
  }
}
</style>