<script setup>
import { Link } from '@inertiajs/vue3';
import { CreditCard, Tag, Calendar, User, Tent, DollarSign, Building } from 'lucide-vue-next';

defineProps({
  payments: {
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
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm border-collapse">
      <thead>
        <tr class="border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/70 dark:bg-[#262638]/50 text-slate-500 dark:text-slate-400 font-mono text-[11px] uppercase tracking-wider">
          <th class="py-3 px-4">#</th>
          <th class="py-3 px-4">Camp Details</th>
          <th class="py-3 px-4">Referee / User</th>
          <th class="py-3 px-4 text-right">Gross Amount</th>
          <th class="py-3 px-4 text-right">Admin Fee</th>
          <th class="py-3 px-4 text-right">Director Share</th>
          <th class="py-3 px-4 text-right">Discount</th>
          <th class="py-3 px-4">Coupon</th>
          <th class="py-3 px-4 text-right">Paid Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
        <tr
          v-for="(item, idx) in payments.data"
          :key="item.id"
          class="hover:bg-slate-50/60 dark:hover:bg-[#262638]/40 transition-colors"
        >
          <!-- Index -->
          <td class="py-3.5 px-4 font-mono text-slate-400 dark:text-slate-500">
            {{ ((payments.current_page - 1) * payments.per_page) + idx + 1 }}
          </td>

          <!-- Camp Name -->
          <td class="py-3.5 px-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-md bg-amber-500/10 dark:bg-amber-500/20 text-amber-500 flex items-center justify-center flex-shrink-0">
                <Tent class="w-3.5 h-3.5" />
              </div>
              <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[200px]">
                {{ item.camp?.camp_name || 'N/A' }}
              </span>
            </div>
          </td>

          <!-- Referee Details -->
          <td class="py-3.5 px-4">
            <div v-if="item.referee" class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-full bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold text-xs flex-shrink-0">
                {{ item.referee.first_name?.[0] || 'U' }}
              </div>
              <div class="flex flex-col">
                <span class="font-medium text-slate-800 dark:text-slate-200">
                  {{ item.referee.first_name }} {{ item.referee.last_name }}
                </span>
                <span class="text-[11px] text-slate-400 font-mono">
                  {{ item.referee.email }}
                </span>
              </div>
            </div>
            <span v-else class="text-slate-400 italic">User Removed</span>
          </td>

          <!-- Gross Amount -->
          <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
            {{ formatCurrency(item.amount) }}
          </td>

          <!-- Admin Fee -->
          <td class="py-3.5 px-4 text-right font-mono font-medium text-indigo-600 dark:text-indigo-400">
            {{ formatCurrency(item.admin_fee) }}
          </td>

          <!-- Director Share -->
          <td class="py-3.5 px-4 text-right font-mono text-slate-600 dark:text-slate-300">
            {{ formatCurrency(item.director_amount) }}
          </td>

          <!-- Discount Amount -->
          <td class="py-3.5 px-4 text-right font-mono text-amber-600 dark:text-amber-400">
            {{ item.discount_amount > 0 ? formatCurrency(item.discount_amount) : '-' }}
          </td>

          <!-- Coupon Code -->
          <td class="py-3.5 px-4">
            <span 
              v-if="item.coupon?.code" 
              class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-mono font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"
            >
              <Tag class="w-3 h-3" />
              {{ item.coupon.code }}
            </span>
            <span v-else class="text-slate-400 font-mono text-xs">-</span>
          </td>

          <!-- Paid Date -->
          <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
            {{ formatDate(item.paid_at) }}
          </td>
        </tr>

        <!-- Empty State -->
        <tr v-if="!payments.data || payments.data.length === 0">
          <td colspan="9" class="py-12 text-center text-slate-400 dark:text-slate-500">
            <CreditCard class="w-8 h-8 mx-auto mb-2 opacity-50" />
            No successful payments found matching your filter criteria.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
