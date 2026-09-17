<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Dropdown from '../Common/Dropdown.vue';
import {
  X,
  Ticket,
  Percent,
  DollarSign,
  Calendar,
  Sparkles,
  RefreshCw,
  Shield,
  Info,
  MapPin,
  Users,
  Search,
  Check,
  Tag
} from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  coupon: {
    type: Object,
    default: null,
  },
  camps: {
    type: Array,
    default: () => [],
  },
  referees: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close']);

const activeTab = ref('basic');
const refereeSearch = ref('');

const typeOptions = [
  { label: 'Percentage Discount (%)', value: 'percentage' },
  { label: 'Fixed Dollar Amount ($)', value: 'fixed' },
];

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const campOptions = computed(() => [
  { label: 'All Camps (Global)', value: '' },
  ...props.camps.map((camp) => ({
    label: camp.camp_name,
    value: camp.id,
  })),
]);

const isEditing = computed(() => !!props.coupon?.id);

const form = useForm({
  id: null,
  code: '',
  type: 'percentage',
  discount_value: 10,
  max_uses: null,
  camp_id: '',
  referee_ids: [],
  expires_at: '',
  status: 'active',
});

// Sync form with coupon prop on open/edit
watch(
  () => props.coupon,
  (coupon) => {
    if (coupon) {
      form.id = coupon.id;
      form.code = coupon.code;
      form.type = coupon.type;
      form.discount_value = coupon.discount_value;
      form.max_uses = coupon.max_uses;
      form.camp_id = coupon.camp_id || '';
      form.referee_ids = coupon.referees ? coupon.referees.map((r) => r.id) : [];
      form.expires_at = coupon.expires_at ? coupon.expires_at.split('T')[0] : '';
      form.status = coupon.status;
    } else {
      form.reset();
      form.id = null;
      form.code = '';
      form.type = 'percentage';
      form.discount_value = 10;
      form.max_uses = null;
      form.camp_id = '';
      form.referee_ids = [];
      form.expires_at = '';
      form.status = 'active';
    }
    activeTab.value = 'basic';
  },
  { immediate: true }
);

// Auto-generate random promotional code
const generateCode = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let randomStr = '';
  for (let i = 0; i < 6; i++) {
    randomStr += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  form.code = `CPN-${randomStr}`;
};

// Filtered referees list based on search
const filteredReferees = computed(() => {
  if (!refereeSearch.value.trim()) return props.referees;
  const q = refereeSearch.value.toLowerCase();
  return props.referees.filter(
    (r) => r.name.toLowerCase().includes(q) || r.email.toLowerCase().includes(q)
  );
});

// Selected referees collection
const selectedReferees = computed(() => {
  return props.referees.filter((r) => form.referee_ids.includes(r.id));
});

// Toggle referee selection
const toggleReferee = (refereeId) => {
  const index = form.referee_ids.indexOf(refereeId);
  if (index === -1) {
    form.referee_ids.push(refereeId);
  } else {
    form.referee_ids.splice(index, 1);
  }
};

const removeReferee = (refereeId) => {
  const index = form.referee_ids.indexOf(refereeId);
  if (index !== -1) {
    form.referee_ids.splice(index, 1);
  }
};

// Dynamic Scope Summary Text
const scopeSummary = computed(() => {
  const parts = [];
  if (form.camp_id) {
    const foundCamp = props.camps.find((c) => String(c.id) === String(form.camp_id));
    if (foundCamp) {
      parts.push(`specific camp: "${foundCamp.camp_name}"`);
    }
  }
  if (form.referee_ids.length > 0) {
    parts.push(`${form.referee_ids.length} selected referee(s) (one-time each)`);
  }

  if (parts.length === 0) {
    return 'This is a Global Coupon: applicable to all referees for any camp.';
  }
  return `This coupon is restricted to ${parts.join(' AND ')}.`;
});

