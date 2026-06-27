<template>
  <div class="admin-dashboard">

    <h1>🛠 Admin Dashboard</h1>

    <div class="cards">

      <div class="card">
        <h3>Total Users</h3>
        <h2>{{ dashboard.total_users }}</h2>
      </div>

      <div class="card">
        <h3>Total Activities</h3>
        <h2>{{ dashboard.total_activities }}</h2>
      </div>

      <div class="card">
        <h3>Total Tips</h3>
        <h2>{{ dashboard.total_tips }}</h2>
      </div>

      <div class="card">
        <h3>Average Footprint</h3>
        <h2>{{ dashboard.average_footprint }} kg</h2>
      </div>

    </div>

    <h2>Category Breakdown</h2>

    <table>

      <thead>

      <tr>

        <th>Category</th>

        <th>Total CO₂</th>

      </tr>

      </thead>

      <tbody>

      <tr
        v-for="item in dashboard.category_breakdown"
        :key="item.category"
      >

        <td>{{ item.category }}</td>

        <td>{{ item.total }}</td>

      </tr>

      </tbody>

    </table>

    <h2>Latest Users</h2>

    <table>

      <thead>

      <tr>

        <th>Name</th>

        <th>Email</th>

        <th>Role</th>

      </tr>

      </thead>

      <tbody>

      <tr
        v-for="user in dashboard.latest_users"
        :key="user.id"
      >

        <td>{{ user.name }}</td>

        <td>{{ user.email }}</td>

        <td>{{ user.role }}</td>

      </tr>

      </tbody>

    </table>

    <h2>Latest Activities</h2>

    <table>

      <thead>

      <tr>

        <th>User</th>

        <th>Activity</th>

        <th>CO₂</th>

      </tr>

      </thead>

      <tbody>

      <tr
        v-for="activity in dashboard.latest_activities"
        :key="activity.name + activity.logged_on"
      >

        <td>{{ activity.name }}</td>

        <td>{{ activity.activity_name }}</td>

        <td>{{ activity.carbon_footprint }}</td>

      </tr>

      </tbody>

    </table>

  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { adminAPI } from "@/services/api";

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

.cards{

display:grid;

grid-template-columns:repeat(4,1fr);

gap:20px;

margin-bottom:30px;

}

.card{

padding:20px;

border-radius:12px;

background:#F3F7F4;

box-shadow:0 2px 8px rgba(0,0,0,.1);

text-align:center;

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

</style>