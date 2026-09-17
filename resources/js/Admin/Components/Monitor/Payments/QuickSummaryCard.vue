<script setup>
import {
  AlertTriangle,
  XCircle,
  CheckCircle2,
  Tent,
  UserCheck,
  Trophy,
  Ticket
} from 'lucide-vue-next';

defineProps({
  stats: {
    type: Object,
    required: true,
  },
  topCoupons: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <div class="bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg p-4 sm:p-5 shadow-xs flex flex-col justify-between h-full space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100 dark:border-white/[0.06]">
      <div class="w-8 h-8 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 text-[#F29F67] flex items-center justify-center">
        <AlertTriangle class="w-4 h-4" />
      </div>
      <div>
        <h3 class="font-bold text-sm sm:text-base text-slate-900 dark:text-white">
          System Pulse
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Real-time transaction health & top promotions
        </p>
      </div>
    </div>

    <!-- Status Breakdown List -->
    <div class="space-y-2.5">
      <!-- Pending Attempts -->
      <div class="flex items-center justify-between p-2.5 rounded-md bg-amber-500/5 dark:bg-amber-500/10 border border-amber-500/20">
        <div class="flex items-center gap-2 text-xs font-medium text-amber-700 dark:text-amber-400">
          <AlertTriangle class="w-3.5 h-3.5" />
          <span>Pending Payments</span>
        </div>
        <span class="px-2 py-0.5 text-xs font-bold font-mono rounded-md bg-amber-500/20 text-amber-800 dark:text-amber-300">
          {{ stats.pendingAttempts }}
        </span>
      </div>

      <!-- Failed Attempts -->
      <div class="flex items-center justify-between p-2.5 rounded-md bg-rose-500/5 dark:bg-rose-500/10 border border-rose-500/20">
        <div class="flex items-center gap-2 text-xs font-medium text-rose-700 dark:text-rose-400">
          <XCircle class="w-3.5 h-3.5" />
          <span>Failed Attempts</span>
        </div>
        <span class="px-2 py-0.5 text-xs font-bold font-mono rounded-md bg-rose-500/20 text-rose-800 dark:text-rose-300">
          {{ stats.failedAttempts }}
        </span>
      </div>

      <!-- Successful Payments -->
      <div class="flex items-center justify-between p-2.5 rounded-md bg-emerald-500/5 dark:bg-emerald-500/10 border border-emerald-500/20">
        <div class="flex items-center gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-400">
          <CheckCircle2 class="w-3.5 h-3.5" />
          <span>Completed Transactions</span>
        </div>
        <span class="px-2 py-0.5 text-xs font-bold font-mono rounded-md bg-emerald-500/20 text-emerald-800 dark:text-emerald-300">
          {{ stats.transactionCount }}
        </span>
      </div>

      <!-- Active Camps -->
      <div class="flex items-center justify-between p-2.5 rounded-md bg-indigo-500/5 dark:bg-indigo-500/10 border border-indigo-500/20">
        <div class="flex items-center gap-2 text-xs font-medium text-indigo-700 dark:text-indigo-400">
          <Tent class="w-3.5 h-3.5" />
          <span>Active Camps</span>
        </div>
        <span class="px-2 py-0.5 text-xs font-bold font-mono rounded-md bg-indigo-500/20 text-indigo-800 dark:text-indigo-300">
          {{ stats.activeCamps }} / {{ stats.totalCamps }}
        </span>
      </div>

      <!-- Checked-In Referees -->
      <div class="flex items-center justify-between p-2.5 rounded-md bg-blue-500/5 dark:bg-blue-500/10 border border-blue-500/20">
        <div class="flex items-center gap-2 text-xs font-medium text-blue-700 dark:text-blue-400">
          <UserCheck class="w-3.5 h-3.5" />
          <span>Checked-In Referees</span>
        </div>
        <span class="px-2 py-0.5 text-xs font-bold font-mono rounded-md bg-blue-500/20 text-blue-800 dark:text-blue-300">
          {{ stats.totalCheckedIn }}
        </span>
      </div>
    </div>

    <!-- Top Coupons Leaderboard -->
    <div v-if="topCoupons && topCoupons.length > 0" class="pt-3 border-t border-slate-100 dark:border-white/[0.06] space-y-2">
      <div class="flex items-center justify-between text-xs font-bold text-slate-800 dark:text-slate-200">
        <span class="flex items-center gap-1.5 text-amber-500">
          <Trophy class="w-3.5 h-3.5" />
          Top Performing Coupons
        </span>
        <span class="text-[10px] text-slate-400 font-mono uppercase">Redemptions</span>
      </div>

      <div class="space-y-1.5">
        <div
          v-for="coupon in topCoupons"
          :key="coupon.id"
          class="flex items-center justify-between text-xs p-1.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200/60 dark:border-white/[0.04]"
        >
          <div class="flex items-center gap-1.5 font-mono font-bold text-slate-900 dark:text-white">
            <Ticket class="w-3 h-3 text-indigo-500" />
            <span>{{ coupon.code }}</span>
          </div>
          <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold font-mono">
            {{ coupon.used_count }} uses
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
