<script setup>
import { Tent, UserCheck, Clock, ShieldCheck, User } from 'lucide-vue-next';

defineProps({
  registrations: {
    type: Object,
    required: true,
  },
});

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
  if (status === 'checked_in') {
    return {
      label: 'Checked In',
      bg: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
      icon: UserCheck
    };
  }
  return {
    label: 'Registered',
    bg: 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
    icon: Clock
  };
};
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-xs sm:text-sm border-collapse">
      <thead>
        <tr class="border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/70 dark:bg-[#262638]/50 text-slate-500 dark:text-slate-400 font-mono text-[11px] uppercase tracking-wider">
          <th class="py-3 px-4">#</th>
          <th class="py-3 px-4">Camp Name</th>
          <th class="py-3 px-4">Referee Name</th>
          <th class="py-3 px-4">Attendance Status</th>
          <th class="py-3 px-4">Registered Date</th>
          <th class="py-3 px-4 text-right">Checked-In Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04]">
        <tr
          v-for="(item, idx) in registrations.data"
          :key="item.id"
          class="hover:bg-slate-50/60 dark:hover:bg-[#262638]/40 transition-colors"
        >
          <!-- Index -->
          <td class="py-3.5 px-4 font-mono text-slate-400 dark:text-slate-500">
            {{ ((registrations.current_page - 1) * registrations.per_page) + idx + 1 }}
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
              <div class="w-7 h-7 rounded-full bg-blue-500/10 text-blue-500 flex items-center justify-center font-bold text-xs flex-shrink-0">
                {{ item.referee.first_name?.[0] || 'R' }}
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
            <span v-else class="text-slate-400 italic">Referee Removed</span>
          </td>

          <!-- Registration Status -->
          <td class="py-3.5 px-4">
            <span 
              :class="[
                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border',
                getStatusBadge(item.registration_status).bg
              ]"
            >
              <component :is="getStatusBadge(item.registration_status).icon" class="w-3.5 h-3.5" />
              {{ getStatusBadge(item.registration_status).label }}
            </span>
          </td>

          <!-- Registered Date -->
          <td class="py-3.5 px-4 font-mono text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
            {{ formatDate(item.registered_at || item.created_at) }}
          </td>

          <!-- Checked-In Date -->
          <td class="py-3.5 px-4 text-right font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
            <span v-if="item.checked_in_at" class="text-emerald-600 dark:text-emerald-400 font-bold">
              {{ formatDate(item.checked_in_at) }}
            </span>
            <span v-else class="text-slate-400 italic">Not Checked In</span>
          </td>
        </tr>

        <!-- Empty State -->
        <tr v-if="!registrations.data || registrations.data.length === 0">
          <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
            <UserCheck class="w-8 h-8 mx-auto mb-2 opacity-50" />
            No referee check-in or registration records found matching your query.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
