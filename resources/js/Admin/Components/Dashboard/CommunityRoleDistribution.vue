<script setup>
import { TrendingUp } from 'lucide-vue-next';

defineProps({
  userStats: {
    type: Object,
    default: () => ({}),
  },
});

const formatNumber = (val) => {
  return new Intl.NumberFormat('en-US').format(val || 0);
};
</script>

<template>
  <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] p-5 sm:p-6 backdrop-blur-xl shadow-xs flex flex-col justify-between h-full transition-colors duration-150">
    <div>
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Community Roles</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Distribution across active platform personas</p>
        </div>
      </div>

      <!-- Role Progress Bars -->
      <div class="space-y-4">
        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="text-slate-700 dark:text-slate-300 font-medium">Referees</span>
            <span class="text-[#3B8FF3] font-mono font-bold">{{ formatNumber(userStats.referees) }}</span>
          </div>
          <div class="h-2 rounded-full bg-slate-100 dark:bg-[#1E1E2C] overflow-hidden">
            <div class="h-full bg-gradient-to-r from-[#3B8FF3] to-[#60A5FA] rounded-full" :style="{ width: `${userStats.total ? (userStats.referees / userStats.total) * 100 : 50}%` }"></div>
          </div>
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="text-slate-700 dark:text-slate-300 font-medium">Directors & Managers</span>
            <span class="text-[#E08A50] dark:text-[#F29F67] font-mono font-bold">{{ formatNumber(userStats.directors) }}</span>
          </div>
          <div class="h-2 rounded-full bg-slate-100 dark:bg-[#1E1E2C] overflow-hidden">
            <div class="h-full bg-gradient-to-r from-[#F29F67] to-[#F7C4A1] rounded-full" :style="{ width: `${userStats.total ? (userStats.directors / userStats.total) * 100 : 25}%` }"></div>
          </div>
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="text-slate-700 dark:text-slate-300 font-medium">Evaluators</span>
            <span class="text-[#2B9B95] dark:text-[#34B1AA] font-mono font-bold">{{ formatNumber(userStats.evaluators) }}</span>
          </div>
          <div class="h-2 rounded-full bg-slate-100 dark:bg-[#1E1E2C] overflow-hidden">
            <div class="h-full bg-gradient-to-r from-[#34B1AA] to-[#5EEAD4] rounded-full" :style="{ width: `${userStats.total ? (userStats.evaluators / userStats.total) * 100 : 15}%` }"></div>
          </div>
        </div>
      </div>

      <!-- Growth Summary Pill -->
      <div class="mt-6 p-3.5 rounded-md bg-[#F29F67]/10 dark:bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center gap-3">
        <div class="w-8 h-8 rounded bg-[#F29F67]/20 flex items-center justify-center text-[#E08A50] dark:text-[#F29F67] flex-shrink-0">
          <TrendingUp class="w-4 h-4" />
        </div>
        <div class="min-w-0">
          <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">+{{ userStats.newThisMonth }} New Registrations</p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">Joined in the current month</p>
        </div>
      </div>
    </div>

    <div class="mt-6 pt-3.5 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-between text-xs">
      <span class="text-slate-500">Active Ratio</span>
      <span class="text-[#2B9B95] dark:text-[#34B1AA] font-semibold font-mono">
        {{ userStats.total ? Math.round((userStats.active / userStats.total) * 100) : 0 }}% Healthy
      </span>
    </div>
  </div>
</template>
