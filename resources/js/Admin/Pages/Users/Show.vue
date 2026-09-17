<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import ConfirmationModal from '../../Components/Common/ConfirmationModal.vue';
import {
  ArrowLeft,
  User as UserIcon,
  Mail,
  Phone,
  MapPin,
  Calendar,
  Shield,
  CreditCard,
  Briefcase,
  Award,
  DollarSign,
  TrendingUp,
  CheckCircle2,
  XCircle,
  Clock,
  Trash2,
  RefreshCw,
  Eye,
  Tent,
  Layers,
  FileText,
  Star
} from 'lucide-vue-next';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  directorData: {
    type: Object,
    default: () => ({ stats: null, camps: [], revenue: [] }),
  },
  refereeData: {
    type: Object,
    default: () => ({ camps: [], checkins: [], payments: [], evaluations: [] }),
  },
  evaluatorData: {
    type: Object,
    default: () => ({ registrations: [], evaluations: [] }),
  },
});

const isDirector = computed(() => (props.user.primary_role || '').toLowerCase() === 'director');
const isReferee = computed(() => (props.user.primary_role || '').toLowerCase() === 'referee');
const isEvaluator = computed(() => (props.user.primary_role || '').toLowerCase() === 'evaluator');

const activeTab = ref(
  isDirector.value ? 'camps' : isReferee.value ? 'referee-camps' : isEvaluator.value ? 'eval-camps' : 'overview'
);

// Confirmation modal states
const isDeleteModalOpen = ref(false);
const isRestoreModalOpen = ref(false);
const isStatusModalOpen = ref(false);
const isStatusLoading = ref(false);

