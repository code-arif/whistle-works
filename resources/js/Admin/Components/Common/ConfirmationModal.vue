<script setup>
import Modal from './Modal.vue';
import { AlertTriangle, Info } from 'lucide-vue-next';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Confirm Action',
  },
  message: {
    type: String,
    default: 'Are you sure you want to perform this action?',
  },
  confirmText: {
    type: String,
    default: 'Confirm',
  },
  cancelText: {
    type: String,
    default: 'Cancel',
  },
  type: {
    type: String,
    default: 'danger',
    validator: (val) => ['danger', 'warning', 'info'].includes(val),
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close', 'confirm']);
</script>

<template>
  <Modal :show="show" max-width="sm" @close="$emit('close')">
    <div class="flex items-start gap-4">
      <div 
        :class="[
          type === 'danger' ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
          'w-10 h-10 rounded-full border flex items-center justify-center flex-shrink-0'
        ]"
      >
        <AlertTriangle v-if="type === 'danger' || type === 'warning'" class="w-5 h-5" />
        <Info v-else class="w-5 h-5" />
      </div>

      <div class="space-y-1">
        <h3 class="text-base font-bold font-display text-slate-900 dark:text-white leading-tight">
          {{ title }}
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
          {{ message }}
        </p>
      </div>
    </div>

    <template #footer>
      <button
        @click="$emit('close')"
        type="button"
        :disabled="isLoading"
        class="px-3.5 py-2 text-xs font-medium rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer"
      >
        {{ cancelText }}
      </button>
      <button
        @click="$emit('confirm')"
        type="button"
        :disabled="isLoading"
        :class="[
          type === 'danger' ? 'bg-rose-600 hover:bg-rose-500 active:bg-rose-700 text-white' : 'bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold',
          'px-3.5 py-2 text-xs font-semibold rounded-md shadow-xs transition-colors cursor-pointer flex items-center gap-1.5'
        ]"
      >
        <span v-if="isLoading" class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
        <span>{{ confirmText }}</span>
      </button>
    </template>
  </Modal>
</template>
