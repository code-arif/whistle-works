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
    
    <!-- CARD 1: TOTAL REVENUE (Teal #34B1AA) -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#262638] hover:bg-slate-50 dark:hover:bg-[#2C2C40] border border-slate-200 dark:border-white/[0.08] hover:border-[#34B1AA]/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Total Gross Revenue</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white tracking-tight truncate">
            {{ formatCurrency(revenueStats.totalRevenue) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 flex items-center justify-center text-[#2B9B95] dark:text-[#34B1AA] group-hover:scale-105 transition-transform flex-shrink-0">
          <DollarSign class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-between text-xs">
        <div class="flex items-center gap-1.5 font-semibold truncate" :class="revenueStats.revenueGrowth >= 0 ? 'text-[#2B9B95] dark:text-[#34B1AA]' : 'text-rose-600 dark:text-rose-400'">
          <component :is="revenueStats.revenueGrowth >= 0 ? TrendingUp : TrendingDown" class="w-3.5 h-3.5 flex-shrink-0" />
          <span>{{ Math.abs(revenueStats.revenueGrowth || 0) }}%</span>
          <span class="text-slate-400 dark:text-slate-500 font-normal hidden sm:inline">vs last mo</span>
        </div>
        <span class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ revenueStats.totalTransactions }} txns</span>
      </div>
    </div>

    <!-- CARD 2: ADMIN PLATFORM FEES (Goldenrod #E0B50F) -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#262638] hover:bg-slate-50 dark:hover:bg-[#2C2C40] border border-slate-200 dark:border-white/[0.08] hover:border-[#E0B50F]/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Platform Net Earnings</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-amber-700 dark:text-[#E0B50F] tracking-tight truncate">
            {{ formatCurrency(revenueStats.totalAdminFees) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#E0B50F]/10 border border-[#E0B50F]/20 flex items-center justify-center text-amber-600 dark:text-[#E0B50F] group-hover:scale-105 transition-transform flex-shrink-0">
          <Award class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-between text-xs">
        <span class="text-slate-500 dark:text-slate-400 truncate">Director Payout:</span>
        <span class="font-semibold text-slate-700 dark:text-slate-200 font-mono">{{ formatCurrency(revenueStats.totalDirectorAmount) }}</span>
      </div>
    </div>

    <!-- CARD 3: TOTAL ACTIVE COMMUNITY (Azure #3B8FF3) -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#262638] hover:bg-slate-50 dark:hover:bg-[#2C2C40] border border-slate-200 dark:border-white/[0.08] hover:border-[#3B8FF3]/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Community & Users</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white tracking-tight truncate">
            {{ formatNumber(userStats.total) }}
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 flex items-center justify-center text-[#3B8FF3] group-hover:scale-105 transition-transform flex-shrink-0">
          <Users class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-1.5 truncate">
          <span class="text-[#2B9B95] dark:text-[#34B1AA] font-semibold">{{ formatNumber(userStats.active) }} Active</span>
          <span>&bull;</span>
          <span class="text-[#3B8FF3] truncate">{{ formatNumber(userStats.referees) }} Referees</span>
        </div>
        <span class="text-amber-600 dark:text-[#E0B50F] font-mono">+{{ userStats.growth }}%</span>
      </div>
    </div>

    <!-- CARD 4: CAMPS & GAME SLOTS (Peach Coral #F29F67) -->
    <div class="relative group overflow-hidden rounded-lg bg-white dark:bg-[#262638] hover:bg-slate-50 dark:hover:bg-[#2C2C40] border border-slate-200 dark:border-white/[0.08] hover:border-[#F29F67]/40 p-4 sm:p-5 transition-all duration-150 shadow-xs">
      <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 min-w-0">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 font-medium block truncate">Camp Operations</span>
          <div class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white tracking-tight truncate">
            {{ formatNumber(campStats.total) }} Camps
          </div>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center justify-center text-[#E08A50] dark:text-[#F29F67] group-hover:scale-105 transition-transform flex-shrink-0">
          <Tent class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>
      <div class="mt-3.5 pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-center gap-1.5 truncate">
          <span class="text-[#E08A50] dark:text-[#F29F67] font-semibold">{{ campStats.active }} Active</span>
          <span>&bull;</span>
          <span class="truncate">{{ campStats.upcoming }} Upcm</span>
        </div>
        <span class="text-[#2B9B95] dark:text-[#34B1AA] font-mono">{{ gameSlotStats.utilizationRate }}% Fill</span>
      </div>
    </div>

  </div>
</template>
