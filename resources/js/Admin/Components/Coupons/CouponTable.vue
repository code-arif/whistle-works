<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Ticket,
  Percent,
  DollarSign,
  Copy,
  Check,
  Edit,
  Trash2,
  MapPin,
  Users,
  Globe,
  Calendar
} from 'lucide-vue-next';

const props = defineProps({
  coupons: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['edit', 'delete']);

const copiedId = ref(null);

const copyCode = (code, id) => {
  navigator.clipboard.writeText(code);
  copiedId.value = id;
  setTimeout(() => {
    copiedId.value = null;
  }, 2000);
};

const toggleStatus = (id) => {
  router.post(
    `/admin/v2/coupons/${id}/status`,
    {},
    {
      preserveScroll: true,
    }
  );
};

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
          <th class="py-3 px-4">Code</th>
          <th class="py-3 px-4">Discount</th>
          <th class="py-3 px-4">Usage & Capacity</th>
          <th class="py-3 px-4">Scope & Target</th>
          <th class="py-3 px-4">Expiration</th>
          <th class="py-3 px-4 text-center">Status</th>
          <th class="py-3 px-4 text-right">Actions</th>
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

          <!-- Code with Copy Action -->
          <td class="py-3.5 px-4 font-mono font-bold">
            <div class="inline-flex items-center gap-2">
              <span class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 text-xs tracking-wider">
                {{ item.code }}
              </span>
              <button
                type="button"
                @click="copyCode(item.code, item.id)"
                class="p-1 rounded hover:bg-slate-100 dark:hover:bg-[#262638] text-slate-400 hover:text-slate-700 dark:hover:text-white transition-colors cursor-pointer"
                title="Copy coupon code"
              >
                <Check v-if="copiedId === item.id" class="w-3.5 h-3.5 text-emerald-500" />
                <Copy v-else class="w-3.5 h-3.5" />
              </button>
            </div>
          </td>

          <!-- Discount Type & Value -->
          <td class="py-3.5 px-4 font-medium text-slate-800 dark:text-slate-200">
            <span v-if="item.type === 'percentage'" class="inline-flex items-center gap-1 font-mono font-bold text-indigo-600 dark:text-indigo-400">
              <Percent class="w-3.5 h-3.5" />
              {{ item.discount_value }}% OFF
            </span>
            <span v-else class="inline-flex items-center gap-1 font-mono font-bold text-emerald-600 dark:text-emerald-400">
              <DollarSign class="w-3.5 h-3.5" />
              {{ formatCurrency(item.discount_value) }} OFF
            </span>
          </td>

          <!-- Usage Progress & Capacity -->
          <td class="py-3.5 px-4">
            <div class="flex flex-col space-y-1 max-w-[130px]">
              <div class="flex items-center justify-between text-xs font-mono">
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ item.used_count }}</span>
                <span class="text-slate-400">/ {{ item.max_uses || '∞' }}</span>
              </div>
              <div class="w-full bg-slate-100 dark:bg-white/[0.08] rounded-full h-1.5 overflow-hidden">
                <div
                  class="bg-amber-500 h-1.5 rounded-full transition-all"
                  :style="{ width: `${item.max_uses ? Math.min(100, (item.used_count / item.max_uses) * 100) : 100}%` }"
                ></div>
              </div>
            </div>
          </td>

          <!-- Scope -->
          <td class="py-3.5 px-4">
            <div class="flex flex-wrap items-center gap-1.5">
              <!-- Global Badge -->
              <span
                v-if="!item.camp_id && (!item.referees || item.referees.length === 0)"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-[#262638] text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-white/[0.06]"
              >
                <Globe class="w-3 h-3 text-slate-400" />
                Global (All)
              </span>

              <!-- Camp Scope Badge -->
              <span
                v-if="item.camp"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-500/20"
                :title="`Camp: ${item.camp.camp_name}`"
              >
                <MapPin class="w-3 h-3" />
                <span class="truncate max-w-[120px]">{{ item.camp.camp_name }}</span>
              </span>

              <!-- Referees Target Scope Badge -->
              <span
                v-if="item.referees && item.referees.length > 0"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/20"
                :title="item.referees.map(r => r.name || (r.first_name + ' ' + r.last_name)).join(', ')"
              >
                <Users class="w-3 h-3" />
                <span>{{ item.referees.length }} Referee(s)</span>
              </span>
            </div>
          </td>

          <!-- Expiration Date -->
          <td class="py-3.5 px-4 font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
            {{ formatDate(item.expires_at) }}
          </td>

          <!-- Status Toggle Switch -->
          <td class="py-3.5 px-4 text-center">
            <button
              type="button"
              @click="toggleStatus(item.id)"
              :class="[
                'relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                item.status === 'active' ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700'
              ]"
              title="Toggle status"
            >
              <span
                :class="[
                  'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                  item.status === 'active' ? 'translate-x-5' : 'translate-x-0'
                ]"
              />
            </button>
          </td>

          <!-- Actions -->
          <td class="py-3.5 px-4 text-right whitespace-nowrap">
            <div class="inline-flex items-center gap-1">
              <button
                type="button"
                @click="emit('edit', item)"
                class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer"
                title="Edit Coupon"
              >
                <Edit class="w-4 h-4" />
              </button>
              <button
                type="button"
                @click="emit('delete', item)"
                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer"
                title="Delete Coupon"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </td>
        </tr>

        <!-- Empty State -->
        <tr v-if="!coupons.data || coupons.data.length === 0">
          <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
            <Ticket class="w-8 h-8 mx-auto mb-2 opacity-50" />
            No promotional coupons found matching your search or filters.
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
