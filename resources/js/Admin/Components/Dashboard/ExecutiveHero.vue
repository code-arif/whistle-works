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
  <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-[#0E1726] to-indigo-950/60 p-6 sm:p-8 border border-slate-800/80 shadow-2xl backdrop-blur-xl">
    <!-- Glow blobs -->
    <div class="absolute -right-10 -top-10 w-80 h-80 bg-indigo-600/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute right-40 -bottom-10 w-60 h-60 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-mono">
          <Sparkles class="w-3.5 h-3.5 text-amber-400" />
          <span>Executive Business Intelligence</span>
        </div>
        <h2 class="text-2xl sm:text-3xl font-bold font-display text-white tracking-tight">
          Platform Performance & Analytics
        </h2>
        <p class="text-slate-400 text-sm max-w-2xl">
          Real-time multi-dimensional overview of revenue channels, user growth across {{ formatNumber(userTotal) }} members, camp schedules, and game assignments.
        </p>
      </div>

      <!-- Quick Summary Badges -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="px-4 py-2.5 rounded-2xl bg-[#0B0F17]/80 border border-slate-800 shadow-inner flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
            <Zap class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] uppercase font-mono tracking-wider text-slate-500">System Status</p>
            <p class="text-xs font-semibold text-emerald-400 flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              Optimal (Sub-50ms)
            </p>
          </div>
        </div>

        <div class="px-4 py-2.5 rounded-2xl bg-[#0B0F17]/80 border border-slate-800 shadow-inner flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-400">
            <ShieldCheck class="w-5 h-5" />
          </div>
          <div>
            <p class="text-[10px] uppercase font-mono tracking-wider text-slate-500">Success Rate</p>
            <p class="text-xs font-semibold text-amber-300">{{ paymentSuccessRate }}% Checkout</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
