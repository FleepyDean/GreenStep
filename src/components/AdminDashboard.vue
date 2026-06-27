<template>
  <div class="admin-dashboard">

    <h1>🛠 Admin Dashboard</h1>

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

    <h2>Carbon Emission Breakdown</h2>

    <AdminCategoryChart
        :categories="dashboard.category_breakdown"
    />

    <h2 class="section-title">👥 Latest Registered Users</h2>

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

    <h2 class="section-title">
    📝 Recent Activity Logs
    </h2>

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

const fetchDashboard = async () => {
  try {
    loading.value = true;

    const response = await adminAPI.getDashboard();

    console.log(response.data);

    dashboard.value = response.data.data;

  } catch (err) {
    console.error(err);
  } finally {
    loading.value = false;
  }
};

onMounted(fetchDashboard);
</script>

<style scoped>

.admin-dashboard{

padding:30px;

}

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:35px;
}

.stat-card{
    display:flex;
    align-items:center;
    gap:20px;
    padding:24px;
    border-radius:16px;
    color:white;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
    transition:.3s;
}

.stat-card:hover{
    transform:translateY(-5px);
}

.icon{
    font-size:2.4rem;
}

.info p{
    margin:0;
    opacity:.9;
    font-size:.9rem;
}

.info h2{
    margin-top:8px;
    font-size:2rem;
}

.users{
    background:#2E7D32;
}

.activity{
    background:#1565C0;
}

.tips{
    background:#F9A825;
}

.carbon{
    background:#D84315;
}

table{

width:100%;

border-collapse:collapse;

margin-bottom:30px;

}

th,td{

padding:12px;

border-bottom:1px solid #ddd;

text-align:left;

}

.user-grid,
.activity-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
    margin-bottom:35px;
}

.user-card,
.activity-card{
    background:white;
    border-radius:16px;
    padding:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
    transition:.25s;
}

.user-card:hover,
.activity-card:hover{
    transform:translateY(-4px);
}

.user-card{
    display:flex;
    gap:18px;
    align-items:center;
}

.avatar{
    width:55px;
    height:55px;
    border-radius:50%;
    background:#E8F5E9;
    display:flex;
    justify-content:center;
    align-items:center;
    font-size:1.6rem;
}

.user-info h3{
    margin:0;
}

.user-info p{
    margin:6px 0;
    color:#666;
}

.role{
    background:#4CAF50;
    color:white;
    padding:4px 10px;
    border-radius:20px;
    font-size:.8rem;
}

.activity-card h3{
    margin-bottom:12px;
}

.activity-card p{
    margin:8px 0;
}

.activity-card small{
    color:#777;
}

.section-title{
    margin:40px 0 20px;
    color:#1c4532;
}

.user-grid{

display:grid;

grid-template-columns:repeat(auto-fit,minmax(250px,1fr));

gap:20px;

margin-bottom:40px;

}

.user-card{

display:flex;

align-items:center;

gap:15px;

background:white;

padding:20px;

border-radius:16px;

box-shadow:0 5px 15px rgba(0,0,0,.08);

transition:.25s;

}

.user-card:hover{

transform:translateY(-5px);

}

.avatar{

width:60px;

height:60px;

border-radius:50%;

background:#2ecc71;

color:white;

font-size:24px;

display:flex;

justify-content:center;

align-items:center;

font-weight:bold;

}

.user-info h3{

margin:0;

}

.user-info p{

margin:5px 0;

font-size:14px;

color:#666;

}

.badge{

display:inline-block;

padding:4px 10px;

border-radius:20px;

font-size:12px;

font-weight:bold;

margin-bottom:8px;

}

.badge.admin{

background:#ffdddd;

color:#c0392b;

}

.badge.leader{

background:#dbeafe;

color:#2563eb;

}

.badge.end-user{

background:#dcfce7;

color:#16a34a;

}

.activity-grid{

display:grid;

grid-template-columns:repeat(auto-fit,minmax(300px,1fr));

gap:20px;

}

.activity-card{

background:white;

padding:20px;

border-radius:16px;

box-shadow:0 5px 15px rgba(0,0,0,.08);

transition:.25s;

}

.activity-card:hover{

transform:translateY(-5px);

}

.activity-header{

display:flex;

justify-content:space-between;

align-items:center;

margin-bottom:10px;

}

.co2{

background:#e8f5e9;

padding:6px 12px;

border-radius:20px;

font-weight:bold;

color:#2e7d32;

}

.activity-name{

margin:10px 0;

font-size:15px;

}

</style>