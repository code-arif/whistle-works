<script setup>
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from '@/Admin/Composables/useToast';
import {
  X,
  KeyRound,
  Sparkles,
  Loader2,
  Layers,
  HelpCircle,
  Check
} from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  availableModules: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close', 'saved']);
const toast = useToast();

const form = useForm({
  name: '',
  module: 'Custom & Additional Permissions',
});

// Normalized preview
const formattedSlug = computed(() => {
  return form.name
    .trim()
    .toLowerCase()
    .replace(/\s+/g, ' ');
});

watch(
  () => props.show,
  (val) => {
    if (val) {
      form.reset();
      form.clearErrors();
      form.module = props.availableModules[0] || 'Custom & Additional Permissions';
    }
  }
);

const submit = () => {
  if (!form.name.trim()) {
    form.setError('name', 'Permission name is required');
    return;
  }

  form.post('/admin/v2/roles/permissions', {
    preserveScroll: true,
    onSuccess: () => {
      toast.success(`Permission "${formattedSlug.value}" created successfully`);
      emit('saved');
      emit('close');
    },
    onError: (err) => {
      toast.error(err.name || 'Failed to create permission.');
    },
  });
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
        class="relative w-full max-w-lg bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md shadow-2xl overflow-hidden flex flex-col my-8 z-10 animate-in fade-in zoom-in-95 duration-150"
      >
        <!-- Modal Header -->
        <div
          class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-white/[0.08] bg-slate-50/60 dark:bg-[#262638]/50"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-md bg-[#F29F67]/10 text-[#F29F67] border border-[#F29F67]/20 flex items-center justify-center shrink-0"
            >
              <KeyRound class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                Create New Permission
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Define a fine-grained capability to grant to roles across the platform.
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

        <!-- Form Body -->
        <form @submit.prevent="submit" id="perm-form" class="p-6 space-y-5">
          <!-- Permission Name -->
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
              Permission Name <span class="text-[#F29F67]">*</span>
            </label>
            <input
              v-model="form.name"
              type="text"
              placeholder="e.g. refund payments, view analytics, export rosters"
              class="w-full px-3.5 py-2.5 text-sm rounded-md border border-slate-300 dark:border-white/[0.12] bg-white dark:bg-[#151521] text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
            />
            <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-500 font-medium">
              {{ form.errors.name }}
            </p>
            <div v-else class="mt-1.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
              <span>Preview key:</span>
              <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-white/[0.06] text-slate-800 dark:text-slate-200 font-mono text-[11px]">
                {{ formattedSlug || 'example-permission' }}
              </code>
            </div>
          </div>

          <!-- Module Selector -->
          <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
              Module Grouping
            </label>
            <div class="relative">
              <select
                v-model="form.module"
                class="w-full px-3.5 py-2.5 text-sm rounded-md border border-slate-300 dark:border-white/[0.12] bg-white dark:bg-[#151521] text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              >
                <option v-for="mod in availableModules" :key="mod" :value="mod">
                  {{ mod }}
                </option>
              </select>
            </div>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
              Categorizes the permission in the role configuration matrix.
            </p>
          </div>

          <!-- Info Banner -->
          <div class="p-3.5 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300">
            <Sparkles class="w-4 h-4 text-[#F29F67] shrink-0 mt-0.5" />
            <div>
              <strong class="text-slate-900 dark:text-white">Auto-assigned to Super Admin:</strong> New permissions are automatically added to the master <code>admin</code> role and made immediately available for custom role assignment.
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
            form="perm-form"
            :disabled="form.processing || !form.name.trim()"
            class="px-5 py-2 text-xs font-semibold rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white shadow-sm inline-flex items-center gap-2 transition-colors disabled:opacity-50 cursor-pointer"
          >
            <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
            <span>Create Permission</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
