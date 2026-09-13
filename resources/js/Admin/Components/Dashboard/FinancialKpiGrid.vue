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
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    <!-- CARD 1: TOTAL REVENUE -->
    <div class="relative group overflow-hidden rounded-3xl bg-[#111827]/80 hover:bg-[#111827] border border-slate-800/80 hover:border-emerald-500/40 p-6 transition-all duration-300 shadow-lg hover:shadow-glow-emerald">
      <div class="flex items-start justify-between">
        <div class="space-y-2">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-medium">Total Gross Revenue</span>
          <div class="text-2xl sm:text-3xl font-bold font-display text-white tracking-tight">
            {{ formatCurrency(revenueStats.totalRevenue) }}
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform">
          <DollarSign class="w-6 h-6" />
        </div>
      </div>
      <div class="mt-4 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs">
        <div class="flex items-center gap-1.5 font-semibold" :class="revenueStats.revenueGrowth >= 0 ? 'text-emerald-400' : 'text-rose-400'">
          <component :is="revenueStats.revenueGrowth >= 0 ? TrendingUp : TrendingDown" class="w-4 h-4" />
          <span>{{ Math.abs(revenueStats.revenueGrowth || 0) }}%</span>
          <span class="text-slate-500 font-normal">vs last month</span>
        </div>
        <span class="text-slate-400 font-mono text-[11px]">{{ revenueStats.totalTransactions }} txns</span>
      </div>
    </div>

    <!-- CARD 2: ADMIN PLATFORM FEES -->
    <div class="relative group overflow-hidden rounded-3xl bg-[#111827]/80 hover:bg-[#111827] border border-slate-800/80 hover:border-amber-500/40 p-6 transition-all duration-300 shadow-lg hover:shadow-glow-gold">
      <div class="flex items-start justify-between">
        <div class="space-y-2">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-medium">Platform Net Earnings</span>
          <div class="text-2xl sm:text-3xl font-bold font-display text-amber-300 tracking-tight">
            {{ formatCurrency(revenueStats.totalAdminFees) }}
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 group-hover:scale-110 transition-transform">
          <Award class="w-6 h-6" />
        </div>
      </div>
      <div class="mt-4 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs">
        <span class="text-slate-400">Director Payout:</span>
        <span class="font-semibold text-slate-200">{{ formatCurrency(revenueStats.totalDirectorAmount) }}</span>
      </div>
    </div>

    <!-- CARD 3: TOTAL ACTIVE COMMUNITY -->
    <div class="relative group overflow-hidden rounded-3xl bg-[#111827]/80 hover:bg-[#111827] border border-slate-800/80 hover:border-indigo-500/40 p-6 transition-all duration-300 shadow-lg hover:shadow-glow-indigo">
      <div class="flex items-start justify-between">
        <div class="space-y-2">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-medium">Community & Users</span>
          <div class="text-2xl sm:text-3xl font-bold font-display text-white tracking-tight">
            {{ formatNumber(userStats.total) }}
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 group-hover:scale-110 transition-transform">
          <Users class="w-6 h-6" />
        </div>
      </div>
      <div class="mt-4 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs text-slate-400">
        <div class="flex items-center gap-2">
          <span class="text-emerald-400 font-semibold">{{ formatNumber(userStats.active) }} Active</span>
          <span>&bull;</span>
          <span class="text-indigo-300">{{ formatNumber(userStats.referees) }} Referees</span>
        </div>
        <span class="text-amber-400 font-mono">+{{ userStats.growth }}%</span>
      </div>
    </div>

    <!-- CARD 4: CAMPS & GAME SLOTS -->
    <div class="relative group overflow-hidden rounded-3xl bg-[#111827]/80 hover:bg-[#111827] border border-slate-800/80 hover:border-violet-500/40 p-6 transition-all duration-300 shadow-lg">
      <div class="flex items-start justify-between">
        <div class="space-y-2">
          <span class="text-xs font-mono uppercase tracking-wider text-slate-400 font-medium">Camp Operations</span>
          <div class="text-2xl sm:text-3xl font-bold font-display text-white tracking-tight">
            {{ formatNumber(campStats.total) }} Camps
          </div>
        </div>
        <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 group-hover:scale-110 transition-transform">
          <Tent class="w-6 h-6" />
        </div>
      </div>
      <div class="mt-4 pt-4 border-t border-slate-800/60 flex items-center justify-between text-xs text-slate-400">
        <div class="flex items-center gap-2">
          <span class="text-violet-300 font-semibold">{{ campStats.active }} Active</span>
          <span>&bull;</span>
          <span>{{ campStats.upcoming }} Upcoming</span>
        </div>
        <span class="text-emerald-400 font-mono">{{ gameSlotStats.utilizationRate }}% Slot Fill</span>
      </div>
    </div>

  </div>
</template>
