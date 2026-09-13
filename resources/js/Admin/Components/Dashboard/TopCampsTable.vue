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
  <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] p-5 sm:p-6 backdrop-blur-xl shadow-xs flex flex-col justify-between h-full transition-colors duration-150">
    <div>
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Top Camps by Revenue</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Highest grossing camp programs</p>
        </div>
        <a href="/admin/camps" class="text-xs font-semibold text-[#E08A50] dark:text-[#F29F67] hover:underline flex items-center gap-1 transition-colors">
          <span>View All</span>
          <ChevronRight class="w-4 h-4" />
        </a>
      </div>

      <!-- Camps Table Container with smooth mobile scroll -->
      <div class="overflow-x-auto -mx-5 sm:mx-0 px-5 sm:px-0">
        <table class="w-full text-left text-xs min-w-[500px]">
          <thead>
            <tr class="border-b border-slate-200 dark:border-white/[0.08] text-slate-500 dark:text-slate-400 font-mono uppercase text-[10px]">
              <th class="pb-3 pl-1">Camp Details</th>
              <th class="pb-3">Sport</th>
              <th class="pb-3">Status</th>
              <th class="pb-3">Price</th>
              <th class="pb-3 text-right pr-1">Total Gross</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
            <tr v-for="camp in topCamps" :key="camp.id" class="group hover:bg-slate-50 dark:hover:bg-[#2D2D42] transition-colors">
              <td class="py-3 pl-1">
                <p class="font-semibold text-slate-900 dark:text-slate-200 group-hover:text-[#F29F67] transition-colors truncate max-w-[180px] sm:max-w-xs">{{ camp.camp_name }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[180px] sm:max-w-xs">{{ camp.location || 'Location not set' }}</p>
              </td>
              <td class="py-3">
                <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-[#1E1E2C] text-slate-700 dark:text-slate-300 text-[11px] font-medium border border-slate-200 dark:border-white/[0.08]">
                  {{ camp.sports_type_name || 'Basketball' }}
                </span>
              </td>
              <td class="py-3">
                <span 
                  :class="[
                    camp.status === 'active' 
                      ? 'bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border-[#34B1AA]/30' 
                      : 'bg-slate-100 dark:bg-[#1E1E2C] text-slate-600 dark:text-slate-400 border-slate-200 dark:border-white/[0.08]',
                    'px-2 py-0.5 rounded text-[10px] font-mono border uppercase font-semibold'
                  ]"
                >
                  {{ camp.status }}
                </span>
              </td>
              <td class="py-3 font-mono text-slate-700 dark:text-slate-300">
                {{ formatCurrency(camp.price) }}
              </td>
              <td class="py-3 text-right pr-1 font-mono font-bold text-[#2B9B95] dark:text-[#34B1AA]">
                {{ formatCurrency(camp.total_revenue) }}
              </td>
            </tr>
            <tr v-if="topCamps.length === 0">
              <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500">
                No revenue data available yet.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
