<script setup>
import { ref, onMounted, watch, onUnmounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import { TrendingUp, Calendar, RefreshCw } from 'lucide-vue-next';

Chart.register(...registerables);

const props = defineProps({
  chartData: {
    type: Object,
    required: true,
    default: () => ({
      monthLabels: [],
      revenue: [],
      fees: [],
      transactions: [],
      selectedMonths: 12,
    }),
  },
  loading: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['update-months']);

const chartCanvas = ref(null);
let chartInstance = null;

const formatMonthLabel = (mStr) => {
  if (!mStr) return '';
  const parts = mStr.split('-');
  if (parts.length < 2) return mStr;
  const date = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1);
  return date.toLocaleString('default', { month: 'short', year: '2-digit' });
};

const renderChart = () => {
  if (!chartCanvas.value) return;

  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = chartCanvas.value.getContext('2d');
  const labels = (props.chartData.monthLabels || []).map(formatMonthLabel);

  // Gradient fills
  const revGrad = ctx.createLinearGradient(0, 0, 0, 240);
  revGrad.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
  revGrad.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

  const feesGrad = ctx.createLinearGradient(0, 0, 0, 240);
  feesGrad.addColorStop(0, 'rgba(99, 102, 241, 0.30)');
  feesGrad.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

  const isDarkMode = document.documentElement.classList.contains('dark');
  const textColor = isDarkMode ? '#94A3B8' : '#64748B';
  const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.06)';

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Gross Revenue ($)',
          data: props.chartData.revenue || [],
          borderColor: '#10B981',
          backgroundColor: revGrad,
          fill: true,
          tension: 0.35,
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBackgroundColor: '#10B981',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          yAxisID: 'y',
        },
        {
          label: 'Admin Fees ($)',
          data: props.chartData.fees || [],
          borderColor: '#6366F1',
          backgroundColor: feesGrad,
          fill: true,
          tension: 0.35,
          borderWidth: 2.5,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBackgroundColor: '#6366F1',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          yAxisID: 'y',
        },
        {
          label: 'Transactions',
          data: props.chartData.transactions || [],
          borderColor: '#F59E0B',
          fill: false,
          tension: 0.35,
          borderDash: [5, 4],
          borderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 6,
          pointBackgroundColor: '#F59E0B',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          yAxisID: 'y1',
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index',
      },
      plugins: {
        legend: {
          position: 'top',
          align: 'end',
          labels: {
            boxWidth: 10,
            padding: 16,
            usePointStyle: true,
            color: textColor,
            font: {
              size: 12,
              weight: '500',
            },
          },
        },
        tooltip: {
          backgroundColor: isDarkMode ? '#1E293B' : '#FFFFFF',
          titleColor: isDarkMode ? '#F8FAFC' : '#0F172A',
          bodyColor: isDarkMode ? '#CBD5E1' : '#334155',
          borderColor: isDarkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          usePointStyle: true,
          callbacks: {
            label: function (context) {
              const val = context.raw;
              if (context.dataset.yAxisID === 'y1') {
                return `${context.dataset.label}: ${val} orders`;
              }
              return `${context.dataset.label}: $${Number(val).toLocaleString('en-US', { minimumFractionDigits: 2 })}`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            color: gridColor,
          },
          ticks: {
            color: textColor,
            font: { size: 11 },
          },
        },
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          beginAtZero: true,
          grid: {
            color: gridColor,
          },
          ticks: {
            color: textColor,
            font: { size: 11 },
            callback: (val) => `$${val}`,
          },
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          beginAtZero: true,
          grid: {
            drawOnChartArea: false,
          },
          ticks: {
            color: textColor,
            font: { size: 11 },
          },
        },
      },
    },
  });
};

onMounted(() => {
  renderChart();
});

watch(
  () => props.chartData,
  () => {
    renderChart();
  },
  { deep: true }
);

onUnmounted(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>

<template>
  <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-4 sm:p-5 shadow-xs relative">
    <!-- Header Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-4 border-b border-slate-100 dark:border-white/[0.06]">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 text-[#34B1AA] flex items-center justify-center">
          <TrendingUp class="w-4 h-4" />
        </div>
        <div>
          <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white flex items-center gap-2">
            Financial & Volume Analytics
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Monthly gross revenue, admin platform fees, and transaction volume
          </p>
        </div>
      </div>

      <!-- Time Range Selector Tabs -->
      <div class="inline-flex items-center p-1 rounded-md bg-slate-100 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.06] self-start sm:self-auto">
        <button
          v-for="m in [6, 12, 24]"
          :key="m"
          @click="emit('update-months', m)"
          :class="[
            'px-3 py-1 text-xs font-mono font-semibold rounded-md transition-all duration-150',
            (props.chartData.selectedMonths === m)
              ? 'bg-white dark:bg-[#1E1E2C] text-emerald-600 dark:text-emerald-400 shadow-2xs font-bold'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
          ]"
        >
          {{ m }}M
        </button>
      </div>
    </div>

    <!-- Chart Container -->
    <div class="relative h-64 sm:h-72 w-full">
      <div v-if="loading" class="absolute inset-0 bg-white/70 dark:bg-[#1E1E2C]/70 backdrop-blur-xs z-10 flex items-center justify-center gap-2 text-xs text-slate-500">
        <RefreshCw class="w-4 h-4 animate-spin text-emerald-500" />
        Loading analytics...
      </div>
      <canvas ref="chartCanvas"></canvas>
    </div>
  </div>
</template>
