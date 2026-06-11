<template>
  <div class="app-container">
    <header class="app-header">
      <div class="brand-wrapper">
        <div class="brand-logo">🌱</div>
        <div class="brand-text">
          <h1>GreenStep</h1>
          <p>Small daily steps. Measurable impact.</p>
        </div>
      </div>
      <div class="nav-links">
        <router-link to="/dashboard" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper">📊</span>
          <span class="nav-text-label">Dashboard</span>
        </router-link>
        <router-link to="/activity" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper">📝</span>
          <span class="nav-text-label">Log</span>
        </router-link>
        <router-link to="/challenges" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper">🏆</span>
          <span class="nav-text-label">Challenges</span>
        </router-link>
        <router-link to="/tips" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper">💡</span>
          <span class="nav-text-label">Tips</span>
        </router-link>
        <router-link to="/profile" class="nav-link-btn" active-class="active">
          <span class="nav-icon-wrapper">👤</span>
          <span class="nav-text-label">Profile</span>
        </router-link>
      </div>
    </header>

    <main class="main-content">
      <router-view></router-view>
    </main>
  </div>
</template>

<style scoped>
/* Hidden on desktop views, visible on mobile bars through inheritance layout modifications */
.nav-icon-wrapper {
  display: none;
  font-size: 1.2rem;
  margin-bottom: 2px;
}

@media (max-width: 768px) {
  .nav-icon-wrapper {
    display: inline-block;
  }
}
</style>