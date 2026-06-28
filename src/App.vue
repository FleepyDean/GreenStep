<template>
  <div class="app-container">
    <ToastNotification />
    <header class="app-header">
      <div class="header-left">
        <!-- Mobile only: hamburger sits top-left -->
        <button v-if="authStore.isAuthenticated" class="header-hamburger" @click="drawerOpen = true" aria-label="Open menu">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </button>

        <div class="brand-wrapper">
          <div class="brand-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6"/></svg></div>
          <div class="brand-text">
            <h1>GreenStep</h1>
            <p>Small daily steps. Measurable impact.</p>
          </div>
        </div>
      </div>
      <div v-if="authStore.isAuthenticated" class="nav-links">
        <router-link
          :to="isAdmin ? '/admin-dashboard' : '/dashboard'"
          class="nav-link-btn"
          active-class="active"
        >
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></span>
          <span class="nav-text-label">
            {{ isAdmin ? 'Admin Dashboard' : 'Dashboard' }}
          </span>
        </router-link>
        <router-link to="/challenges" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg></span>
          <span class="nav-text-label">Challenges</span>
        </router-link>
        <router-link v-if="!isAdmin" to="/activity" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
          <span class="nav-text-label">Activity</span>
        </router-link>
        <router-link to="/tips" class="nav-link-btn desktop-only" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg></span>
          <span class="nav-text-label">Tips</span>
        </router-link>
        <router-link v-if="!isAdmin" to="/friends" class="nav-link-btn desktop-only" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2"/><path d="M11 17v2a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2h-4"/></svg></span>
          <span class="nav-text-label">Friends</span>
        </router-link>
        <router-link to="/leaderboard" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/></svg></span>
          <span class="nav-text-label">Board</span>
        </router-link>
        <router-link v-if="isAdmin" to="/emission-factors" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
          <span class="nav-text-label">Factors</span>
        </router-link>
        <router-link v-if="isAdmin" to="/admin/add-badges" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.12"/></svg></span>
          <span class="nav-text-label">Badges</span>
        </router-link>
        <router-link to="/profile" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
          <span class="nav-text-label">Profile</span>
        </router-link>
      </div>
    </header>

    <main class="main-content">
      <router-view></router-view>
    </main>

    <!-- Side drawer overlay -->
    <Transition name="drawer-fade">
      <div v-if="drawerOpen" class="drawer-overlay" @click.self="drawerOpen = false">
        <Transition name="drawer-slide">
          <div v-if="drawerOpen" class="drawer-sheet">
            <div class="drawer-header">
              <div class="drawer-brand">
                <svg class="drawer-brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6"/></svg>
                <span class="drawer-brand-name">GreenStep</span>
              </div>
              <button class="drawer-close" @click="drawerOpen = false" aria-label="Close">✕</button>
            </div>

            <nav class="drawer-nav">
              <router-link to="/tips" class="drawer-item" @click="drawerOpen = false">
                <span class="drawer-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg></span>
                <div class="drawer-item-text">
                  <span class="drawer-label">Eco Tips</span>
                  <span class="drawer-sub">Actionable habits for a greener life</span>
                </div>
              </router-link>

              <router-link v-if="!isAdmin" to="/friends" class="drawer-item" @click="drawerOpen = false">
                <span class="drawer-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 17h2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h2"/><path d="M11 17v2a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-5a2 2 0 0 0-2-2h-4"/></svg></span>
                <div class="drawer-item-text">
                  <span class="drawer-label">Friends</span>
                  <span class="drawer-sub">Connect and compare progress</span>
                </div>
              </router-link>

            </nav>
          </div>
        </Transition>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRoute } from 'vue-router'
import ToastNotification from '@/components/ToastNotification.vue'

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.user?.role === 'admin')

const route = useRoute()
const navBouncing = ref(false)
const drawerOpen = ref(false)

watch(() => route.path, () => {
  drawerOpen.value = false
  navBouncing.value = true
  setTimeout(() => { navBouncing.value = false }, 300)
})
</script>

