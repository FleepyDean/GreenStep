<template>
  <div class="chart-container">
    <Line :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Filler
} from 'chart.js'

// Register core architecture rendering blocks for ChartJS core engines
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Filler)

// Map array variables downward from parent dashboard containers
const props = defineProps({
  weeklyData: {
    type: Array,
    default: () => [0, 0, 0, 0, 0, 0, 0]
  }
})

// Computed dataset configurations
const chartData = computed(() => ({
  labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
  datasets: [
    {
      label: 'Carbon Spent (kg)',
      data: props.weeklyData,
      borderColor: '#00A884',
      backgroundColor: 'rgba(0, 168, 132, 0.06)',
      borderWidth: 2,
      pointBackgroundColor: '#00A884',
      pointHoverRadius: 6,
      tension: 0.38, 
      fill: true
    }
  ]
}))

// Minimalist canvas grid formatting options object
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#54656F', font: { size: 11 } }
    },
    y: {
      grid: { color: 'rgba(0, 0, 0, 0.06)' },
      ticks: { color: '#54656F', font: { size: 11 } },
      border: { dash: [4, 4] }
    }
  }
}
</script>

<style scoped>
.chart-container {
  width: 100%;
  height: 220px;
}
</style>