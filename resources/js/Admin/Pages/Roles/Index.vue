<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Admin/Components/Common/Breadcrumb.vue';
import RoleModal from '@/Admin/Components/Roles/RoleModal.vue';
import PermissionModal from '@/Admin/Components/Roles/PermissionModal.vue';
import { useToast } from '@/Admin/Composables/useToast';
import {
  Shield,
  ShieldCheck,
  ShieldAlert,
  Users,
  KeyRound,
  Plus,
  Search,
  Edit3,
  Trash2,
  Lock,
  Sparkles,
  AlertCircle,
  CheckCircle2,
  X,
  Loader2,
  Layers,
  Tag,
  Check
} from 'lucide-vue-next';

const props = defineProps({
  roles: {
    type: Array,
    required: true,
    default: () => [],
  },
  groupedPermissions: {
    type: Array,
    required: true,
    default: () => [],
  },
  allPermissionsCatalog: {
    type: Array,
    default: () => [],
  },
  availableModules: {
    type: Array,
    default: () => [],
  },
  metrics: {
    type: Object,
    required: true,
    default: () => ({
      total_roles: 0,
      system_roles: 0,
      custom_roles: 0,
      total_permissions: 0,
      system_permissions: 0,
      custom_permissions: 0,
      total_assigned: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
    }),
  },
});

const toast = useToast();

// Active Tab ('roles' or 'permissions')
const activeTab = ref('roles');

// Local Search Filters
const roleSearch = ref(props.filters.search || '');
const permSearch = ref('');
const selectedModuleFilter = ref('all');

// Modals State
const showRoleModal = ref(false);
const editingRole = ref(null);

const showPermModal = ref(false);

const showDeleteRoleModal = ref(false);
const roleToDelete = ref(null);
const isDeletingRole = ref(false);

const showDeletePermModal = ref(false);
const permToDelete = ref(null);
const isDeletingPerm = ref(false);

// Filtered Roles
const filteredRoles = computed(() => {
  const q = roleSearch.value.trim().toLowerCase();
  if (!q) return props.roles;
  return props.roles.filter(
    (r) =>
      r.name.toLowerCase().includes(q) ||
      (r.permissions && r.permissions.some((p) => p.toLowerCase().includes(q)))
  );
});

// Filtered Permissions Catalog
const filteredPermissionsCatalog = computed(() => {
  let list = props.allPermissionsCatalog;

  if (selectedModuleFilter.value !== 'all') {
    list = list.filter((p) => p.module === selectedModuleFilter.value);
  }

  const q = permSearch.value.trim().toLowerCase();
  if (q) {
    list = list.filter(
      (p) =>
        p.name.toLowerCase().includes(q) ||
        p.label.toLowerCase().includes(q) ||
        p.module.toLowerCase().includes(q)
    );
  }

  return list;
});

// KPI Cards styled with Whistle-Works brand color palette
const kpiCards = computed(() => [
  {
    label: 'Total Roles',
    value: props.metrics.total_roles,
    sub: `${props.metrics.system_roles} system • ${props.metrics.custom_roles} custom`,
    icon: Shield,
    colorClass: 'text-slate-900 dark:text-white',
    iconBoxClass: 'bg-[#F29F67]/10 border border-[#F29F67]/20 text-[#F29F67]',
  },
  {
    label: 'Assigned Users',
    value: props.metrics.total_assigned,
    sub: 'Active role assignments',
    icon: Users,
    colorClass: 'text-[#34B1AA] dark:text-[#34B1AA]',
    iconBoxClass: 'bg-[#34B1AA]/10 border border-[#34B1AA]/20 text-[#34B1AA]',
  },
  {
    label: 'Total Permissions',
    value: props.metrics.total_permissions,
    sub: `${props.metrics.system_permissions} core baseline permissions`,
    icon: KeyRound,
    colorClass: 'text-[#3B8FF3]',
    iconBoxClass: 'bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 text-[#3B8FF3]',
  },
  {
    label: 'Custom Capabilities',
    value: props.metrics.custom_permissions,
    sub: 'User-created permissions',
    icon: Sparkles,
    colorClass: 'text-purple-500',
    iconBoxClass: 'bg-purple-500/10 border border-purple-500/20 text-purple-500',
  },
]);

