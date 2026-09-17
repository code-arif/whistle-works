<script setup>
import { computed } from 'vue';
import {
  DollarSign,
  Building2,
  Tag,
  Users
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
    subText: `${formatNumber(props.stats.transactionCount)} transactions`,
    valueColor: 'text-[#2B9B95] dark:text-[#34B1AA]',
    iconBoxClass: 'bg-[#34B1AA]/10 border border-[#34B1AA]/20 text-[#34B1AA]',
    icon: DollarSign,
  },
  {
    key: 'admin_fees',
    label: 'Admin Fees Collected',
    value: formatCurrency(props.stats.totalAdminFees),
    subText: `Director: ${formatCurrency(props.stats.totalDirectorAmount)}`,
    valueColor: 'text-slate-900 dark:text-white',
    iconBoxClass: 'bg-[#F29F67]/10 border border-[#F29F67]/20 text-[#F29F67]',
    icon: Building2,
  },
  {
    key: 'discounts',
    label: 'Discounts Granted',
    value: formatCurrency(props.stats.totalDiscountGiven),
    subText: `${formatNumber(props.stats.activeCoupons)} active coupons`,
    valueColor: 'text-amber-600 dark:text-[#E0B50F]',
    iconBoxClass: 'bg-[#E0B50F]/10 border border-[#E0B50F]/20 text-[#E0B50F]',
    icon: Tag,
  },
  {
    key: 'registrations',
    label: 'Referee Registrations',
    value: formatNumber(props.stats.totalRegistrations),
    subText: `${formatNumber(props.stats.totalCheckedIn)} checked-in`,
    valueColor: 'text-[#3B8FF3]',
    iconBoxClass: 'bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 text-[#3B8FF3]',
    icon: Users,
  },
]);
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
    <div
      v-for="card in cards"
      :key="card.key"
      class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between transition-all hover:border-slate-300 dark:hover:border-white/[0.15]"
    >
      <div class="space-y-1 min-w-0">
        <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider truncate">{{ card.label }}</p>
        <p class="text-xl sm:text-2xl font-bold font-display" :class="card.valueColor">{{ card.value }}</p>
        <p class="text-[11px] text-slate-400 font-mono truncate">{{ card.subText }}</p>
      </div>
      <div 
        class="w-9 h-9 rounded-md flex items-center justify-center flex-shrink-0"
        :class="card.iconBoxClass"
      >
        <component :is="card.icon" class="w-4 h-4 sm:w-5 sm:h-5" />
      </div>
    </div>
  </div>
</template>
