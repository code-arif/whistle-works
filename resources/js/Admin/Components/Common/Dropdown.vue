<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, Object],
    default: null,
  },
  options: {
    type: Array,
    required: true,
    // Format: [{ label: '10 / page', value: 10 }] or ['10', '25', '50']
  },
  placeholder: {
    type: String,
    default: 'Select option',
  },
  align: {
    type: String,
    default: 'right',
    validator: (val) => ['left', 'right'].includes(val),
  },
  size: {
    type: String,
    default: 'sm',
    validator: (val) => ['xs', 'sm', 'md'].includes(val),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  buttonClass: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const normalizedOptions = computed(() => {
  return props.options.map((opt) => {
    if (typeof opt === 'object' && opt !== null) {
      return {
        label: opt.label !== undefined ? opt.label : opt.name || opt.value,
        value: opt.value !== undefined ? opt.value : opt.id,
        icon: opt.icon || null,
      };
    }
    return { label: String(opt), value: opt, icon: null };
  });
});

const selectedOption = computed(() => {
  return normalizedOptions.value.find((opt) => opt.value === props.modelValue);
});

const displayLabel = computed(() => {
  return selectedOption.value ? selectedOption.value.label : props.placeholder;
});

const toggle = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
};

const select = (opt) => {
  emit('update:modelValue', opt.value);
  emit('change', opt.value);
  isOpen.value = false;
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div ref="dropdownRef" class="relative inline-block text-left flex-shrink-0">
    <!-- Trigger Button -->
    <button
      type="button"
      @click="toggle"
      :disabled="disabled"
      :class="[
        buttonClass,
        size === 'xs' ? 'px-2 py-1 text-[11px]' : size === 'sm' ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-2 text-sm',
        'inline-flex items-center justify-between gap-1.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-slate-100 hover:bg-slate-100 dark:hover:bg-[#2C2C40] hover:border-slate-300 dark:hover:border-white/[0.15] focus:outline-none focus:ring-1 focus:ring-[#F29F67] transition-all cursor-pointer select-none font-mono disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs'
      ]"
    >
      <span class="truncate">{{ displayLabel }}</span>
      <ChevronDown
        :class="[
          'w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-150 flex-shrink-0',
          isOpen ? 'rotate-180 text-[#F29F67]' : ''
        ]"
      />
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        :class="[
          align === 'right' ? 'right-0' : 'left-0',
          'absolute z-50 mt-1 min-w-[120px] max-h-60 overflow-y-auto rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xl p-1 backdrop-blur-xl focus:outline-none'
        ]"
      >
        <button
          v-for="opt in normalizedOptions"
          :key="opt.value"
          type="button"
          @click="select(opt)"
          :class="[
            opt.value === modelValue
              ? 'bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] font-semibold'
              : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#32324A] hover:text-slate-900 dark:hover:text-white',
            'w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-md text-xs font-mono transition-colors text-left cursor-pointer'
          ]"
        >
          <span class="truncate">{{ opt.label }}</span>
          <Check v-if="opt.value === modelValue" class="w-3.5 h-3.5 text-[#F29F67] flex-shrink-0" />
        </button>
      </div>
    </Transition>
  </div>
</template>
