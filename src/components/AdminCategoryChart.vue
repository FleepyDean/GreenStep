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
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: "bottom",
      labels: {
        padding: 20,
        font: { size: 13, family: "inherit" },
        usePointStyle: true,
        pointStyleWidth: 10
      }
    },
    tooltip: {
      callbacks: {
        label: (ctx) => ` ${ctx.label}: ${ctx.parsed} kg`
      }
    }
  }
};
</script>

<style scoped>
.chart-container {
  width: 100%;
  max-width: 480px;
  height: 320px;
  margin: 0 auto;
}
</style>