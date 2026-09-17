<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Admin/Composables/useToast';
import {
  X,
  Shield,
  ShieldCheck,
  Search,
  Check,
  CheckSquare,
  Square,
  AlertCircle,
  Loader2,
  Lock
} from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  role: {
    type: Object,
    default: null,
  },
  groupedPermissions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'saved']);

const toast = useToast();
const searchQuery = ref('');

const form = useForm({
  name: '',
  permissions: [],
});

const isEditing = computed(() => !!props.role);
const isProtectedRole = computed(() => props.role?.is_system ?? false);
const isAdminRole = computed(() => props.role?.is_admin || props.role?.name === 'admin');

// Reset or populate form when modal opens
watch(
  () => props.show,
  (val) => {
    if (val) {
      searchQuery.value = '';
      form.clearErrors();
      if (props.role) {
        form.name = props.role.name;
        form.permissions = Array.isArray(props.role.permissions) ? [...props.role.permissions] : [];
      } else {
        form.name = '';
        form.permissions = [];
      }
    }
  }
);

// All permission names across all groups
const allAvailablePermissions = computed(() => {
  const list = [];
  props.groupedPermissions.forEach((grp) => {
    grp.permissions?.forEach((p) => {
      list.push(p.name);
    });
  });
  return list;
});

// Filter groups and their permissions by search query
const filteredGroups = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) {
    return props.groupedPermissions;
  }

  return props.groupedPermissions
    .map((grp) => {
      const moduleMatch = grp.module.toLowerCase().includes(query);
      const matchedPerms = grp.permissions.filter(
        (p) =>
          moduleMatch ||
          p.name.toLowerCase().includes(query) ||
          (p.label && p.label.toLowerCase().includes(query))
      );

      return {
        ...grp,
        permissions: matchedPerms,
      };
    })
    .filter((grp) => grp.permissions.length > 0);
});

// Master select all / deselect all
const isAllSelected = computed(() => {
  if (allAvailablePermissions.value.length === 0) return false;
  return allAvailablePermissions.value.every((p) => form.permissions.includes(p));
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    form.permissions = [];
  } else {
    form.permissions = [...allAvailablePermissions.value];
  }
};

// Module-level select all / deselect all
const isModuleAllSelected = (group) => {
  if (!group.permissions || group.permissions.length === 0) return false;
  return group.permissions.every((p) => form.permissions.includes(p.name));
};

const isModulePartiallySelected = (group) => {
  if (!group.permissions || group.permissions.length === 0) return false;
  const count = group.permissions.filter((p) => form.permissions.includes(p.name)).length;
  return count > 0 && count < group.permissions.length;
};

const toggleModule = (group) => {
  const names = group.permissions.map((p) => p.name);
  if (isModuleAllSelected(group)) {
    form.permissions = form.permissions.filter((name) => !names.includes(name));
  } else {
    const toAdd = names.filter((name) => !form.permissions.includes(name));
    form.permissions.push(...toAdd);
  }
};

// Individual permission toggle
const togglePermission = (permName) => {
  const index = form.permissions.indexOf(permName);
  if (index > -1) {
    form.permissions.splice(index, 1);
  } else {
    form.permissions.push(permName);
  }
};

