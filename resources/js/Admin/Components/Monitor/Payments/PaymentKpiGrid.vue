<script setup>
import { computed } from 'vue';
import {
  DollarSign,
  Building2,
  Tag,
  Users,
  TrendingUp,
  Coins,
  Percent,
  UserCheck
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      totalRevenue: 0,
      totalAdminFees: 0,
      totalDirectorAmount: 0,
      totalDiscountGiven: 0,
      transactionCount: 0,
      pendingAttempts: 0,
      failedAttempts: 0,
      activeCoupons: 0,
      totalRegistrations: 0,
      totalCheckedIn: 0,
      totalCamps: 0,
      activeCamps: 0,
    }),
  },
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(val || 0);
};

const formatNumber = (val) => {
  return new Intl.NumberFormat('en-US').format(val || 0);
};

const cards = computed(() => [
  {
    key: 'revenue',
    label: 'Total Gross Revenue',
    value: formatCurrency(props.stats.totalRevenue),
    subText: `${formatNumber(props.stats.transactionCount)} transactions completed`,
    icon: DollarSign,
    bgGradient: 'from-emerald-500/10 via-teal-500/5 to-transparent',
    iconGradient: 'from-emerald-500 to-teal-600',
    iconColor: 'text-emerald-500',
    borderColor: 'hover:border-emerald-500/40',
    accentDot: 'bg-emerald-500',
    statBadge: 'Revenue Flow',
  },
  {
    key: 'admin_fees',
    label: 'Admin Fees Collected',
    value: formatCurrency(props.stats.totalAdminFees),
    subText: `Director Payouts: ${formatCurrency(props.stats.totalDirectorAmount)}`,
    icon: Building2,
    bgGradient: 'from-indigo-500/10 via-purple-500/5 to-transparent',
    iconGradient: 'from-indigo-500 to-purple-600',
    iconColor: 'text-indigo-500',
    borderColor: 'hover:border-indigo-500/40',
    accentDot: 'bg-indigo-500',
    statBadge: 'Platform Share',
  },
  {
    key: 'discounts',
    label: 'Discounts Granted',
    value: formatCurrency(props.stats.totalDiscountGiven),
    subText: `${formatNumber(props.stats.activeCoupons)} active promotional coupons`,
    icon: Tag,
    bgGradient: 'from-amber-500/10 via-orange-500/5 to-transparent',
    iconGradient: 'from-amber-500 to-orange-600',
    iconColor: 'text-amber-500',
    borderColor: 'hover:border-amber-500/40',
    accentDot: 'bg-amber-500',
    statBadge: 'Promotions',
  },
  {
    key: 'registrations',
    label: 'Referee Registrations',
    value: formatNumber(props.stats.totalRegistrations),
    subText: `${formatNumber(props.stats.totalCheckedIn)} checked-in · ${formatNumber(props.stats.activeCamps)} active camps`,
    icon: Users,
    bgGradient: 'from-blue-500/10 via-cyan-500/5 to-transparent',
    iconGradient: 'from-blue-500 to-cyan-600',
    iconColor: 'text-blue-500',
    borderColor: 'hover:border-blue-500/40',
    accentDot: 'bg-blue-500',
    statBadge: 'Attendance',
  },
]);
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
    <div
      v-for="card in cards"
      :key="card.key"
      :class="[
        'group relative overflow-hidden rounded-xl border transition-all duration-200 p-5',
        'bg-white dark:bg-[#1E1E2C] border-slate-200 dark:border-white/[0.08]',
        'hover:shadow-lg dark:hover:shadow-black/30 hover:-translate-y-0.5',
        card.borderColor
      ]"
    >
      <!-- Background Ambient Glow -->
      <div 
        class="absolute -right-10 -bottom-10 w-32 h-32 rounded-full blur-2xl opacity-30 group-hover:opacity-60 transition-opacity bg-gradient-to-br"
        :class="card.bgGradient"
      ></div>

      <div class="relative z-10 flex flex-col justify-between h-full space-y-3">
        <!-- Top Row: Icon & Badge -->
        <div class="flex items-center justify-between">
          <div 
            class="w-10 h-10 rounded-lg flex items-center justify-center shadow-md bg-gradient-to-br text-white transition-transform group-hover:scale-105"
            :class="card.iconGradient"
          >
            <component :is="card.icon" class="w-5 h-5" />
          </div>
          <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-mono font-semibold uppercase tracking-wider bg-slate-100 dark:bg-[#262638] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/[0.06]">
            <span class="w-1.5 h-1.5 rounded-full" :class="card.accentDot"></span>
            {{ card.statBadge }}
          </div>
        </div>

        <!-- Middle: Metric Value & Label -->
        <div>
          <p class="text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400 font-mono">
            {{ card.label }}
          </p>
          <h3 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white mt-1">
            {{ card.value }}
          </h3>
        </div>

        <!-- Bottom: Subtext -->
        <div class="pt-2 border-t border-slate-100 dark:border-white/[0.06] flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
          <span class="truncate">{{ card.subText }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
