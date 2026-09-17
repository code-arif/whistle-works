<script setup>
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useToast } from '@/Admin/Composables/useToast';
import { Check, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const page = usePage();
const toast = useToast();

// Automatically bridge Inertia backend flash messages into the floating Toast
watch(
  () => [page.props.flash?.success, page.props.flash?.error],
  ([newSuccess, newError]) => {
    if (newSuccess) {
      toast.success(newSuccess);
    }
    if (newError) {
      toast.error(newError);
    }
  },
  { immediate: true }
);

const getToastClasses = (type) => {
  switch (type) {
    case 'error':
      return 'bg-rose-950/95 dark:bg-rose-900/95 text-rose-100 border-rose-500/30 shadow-rose-950/20';
    case 'warning':
      return 'bg-amber-950/95 dark:bg-amber-900/95 text-amber-100 border-amber-500/30 shadow-amber-950/20';
    case 'info':
      return 'bg-sky-950/95 dark:bg-sky-900/95 text-sky-100 border-sky-500/30 shadow-sky-950/20';
    case 'success':
    default:
      return 'bg-slate-900/95 dark:bg-white/95 text-white dark:text-slate-950 border-white/10 dark:border-slate-900/10 shadow-black/20';
  }
};
</script>

<template>
  <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none max-w-sm w-full sm:w-auto">
    <TransitionGroup
      enter-active-class="transition duration-250 ease-out"
      enter-from-class="transform translate-y-3 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform translate-y-2 opacity-0 scale-95"
    >
      <div
        v-for="item in toast.toasts.value"
        :key="item.id"
        :class="[
          getToastClasses(item.type),
          'pointer-events-auto flex items-center justify-between gap-3 px-4 py-2.5 rounded-md text-xs font-mono shadow-2xl backdrop-blur-md border select-none transition-all'
        ]"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <Check v-if="item.type === 'success'" class="w-4 h-4 text-[#F29F67] shrink-0" />
          <AlertCircle v-else-if="item.type === 'error'" class="w-4 h-4 text-rose-400 shrink-0" />
          <AlertTriangle v-else-if="item.type === 'warning'" class="w-4 h-4 text-amber-400 shrink-0" />
          <Info v-else class="w-4 h-4 text-sky-400 shrink-0" />

          <span class="truncate leading-tight">{{ item.message }}</span>
        </div>

        <button
          type="button"
          @click="toast.dismiss(item.id)"
          class="p-0.5 rounded hover:bg-white/10 dark:hover:bg-black/10 opacity-70 hover:opacity-100 transition-opacity cursor-pointer shrink-0 ml-1"
          title="Dismiss notification"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
