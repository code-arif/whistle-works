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
  <div class="relative overflow-hidden rounded-lg bg-gradient-to-r from-[#1E1E2C] via-[#242436] to-[#2A2A3E] p-5 sm:p-6 border border-white/[0.08] shadow-md text-white">
    <!-- Subtle Ambient Glow -->
    <div class="absolute -right-10 -top-10 w-64 h-64 bg-[#F29F67]/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute right-40 -bottom-10 w-40 h-40 bg-[#3B8FF3]/15 rounded-full blur-3xl pointer-events-none"></div>

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
        
        <!-- Primary Action: Create Camp (Warm Peach / Coral Hero CTA) -->
        <a 
          href="/admin/camps" 
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2 rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs font-bold shadow-xs transition-all duration-150 cursor-pointer hover:shadow-[#F29F67]/20"
        >
          <Plus class="w-4 h-4 stroke-[2.5]" />
          <span>New Camp</span>
        </a>

        <!-- Secondary Action: Payment Monitor -->
        <a 
          href="/admin/monitor" 
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-md bg-[#262638] hover:bg-[#32324A] text-slate-200 hover:text-white border border-white/[0.08] text-xs font-medium shadow-xs transition-all duration-150 cursor-pointer"
        >
          <CreditCard class="w-3.5 h-3.5 text-[#3B8FF3]" />
          <span>Payments</span>
        </a>

        <!-- Tertiary Action: User Directory -->
        <a 
          href="/admin/users/list" 
          class="hidden sm:inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-md bg-[#262638] hover:bg-[#32324A] text-slate-200 hover:text-white border border-white/[0.08] text-xs font-medium shadow-xs transition-all duration-150 cursor-pointer"
        >
          <Users class="w-3.5 h-3.5 text-[#34B1AA]" />
          <span>Users</span>
        </a>

      </div>
    </div>
  </div>
</template>
