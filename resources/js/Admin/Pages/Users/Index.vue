<script setup>
import { ref } from 'vue';
import { router, Head, Link } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/Common/DataTable.vue';
import ConfirmationModal from '../../Components/Common/ConfirmationModal.vue';
import {
  Users,
  UserCheck,
  Shield,
  Award,
  Briefcase,
  Trash2,
  RefreshCw,
  Download,
  Eye,
  Mail,
  Phone,
  CheckCircle2,
  XCircle,
  Clock,
  Sparkles,
  ExternalLink,
  DollarSign,
  Tent
} from 'lucide-vue-next';

const props = defineProps({
  users: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  metrics: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      directors: 0,
      referees: 0,
      evaluators: 0,
      trashed: 0,
    }),
  },
});

const currentRole = ref(props.filters.role || 'all');

// Role Filter Tabs
const roleTabs = [
  { key: 'all', label: 'All Users', icon: Users },
  { key: 'director', label: 'Directors', icon: Briefcase },
  { key: 'referee', label: 'Referees', icon: Award },
  { key: 'evaluator', label: 'Evaluators', icon: Shield },
  { key: 'trashed', label: 'Trash Archive', icon: Trash2, badge: props.metrics.trashed },
];

const handleRoleTabChange = (roleKey) => {
  currentRole.value = roleKey;
  router.get(
    '/admin/v2/users',
    {
      ...props.filters,
      role: roleKey,
      page: 1,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

// DataTable Column Configuration
const columns = [
  { key: 'user_info', label: 'User Profile', sortable: true, align: 'left' },
  { key: 'roles', label: 'Role Access', align: 'left' },
  { key: 'contact', label: 'Contact', align: 'left' },
  { key: 'role_metrics', label: 'Performance / Link', align: 'left' },
  { key: 'activity', label: 'Activity & Joined', sortable: true, align: 'left' },
  { key: 'status', label: 'Status', sortable: true, align: 'center', class: 'w-24' },
  { key: 'actions', label: 'Actions', align: 'right', class: 'w-28' },
];

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

// Modal States for Delete / Status / Restore / ForceDelete
const isDeleteModalOpen = ref(false);
const isRestoreModalOpen = ref(false);
const isForceDeleteModalOpen = ref(false);
const itemToActOn = ref(null);

const confirmDelete = (user) => {
  itemToActOn.value = user;
  isDeleteModalOpen.value = true;
};

const handleDelete = () => {
  if (!itemToActOn.value) return;
  router.delete(`/admin/v2/users/${itemToActOn.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteModalOpen.value = false;
      itemToActOn.value = null;
    },
  });
};

const confirmRestore = (user) => {
  itemToActOn.value = user;
  isRestoreModalOpen.value = true;
};

const handleRestore = () => {
  if (!itemToActOn.value) return;
  router.post(`/admin/v2/users/${itemToActOn.value.id}/restore`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      isRestoreModalOpen.value = false;
      itemToActOn.value = null;
    },
  });
};

const confirmForceDelete = (user) => {
  itemToActOn.value = user;
  isForceDeleteModalOpen.value = true;
};

const handleForceDelete = () => {
  if (!itemToActOn.value) return;
  router.delete(`/admin/v2/users/${itemToActOn.value.id}/force-delete`, {
    preserveScroll: true,
    onSuccess: () => {
      isForceDeleteModalOpen.value = false;
      itemToActOn.value = null;
    },
  });
};

const handleStatusToggle = (user) => {
  router.post(`/admin/v2/users/${user.id}/status`, {}, {
    preserveScroll: true,
  });
};

const exportCsv = () => {
  const params = new URLSearchParams({
    role: currentRole.value,
    status: props.filters.status || '',
    search: props.filters.search || '',
  });
  window.location.href = `/admin/v2/users/export?${params.toString()}`;
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
    <Head title="User Directory & Access — Whistle Works Admin V2" />

    <div class="space-y-6 pb-12">
      <!-- 1. Executive Hero Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-[#F29F67] animate-pulse"></div>
            <span class="text-[11px] font-mono font-bold uppercase tracking-widest text-[#E08A50] dark:text-[#F29F67]">
              Identity & Access Management
            </span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold font-display tracking-tight text-slate-900 dark:text-white">
            User Directory
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Monitor community profiles, role assignments, camp engagements, and security access.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button
            @click="exportCsv"
            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-md bg-white dark:bg-[#262638] hover:bg-slate-50 dark:hover:bg-[#2D2D42] text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-white/[0.08] text-xs font-semibold shadow-2xs transition-all cursor-pointer"
          >
            <Download class="w-3.5 h-3.5 text-[#E08A50] dark:text-[#F29F67]" />
            <span>Export CSV</span>
          </button>
        </div>
      </div>

      <!-- 2. KPI Metrics Ribbon -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Users -->
        <div class="p-3.5 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Total Users</span>
            <Users class="w-4 h-4 text-slate-400" />
          </div>
          <p class="text-xl font-bold text-slate-900 dark:text-white font-display">
            {{ Number(metrics.total).toLocaleString() }}
          </p>
        </div>

        <!-- Active Users -->
        <div class="p-3.5 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Active Status</span>
            <UserCheck class="w-4 h-4 text-emerald-500" />
          </div>
          <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-display">
            {{ Number(metrics.active).toLocaleString() }}
          </p>
        </div>

        <!-- Directors -->
        <div class="p-3.5 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Directors</span>
            <Briefcase class="w-4 h-4 text-[#F29F67]" />
          </div>
          <p class="text-xl font-bold text-[#E08A50] dark:text-[#F29F67] font-display">
            {{ Number(metrics.directors).toLocaleString() }}
          </p>
        </div>

        <!-- Referees -->
        <div class="p-3.5 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Referees</span>
            <Award class="w-4 h-4 text-emerald-500" />
          </div>
          <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 font-display">
            {{ Number(metrics.referees).toLocaleString() }}
          </p>
        </div>

        <!-- Evaluators -->
        <div class="p-3.5 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Evaluators</span>
            <Shield class="w-4 h-4 text-cyan-500" />
          </div>
          <p class="text-xl font-bold text-cyan-600 dark:text-cyan-400 font-display">
            {{ Number(metrics.evaluators).toLocaleString() }}
          </p>
        </div>

        <!-- Trashed -->
        <div class="p-3.5 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col justify-between">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 mb-2">
            <span class="text-[10px] font-mono font-bold uppercase tracking-wider">Trash Archive</span>
            <Trash2 class="w-4 h-4 text-rose-500" />
          </div>
          <p class="text-xl font-bold text-rose-600 dark:text-rose-400 font-display">
            {{ Number(metrics.trashed).toLocaleString() }}
          </p>
        </div>
      </div>

      <!-- 3. Role Filter Navigation Tabs -->
      <div class="flex items-center gap-1.5 p-1 rounded-lg bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] overflow-x-auto">
        <button
          v-for="tab in roleTabs"
          :key="tab.key"
          @click="handleRoleTabChange(tab.key)"
          :class="[
            currentRole === tab.key
              ? 'bg-white dark:bg-[#262638] text-slate-900 dark:text-white shadow-xs font-bold border border-slate-200/80 dark:border-white/[0.08]'
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 border border-transparent',
            'flex items-center gap-2 px-3 py-1.5 rounded-md text-xs transition-all cursor-pointer whitespace-nowrap'
          ]"
        >
          <component :is="tab.icon" class="w-3.5 h-3.5" :class="currentRole === tab.key ? 'text-[#F29F67]' : ''" />
          <span>{{ tab.label }}</span>
          <span
            v-if="tab.badge !== undefined && tab.badge > 0"
            class="px-1.5 py-0.2 rounded-full text-[9px] font-mono font-semibold bg-rose-500/20 text-rose-600 dark:text-rose-400"
          >
            {{ tab.badge }}
          </span>
        </button>
      </div>

      <!-- 4. Main DataTable Component -->
      <DataTable
        :columns="columns"
        :rows="users.data"
        :pagination="users"
        :filters="filters"
        baseUrl="/admin/v2/users"
        searchPlaceholder="Search by name, email, phone or username..."
        :statusOptions="currentRole === 'trashed' ? [] : statusOptions"
        :title="currentRole === 'trashed' ? 'Trash Archives' : 'Registered Community Members'"
        :subtitle="currentRole === 'trashed' ? 'List of soft-deleted accounts. Restore or permanently purge.' : 'Manage account roles, live connectivity status, and performance insights.'"
      >
        <!-- Custom Cell: User Profile -->
        <template #cell(user_info)="{ row }">
          <div class="flex items-center gap-3">
            <div class="relative flex-shrink-0">
              <img
                :src="row.avatar"
                :alt="row.full_name"
                class="w-9 h-9 rounded-full object-cover border border-slate-200 dark:border-white/[0.1] shadow-2xs"
              />
              <span
                v-if="!row.is_trashed"
                :class="[
                  row.is_online ? 'bg-emerald-500 ring-emerald-500/30' : 'bg-slate-400 ring-slate-400/20',
                  'absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2 ring-white dark:ring-[#262638]'
                ]"
                :title="row.is_online ? 'Online now' : 'Offline'"
              ></span>
            </div>
            <div class="min-w-0">
              <Link
                :href="`/admin/v2/users/${row.id}`"
                class="font-semibold text-slate-900 dark:text-white hover:text-[#E08A50] dark:hover:text-[#F29F67] transition-colors truncate block max-w-[180px] sm:max-w-[220px]"
              >
                {{ row.full_name }}
              </Link>
              <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                <span>@{{ row.username || 'user' }}</span>
                <span class="text-slate-300 dark:text-slate-600">•</span>
                <span>ID #{{ row.id }}</span>
              </div>
            </div>
          </div>
        </template>

        <!-- Custom Cell: Role Badges -->
        <template #cell(roles)="{ row }">
          <div class="flex flex-wrap gap-1">
            <span
              v-for="role in row.roles"
              :key="role.id"
              :class="[
                getRoleBadgeColor(role.name),
                'px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border font-mono'
              ]"
            >
              {{ role.name }}
            </span>
            <span
              v-if="row.roles.length === 0"
              class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-white/[0.06] text-slate-500 border border-slate-200 dark:border-white/[0.08]"
            >
              No Role
            </span>
          </div>
        </template>

        <!-- Custom Cell: Contact Info -->
        <template #cell(contact)="{ row }">
          <div class="space-y-0.5 min-w-[140px]">
            <div class="flex items-center gap-1.5 text-[11px] text-slate-700 dark:text-slate-300 truncate max-w-[200px]">
              <Mail class="w-3 h-3 text-slate-400 flex-shrink-0" />
              <span class="truncate">{{ row.email }}</span>
            </div>
            <div v-if="row.phone" class="flex items-center gap-1.5 text-[10px] text-slate-500 dark:text-slate-400">
              <Phone class="w-3 h-3 text-slate-400 flex-shrink-0" />
              <span>{{ row.phone }}</span>
            </div>
          </div>
        </template>

        <!-- Custom Cell: Role-specific Performance / Link -->
        <template #cell(role_metrics)="{ row }">
          <div v-if="row.director_data" class="space-y-1">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-slate-900 dark:text-white">
              <DollarSign class="w-3.5 h-3.5 text-emerald-500" />
              <span>${{ Number(row.director_data.total_revenue || 0).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}</span>
            </div>
            <div class="flex items-center gap-2 text-[10px]">
              <span class="px-1.5 py-0.2 rounded bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 font-mono">
                {{ row.director_data.camps_count }} camps
              </span>
              <span
                v-if="row.director_data.stripe_linked"
                class="px-1.5 py-0.2 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-mono"
              >
                Stripe ✓
              </span>
            </div>
          </div>
          <div v-else class="text-xs text-slate-400 font-mono">
            —
          </div>
        </template>

        <!-- Custom Cell: Activity & Joined -->
        <template #cell(activity)="{ row }">
          <div class="space-y-0.5">
            <div class="flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300">
              <Clock class="w-3 h-3 text-slate-400" />
              <span>{{ row.last_activity }}</span>
            </div>
            <div class="text-[10px] text-slate-400 font-mono">
              Joined {{ row.created_at }}
            </div>
          </div>
        </template>

        <!-- Custom Cell: Status Toggle / Badge -->
        <template #cell(status)="{ row }">
          <div v-if="row.is_trashed">
            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 font-mono">
              Deleted
            </span>
          </div>
          <div v-else class="flex justify-center">
            <button
              @click="handleStatusToggle(row)"
              :class="[
                row.status === 'active'
                  ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                  : 'bg-slate-500/10 text-slate-500 dark:text-slate-400 border-slate-500/20',
                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono transition-all hover:scale-105 cursor-pointer shadow-2xs'
              ]"
              :title="`Click to switch to ${row.status === 'active' ? 'inactive' : 'active'}`"
            >
              <span
                :class="[
                  row.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400',
                  'w-1.5 h-1.5 rounded-full'
                ]"
              ></span>
              <span>{{ row.status }}</span>
            </button>
          </div>
        </template>

        <!-- Custom Cell: Actions -->
        <template #cell(actions)="{ row }">
          <div class="flex items-center justify-end gap-1.5">
            <!-- Active Users Actions -->
            <template v-if="!row.is_trashed">
              <Link
                :href="`/admin/v2/users/${row.id}`"
                class="p-1.5 rounded text-slate-400 hover:text-[#E08A50] dark:hover:text-[#F29F67] hover:bg-[#F29F67]/10 transition-colors"
                title="View Executive Profile"
              >
                <Eye class="w-4 h-4" />
              </Link>
              <button
                @click="confirmDelete(row)"
                class="p-1.5 rounded text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                title="Move to Trash"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </template>

            <!-- Trashed Users Actions -->
            <template v-else>
              <button
                @click="confirmRestore(row)"
                class="p-1.5 rounded text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors cursor-pointer"
                title="Restore User"
              >
                <RefreshCw class="w-4 h-4" />
              </button>
              <button
                @click="confirmForceDelete(row)"
                class="p-1.5 rounded text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                title="Permanently Delete"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </template>
          </div>
        </template>
      </DataTable>

      <!-- Soft Delete Confirmation Modal -->
      <ConfirmationModal
        :show="isDeleteModalOpen"
        title="Move User to Trash"
        :message="`Are you sure you want to move '${itemToActOn?.full_name}' to trash? They will not be able to log in, but records will be preserved.`"
        confirm-text="Move to Trash"
        type="danger"
        @confirm="handleDelete"
        @close="isDeleteModalOpen = false"
      />

      <!-- Restore Confirmation Modal -->
      <ConfirmationModal
        :show="isRestoreModalOpen"
        title="Restore User Account"
        :message="`Are you sure you want to restore '${itemToActOn?.full_name}'? Their account access will be reactivated.`"
        confirm-text="Restore Account"
        type="info"
        @confirm="handleRestore"
        @close="isRestoreModalOpen = false"
      />

      <!-- Force Delete Confirmation Modal -->
      <ConfirmationModal
        :show="isForceDeleteModalOpen"
        title="Permanently Delete User"
        :message="`DANGER: Are you sure you want to permanently delete '${itemToActOn?.full_name}'? This action cannot be undone and will erase all account associations.`"
        confirm-text="Delete Permanently"
        type="danger"
        @confirm="handleForceDelete"
        @close="isForceDeleteModalOpen = false"
      />
    </div>
  </AdminLayout>
</template>
