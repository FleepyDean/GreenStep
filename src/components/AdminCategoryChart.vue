<template>
  <div class="chart-container">
    <Pie
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>

<script setup>
import { computed } from "vue";
import { Pie } from "vue-chartjs";

import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from "chart.js";

ChartJS.register(
  ArcElement,
  Tooltip,
  Legend
);

const props = defineProps({
  categories: {
    type: Array,
    default: () => []
  }
});

const chartData = computed(() => ({
  labels: props.categories.map(c => c.category),
  datasets: [
    {
      data: props.categories.map(c => Number(c.total)),
      backgroundColor: [
        "#4CAF50",
        "#2196F3",
        "#FFC107",
        "#F44336",
        "#9C27B0",
        "#009688"
      ]
    }
  ]
}));

const chartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: "bottom"
    }
  }
};
</script>

<style scoped>
.chart-container{
    width:100%;
    max-width:500px;
    margin:auto;
}
</style>