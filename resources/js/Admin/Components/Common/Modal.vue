<script setup>
import { watch, onMounted, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg', 'xl', '2xl'].includes(value),
  },
  closeable: {
    type: Boolean,
    default: true,
  },
  title: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close']);

const close = () => {
  if (props.closeable) {
    emit('close');
  }
};

const closeOnEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    close();
  }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

watch(
  () => props.show,
  (show) => {
    if (show) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = null;
    }
  }
);

const maxWidthClass = {
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl',
}[props.maxWidth];
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-show="show" class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-center justify-center">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/60 dark:bg-black/75 backdrop-blur-xs transition-opacity" @click="close"></div>

        <!-- Modal Dialog Box -->
        <Transition
          enter-active-class="ease-out duration-200"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div
            v-show="show"
            :class="[
              maxWidthClass,
              'relative w-full rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-2xl overflow-hidden transition-all my-auto z-10'
            ]"
          >
            <!-- Modal Header -->
            <div v-if="title || $slots.header" class="px-5 py-4 border-b border-slate-100 dark:border-white/[0.08] flex items-center justify-between">
              <slot name="header">
                <h3 class="text-base font-bold font-display text-slate-900 dark:text-white">
                  {{ title }}
                </h3>
              </slot>
              <button
                v-if="closeable"
                @click="close"
                class="p-1 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#1E1E2C] transition-colors cursor-pointer"
              >
                <X class="w-4 h-4" />
              </button>
            </div>

            <!-- Modal Body Content -->
            <div class="p-5 sm:p-6 text-slate-700 dark:text-slate-300 text-xs sm:text-sm">
              <slot />
            </div>

            <!-- Modal Footer -->
            <div v-if="$slots.footer" class="px-5 py-3.5 bg-slate-50 dark:bg-[#1E1E2C]/80 border-t border-slate-100 dark:border-white/[0.08] flex flex-wrap items-center justify-end gap-2.5">
              <slot name="footer" />
            </div>
          </div>
        </Transition>

      </div>
    </Transition>
  </Teleport>
</template>
