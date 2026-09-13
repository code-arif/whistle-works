<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
  monthlyRevenue: {
    type: Object,
    default: () => ({ labels: [], revenue: [], fees: [], transactions: [] }),
  },
  totalRevenue: {
    type: Number,
    default: 0,
  },
});

const hoveredMonth = ref(null);

const maxRevenue = computed(() => {
  const values = props.monthlyRevenue?.revenue || [];
  return values.length > 0 ? Math.max(...values, 1000) : 1000;
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};
</script>

<template>
  <div class="rounded-xl bg-[#111827]/80 border border-slate-800/80 p-5 sm:p-6 backdrop-blur-xl shadow-sm flex flex-col justify-between h-full">
    <div>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
        <div>
          <h3 class="text-base sm:text-lg font-bold font-display text-white">Monthly Revenue Analytics</h3>
          <p class="text-xs text-slate-400">12-month performance overview (Gross volume vs Fees)</p>
        </div>
        <div class="flex items-center gap-3 text-xs self-start sm:self-auto">
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-sm bg-indigo-500"></span>
            <span class="text-slate-300">Revenue</span>
          </div>
          <div class="flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-sm bg-amber-400"></span>
            <span class="text-slate-300">Admin Fee</span>
          </div>
        </div>
      </div>

      <!-- Horizontal scroll container on extra small screens -->
      <div class="overflow-x-auto pb-2">
        <div class="min-w-[480px] h-56 sm:h-64 flex items-end justify-between gap-1.5 sm:gap-2 pt-6 pb-2 px-1 border-b border-slate-800">
          <div 
            v-for="(label, idx) in monthlyRevenue.labels" 
            :key="label" 
            class="flex-1 flex flex-col items-center gap-2 h-full justify-end group relative cursor-pointer"
            @mouseenter="hoveredMonth = { label, rev: monthlyRevenue.revenue[idx], fee: monthlyRevenue.fees[idx], tx: monthlyRevenue.transactions[idx] }"
            @mouseleave="hoveredMonth = null"
          >
            <!-- Tooltip on hover -->
            <div 
              v-if="hoveredMonth?.label === label" 
              class="absolute -top-14 z-20 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-700 shadow-xl text-[10px] font-mono text-white whitespace-nowrap animate-in fade-in duration-100"
            >
              <p class="font-bold text-indigo-300">{{ label }}</p>
              <p class="text-emerald-400">Rev: {{ formatCurrency(hoveredMonth.rev) }}</p>
              <p class="text-amber-400">Fee: {{ formatCurrency(hoveredMonth.fee) }}</p>
            </div>

            <!-- Bar Stack -->
            <div class="w-full max-w-[24px] rounded-t-sm bg-indigo-500/20 group-hover:bg-indigo-500/30 transition-all flex flex-col justify-end overflow-hidden" :style="{ height: `${Math.max(8, (monthlyRevenue.revenue[idx] / maxRevenue) * 100)}%` }">
              <div class="w-full bg-gradient-to-t from-indigo-600 to-indigo-400 rounded-t-sm transition-all duration-300" :style="{ height: '80%' }"></div>
            </div>

            <!-- Month label -->
            <span class="text-[10px] font-mono text-slate-500 group-hover:text-slate-300 transition-colors">
              {{ label.split(' ')[0] }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-xs text-slate-500 font-mono">
      <span>* High-frequency Redis cached calculations</span>
      <span class="text-indigo-400 font-semibold">Total: {{ formatCurrency(totalRevenue) }}</span>
    </div>
  </div>
</template>
