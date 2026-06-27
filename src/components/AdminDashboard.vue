<template>
  <div class="admin-dashboard">

    <div class="page-title">
      <h2>Admin Dashboard</h2>
      <p>Overview of platform activity and user statistics</p>
    </div>
    <div class="stats-grid">

        <div class="stat-card users">
            <div class="icon">👥</div>

            <div class="info">
                <p>Total Users</p>
                <h2>{{ dashboard.total_users }}</h2>
            </div>
        </div>

        <div class="stat-card activity">
            <div class="icon">📝</div>

            <div class="info">
                <p>Activities Logged</p>
                <h2>{{ dashboard.total_activities }}</h2>
            </div>
        </div>

        <div class="stat-card tips">
            <div class="icon">💡</div>

            <div class="info">
                <p>Eco Tips</p>
                <h2>{{ dashboard.total_tips }}</h2>
            </div>
        </div>

        <div class="stat-card carbon">
            <div class="icon">🌍</div>

            <div class="info">
                <p>Average Footprint</p>
                <h2>{{ dashboard.average_footprint }} kg</h2>
            </div>
        </div>

    </div>

    <h2 class="section-title">Carbon Emission Breakdown</h2>

    <div class="chart-card">
      <AdminCategoryChart
          :categories="dashboard.category_breakdown"
      />
    </div>

    <div class="dataset-card">

        <h2>🌍 Carbon Dataset</h2>

        <p>
            <strong>{{ dataset.name }}</strong>
        </p>

        <p>
            Year: {{ dataset.year }}
        </p>

        <p>
            Provider: {{ dataset.provider }}
        </p>

        <a
            :href="dataset.url"
            target="_blank"
            class="dataset-btn"
        >
            View Official Dataset
        </a>

    </div>

    <h2 class="section-title">Latest Registered Users</h2>

    <div class="user-grid">

        <div
            class="user-card"
            v-for="user in dashboard.latest_users"
            :key="user.id"
        >

            <div class="avatar">
            {{ user.name.charAt(0).toUpperCase() }}
            </div>

            <div class="user-info">

            <h3>{{ user.name }}</h3>

            <p>{{ user.email }}</p>

            <span
                class="badge"
                :class="user.role"
            >
                {{ user.role }}
            </span>

            <small>
                Joined:
                {{ new Date(user.joined_at).toLocaleDateString() }}
            </small>

            </div>

        </div>

    </div>

    <h2 class="section-title">Recent Activity Logs</h2>

    <div class="activity-grid">

        <div
            class="activity-card"
            v-for="activity in dashboard.latest_activities"
            :key="activity.name + activity.logged_on + activity.activity_name"
        >

            <div class="activity-header">

                <h3>{{ activity.name }}</h3>

                <span class="co2">

                    {{ activity.carbon_footprint }} kg

                </span>

            </div>

            <p class="activity-name">

                🚶 {{ activity.activity_name }}

            </p>

            <small>

                {{ activity.logged_on }}

            </small>

        </div>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { adminAPI } from "@/services/api";
import AdminCategoryChart from "./AdminCategoryChart.vue";

const dashboard = ref({
  total_users: 0,
  total_activities: 0,
  total_tips: 0,
  average_footprint: 0,
  category_breakdown: [],
  latest_users: [],
  latest_activities: []
});

const loading = ref(true);

const dataset = ref({
  name: "",
  year: "",
  provider: "",
  url: ""
});

const fetchDashboard = async () => {
  try {
    loading.value = true;

    // Dashboard statistics
    const response = await adminAPI.getDashboard();
    dashboard.value = response.data.data;

    // Carbon dataset information
    const datasetResponse = await adminAPI.getDataset();
    dataset.value = datasetResponse.data.data;

  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchDashboard);
</script>

<style scoped>
.admin-dashboard {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* ── Stat Cards ─────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 22px 24px;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
  border-left: 4px solid transparent;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
}

.stat-card.users  { border-left-color: #16a34a; }
.stat-card.activity { border-left-color: #2563eb; }
.stat-card.tips   { border-left-color: #d97706; }
.stat-card.carbon { border-left-color: #dc2626; }

.icon {
  font-size: 2rem;
  line-height: 1;
}

.info p {
  margin: 0;
  font-size: 0.8rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  font-weight: 500;
}

.info h2 {
  margin: 4px 0 0;
  font-size: 1.9rem;
  font-weight: 700;
  color: #1b3a4b;
}

/* ── Section Headings ────────────────────────── */
.section-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: #1b3a4b;
  margin: 0;
}

/* ── Chart Card ─────────────────────────────── */
.chart-card {
  background: #fff;
  border-radius: 14px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
}

/* ── Dataset Card ────────────────────────────── */
.dataset-card {
  padding: 24px;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
}

.dataset-card h2 {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1b3a4b;
  margin: 0 0 10px;
}

.dataset-card p {
  margin: 4px 0;
  font-size: 0.9rem;
  color: #4b5563;
}

.dataset-btn {
  display: inline-block;
  margin-top: 16px;
  padding: 10px 20px;
  background: #16a34a;
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  transition: background 0.2s;
}

.dataset-btn:hover {
  background: #15803d;
}

/* ── User Grid ───────────────────────────────── */
.user-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 18px;
}

.user-card {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #fff;
  padding: 20px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
  transition: transform 0.2s, box-shadow 0.2s;
}

.user-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
}

.avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #dcfce7;
  color: #16a34a;
  font-size: 1.4rem;
  font-weight: 700;
  display: flex;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
}

.user-info h3 {
  margin: 0 0 2px;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1b3a4b;
}

.user-info p {
  margin: 0 0 6px;
  font-size: 0.8rem;
  color: #6b7280;
}

.user-info small {
  font-size: 0.75rem;
  color: #9ca3af;
}

.badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  margin-bottom: 6px;
  margin-right: 4px;
}

.badge.admin    { background: #fee2e2; color: #b91c1c; }
.badge.leader   { background: #dbeafe; color: #1d4ed8; }
.badge.end-user { background: #dcfce7; color: #15803d; }

/* ── Activity Grid ───────────────────────────── */
.activity-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 18px;
}

.activity-card {
  background: #fff;
  padding: 20px;
  border-radius: 14px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.07);
  transition: transform 0.2s, box-shadow 0.2s;
}

.activity-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.activity-header h3 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #1b3a4b;
}

.co2 {
  background: #dcfce7;
  padding: 4px 12px;
  border-radius: 999px;
  font-size: 0.82rem;
  font-weight: 600;
  color: #15803d;
}

.activity-name {
  margin: 6px 0;
  font-size: 0.875rem;
  color: #4b5563;
}

.activity-card small {
  font-size: 0.78rem;
  color: #9ca3af;
}
</style>