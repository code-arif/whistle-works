<script setup>
import { Ticket, Percent, DollarSign, CheckCircle2, XCircle, Calendar } from 'lucide-vue-next';

defineProps({
  coupons: {
    type: Object,
    required: true,
  },
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2,
  }).format(val || 0);
};

const formatDate = (dateStr) => {
  if (!dateStr) return 'Never';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
};
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm border-collapse">
      <thead>
        <tr class="border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/70 dark:bg-[#262638]/50 text-slate-500 dark:text-slate-400 font-mono text-[11px] uppercase tracking-wider">
          <th class="py-3 px-4">#</th>
          <th class="py-3 px-4">Coupon Code</th>
          <th class="py-3 px-4">Discount Type</th>
          <th class="py-3 px-4">Usage Count</th>
          <th class="py-3 px-4 text-right">Revenue Generated</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4 text-right">Expiration Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
        <tr
          v-for="(item, idx) in coupons.data"
          :key="item.id"
          class="hover:bg-slate-50/60 dark:hover:bg-[#262638]/40 transition-colors"
        >
          <!-- Index -->
          <td class="py-3.5 px-4 font-mono text-slate-400 dark:text-slate-500">
            {{ ((coupons.current_page - 1) * coupons.per_page) + idx + 1 }}
          </td>

          <!-- Coupon Code -->
          <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
              <Ticket class="w-3.5 h-3.5 text-amber-500" />
              {{ item.code }}
            </span>
          </td>

          <!-- Discount Type & Value -->
          <td class="py-3.5 px-4 font-medium text-slate-700 dark:text-slate-300">
            <span v-if="item.type === 'percentage'" class="inline-flex items-center gap-1">
              <Percent class="w-3.5 h-3.5 text-indigo-500" />
              {{ item.discount_value }}% OFF
            </span>
            <span v-else class="inline-flex items-center gap-1">
              <DollarSign class="w-3.5 h-3.5 text-emerald-500" />
              {{ formatCurrency(item.discount_value) }} OFF
            </span>
          </td>

          <!-- Usage Progress -->
          <td class="py-3.5 px-4 font-mono text-xs font-semibold text-slate-800 dark:text-slate-200">
            {{ item.used_count }} / {{ item.max_uses || '∞' }}
          </td>

          <!-- Revenue Generated -->
          <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
            {{ formatCurrency(item.revenue_generated) }}
          </td>

          <!-- Status -->
          <td class="py-3.5 px-4">
            <span 
              :class="[
                'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold border',
                item.status === 'active' 
                  ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
                  : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20'
              ]"
            >
              <CheckCircle2 v-if="item.status === 'active'" class="w-3 h-3" />
              <XCircle v-else class="w-3 h-3" />
              {{ item.status === 'active' ? 'Active' : 'Inactive' }}
            </span>
          </td>

          <!-- Expiration Date -->
          <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
            {{ formatDate(item.expires_at) }}
          </td>
        </tr>

        <!-- Empty State -->
        <tr v-if="!coupons.data || coupons.data.length === 0">
          <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
            <Ticket class="w-8 h-8 mx-auto mb-2 opacity-50" />
            No promotional coupons found matching your search.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
