<script setup>
import { Zap, ShieldCheck, Sparkles } from 'lucide-vue-next';

defineProps({
  userTotal: {
    type: Number,
    default: 150000,
  },
  paymentSuccessRate: {
    type: Number,
    default: 100,
  },
});

const formatNumber = (val) => {
  return new Intl.NumberFormat('en-US').format(val || 0);
};
</script>

<template>
  <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-slate-900 via-[#0E1726] to-indigo-950/80 p-5 sm:p-6 border border-slate-800 shadow-sm text-white">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -right-10 -top-10 w-64 h-64 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute right-40 -bottom-10 w-40 h-40 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5">
      <div class="space-y-1.5">
        <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-indigo-500/20 border border-indigo-500/30 text-indigo-300 text-xs font-mono">
          <Sparkles class="w-3.5 h-3.5 text-amber-400" />
          <span>Executive Business Intelligence</span>
        </div>
        <h2 class="text-xl sm:text-2xl font-bold font-display text-white tracking-tight">
          Platform Performance & Analytics
        </h2>
        <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
          Real-time multi-dimensional overview of revenue channels, user growth across {{ formatNumber(userTotal) }} members, camp schedules, and game assignments.
        </p>
      </div>

      <!-- Quick Summary Badges -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex items-center gap-2.5 w-full lg:w-auto">
        <div class="px-3.5 py-2.5 rounded-lg bg-[#0B0F17]/90 border border-slate-800 shadow-xs flex items-center gap-3">
          <div class="w-8 h-8 rounded-md bg-emerald-500/10 flex items-center justify-center text-emerald-400 flex-shrink-0">
            <Zap class="w-4 h-4" />
          </div>
          <div>
            <p class="text-[10px] uppercase font-mono tracking-wider text-slate-400">System Status</p>
            <p class="text-xs font-semibold text-emerald-400 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
              Optimal (Sub-50ms)
            </p>
          </div>
        </div>

        <div class="px-3.5 py-2.5 rounded-lg bg-[#0B0F17]/90 border border-slate-800 shadow-xs flex items-center gap-3">
          <div class="w-8 h-8 rounded-md bg-amber-500/10 flex items-center justify-center text-amber-400 flex-shrink-0">
            <ShieldCheck class="w-4 h-4" />
          </div>
          <div>
            <p class="text-[10px] uppercase font-mono tracking-wider text-slate-400">Success Rate</p>
            <p class="text-xs font-semibold text-amber-300">{{ paymentSuccessRate }}% Checkout</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
