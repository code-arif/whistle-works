<script setup>
import { computed } from 'vue';
import {
  Ticket,
  CheckCircle2,
  TrendingUp,
  DollarSign
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      totalCoupons: 0,
      activeCoupons: 0,
      inactiveCoupons: 0,
      totalUses: 0,
      totalDiscountGiven: 0,
      totalRevenueGenerated: 0,
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
    key: 'total',
    label: 'Total Coupons',
    value: formatNumber(props.stats.totalCoupons),
    valueColor: 'text-slate-900 dark:text-white',
    iconBoxClass: 'bg-[#F29F67]/10 border border-[#F29F67]/20 text-[#F29F67]',
    icon: Ticket,
  },
  {
    key: 'active',
    label: 'Active Coupons',
    value: formatNumber(props.stats.activeCoupons),
    valueColor: 'text-[#2B9B95] dark:text-[#34B1AA]',
    iconBoxClass: 'bg-[#34B1AA]/10 border border-[#34B1AA]/20 text-[#34B1AA]',
    icon: CheckCircle2,
  },
  {
    key: 'redemptions',
    label: 'Total Redemptions',
    value: formatNumber(props.stats.totalUses),
    valueColor: 'text-[#3B8FF3]',
    iconBoxClass: 'bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 text-[#3B8FF3]',
    icon: TrendingUp,
  },
  {
    key: 'discounts',
    label: 'Total Discounts Given',
    value: formatCurrency(props.stats.totalDiscountGiven),
    valueColor: 'text-amber-600 dark:text-[#E0B50F]',
    iconBoxClass: 'bg-[#E0B50F]/10 border border-[#E0B50F]/20 text-[#E0B50F]',
    icon: DollarSign,
  },
]);
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    <div
      v-for="card in cards"
      :key="card.key"
      class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between transition-all hover:border-slate-300 dark:hover:border-white/[0.15]"
    >
      <div class="space-y-1">
        <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">{{ card.label }}</p>
        <p class="text-xl sm:text-2xl font-bold font-display" :class="card.valueColor">{{ card.value }}</p>
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