// Actions for Roles
const openCreateRoleModal = () => {
  editingRole.value = null;
  showRoleModal.value = true;
};

const openEditRoleModal = (role) => {
  editingRole.value = role;
  showRoleModal.value = true;
};

const promptDeleteRole = (role) => {
  if (role.is_system) {
    toast.error(`System role "${role.name}" is protected and cannot be deleted.`);
    return;
  }
  if (role.users_count > 0) {
    toast.error(`Cannot delete role "${role.name}" because ${role.users_count} user(s) are currently assigned to it.`);
    return;
  }
  roleToDelete.value = role;
  showDeleteRoleModal.value = true;
};

const executeDeleteRole = () => {
  if (!roleToDelete.value) return;
  isDeletingRole.value = true;

  router.delete(`/admin/v2/roles/${roleToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Role "${roleToDelete.value?.name}" deleted successfully.`);
      showDeleteRoleModal.value = false;
      roleToDelete.value = null;
    },
    onError: (errors) => {
      const msg = errors?.error || 'Failed to delete role.';
      toast.error(msg);
    },
    onFinish: () => {
      isDeletingRole.value = false;
    },
  });
};

// Actions for Permissions
const openCreatePermModal = () => {
  showPermModal.value = true;
};

const promptDeletePerm = (perm) => {
  if (perm.is_system) {
    toast.error(`"${perm.name}" is a core system baseline permission and cannot be deleted.`);
    return;
  }
  permToDelete.value = perm;
  showDeletePermModal.value = true;
};

const executeDeletePerm = () => {
  if (!permToDelete.value) return;
  isDeletingPerm.value = true;

  router.delete(`/admin/v2/roles/permissions/${permToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Permission "${permToDelete.value?.name}" deleted successfully.`);
      showDeletePermModal.value = false;
      permToDelete.value = null;
    },
    onError: (errors) => {
      const msg = errors?.error || 'Failed to delete permission.';
      toast.error(msg);
    },
    onFinish: () => {
      isDeletingPerm.value = false;
    },
  });
};
</script>

