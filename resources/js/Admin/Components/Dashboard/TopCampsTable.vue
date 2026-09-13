<script setup>
import { ChevronRight } from 'lucide-vue-next';

defineProps({
  topCamps: {
    type: Array,
    default: () => [],
  },
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};
</script>

<template>
  <div class="rounded-3xl bg-[#111827]/80 border border-slate-800/80 p-6 backdrop-blur-xl shadow-xl">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-bold font-display text-white">Top Camps by Revenue</h3>
        <p class="text-xs text-slate-400">Highest grossing camp programs</p>
      </div>
      <a href="/admin/camps" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
        <span>View All Camps</span>
        <ChevronRight class="w-4 h-4" />
      </a>
    </div>

    <!-- Camps Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-800 text-slate-400 font-mono uppercase text-[10px]">
            <th class="pb-3 pl-2">Camp Details</th>
            <th class="pb-3">Sport Type</th>
            <th class="pb-3">Status</th>
            <th class="pb-3">Price</th>
            <th class="pb-3 text-right pr-2">Total Gross</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          <tr v-for="camp in topCamps" :key="camp.id" class="group hover:bg-slate-800/40 transition-colors">
            <td class="py-3.5 pl-2">
              <p class="font-semibold text-slate-200 group-hover:text-indigo-300 transition-colors">{{ camp.camp_name }}</p>
              <p class="text-[11px] text-slate-500 truncate max-w-[200px]">{{ camp.location || 'Location not set' }}</p>
            </td>
            <td class="py-3.5">
              <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 text-[11px]">
                {{ camp.sports_type_name || 'Basketball' }}
              </span>
            </td>
            <td class="py-3.5">
              <span 
                :class="[
                  camp.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700',
                  'px-2 py-0.5 rounded-full text-[10px] font-mono border uppercase font-semibold'
                ]"
              >
                {{ camp.status }}
              </span>
            </td>
            <td class="py-3.5 font-mono text-slate-300">
              {{ formatCurrency(camp.price) }}
            </td>
            <td class="py-3.5 text-right pr-2 font-mono font-bold text-emerald-400">
              {{ formatCurrency(camp.total_revenue) }}
            </td>
          </tr>
          <tr v-if="topCamps.length === 0">
            <td colspan="5" class="py-8 text-center text-slate-500">
              No revenue data available yet.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
