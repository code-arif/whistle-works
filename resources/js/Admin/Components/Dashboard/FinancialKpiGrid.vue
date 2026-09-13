<script setup>
import {
  DollarSign,
  Award,
  Users,
  Tent,
  TrendingUp,
  TrendingDown
} from 'lucide-vue-next';

defineProps({
  revenueStats: {
    type: Object,
    default: () => ({}),
  },
  userStats: {
    type: Object,
    default: () => ({}),
  },
  campStats: {
    type: Object,
    default: () => ({}),
  },
  gameSlotStats: {
    type: Object,
    default: () => ({}),
  },
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};

const formatNumber = (val) => {
  return new Intl.NumberFormat('en-US').format(val || 0);
};
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5">
    
    <!-- CARD 1: TOTAL REVENUE -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#111827]/80 hover:bg-slate-50 dark:hover:bg-[#111827] border border-slate-200 dark:border-slate-800/80 hover:border-emerald-500/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Total Gross Revenue</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white tracking-tight truncate">
            {{ formatCurrency(revenueStats.totalRevenue) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform flex-shrink-0">
          <DollarSign class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs">
        <div class="flex items-center gap-1.5 font-semibold truncate" :class="revenueStats.revenueGrowth >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
          <component :is="revenueStats.revenueGrowth >= 0 ? TrendingUp : TrendingDown" class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ Math.abs(revenueStats.revenueGrowth || 0) }}%</span>
          <span class="text-slate-400 dark:text-slate-500 font-normal hidden sm:inline">vs last mo</span>
        </div>
        <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ revenueStats.totalTransactions }} txns</span>
      </div>
    </div>

    <!-- CARD 2: ADMIN PLATFORM FEES -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#111827]/80 hover:bg-slate-50 dark:hover:bg-[#111827] border border-slate-200 dark:border-slate-800/80 hover:border-amber-500/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Platform Net Earnings</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-amber-600 dark:text-amber-300 tracking-tight truncate">
            {{ formatCurrency(revenueStats.totalAdminFees) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-105 transition-transform flex-shrink-0">
          <Award class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs">
        <span class="text-slate-500 dark:text-slate-400 truncate">Director Payout:</span>
        <span class="font-semibold text-slate-700 dark:text-slate-200 font-mono">{{ formatCurrency(revenueStats.totalDirectorAmount) }}</span>
      </div>
    </div>

    <!-- CARD 3: TOTAL ACTIVE COMMUNITY -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#111827]/80 hover:bg-slate-50 dark:hover:bg-[#111827] border border-slate-200 dark:border-slate-800/80 hover:border-indigo-500/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Community & Users</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white tracking-tight truncate">
            {{ formatNumber(userStats.total) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform flex-shrink-0">
          <Users class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-1.5 truncate">
          <span class="text-emerald-600 dark:text-emerald-400 font-semibold">{{ formatNumber(userStats.active) }} Active</span>
          <span>&bull;</span>
          <span class="text-indigo-600 dark:text-indigo-300 truncate">{{ formatNumber(userStats.referees) }} Referees</span>
        </div>
        <span class="text-amber-600 dark:text-amber-400 font-mono">+{{ userStats.growth }}%</span>
      </div>
    </div>

    <!-- CARD 4: CAMPS & GAME SLOTS -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#111827]/80 hover:bg-slate-50 dark:hover:bg-[#111827] border border-slate-200 dark:border-slate-800/80 hover:border-violet-500/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Camp Operations</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white tracking-tight truncate">
            {{ formatNumber(campStats.total) }} Camps
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-600 dark:text-violet-400 group-hover:scale-105 transition-transform flex-shrink-0">
          <Tent class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-1.5 truncate">
          <span class="text-violet-600 dark:text-violet-300 font-semibold">{{ campStats.active }} Active</span>
          <span>&bull;</span>
          <span class="truncate">{{ campStats.upcoming }} Upcm</span>
        </div>
        <span class="text-emerald-600 dark:text-emerald-400 font-mono">{{ gameSlotStats.utilizationRate }}% Fill</span>
      </div>
    </div>

  </div>
</template>