<template>
  <AdminLayout>
    <Head title="Roles & Permissions Management" />

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Breadcrumb & Header Title -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <Breadcrumb
            :items="[
              { label: 'System Governance', href: '/admin/v2/settings' },
              { label: 'Roles & Permissions' }
            ]"
          />
          <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
              <Shield class="w-6 h-6 text-[#F29F67]" />
              <span>Roles & Permissions Hub</span>
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Enterprise role-based access control (RBAC). Configure user roles, create dynamic permissions, and maintain governance.
          </p>
        </div>

        <!-- Top Action Buttons (Themed with #F29F67) -->
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
          <button
            type="button"
            @click="openCreatePermModal"
            class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-md border border-slate-300 dark:border-white/[0.12] bg-white dark:bg-[#1E1E2C] hover:bg-slate-50 dark:hover:bg-[#262638] text-slate-700 dark:text-slate-200 shadow-2xs transition-colors cursor-pointer"
          >
            <KeyRound class="w-3.5 h-3.5 text-[#F29F67]" />
            <span>+ Create Permission</span>
          </button>

          <button
            type="button"
            @click="openCreateRoleModal"
            class="inline-flex items-center gap-2 px-3.5 py-2 text-xs font-semibold rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white shadow-sm transition-colors cursor-pointer"
          >
            <Plus class="w-4 h-4" />
            <span>Create New Role</span>
          </button>
        </div>
      </div>

      <!-- KPI Metrics Grid (Harmonious Theme) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="card in kpiCards"
          :key="card.label"
          class="p-4 rounded-md bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between"
        >
          <div>
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
              {{ card.label }}
            </span>
            <div class="text-2xl font-bold mt-1" :class="card.colorClass">
              {{ card.value }}
            </div>
            <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block">
              {{ card.sub }}
            </span>
          </div>
          <div class="w-11 h-11 rounded-md flex items-center justify-center shrink-0" :class="card.iconBoxClass">
            <component :is="card.icon" class="w-5 h-5" />
          </div>
        </div>
      </div>

      <!-- Tab Switcher Navigation -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-white/[0.08]">
        <button
          type="button"
          @click="activeTab = 'roles'"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all cursor-pointer"
          :class="[
            activeTab === 'roles'
              ? 'border-[#F29F67] text-[#F29F67] dark:text-[#F29F67]'
              : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
          ]"
        >
          <Shield class="w-4 h-4" />
          <span>Roles ({{ roles.length }})</span>
        </button>

        <button
          type="button"
          @click="activeTab = 'permissions'"
          class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all cursor-pointer"
          :class="[
            activeTab === 'permissions'
              ? 'border-[#F29F67] text-[#F29F67] dark:text-[#F29F67]'
              : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'
          ]"
        >
          <KeyRound class="w-4 h-4" />
          <span>Permissions Catalog ({{ allPermissionsCatalog.length }})</span>
        </button>
      </div>

      <!-- TAB 1: ROLES MANAGEMENT -->
      <div v-show="activeTab === 'roles'" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md shadow-xs overflow-hidden">
        <!-- Control Bar -->
        <div class="p-4 border-b border-slate-200 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/60 dark:bg-[#262638]/50">
          <div class="relative w-full sm:w-80">
            <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-2.5" />
            <input
              v-model="roleSearch"
              type="text"
              placeholder="Search roles or permissions..."
              class="w-full pl-9 pr-8 py-2 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-white dark:bg-[#151521] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
            />
            <button
              v-if="roleSearch"
              type="button"
              @click="roleSearch = ''"
              class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <div class="text-xs text-slate-500 dark:text-slate-400">
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ filteredRoles.length }}</span> of {{ roles.length }} roles
          </div>
        </div>

        <!-- Roles Grid -->
        <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="role in filteredRoles"
            :key="role.id"
            class="flex flex-col justify-between border border-slate-200 dark:border-white/[0.08] rounded-md p-4 bg-white dark:bg-[#151521]/60 hover:border-slate-300 dark:hover:border-white/[0.18] transition-all shadow-2xs"
          >
            <!-- Card Header -->
            <div>
              <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100 dark:border-white/[0.06]">
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-9 h-9 rounded-md flex items-center justify-center border font-mono font-bold text-sm"
                    :class="[
                      role.is_admin
                        ? 'bg-[#3B8FF3]/10 text-[#3B8FF3] border-[#3B8FF3]/20'
                        : role.is_system
                        ? 'bg-[#F29F67]/10 text-[#F29F67] border-[#F29F67]/20'
                        : 'bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 border-slate-200 dark:border-white/[0.08]'
                    ]"
                  >
                    {{ role.name.charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5 capitalize">
                      <span>{{ role.name }}</span>
                    </h3>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">
                      guard: {{ role.guard_name }}
                    </span>
                  </div>
                </div>

                <!-- Badges -->
                <div class="flex items-center gap-1.5 flex-wrap justify-end">
                  <span
                    v-if="role.is_admin"
                    class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-[#3B8FF3]/10 text-[#3B8FF3] border border-[#3B8FF3]/20 inline-flex items-center gap-1"
                  >
                    <Sparkles class="w-3 h-3" /> Superuser
                  </span>
                  <span
                    v-else-if="role.is_system"
                    class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-[#F29F67]/10 text-[#F29F67] border border-[#F29F67]/20 inline-flex items-center gap-1"
                  >
                    <Lock class="w-3 h-3" /> Core Role
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-[#34B1AA]/10 text-[#34B1AA] border border-[#34B1AA]/20"
                  >
                    Custom
                  </span>
                </div>
              </div>

              <!-- Stats & Summary Row -->
              <div class="flex items-center gap-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                <div class="flex items-center gap-1.5">
                  <Users class="w-3.5 h-3.5 text-slate-400" />
                  <span>
                    <strong class="text-slate-900 dark:text-white">{{ role.users_count }}</strong>
                    {{ role.users_count === 1 ? 'user' : 'users' }} assigned
                  </span>
                </div>
                <div class="flex items-center gap-1.5">
                  <KeyRound class="w-3.5 h-3.5 text-slate-400" />
                  <span>
                    <strong class="text-slate-900 dark:text-white">{{ role.permissions_count }}</strong>
                    permissions active
                  </span>
                </div>
              </div>

              <!-- Permissions Preview Chips -->
              <div class="pt-1 pb-2">
                <div v-if="role.permissions && role.permissions.length > 0" class="flex flex-wrap gap-1.5">
                  <span
                    v-for="perm in role.permissions.slice(0, 5)"
                    :key="perm"
                    class="px-2 py-0.5 text-[11px] rounded-md font-mono bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08]"
                  >
                    {{ perm }}
                  </span>
                  <span
                    v-if="role.permissions.length > 5"
                    class="px-2 py-0.5 text-[11px] rounded-md font-bold bg-[#F29F67]/10 text-[#F29F67] border border-[#F29F67]/20"
                  >
                    +{{ role.permissions.length - 5 }} more
                  </span>
                </div>
                <div v-else class="text-xs text-slate-400 dark:text-slate-500 italic py-1">
                  No individual permissions granted.
                </div>
              </div>
            </div>

            <!-- Card Footer / Actions -->
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-white/[0.06] flex items-center justify-between gap-2">
              <span class="text-[11px] text-slate-400 dark:text-slate-500">
                Created: {{ role.created_at }}
              </span>

              <div class="flex items-center gap-1.5">
                <!-- Configure Button -->
                <button
                  type="button"
                  @click="openEditRoleModal(role)"
                  class="px-2.5 py-1.5 text-xs font-semibold rounded-md border border-slate-300 dark:border-white/[0.12] text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                  <Edit3 class="w-3.5 h-3.5 text-[#F29F67]" />
                  <span>Configure</span>
                </button>

                <!-- Delete Button -->
                <button
                  type="button"
                  @click="promptDeleteRole(role)"
                  :disabled="role.is_system || role.users_count > 0"
                  :title="
                    role.is_system
                      ? 'System roles are protected and cannot be deleted.'
                      : role.users_count > 0
                      ? 'Cannot delete role with assigned users.'
                      : 'Delete role'
                  "
                  class="p-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-white/[0.08] text-slate-400 hover:text-rose-500 hover:border-rose-500/20 hover:bg-rose-500/10 transition-colors disabled:opacity-40 disabled:hover:bg-transparent disabled:hover:text-slate-400 disabled:hover:border-slate-200 dark:disabled:hover:border-white/[0.08] disabled:cursor-not-allowed cursor-pointer"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="filteredRoles.length === 0"
          class="py-16 text-center text-slate-500 dark:text-slate-400 space-y-2"
        >
          <ShieldAlert class="w-10 h-10 text-slate-400 mx-auto" />
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">No roles found</h3>
          <p class="text-xs max-w-sm mx-auto">
            Try adjusting your search filter or create a new role using the button above.
          </p>
        </div>
      </div>

      <!-- TAB 2: PERMISSIONS CATALOG -->
      <div v-show="activeTab === 'permissions'" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md shadow-xs overflow-hidden">
        <!-- Control & Filter Bar -->
        <div class="p-4 border-b border-slate-200 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/60 dark:bg-[#262638]/50">
          <div class="flex items-center gap-3 w-full sm:w-auto flex-1">
            <!-- Search -->
            <div class="relative w-full sm:w-72">
              <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-2.5" />
              <input
                v-model="permSearch"
                type="text"
                placeholder="Search permissions or labels..."
                class="w-full pl-9 pr-8 py-2 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-white dark:bg-[#151521] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              />
              <button
                v-if="permSearch"
                type="button"
                @click="permSearch = ''"
                class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
              >
                <X class="w-4 h-4" />
              </button>
            </div>

            <!-- Module Filter Dropdown -->
            <div class="w-52 shrink-0 hidden sm:block">
              <select
                v-model="selectedModuleFilter"
                class="w-full px-3 py-2 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-white dark:bg-[#151521] text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              >
                <option value="all">All Modules ({{ allPermissionsCatalog.length }})</option>
                <option v-for="mod in availableModules" :key="mod" :value="mod">
                  {{ mod }}
                </option>
              </select>
            </div>
          </div>

          <div class="flex items-center gap-3 shrink-0">
            <span class="text-xs text-slate-500 dark:text-slate-400">
              Showing <span class="font-bold text-slate-900 dark:text-white">{{ filteredPermissionsCatalog.length }}</span> permissions
            </span>
            <button
              type="button"
              @click="openCreatePermModal"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white shadow-xs transition-colors cursor-pointer"
            >
              <Plus class="w-3.5 h-3.5" />
              <span>New Permission</span>
            </button>
          </div>
        </div>

        <!-- Permissions Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/70 dark:bg-[#262638]/60 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                <th class="px-5 py-3">Permission</th>
                <th class="px-5 py-3">Module</th>
                <th class="px-5 py-3">Type</th>
                <th class="px-5 py-3">Granted Roles</th>
                <th class="px-5 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.04] text-xs">
              <tr
                v-for="perm in filteredPermissionsCatalog"
                :key="perm.id"
                class="hover:bg-slate-50/60 dark:hover:bg-white/[0.02] transition-colors"
              >
                <!-- Permission Info -->
                <td class="px-5 py-3.5">
                  <div class="font-semibold text-slate-900 dark:text-white">
                    {{ perm.label }}
                  </div>
                  <div class="text-[11px] font-mono text-slate-400 dark:text-slate-500 mt-0.5">
                    {{ perm.name }}
                  </div>
                </td>

                <!-- Module -->
                <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-slate-100 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] text-[11px] font-medium">
                    {{ perm.module }}
                  </span>
                </td>

                <!-- Type -->
                <td class="px-5 py-3.5">
                  <span
                    v-if="perm.is_system"
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#F29F67]/10 text-[#F29F67] border border-[#F29F67]/20 inline-flex items-center gap-1"
                  >
                    <Lock class="w-3 h-3" /> Core System
                  </span>
                  <span
                    v-else
                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-[#34B1AA]/10 text-[#34B1AA] border border-[#34B1AA]/20 inline-flex items-center gap-1"
                  >
                    <Sparkles class="w-3 h-3" /> Custom
                  </span>
                </td>

                <!-- Granted Roles Chips -->
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1.5 flex-wrap max-w-sm">
                    <span
                      v-for="rName in perm.roles"
                      :key="rName"
                      class="px-2 py-0.5 text-[10px] font-medium rounded-md capitalize bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.06]"
                    >
                      {{ rName }}
                    </span>
                    <span v-if="!perm.roles || perm.roles.length === 0" class="text-slate-400 italic text-[11px]">
                      Not assigned
                    </span>
                  </div>
                </td>

                <!-- Actions -->
                <td class="px-5 py-3.5 text-right">
                  <button
                    v-if="!perm.is_system"
                    type="button"
                    @click="promptDeletePerm(perm)"
                    title="Delete custom permission"
                    class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 rounded-md transition-colors cursor-pointer"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                  <span
                    v-else
                    title="System baseline permissions cannot be deleted"
                    class="text-slate-300 dark:text-slate-600 p-1.5 inline-block cursor-not-allowed"
                  >
                    <Lock class="w-3.5 h-3.5" />
                  </span>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Empty Permissions State -->
          <div
            v-if="filteredPermissionsCatalog.length === 0"
            class="py-14 text-center text-slate-500 dark:text-slate-400 space-y-2"
          >
            <KeyRound class="w-8 h-8 text-slate-400 mx-auto" />
            <h4 class="text-sm font-bold text-slate-900 dark:text-white">No permissions found</h4>
            <p class="text-xs">Adjust your search query or module filter above.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <RoleModal
      :show="showRoleModal"
      :role="editingRole"
      :grouped-permissions="groupedPermissions"
      @close="showRoleModal = false"
    />

    <PermissionModal
      :show="showPermModal"
      :available-modules="availableModules"
      @close="showPermModal = false"
    />

    <!-- Delete Role Modal -->
    <Teleport to="body">
      <div
        v-if="showDeleteRoleModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
        @keydown.esc="showDeleteRoleModal = false"
      >
        <div
          class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
          @click="showDeleteRoleModal = false"
        ></div>

        <div
          class="relative w-full max-w-md bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md shadow-2xl overflow-hidden p-6 z-10 animate-in fade-in zoom-in-95 duration-150 space-y-4"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md bg-rose-500/10 text-rose-500 flex items-center justify-center border border-rose-500/20 shrink-0">
              <Trash2 class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                Delete Role
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Are you sure you want to permanently delete role <strong class="text-slate-900 dark:text-white capitalize font-mono">"{{ roleToDelete?.name }}"</strong>?
              </p>
            </div>
          </div>

          <div class="text-xs text-amber-700 dark:text-amber-300 bg-amber-500/10 border border-amber-500/20 rounded-md p-3">
            This action is permanent and will revoke all permissions attached to this role.
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showDeleteRoleModal = false"
              :disabled="isDeletingRole"
              class="px-3.5 py-1.5 text-xs font-medium rounded-md border border-slate-300 dark:border-white/[0.12] text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-colors disabled:opacity-50 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="executeDeleteRole"
              :disabled="isDeletingRole"
              class="px-4 py-1.5 text-xs font-semibold rounded-md bg-[#E05345] hover:bg-[#c94538] text-white shadow-sm inline-flex items-center gap-1.5 transition-colors disabled:opacity-50 cursor-pointer"
            >
              <Loader2 v-if="isDeletingRole" class="w-3.5 h-3.5 animate-spin" />
              <span>Confirm Delete</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Permission Modal -->
    <Teleport to="body">
      <div
        v-if="showDeletePermModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
        @keydown.esc="showDeletePermModal = false"
      >
        <div
          class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
          @click="showDeletePermModal = false"
        ></div>

        <div
          class="relative w-full max-w-md bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md shadow-2xl overflow-hidden p-6 z-10 animate-in fade-in zoom-in-95 duration-150 space-y-4"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md bg-rose-500/10 text-rose-500 flex items-center justify-center border border-rose-500/20 shrink-0">
              <Trash2 class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                Delete Custom Permission
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Are you sure you want to delete permission <strong class="text-slate-900 dark:text-white font-mono">"{{ permToDelete?.name }}"</strong>?
              </p>
            </div>
          </div>

          <div class="text-xs text-rose-700 dark:text-rose-300 bg-rose-500/10 border border-rose-500/20 rounded-md p-3">
            This will immediately revoke this permission from all assigned roles.
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showDeletePermModal = false"
              :disabled="isDeletingPerm"
              class="px-3.5 py-1.5 text-xs font-medium rounded-md border border-slate-300 dark:border-white/[0.12] text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-colors disabled:opacity-50 cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="executeDeletePerm"
              :disabled="isDeletingPerm"
              class="px-4 py-1.5 text-xs font-semibold rounded-md bg-[#E05345] hover:bg-[#c94538] text-white shadow-sm inline-flex items-center gap-1.5 transition-colors disabled:opacity-50 cursor-pointer"
            >
              <Loader2 v-if="isDeletingPerm" class="w-3.5 h-3.5 animate-spin" />
              <span>Confirm Delete</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