const handleStatusToggle = () => {
  isStatusLoading.value = true;
  router.post(`/admin/v2/users/${props.user.id}/status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      isStatusModalOpen.value = false;
    },
    onFinish: () => {
      isStatusLoading.value = false;
    },
  });
};

const handleDelete = () => {
  router.delete(`/admin/v2/users/${props.user.id}`, {
    onSuccess: () => {
      isDeleteModalOpen.value = false;
    },
  });
};

const handleRestore = () => {
  router.post(`/admin/v2/users/${props.user.id}/restore`, {}, {
    onSuccess: () => {
      isRestoreModalOpen.value = false;
    },
  });
};

const getRoleBadgeColor = (roleName) => {
  const normalized = (roleName || '').toLowerCase();
  switch (normalized) {
    case 'admin':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20';
    case 'director':
      return 'bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border-[#F29F67]/20';
    case 'referee':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20';
    case 'evaluator':
      return 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20';
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20';
  }
};
</script>

<template>
  <AdminLayout>
    <Head :title="`${user.full_name} — User Profile — Whistle Works Admin V2`" />

    <div class="space-y-6 pb-12">
      <!-- 1. Top Navigation Bar -->
      <div class="flex items-center justify-between">
        <Link
          href="/admin/v2/users"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md bg-white dark:bg-[#262638] text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-white/[0.08] text-xs font-semibold shadow-2xs transition-colors"
        >
          <ArrowLeft class="w-3.5 h-3.5" />
          <span>Back to User Directory</span>
        </Link>

        <div class="flex items-center gap-2">
          <!-- Toggle Status -->
          <button
            v-if="!user.is_trashed"
            @click="isStatusModalOpen = true"
            :class="[
              user.status === 'active'
                ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border-amber-500/30'
                : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
              'px-3 py-1.5 rounded-md text-xs font-semibold border transition-all cursor-pointer shadow-2xs'
            ]"
          >
            {{ user.status === 'active' ? 'Deactivate Account' : 'Activate Account' }}
          </button>

          <!-- Delete / Restore -->
          <button
            v-if="!user.is_trashed"
            @click="isDeleteModalOpen = true"
            class="p-1.5 rounded-md text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 dark:border-rose-500/30 transition-all duration-150 cursor-pointer shadow-2xs inline-flex items-center justify-center"
            title="Move to Trash"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
          <button
            v-else
            @click="isRestoreModalOpen = true"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 text-xs font-semibold transition-colors cursor-pointer shadow-2xs"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>Restore User</span>
          </button>
        </div>
      </div>

      <!-- 2. Profile Header Card -->
      <div class="p-5 sm:p-6 rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-5">
          <div class="flex items-center gap-4">
            <img
              :src="user.avatar"
              :alt="user.full_name"
              class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover border-2 border-slate-200 dark:border-white/[0.1] shadow-sm"
            />
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold font-display tracking-tight text-slate-900 dark:text-white">
                  {{ user.full_name }}
                </h1>
                <span
                  :class="[
                    getRoleBadgeColor(user.primary_role),
                    'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border font-mono'
                  ]"
                >
                  {{ user.primary_role }}
                </span>
                <span
                  :class="[
                    user.status === 'active'
                      ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                      : 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono'
                  ]"
                >
                  {{ user.status }}
                </span>
                <span
                  v-if="user.is_trashed"
                  class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-600 dark:text-rose-400 text-[10px] font-bold uppercase tracking-wider font-mono"
                >
                  Trashed
                </span>
              </div>

              <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                @{{ user.username }} • Member since {{ user.created_at }}
              </p>

              <div class="flex flex-wrap items-center gap-4 text-xs text-slate-600 dark:text-slate-300 pt-1">
                <div class="flex items-center gap-1.5">
                  <Mail class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ user.email }}</span>
                </div>
                <div v-if="user.phone" class="flex items-center gap-1.5">
                  <Phone class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ user.phone }}</span>
                </div>
                <div v-if="user.address" class="flex items-center gap-1.5">
                  <MapPin class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ user.address }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Stats / Status Pill -->
          <div class="flex flex-row md:flex-col items-start md:items-end justify-between border-t md:border-t-0 border-slate-100 dark:border-white/[0.06] pt-3 md:pt-0 gap-2">
            <div class="text-left md:text-right">
              <span class="text-[10px] font-mono font-bold uppercase text-slate-400">Last Active</span>
              <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">{{ user.last_activity_at }}</p>
            </div>
            <div v-if="user.stripe_linked" class="flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-xs font-mono">
              <CreditCard class="w-3.5 h-3.5" />
              <span>Stripe Connected</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Director KPI Highlights (Only if Director) -->
      <div v-if="isDirector && directorData.stats" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
          <span class="text-[10px] font-mono font-bold uppercase text-slate-400">Gross Revenue</span>
          <p class="text-xl font-bold font-display text-emerald-600 dark:text-emerald-400 mt-1">
            ${{ Number(directorData.stats.gross_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </p>
        </div>
        <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
          <span class="text-[10px] font-mono font-bold uppercase text-slate-400">Director Net Earnings</span>
          <p class="text-xl font-bold font-display text-[#E08A50] dark:text-[#F29F67] mt-1">
            ${{ Number(directorData.stats.net_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </p>
        </div>
        <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
          <span class="text-[10px] font-mono font-bold uppercase text-slate-400">Platform Admin Fees</span>
          <p class="text-xl font-bold font-display text-slate-900 dark:text-white mt-1">
            ${{ Number(directorData.stats.admin_fees || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
          </p>
        </div>
        <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
          <span class="text-[10px] font-mono font-bold uppercase text-slate-400">Total Transactions</span>
          <p class="text-xl font-bold font-display text-indigo-600 dark:text-indigo-400 mt-1">
            {{ Number(directorData.stats.total_payments || 0).toLocaleString() }}
          </p>
        </div>
      </div>

      <!-- 4. Tab Navigation Header for Role Activities -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-white/[0.08] pb-1 overflow-x-auto">
        <!-- Director Tabs -->
        <template v-if="isDirector">
          <button
            @click="activeTab = 'camps'"
            :class="[
              activeTab === 'camps'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Assigned Camps ({{ directorData.camps.length }})
          </button>
          <button
            @click="activeTab = 'revenue'"
            :class="[
              activeTab === 'revenue'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Revenue Stream History
          </button>
        </template>

        <!-- Referee Tabs -->
        <template v-if="isReferee">
          <button
            @click="activeTab = 'referee-camps'"
            :class="[
              activeTab === 'referee-camps'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Camp Registrations ({{ refereeData.camps.length }})
          </button>
          <button
            @click="activeTab = 'checkins'"
            :class="[
              activeTab === 'checkins'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Check-ins ({{ refereeData.checkins.length }})
          </button>
          <button
            @click="activeTab = 'payments'"
            :class="[
              activeTab === 'payments'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Payments ({{ refereeData.payments.length }})
          </button>
          <button
            @click="activeTab = 'evaluations'"
            :class="[
              activeTab === 'evaluations'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Evaluations Received ({{ refereeData.evaluations.length }})
          </button>
        </template>

        <!-- Evaluator Tabs -->
        <template v-if="isEvaluator">
          <button
            @click="activeTab = 'eval-camps'"
            :class="[
              activeTab === 'eval-camps'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Assigned Camps ({{ evaluatorData.registrations.length }})
          </button>
          <button
            @click="activeTab = 'eval-reviews'"
            :class="[
              activeTab === 'eval-reviews'
                ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
                : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
              'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            Evaluations Conducted ({{ evaluatorData.evaluations.length }})
          </button>
        </template>

        <!-- Common Overview Tab -->
        <button
          @click="activeTab = 'overview'"
          :class="[
            activeTab === 'overview'
              ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] font-bold'
              : 'border-transparent text-slate-500 hover:text-slate-900 dark:hover:text-slate-100',
            'px-3 py-2 text-xs border-b-2 transition-all cursor-pointer whitespace-nowrap'
          ]"
        >
          Security & Roles
        </button>
      </div>

      <!-- 5. Tab Contents -->

      <!-- Tab: Director Camps -->
      <div v-if="activeTab === 'camps'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Camps Organized by Director</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp Name</th>
                <th class="py-2.5 px-4">Sport</th>
                <th class="py-2.5 px-4">Schedule</th>
                <th class="py-2.5 px-4">Price</th>
                <th class="py-2.5 px-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="camp in directorData.camps" :key="camp.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ camp.camp_name }}</td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ camp.sports_type_name }}</td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ camp.start_date }} - {{ camp.end_date }}</td>
                <td class="py-3 px-4 font-mono font-semibold">${{ camp.price }}</td>
                <td class="py-3 px-4">
                  <span :class="camp.status === 'active' ? 'text-emerald-500 font-semibold' : 'text-slate-400'">
                    {{ camp.status }}
                  </span>
                </td>
              </tr>
              <tr v-if="directorData.camps.length === 0">
                <td colspan="5" class="py-6 text-center text-slate-400">No camps found for this director.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Director Revenue -->
      <div v-if="activeTab === 'revenue'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Monthly Revenue Breakdown</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Month</th>
                <th class="py-2.5 px-4">Gross Revenue</th>
                <th class="py-2.5 px-4">Director Net</th>
                <th class="py-2.5 px-4">Transactions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="rev in directorData.revenue" :key="rev.month" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-mono font-semibold text-slate-900 dark:text-white">{{ rev.month }}</td>
                <td class="py-3 px-4 text-emerald-600 dark:text-emerald-400 font-mono">${{ Number(rev.gross_revenue).toFixed(2) }}</td>
                <td class="py-3 px-4 text-[#E08A50] dark:text-[#F29F67] font-mono">${{ Number(rev.net_revenue).toFixed(2) }}</td>
                <td class="py-3 px-4 font-mono">{{ rev.transactions }}</td>
              </tr>
              <tr v-if="directorData.revenue.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">No revenue records found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Referee Camps -->
      <div v-if="activeTab === 'referee-camps'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Referee Camp Registrations</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp Name</th>
                <th class="py-2.5 px-4">Jersey #</th>
                <th class="py-2.5 px-4">Dates</th>
                <th class="py-2.5 px-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="c in refereeData.camps" :key="c.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ c.camp_name }}</td>
                <td class="py-3 px-4 font-mono font-bold text-[#E08A50] dark:text-[#F29F67]">#{{ c.jersey_number }}</td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ c.start_date }} - {{ c.end_date }}</td>
                <td class="py-3 px-4 text-slate-400">{{ c.status }}</td>
              </tr>
              <tr v-if="refereeData.camps.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">No camp registrations recorded.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Referee Checkins -->
      <div v-if="activeTab === 'checkins'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Check-in Logs</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp Name</th>
                <th class="py-2.5 px-4">Check-in Status</th>
                <th class="py-2.5 px-4">Timestamp</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="ck in refereeData.checkins" :key="ck.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ ck.camp_name }}</td>
                <td class="py-3 px-4">
                  <span :class="ck.registration_status === 'checked_in' ? 'text-emerald-500 font-semibold' : 'text-slate-400'">
                    {{ ck.registration_status }}
                  </span>
                </td>
                <td class="py-3 px-4 text-slate-500 font-mono">{{ ck.checked_in_at }}</td>
              </tr>
              <tr v-if="refereeData.checkins.length === 0">
                <td colspan="3" class="py-6 text-center text-slate-400">No check-in activity recorded.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Referee Payments -->
      <div v-if="activeTab === 'payments'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Registration Payment Receipts</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp Name</th>
                <th class="py-2.5 px-4">Amount</th>
                <th class="py-2.5 px-4">Payment Status</th>
                <th class="py-2.5 px-4">Paid On</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="p in refereeData.payments" :key="p.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ p.camp_name }}</td>
                <td class="py-3 px-4 font-mono font-semibold text-emerald-600 dark:text-emerald-400">${{ p.amount.toFixed(2) }}</td>
                <td class="py-3 px-4 font-semibold text-emerald-500">{{ p.status }}</td>
                <td class="py-3 px-4 text-slate-500 font-mono">{{ p.paid_at }}</td>
              </tr>
              <tr v-if="refereeData.payments.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">No payment receipts found.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Referee Evaluations -->
      <div v-if="activeTab === 'evaluations'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Referee Evaluation Scores</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp</th>
                <th class="py-2.5 px-4">Evaluator</th>
                <th class="py-2.5 px-4">Score / Rating</th>
                <th class="py-2.5 px-4">Evaluated On</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="ev in refereeData.evaluations" :key="ev.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ ev.camp_name }}</td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ ev.evaluator_name }}</td>
                <td class="py-3 px-4 font-mono font-bold text-[#E08A50] dark:text-[#F29F67]">{{ ev.rating }}</td>
                <td class="py-3 px-4 text-slate-500 font-mono">{{ ev.created_at }}</td>
              </tr>
              <tr v-if="refereeData.evaluations.length === 0">
                <td colspan="4" class="py-6 text-center text-slate-400">No evaluations received yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Evaluator Assigned Camps -->
      <div v-if="activeTab === 'eval-camps'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Assigned Evaluator Camps</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp Name</th>
                <th class="py-2.5 px-4">Dates</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="er in evaluatorData.registrations" :key="er.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ er.camp_name }}</td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ er.start_date }} - {{ er.end_date }}</td>
              </tr>
              <tr v-if="evaluatorData.registrations.length === 0">
                <td colspan="2" class="py-6 text-center text-slate-400">No camp registrations found for evaluator.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Evaluator Conducted Evaluations -->
      <div v-if="activeTab === 'eval-reviews'" class="rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-white/[0.08]">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Evaluations Performed by User</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-[#1E1E2C] text-slate-500 font-mono text-[10px] uppercase">
              <tr>
                <th class="py-2.5 px-4">Camp</th>
                <th class="py-2.5 px-4">Referee Evaluated</th>
                <th class="py-2.5 px-4">Date</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr v-for="ee in evaluatorData.evaluations" :key="ee.id" class="hover:bg-slate-50/50 dark:hover:bg-[#2D2D42]/50">
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ ee.camp_name }}</td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ ee.referee_name }}</td>
                <td class="py-3 px-4 text-slate-500 font-mono">{{ ee.created_at }}</td>
              </tr>
              <tr v-if="evaluatorData.evaluations.length === 0">
                <td colspan="3" class="py-6 text-center text-slate-400">No evaluation reports conducted yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Tab: Security & Roles Overview -->
      <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Roles List -->
        <div class="p-4 rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Assigned Roles</h3>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="r in user.roles"
              :key="r"
              :class="[
                getRoleBadgeColor(r),
                'px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider border font-mono'
              ]"
            >
              {{ r }}
            </span>
          </div>
        </div>

        <!-- Permissions List -->
        <div class="p-4 rounded-xl bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Direct Permissions</h3>
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="p in user.permissions"
              :key="p"
              class="px-2 py-0.5 rounded text-[11px] font-mono bg-slate-100 dark:bg-white/[0.06] text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08]"
            >
              {{ p }}
            </span>
            <span v-if="user.permissions.length === 0" class="text-xs text-slate-400">
              No direct permissions assigned (inherits via roles).
            </span>
          </div>
        </div>
      </div>

      <!-- Modals -->
      <ConfirmationModal
        :show="isStatusModalOpen"
        :title="user.status === 'active' ? 'Deactivate User Account' : 'Activate User Account'"
        :message="user.status === 'active'
          ? `Are you sure you want to deactivate '${user.full_name}'? The user will be unable to sign in or perform actions until reactivated.`
          : `Are you sure you want to activate '${user.full_name}'? The user will immediately regain full access to the platform.`"
        :confirm-text="user.status === 'active' ? 'Deactivate Account' : 'Activate Account'"
        :type="user.status === 'active' ? 'warning' : 'info'"
        :is-loading="isStatusLoading"
        @confirm="handleStatusToggle"
        @close="() => { if (!isStatusLoading) isStatusModalOpen = false; }"
      />

      <ConfirmationModal
        :show="isDeleteModalOpen"
        title="Move User to Trash"
        :message="`Are you sure you want to move '${user.full_name}' to trash?`"
        confirm-text="Move to Trash"
        type="danger"
        @confirm="handleDelete"
        @close="isDeleteModalOpen = false"
      />

      <ConfirmationModal
        :show="isRestoreModalOpen"
        title="Restore User Account"
        :message="`Are you sure you want to restore '${user.full_name}'?`"
        confirm-text="Restore Account"
        type="info"
        @confirm="handleRestore"
        @close="isRestoreModalOpen = false"
      />
    </div>
  </AdminLayout>
</template>