const submit = () => {
  if (isEditing.value) {
    form.put(`/admin/v2/roles/${props.role.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`Role "${props.role.name}" updated successfully`);
        emit('saved');
        emit('close');
      },
      onError: () => {
        toast.error('Failed to update role. Please verify your input.');
      },
    });
  } else {
    form.post('/admin/v2/roles', {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(`Role created successfully`);
        emit('saved');
        emit('close');
      },
      onError: () => {
        toast.error('Failed to create role. Please verify your input.');
      },
    });
  }
};
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
      @keydown.esc="emit('close')"
    >
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
        @click="emit('close')"
      ></div>

      <!-- Modal Card -->
      <div
        class="relative w-full max-w-3xl bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md shadow-2xl overflow-hidden flex flex-col my-8 max-h-[88vh] z-10 animate-in fade-in zoom-in-95 duration-150"
      >
        <!-- Modal Header -->
        <div
          class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/60 dark:bg-[#262638]/50"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-md bg-[#F29F67]/10 text-[#F29F67] flex items-center justify-center border border-[#F29F67]/20 shrink-0"
            >
              <Shield class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>{{ isEditing ? `Edit Role: ${role?.name}` : 'Create New Role' }}</span>
                <span
                  v-if="isProtectedRole"
                  class="text-[11px] font-medium px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 inline-flex items-center gap-1"
                >
                  <Lock class="w-3 h-3" /> System Role
                </span>
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Configure role identity and granular access permissions across system modules.
              </p>
            </div>
          </div>
          <button
            type="button"
            @click="emit('close')"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-colors"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- System Role Notification Banner -->
        <div
          v-if="isAdminRole"
          class="px-6 py-3 bg-blue-500/10 border-b border-blue-500/20 flex items-start gap-3 text-xs text-blue-700 dark:text-blue-300"
        >
          <ShieldCheck class="w-4 h-4 text-[#3B8FF3] shrink-0 mt-0.5" />
          <div>
            <span class="font-bold">Super Administrator:</span> The <code>admin</code> role retains master access to all system resources. Its role identifier cannot be modified.
          </div>
        </div>
        <div
          v-else-if="isProtectedRole"
          class="px-6 py-3 bg-amber-500/10 border-b border-amber-500/20 flex items-start gap-3 text-xs text-amber-700 dark:text-amber-300"
        >
          <AlertCircle class="w-4 h-4 text-[#F59E0B] shrink-0 mt-0.5" />
          <div>
            <span class="font-bold">Protected Core Role:</span> This role is critical to platform operations. The role identifier is locked, but you can adjust permissions below.
          </div>
        </div>

        <!-- Form Body (Scrollable) -->
        <form @submit.prevent="submit" id="role-modal-form" class="flex-1 overflow-y-auto p-6 space-y-6">
          <!-- Role Name Field -->
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
              Role Name <span class="text-[#F29F67]">*</span>
            </label>
            <div class="relative">
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. content_manager or regional_evaluator"
                :disabled="isProtectedRole"
                class="w-full px-3.5 py-2.5 text-sm rounded-md border border-slate-300 dark:border-white/[0.12] bg-white dark:bg-[#151521] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] disabled:opacity-60 disabled:bg-slate-100 dark:disabled:bg-white/[0.04] disabled:cursor-not-allowed transition"
              />
              <Lock
                v-if="isProtectedRole"
                class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute right-3 top-3"
              />
            </div>
            <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-500 font-medium">
              {{ form.errors.name }}
            </p>
            <p v-else class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
              Unique name identifier (lowercase letters, numbers, dashes, and underscores).
            </p>
          </div>

          <!-- Permissions Section -->
          <div class="space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-2 border-b border-slate-200 dark:border-white/[0.08]">
              <div>
                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                  Module Permissions
                </h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Selected <span class="font-bold text-[#F29F67]">{{ form.permissions.length }}</span> of {{ allAvailablePermissions.length }} total permissions
                </p>
              </div>

              <!-- Master Actions & Search -->
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="toggleSelectAll"
                  class="text-xs font-medium px-2.5 py-1.5 rounded-md border border-slate-300 dark:border-white/[0.12] text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-colors inline-flex items-center gap-1.5 cursor-pointer"
                >
                  <CheckSquare v-if="!isAllSelected" class="w-3.5 h-3.5 text-[#F29F67]" />
                  <Square v-else class="w-3.5 h-3.5 text-slate-400" />
                  <span>{{ isAllSelected ? 'Deselect All' : 'Select All' }}</span>
                </button>
              </div>
            </div>

            <!-- Permission Search Bar -->
            <div class="relative">
              <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-2.5" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Filter permissions or modules..."
                class="w-full pl-9 pr-8 py-2 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#151521] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              />
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
              >
                <X class="w-4 h-4" />
              </button>
            </div>

            <!-- Modules List -->
            <div class="space-y-3 pt-1">
              <div
                v-for="group in filteredGroups"
                :key="group.module"
                class="border border-slate-200 dark:border-white/[0.08] rounded-md overflow-hidden bg-slate-50/40 dark:bg-[#151521]/40"
              >
                <!-- Group Header -->
                <div
                  class="flex items-center justify-between px-4 py-2.5 bg-slate-100/70 dark:bg-[#262638]/60 border-b border-slate-200 dark:border-white/[0.08] cursor-pointer select-none"
                  @click="toggleModule(group)"
                >
                  <div class="flex items-center gap-2.5">
                    <div
                      class="w-4 h-4 rounded flex items-center justify-center border transition-colors"
                      :class="[
                        isModuleAllSelected(group)
                          ? 'bg-[#F29F67] border-[#F29F67] text-white'
                          : isModulePartiallySelected(group)
                          ? 'bg-[#F29F67]/20 border-[#F29F67] text-[#F29F67]'
                          : 'border-slate-300 dark:border-white/[0.2] bg-white dark:bg-[#1E1E2C]'
                      ]"
                    >
                      <Check v-if="isModuleAllSelected(group)" class="w-3 h-3 stroke-[3]" />
                      <div v-else-if="isModulePartiallySelected(group)" class="w-2 h-0.5 bg-[#F29F67] rounded"></div>
                    </div>
                    <span class="text-xs font-bold text-slate-900 dark:text-slate-200">
                      {{ group.module }}
                    </span>
                  </div>

                  <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                    {{ group.permissions.filter(p => form.permissions.includes(p.name)).length }}/{{ group.permissions.length }}
                  </span>
                </div>

                <!-- Group Permissions Grid -->
                <div class="p-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                  <div
                    v-for="perm in group.permissions"
                    :key="perm.id"
                    @click="togglePermission(perm.name)"
                    class="flex items-start gap-2.5 p-2 rounded-md border cursor-pointer select-none transition-all"
                    :class="[
                      form.permissions.includes(perm.name)
                        ? 'bg-[#F29F67]/10 border-[#F29F67]/30 text-slate-900 dark:text-white'
                        : 'bg-white dark:bg-[#1E1E2C] border-slate-200/80 dark:border-white/[0.06] hover:border-slate-300 dark:hover:border-white/[0.15] text-slate-700 dark:text-slate-300'
                    ]"
                  >
                    <div
                      class="w-3.5 h-3.5 rounded mt-0.5 flex items-center justify-center border transition-colors shrink-0"
                      :class="[
                        form.permissions.includes(perm.name)
                          ? 'bg-[#F29F67] border-[#F29F67] text-white'
                          : 'border-slate-300 dark:border-white/[0.2] bg-white dark:bg-[#151521]'
                      ]"
                    >
                      <Check v-if="form.permissions.includes(perm.name)" class="w-2.5 h-2.5 stroke-[3]" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <div class="text-xs font-medium leading-tight truncate">
                        {{ perm.label }}
                      </div>
                      <div class="text-[10px] text-slate-400 dark:text-slate-500 font-mono truncate mt-0.5">
                        {{ perm.name }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty state when search matches nothing -->
              <div
                v-if="filteredGroups.length === 0"
                class="py-8 text-center text-xs text-slate-500 dark:text-slate-400"
              >
                No permissions matching "{{ searchQuery }}"
              </div>
            </div>
          </div>
        </form>

        <!-- Modal Footer -->
        <div
          class="flex items-center justify-between px-6 py-4 border-t border-slate-200 dark:border-white/[0.08] bg-slate-50/60 dark:bg-[#262638]/50"
        >
          <button
            type="button"
            @click="emit('close')"
            :disabled="form.processing"
            class="px-4 py-2 text-xs font-medium rounded-md border border-slate-300 dark:border-white/[0.12] text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-white/[0.05] transition-colors disabled:opacity-50 cursor-pointer"
          >
            Cancel
          </button>

          <button
            type="submit"
            form="role-modal-form"
            :disabled="form.processing"
            class="px-5 py-2 text-xs font-semibold rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white shadow-sm inline-flex items-center gap-2 transition-colors disabled:opacity-50 cursor-pointer"
          >
            <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
            <span>{{ isEditing ? 'Save Changes' : 'Create Role' }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