<style scoped>
.nav-icon-wrapper {
  display: none;
  margin-bottom: 2px;
}

.nav-icon-wrapper svg {
  width: 20px;
  height: 20px;
}

/* Header left group: hamburger + brand always together */
.header-left {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Tips/Friends nav links — desktop only, hidden on mobile */
.desktop-only {
  display: flex;
}

/* Header hamburger — hidden on desktop */
.header-hamburger {
  display: none;
}

@media (max-width: 768px) {
  .nav-icon-wrapper {
    display: inline-block;
  }

  /* Active icon pop-in on route change */
  :deep(.nav-link-btn.active .nav-icon-wrapper) {
    animation: icon-pop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  @keyframes icon-pop {
    0%   { transform: scale(0.75); }
    60%  { transform: scale(1.25); }
    100% { transform: scale(1.15); }
  }

  /* Hide desktop-only links in mobile pill nav */
  .desktop-only {
    display: none !important;
  }

  /* Header hamburger — top-left of the header bar */
  .header-hamburger {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    width: 40px;
    height: 40px;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    padding: 0;
    flex-shrink: 0;
    transition: background 0.15s ease, transform 0.15s ease;
    -webkit-tap-highlight-color: transparent;
    order: -1; /* always leftmost */
  }

  .header-hamburger:active {
    transform: scale(0.9);
    background: rgba(0, 0, 0, 0.06);
  }

  .hamburger-line {
    display: block;
    width: 20px;
    height: 2px;
    background: #111B21;
    border-radius: 2px;
  }

  /* ── Side drawer overlay ── */
  .drawer-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.35);
    z-index: 1200;
    display: flex;
    flex-direction: row;
    align-items: stretch;
  }

  /* The drawer panel slides in from the left */
  .drawer-sheet {
    width: 78vw;
    max-width: 300px;
    background: #FFFFFF;
    height: 100%;
    display: flex;
    flex-direction: column;
    padding-top: calc(env(safe-area-inset-top, 0px));
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
  }

  /* Drawer top bar */
  .drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.25rem 1rem;
    border-bottom: 1px solid #E9EDEF;
  }

  .drawer-brand {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.3rem;
  }

  .drawer-brand-icon {
    width: 24px;
    height: 24px;
    color: #00A884;
  }

  .drawer-brand-name {
    font-size: 1rem;
    font-weight: 700;
    color: #111B21;
  }

  .drawer-close {
    background: transparent;
    border: none;
    font-size: 1.1rem;
    color: #54656F;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 6px;
    line-height: 1;
    transition: background 0.15s;
    -webkit-tap-highlight-color: transparent;
  }

  .drawer-close:active {
    background: #F0F2F5;
  }

  /* Nav items */
  .drawer-nav {
    padding: 0.75rem 0;
    flex: 1;
    overflow-y: auto;
  }

  .drawer-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 1.25rem;
    text-decoration: none;
    color: #111B21;
    transition: background 0.15s ease;
  }

  .drawer-item:active {
    background: #F0F2F5;
  }

  .drawer-item.active {
    background: rgba(0, 168, 132, 0.07);
  }

  .drawer-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #F0F2F5;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .drawer-icon svg {
    width: 20px;
    height: 20px;
  }

  .drawer-item-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }

  .drawer-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: #111B21;
  }

  .drawer-sub {
    font-size: 0.78rem;
    color: #8696A0;
  }

  /* Overlay fade */
  .drawer-fade-enter-active,
  .drawer-fade-leave-active {
    transition: opacity 0.25s ease;
  }
  .drawer-fade-enter-from,
  .drawer-fade-leave-to {
    opacity: 0;
  }

  /* Sheet slides from left */
  .drawer-slide-enter-active,
  .drawer-slide-leave-active {
    transition: transform 0.28s cubic-bezier(0.32, 0.72, 0, 1);
  }
  .drawer-slide-enter-from,
  .drawer-slide-leave-to {
    transform: translateX(-100%);
  }
}
</style>