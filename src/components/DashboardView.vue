<template>
  <div class="dashboard-wrapper">
    <div class="dashboard-title">
      <h2>🌱 Your Carbon Dashboard</h2>
      <p>Track your environmental impact and progress</p>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <span class="card-icon">📊</span>
        <h3 class="value font-green">107.24 kg</h3>
        <p class="label">Today's Footprint</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">📝</span>
        <h3 class="value font-green">107.24 kg</h3>
        <p class="label">Weekly Total</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">🔥</span>
        <h3 class="value font-orange">30</h3>
        <p class="label">Daily Streak</p>
      </div>
      <div class="metric-card">
        <span class="card-icon">🏆</span>
        <h3 class="value font-gold">1</h3>
        <p class="label">Badges Earned</p>
      </div>
    </div>

    <div class="charts-layout">
      <div class="chart-box">
        <h4>Weekly Trend</h4>
        <div class="placeholder-graph">[ Weekly Data Trend View Chart ]</div>
      </div>
      <div class="chart-box">
        <h4>Category Breakdown</h4>
        <div class="placeholder-graph">[ Activity Target Breakdown Map ]</div>
      </div>
    </div>

    <div class="eco-tip-card">
      <div class="tip-header">💡 Today's Eco Tip</div>
      <div class="tip-content">
        <h5>Use a reusable water bottle</h5>
        <p>Save approximately 156 plastic bottles per year by using a reusable water bottle instead of disposable ones.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
// Dashboard business states logic
</script>