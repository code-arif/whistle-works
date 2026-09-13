<script setup>
import { CreditCard, ChevronRight } from 'lucide-vue-next';

defineProps({
  recentPayments: {
    type: Array,
    default: () => [],
  },
});

const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};
</script>

<template>
  <div class="rounded-lg bg-white dark:bg-[#111827]/80 border border-slate-200 dark:border-slate-800/80 p-5 sm:p-6 backdrop-blur-xl shadow-xs flex flex-col justify-between h-full transition-colors duration-150">
    <div>
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">Live Transactions</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Real-time payment records</p>
        </div>
        <a href="/admin/monitor" class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center gap-0.5 transition-colors">
          <span>Monitor</span>
          <ChevronRight class="w-3.5 h-3.5" />
        </a>
      </div>

      <div class="space-y-2.5">
        <div 
          v-for="pmt in recentPayments" 
          :key="pmt.id"
          class="p-2.5 sm:p-3 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800/70 hover:border-slate-300 dark:hover:border-slate-700 transition-all flex items-center justify-between gap-3"
        >
          <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
            <div class="w-8 h-8 rounded-md bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 flex-shrink-0">
              <CreditCard class="w-4 h-4" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-semibold text-slate-900 dark:text-slate-200 truncate">
                {{ pmt.referee ? `${pmt.referee.first_name || ''} ${pmt.referee.last_name || ''}`.trim() : 'Referee Member' }}
              </p>
              <p class="text-[10px] text-slate-500 truncate">
                {{ pmt.camp?.camp_name || 'Camp Registration' }}
              </p>
            </div>
          </div>
          <div class="text-right flex-shrink-0">
            <p class="text-xs font-bold font-mono text-emerald-600 dark:text-emerald-400">+{{ formatCurrency(pmt.amount) }}</p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ pmt.paid_at ? new Date(pmt.paid_at).toLocaleDateString() : 'Paid' }}</p>
          </div>
        </div>

        <div v-if="recentPayments.length === 0" class="py-6 text-center text-slate-400 dark:text-slate-500 text-xs">
          No recent payment transactions.
        </div>
      </div>
    </div>

    <div class="mt-4 pt-3.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 font-mono">
      <span>Stripe Webhooks</span>
      <span class="text-emerald-600 dark:text-emerald-400 font-semibold">100% Synced</span>
    </div>
  </div>
</template>
