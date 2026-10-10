<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Check, Search, X } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, Object],
    default: null,
  },
  options: {
    type: Array,
    required: true,
    // Format: [{ label: 'Name', value: 10, subLabel: 'email@example.com', avatar: 'url' }] or ['10', '25', '50']
  },
  placeholder: {
    type: String,
    default: 'Select option',
  },
  searchable: {
    type: Boolean,
    default: false,
  },
  searchPlaceholder: {
    type: String,
    default: 'Search...',
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
  menuClass: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const dropdownRef = ref(null);
const searchInputRef = ref(null);
const searchQuery = ref('');

const normalizedOptions = computed(() => {
  return props.options.map((opt) => {
    if (typeof opt === 'object' && opt !== null) {
      return {
        label: opt.label !== undefined ? opt.label : opt.name || opt.value,
        subLabel: opt.subLabel || opt.sub_label || opt.email || null,
        value: opt.value !== undefined ? opt.value : opt.id,
        avatar: opt.avatar || null,
        icon: opt.icon || null,
      };
    }
    return { label: String(opt), subLabel: null, value: opt, avatar: null, icon: null };
  });
});

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value.trim()) {
    return normalizedOptions.value;
  }
  const q = searchQuery.value.toLowerCase().trim();
  return normalizedOptions.value.filter((opt) => {
    const labelMatch = String(opt.label).toLowerCase().includes(q);
    const subLabelMatch = opt.subLabel ? String(opt.subLabel).toLowerCase().includes(q) : false;
    return labelMatch || subLabelMatch;
  });
});

const isSelected = (optVal) => {
  if (optVal === props.modelValue) return true;
  if (optVal !== null && props.modelValue !== null && String(optVal) === String(props.modelValue)) return true;
  return false;
};

const selectedOption = computed(() => {
  return normalizedOptions.value.find((opt) => isSelected(opt.value));
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

const handleSearchEnter = () => {
  if (filteredOptions.value.length > 0) {
    select(filteredOptions.value[0]);
  }
};

watch(isOpen, (newVal) => {
  if (newVal) {
    if (props.searchable) {
      nextTick(() => {
        searchInputRef.value?.focus();
      });
    }
  } else {
    searchQuery.value = '';
  }
});

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
      <div class="flex items-center gap-2 truncate min-w-0">
        <img
          v-if="selectedOption?.avatar"
          :src="selectedOption.avatar"
          class="w-4 h-4 rounded-full object-cover flex-shrink-0 border border-slate-200 dark:border-white/10"
          alt=""
        />
        <component v-else-if="selectedOption?.icon" :is="selectedOption.icon" class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" />
        <span class="truncate">{{ displayLabel }}</span>
      </div>
      <ChevronDown
        :class="[
          'w-3.5 h-3.5 text-slate-400 dark:text-slate-500 transition-transform duration-150 flex-shrink-0 ml-1',
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
          'absolute z-50 mt-1 min-w-full min-w-[150px] rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xl backdrop-blur-xl focus:outline-none flex flex-col overflow-hidden',
          menuClass
        ]"
      >
        <!-- Search Input Bar (Sticky at top) -->
        <div v-if="searchable" class="p-2 border-b border-slate-100 dark:border-white/[0.06] flex-shrink-0 bg-white dark:bg-[#262638]">
          <div class="relative">
            <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full pl-8 pr-7 py-1.5 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition-all font-sans"
              @click.stop
              @keydown.stop
              @keydown.enter.prevent="handleSearchEnter"
              @keydown.esc.stop="isOpen = false"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click.stop="searchQuery = ''; searchInputRef?.focus()"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 rounded cursor-pointer"
            >
              <X class="w-3 h-3" />
            </button>
          </div>
        </div>

        <!-- Scrollable Options List -->
        <div class="max-h-56 overflow-y-auto p-1 space-y-0.5">
          <button
            v-for="opt in filteredOptions"
            :key="opt.value"
            type="button"
            @click="select(opt)"
            :class="[
              isSelected(opt.value)
                ? 'bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] font-semibold'
                : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#32324A] hover:text-slate-900 dark:hover:text-white',
              'w-full flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-md text-xs font-mono transition-colors text-left cursor-pointer'
            ]"
          >
            <div class="flex items-center gap-2 min-w-0">
              <img
                v-if="opt.avatar"
                :src="opt.avatar"
                class="w-5 h-5 rounded-full object-cover flex-shrink-0 border border-slate-200 dark:border-white/10"
                alt=""
              />
              <component v-else-if="opt.icon" :is="opt.icon" class="w-3.5 h-3.5 flex-shrink-0 text-slate-400" />
              <div class="flex flex-col min-w-0 text-left">
                <span class="truncate">{{ opt.label }}</span>
                <span v-if="opt.subLabel" class="text-[10px] text-slate-400 font-sans truncate">{{ opt.subLabel }}</span>
              </div>
            </div>
            <Check v-if="isSelected(opt.value)" class="w-3.5 h-3.5 text-[#F29F67] flex-shrink-0 ml-1" />
          </button>

          <!-- Empty State -->
          <div v-if="filteredOptions.length === 0" class="py-3 px-2 text-center text-xs text-slate-400 dark:text-slate-500 font-sans">
            No results matching "<span class="text-slate-600 dark:text-slate-300 font-semibold">{{ searchQuery }}</span>"
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
