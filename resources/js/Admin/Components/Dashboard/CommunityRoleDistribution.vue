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
  <div class="rounded-3xl bg-[#111827]/80 border border-slate-800/80 p-6 sm:p-8 backdrop-blur-xl shadow-xl flex flex-col justify-between">
    <div>
      <div class="flex items-center justify-between mb-6">
        <div>
          <h3 class="text-lg font-bold font-display text-white">Community Roles</h3>
          <p class="text-xs text-slate-400">Distribution across active platform personas</p>
        </div>
      </div>

      <!-- Role Progress Bars -->
      <div class="space-y-4">
        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-300 font-medium">Referees</span>
            <span class="text-indigo-400 font-mono font-bold">{{ formatNumber(userStats.referees) }}</span>
          </div>
          <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-indigo-600 to-indigo-400 rounded-full" :style="{ width: `${userStats.total ? (userStats.referees / userStats.total) * 100 : 50}%` }"></div>
          </div>
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-300 font-medium">Directors & Managers</span>
            <span class="text-amber-400 font-mono font-bold">{{ formatNumber(userStats.directors) }}</span>
          </div>
          <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-amber-500 to-amber-300 rounded-full" :style="{ width: `${userStats.total ? (userStats.directors / userStats.total) * 100 : 25}%` }"></div>
          </div>
        </div>

        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-300 font-medium">Evaluators</span>
            <span class="text-emerald-400 font-mono font-bold">{{ formatNumber(userStats.evaluators) }}</span>
          </div>
          <div class="h-2 rounded-full bg-slate-800 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-300 rounded-full" :style="{ width: `${userStats.total ? (userStats.evaluators / userStats.total) * 100 : 15}%` }"></div>
          </div>
        </div>
      </div>

      <!-- Growth Summary Pill -->
      <div class="mt-8 p-4 rounded-2xl bg-indigo-950/40 border border-indigo-500/20 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-300">
          <TrendingUp class="w-5 h-5" />
        </div>
        <div>
          <p class="text-xs font-semibold text-white">+{{ userStats.newThisMonth }} New Registrations</p>
          <p class="text-[11px] text-slate-400">Joined in the current calendar month</p>
        </div>
      </div>
    </div>

    <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between text-xs">
      <span class="text-slate-500">Active User Ratio</span>
      <span class="text-emerald-400 font-semibold font-mono">
        {{ userStats.total ? Math.round((userStats.active / userStats.total) * 100) : 0 }}% Healthy
      </span>
    </div>
  </div>
</template>
