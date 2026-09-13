<script setup>
import { Plus, CreditCard, Users } from 'lucide-vue-next';

defineProps({
  userTotal: {
    type: Number,
    default: 150000,
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
      <div class="space-y-1">
        <h2 class="text-xl sm:text-2xl font-bold font-display text-white tracking-tight">
          Platform Performance & Analytics
        </h2>
        <p class="text-slate-300 text-xs sm:text-sm max-w-2xl leading-relaxed">
          Real-time overview of revenue channels, user growth across {{ formatNumber(userTotal) }} members, camp schedules, and game assignments.
        </p>
      </div>

      <!-- Quick Action Buttons Group -->
      <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
        
        <!-- Primary Action: Create Camp -->
        <a 
          href="/admin/camps" 
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-md bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white text-xs font-semibold shadow-xs transition-all duration-150 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>New Camp</span>
        </a>

        <!-- Secondary Action: Payment Monitor -->
        <a 
          href="/admin/monitor" 
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-md bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 text-xs font-medium shadow-xs transition-all duration-150 cursor-pointer"
        >
          <CreditCard class="w-3.5 h-3.5 text-slate-400" />
          <span>Payments</span>
        </a>

        <!-- Tertiary Action: User Directory -->
        <a 
          href="/admin/users/list" 
          class="hidden sm:inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-md bg-slate-800/80 hover:bg-slate-700 text-slate-200 hover:text-white border border-slate-700 text-xs font-medium shadow-xs transition-all duration-150 cursor-pointer"
        >
          <Users class="w-3.5 h-3.5 text-slate-400" />
          <span>Users</span>
        </a>

      </div>
    </div>
  </div>
</template>