// Submit form
const submit = () => {
  if (isEditing.value) {
    form.post(`/admin/v2/coupons/${props.coupon.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        emit('close');
      },
    });
  } else {
    form.post('/admin/v2/coupons', {
      preserveScroll: true,
      onSuccess: () => {
        emit('close');
      },
    });
  }
};
</script>

<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="emit('close')"></div>

    <!-- Modal Dialog -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div 
        class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] shadow-2xl transition-all"
        @keydown.ctrl.enter.prevent="submit"
      >
        <!-- Modal Header -->
        <div class="flex items-center justify-between p-5 border-b border-slate-200 dark:border-white/[0.08]">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#F29F67]/15 border border-[#F29F67]/30 text-[#F29F67] flex items-center justify-center">
              <Ticket class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                {{ isEditing ? `Edit Coupon: ${coupon?.code}` : 'Create New Promotional Coupon' }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Configure discount rates, limits, camp restrictions, and referee targeting
              </p>
            </div>
          </div>
          <button 
            @click="emit('close')"
            class="p-1 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Tab Bar -->
        <div class="flex border-b border-slate-200 dark:border-white/[0.08] px-5 bg-slate-50/50 dark:bg-[#262638]/30">
          <button
            type="button"
            @click="activeTab = 'basic'"
            :class="[
              'flex items-center gap-2 py-3 px-4 text-xs font-semibold border-b-2 transition-all cursor-pointer',
              activeTab === 'basic'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67]'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            ]"
          >
            <Info class="w-4 h-4" />
            Basic Info & Discount
          </button>
          <button
            type="button"
            @click="activeTab = 'scope'"
            :class="[
              'flex items-center gap-2 py-3 px-4 text-xs font-semibold border-b-2 transition-all cursor-pointer',
              activeTab === 'scope'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67]'
                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
            ]"
          >
            <Shield class="w-4 h-4" />
            Scope & Restrictions
            <span v-if="form.referee_ids.length > 0 || form.camp_id" class="w-1.5 h-1.5 rounded-full bg-[#F29F67]"></span>
          </button>
        </div>

        <form @submit.prevent="submit">
          <!-- Modal Body -->
          <div class="p-6 space-y-5">
            
            <!-- ───── TAB 1: BASIC INFO ───── -->
            <div v-show="activeTab === 'basic'" class="space-y-4">
              
              <!-- Coupon Code Input -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                  Coupon Code <span class="text-rose-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <div class="relative flex-1">
                    <input
                      v-model="form.code"
                      type="text"
                      placeholder="e.g. SUMMER2026"
                      class="w-full pl-3.5 pr-4 py-2 text-sm uppercase font-mono font-bold rounded-lg border border-slate-300 dark:border-white/[0.1] bg-white dark:bg-[#262638] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                      required
                    />
                  </div>
                  <button
                    type="button"
                    @click="generateCode"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/30 hover:bg-[#F29F67]/20 transition-all cursor-pointer flex-shrink-0"
                    title="Auto-generate coupon code"
                  >
                    <Sparkles class="w-3.5 h-3.5" />
                    Auto Code
                  </button>
                </div>
                <p v-if="form.errors.code" class="text-xs text-rose-500 mt-1 font-mono">
                  {{ form.errors.code }}
                </p>
              </div>

              <!-- Discount Configuration Row -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Type -->
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                    Discount Type <span class="text-rose-500">*</span>
                  </label>
                  <Dropdown
                    v-model="form.type"
                    :options="typeOptions"
                    size="md"
                    align="left"
                    class="w-full"
                    button-class="w-full !py-2 !text-sm"
                  />
                  <p v-if="form.errors.type" class="text-xs text-rose-500 mt-1 font-mono">
                    {{ form.errors.type }}
                  </p>
                </div>

                <!-- Discount Value -->
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                    Discount Value <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 font-mono text-slate-400 text-sm">
                      {{ form.type === 'percentage' ? '%' : '$' }}
                    </span>
                    <input
                      v-model="form.discount_value"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full pl-8 pr-4 py-2 text-sm font-mono font-bold rounded-lg border border-slate-300 dark:border-white/[0.1] bg-white dark:bg-[#262638] text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                      required
                    />
                  </div>
                  <p v-if="form.errors.discount_value" class="text-xs text-rose-500 mt-1 font-mono">
                    {{ form.errors.discount_value }}
                  </p>
                </div>
              </div>

              <!-- Limits & Expiry Row -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Max Uses -->
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                    Max Redemptions
                  </label>
                  <input
                    v-model="form.max_uses"
                    type="number"
                    min="1"
                    placeholder="Unlimited (∞)"
                    class="w-full px-3 py-2 text-sm font-mono rounded-lg border border-slate-300 dark:border-white/[0.1] bg-white dark:bg-[#262638] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                  />
                  <span class="text-[10px] text-slate-400">Empty for unlimited</span>
                </div>

                <!-- Expiry Date -->
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                    Expiration Date
                  </label>
                  <input
                    v-model="form.expires_at"
                    type="date"
                    class="w-full px-3 py-2 text-sm font-mono rounded-lg border border-slate-300 dark:border-white/[0.1] bg-white dark:bg-[#262638] text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                  />
                  <span class="text-[10px] text-slate-400">Empty for no expiry</span>
                </div>

                <!-- Status -->
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                    Status <span class="text-rose-500">*</span>
                  </label>
                  <Dropdown
                    v-model="form.status"
                    :options="statusOptions"
                    size="md"
                    align="left"
                    class="w-full"
                    button-class="w-full !py-2 !text-sm"
                  />
                  <p v-if="form.errors.status" class="text-xs text-rose-500 mt-1 font-mono">
                    {{ form.errors.status }}
                  </p>
                </div>
              </div>

              <!-- Usage Progress (if editing) -->
              <div v-if="isEditing && coupon?.used_count !== undefined" class="p-3.5 rounded-xl bg-slate-50 dark:bg-[#262638]/50 border border-slate-200 dark:border-white/[0.06]">
                <div class="flex justify-between items-center text-xs mb-1.5">
                  <span class="text-slate-500 dark:text-slate-400 font-medium">Redemption Progress</span>
                  <span class="font-mono font-bold text-slate-900 dark:text-white">
                    {{ coupon.used_count }} / {{ coupon.max_uses || '∞' }} uses
                  </span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-white/[0.1] rounded-full h-2 overflow-hidden">
                  <div 
                    class="bg-[#F29F67] h-2 rounded-full transition-all"
                    :style="{ width: `${coupon.max_uses ? Math.min(100, (coupon.used_count / coupon.max_uses) * 100) : 0}%` }"
                  ></div>
                </div>
              </div>
            </div>

            <!-- ───── TAB 2: SCOPE & RESTRICTIONS ───── -->
            <div v-show="activeTab === 'scope'" class="space-y-4">
              
              <!-- Camp Restriction -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                  Restricted to Camp
                </label>
                <div>
                  <Dropdown
                    v-model="form.camp_id"
                    :options="campOptions"
                    size="md"
                    align="left"
                    class="w-full"
                    button-class="w-full !py-2 !text-sm"
                    placeholder="All Camps (Global)"
                  />
                </div>
                <span class="text-[10px] text-slate-400 block mt-1">If set, only applicable during checkout for this specific camp</span>
              </div>

              <!-- Referee Targeting Multi-Select -->
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 font-mono uppercase tracking-wider">
                  Target Specific Referees
                </label>

                <!-- Search box inside dropdown trigger -->
                <div class="relative mb-2">
                  <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    v-model="refereeSearch"
                    type="text"
                    placeholder="Search referees by name or email..."
                    class="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 dark:border-white/[0.1] bg-white dark:bg-[#262638] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                  />
                </div>

                <!-- Referee Selector Pill Box -->
                <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200 dark:border-white/[0.08] p-2 space-y-1 bg-slate-50/50 dark:bg-[#262638]/30">
                  <div
                    v-for="r in filteredReferees"
                    :key="r.id"
                    @click="toggleReferee(r.id)"
                    :class="[
                      'flex items-center justify-between p-2 rounded-lg text-xs cursor-pointer transition-colors',
                      form.referee_ids.includes(r.id)
                        ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/30'
                        : 'hover:bg-slate-100 dark:hover:bg-[#262638] text-slate-700 dark:text-slate-300'
                    ]"
                  >
                    <div class="flex items-center gap-2">
                      <img :src="r.avatar" class="w-6 h-6 rounded-full object-cover border" alt="" />
                      <div>
                        <span class="font-medium">{{ r.name }}</span>
                        <span class="text-[10px] text-slate-400 ml-1">({{ r.email }})</span>
                      </div>
                    </div>
                    <Check v-if="form.referee_ids.includes(r.id)" class="w-4 h-4 text-[#F29F67]" />
                  </div>
                  <div v-if="filteredReferees.length === 0" class="py-4 text-center text-xs text-slate-400 italic">
                    No referees found matching "{{ refereeSearch }}"
                  </div>
                </div>

                <!-- Selected Referees Tag Cloud -->
                <div v-if="selectedReferees.length > 0" class="flex flex-wrap gap-1.5 mt-2">
                  <span
                    v-for="sr in selectedReferees"
                    :key="sr.id"
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20"
                  >
                    <span>{{ sr.name }}</span>
                    <button type="button" @click.stop="removeReferee(sr.id)" class="hover:text-rose-500">
                      <X class="w-3 h-3" />
                    </button>
                  </span>
                </div>
                <span class="text-[10px] text-slate-400 block mt-1">
                  Leave empty to make the coupon available to all registered referees
                </span>
              </div>

              <!-- Scope Live Summary Box -->
              <div class="p-3.5 rounded-xl bg-indigo-500/5 dark:bg-indigo-500/10 border border-indigo-500/20 flex items-start gap-2.5 text-xs text-indigo-900 dark:text-indigo-300">
                <Info class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" />
                <div>
                  <span class="font-bold">Scope Summary:</span>
                  <p class="mt-0.5">{{ scopeSummary }}</p>
                </div>
              </div>
            </div>

          </div>

          <!-- Modal Footer -->
          <div class="p-5 border-t border-slate-200 dark:border-white/[0.08] flex items-center justify-between bg-slate-50/50 dark:bg-[#262638]/20">
            <span class="text-[11px] text-slate-400 font-mono hidden sm:inline">
              Ctrl + Enter to submit
            </span>
            <div class="flex items-center gap-2 ms-auto">
              <button
                type="button"
                @click="emit('close')"
                class="px-4 py-2 text-xs font-semibold rounded-lg border border-slate-300 dark:border-white/[0.1] text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#262638] transition-all cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 text-xs font-bold rounded-lg bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 shadow-xs transition-all cursor-pointer disabled:opacity-50"
              >
                {{ form.processing ? 'Saving...' : isEditing ? 'Update Coupon' : 'Create Coupon' }}
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>
  </div>
</template>
