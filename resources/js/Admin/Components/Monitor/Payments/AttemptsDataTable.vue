<script setup>
import { Tent, Tag, AlertTriangle, CheckCircle2, XCircle, Clock } from 'lucide-vue-next';

defineProps({
  attempts: {
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

const getStatusBadge = (status) => {
  switch (status) {
    case 'completed':
    case 'succeeded':
      return { label: 'Completed', bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20', icon: CheckCircle2 };
    case 'pending':
      return { label: 'Pending', bg: 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20', icon: Clock };
    case 'failed':
      return { label: 'Failed', bg: 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20', icon: XCircle };
    case 'cancelled':
    default:
      return { label: status || 'Cancelled', bg: 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20', icon: AlertTriangle };
  }
};
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm border-collapse">
      <thead>
        <tr class="border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/70 dark:bg-[#262638]/50 text-slate-500 dark:text-slate-400 font-mono text-[11px] uppercase tracking-wider">
          <th class="py-3 px-4">#</th>
          <th class="py-3 px-4">Camp Name</th>
          <th class="py-3 px-4">Referee / User</th>
          <th class="py-3 px-4 text-right">Attempt Amount</th>
          <th class="py-3 px-4">Attempt Status</th>
          <th class="py-3 px-4">Coupon</th>
          <th class="py-3 px-4 text-right">Created Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
        <tr
          v-for="(item, idx) in attempts.data"
          :key="item.id"
          class="hover:bg-slate-50/60 dark:hover:bg-[#262638]/40 transition-colors"
        >
          <!-- Index -->
          <td class="py-3.5 px-4 font-mono text-slate-400 dark:text-slate-500">
            {{ ((attempts.current_page - 1) * attempts.per_page) + idx + 1 }}
          </td>

          <!-- Camp Name -->
          <td class="py-3.5 px-4">
            <div class="flex items-center gap-2">
              <div class="w-7 h-7 rounded-md bg-amber-500/10 text-amber-500 flex items-center justify-center flex-shrink-0">
                <Tent class="w-3.5 h-3.5" />
              </div>
              <span class="font-semibold text-slate-900 dark:text-white truncate max-w-[220px]">
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

          <!-- Attempt Amount -->
          <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white">
            {{ formatCurrency(item.amount) }}
          </td>

          <!-- Status Badge -->
          <td class="py-3.5 px-4">
            <span 
              :class="[
                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border',
                getStatusBadge(item.status).bg
              ]"
            >
              <component :is="getStatusBadge(item.status).icon" class="w-3.5 h-3.5" />
              {{ getStatusBadge(item.status).label }}
            </span>
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

          <!-- Created Date -->
          <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
            {{ formatDate(item.created_at) }}
          </td>
        </tr>

        <!-- Empty State -->
        <tr v-if="!attempts.data || attempts.data.length === 0">
          <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
            <AlertTriangle class="w-8 h-8 mx-auto mb-2 opacity-50" />
            No payment attempts found matching your filter criteria.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
