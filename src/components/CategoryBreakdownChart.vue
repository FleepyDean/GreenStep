<template>
  <div class="chart-container">
    <Doughnut :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

// Register Doughnut-specific engines
ChartJS.register(ArcElement, Tooltip, Legend)

// Receive live breakdown data object from the dashboard view
const props = defineProps({
  breakdownData: {
    type: Object,
    default: () => ({ Transport: 0, Diet: 0, Energy: 0, Recycling: 0, General: 0 })
  }
})

const chartData = computed(() => {
  const labels = Object.keys(props.breakdownData)
  const dataValues = Object.values(props.breakdownData)

  return {
    labels: labels,
    datasets: [
      {
        data: dataValues,
        // Premium earthy/eco cohesive color palette
        backgroundColor: [
          '#10b981', // Transport (Emerald)
          '#34d399', // Diet (Mint)
          '#f59e0b', // Energy (Amber)
          '#3b82f6', // Recycling (Blue)
          '#64748b'  // General (Slate)
        ],
        borderWidth: 2,
        borderColor: '#ffffff', // Clean white gap separation lines
        hoverOffset: 4
      }
    ]
  }
})

// Minimalist configuration setup
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right', // Moves labels to the side cleanly
      labels: {
        boxWidth: 12,
        padding: 15,
        color: '#64748b',
        font: { size: 12, family: 'sans-serif' }
      }
    },
    tooltip: {
      callbacks: {
        label: (context) => ` ${context.label}: ${context.raw.toFixed(2)} kg CO₂`
      }
    }
  },
  // Creates a clean, thin elegant doughnut ring shape
  cutout: '75%' 
}
</script>

<style scoped>
.chart-container {
  width: 100%;
  height: 220px;
}
</style>