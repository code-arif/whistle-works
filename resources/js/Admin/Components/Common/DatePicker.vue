<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import {
  Calendar as CalendarIcon,
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
  X,
  RotateCcw
} from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Select date',
  },
  size: {
    type: String,
    default: 'md',
    validator: (val) => ['xs', 'sm', 'md'].includes(val),
  },
  clearable: {
    type: Boolean,
    default: true,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  align: {
    type: String,
    default: 'auto',
    validator: (val) => ['auto', 'left', 'right', 'center'].includes(val),
  },
  position: {
    type: String,
    default: 'auto',
    validator: (val) => ['auto', 'top', 'bottom'].includes(val),
  },
  minDate: {
    type: String,
    default: '',
  },
  maxDate: {
    type: String,
    default: '',
  },
  inputClass: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const datepickerRef = ref(null);
const viewMode = ref('days'); // 'days' | 'months' | 'years'

// Initialize today reference
const today = new Date();
const todayString = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

// Calendar navigation state
const viewYear = ref(today.getFullYear());
const viewMonth = ref(today.getMonth());

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const shortMonthNames = [
  'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
  'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
];

const dayNames = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

// Synchronize view state when value changes or calendar opens
const syncView = () => {
  if (props.modelValue) {
    const parts = props.modelValue.split('-');
    if (parts.length === 3) {
      const y = parseInt(parts[0], 10);
      const m = parseInt(parts[1], 10) - 1;
      if (!isNaN(y) && !isNaN(m)) {
        viewYear.value = y;
        viewMonth.value = m;
        return;
      }
    }
  }
  viewYear.value = today.getFullYear();
  viewMonth.value = today.getMonth();
};

watch(() => props.modelValue, () => {
  syncView();
}, { immediate: true });

const actualPosition = ref('bottom');
const actualAlign = ref('left');

const updatePlacement = () => {
  if (props.position === 'top' || props.position === 'bottom') {
    actualPosition.value = props.position;
  } else if (datepickerRef.value) {
    const rect = datepickerRef.value.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    const spaceBelow = windowHeight - rect.bottom;
    const spaceAbove = rect.top;
    
    // Calendar is ~330px high. If space below is constrained, open upwards
    if (spaceBelow < 340 && spaceAbove > 280) {
      actualPosition.value = 'top';
    } else {
      actualPosition.value = 'bottom';
    }
  }

  if (props.align && props.align !== 'auto') {
    actualAlign.value = props.align;
  } else if (datepickerRef.value) {
    const rect = datepickerRef.value.getBoundingClientRect();
    const windowWidth = window.innerWidth;
    if (rect.left + 290 > windowWidth - 16) {
      actualAlign.value = 'right';
    } else if (rect.left < 144) {
      actualAlign.value = 'left';
    } else {
      actualAlign.value = 'center';
    }
  }
};

const toggle = () => {
  if (props.disabled) return;
  if (!isOpen.value) {
    syncView();
    viewMode.value = 'days';
    updatePlacement();
  }
  isOpen.value = !isOpen.value;
};

// Formatted display in trigger input
const displayValue = computed(() => {
  if (!props.modelValue) return '';
  const parts = props.modelValue.split('-');
  if (parts.length === 3) {
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10) - 1;
    const d = parseInt(parts[2], 10);
    const date = new Date(y, m, d);
    if (!isNaN(date.getTime())) {
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      });
    }
  }
  return props.modelValue;
});

// Format date object into 'YYYY-MM-DD'
const formatYMD = (year, month, day) => {
  const y = String(year);
  const m = String(month + 1).padStart(2, '0');
  const d = String(day).padStart(2, '0');
  return `${y}-${m}-${d}`;
};

// Grid of days for current view
const calendarDays = computed(() => {
  const year = viewYear.value;
  const month = viewMonth.value;

  const firstDay = new Date(year, month, 1).getDay();
  const totalDays = new Date(year, month + 1, 0).getDate();
  const prevMonthTotalDays = new Date(year, month, 0).getDate();

  const days = [];

  // Trailing days from previous month
  for (let i = firstDay - 1; i >= 0; i--) {
    const d = prevMonthTotalDays - i;
    const prevMonth = month === 0 ? 11 : month - 1;
    const prevYear = month === 0 ? year - 1 : year;
    const dateStr = formatYMD(prevYear, prevMonth, d);
    days.push({
      day: d,
      dateString: dateStr,
      isCurrentMonth: false,
      isSelected: dateStr === props.modelValue,
      isToday: dateStr === todayString,
      disabled: isDateDisabled(dateStr),
    });
  }

  // Current month days
  for (let i = 1; i <= totalDays; i++) {
    const dateStr = formatYMD(year, month, i);
    days.push({
      day: i,
      dateString: dateStr,
      isCurrentMonth: true,
      isSelected: dateStr === props.modelValue,
      isToday: dateStr === todayString,
      disabled: isDateDisabled(dateStr),
    });
  }

  // Leading days for next month to complete standard 7-col grid
  const remaining = (7 - (days.length % 7)) % 7;
  for (let i = 1; i <= remaining; i++) {
    const nextMonth = month === 11 ? 0 : month + 1;
    const nextYear = month === 11 ? year + 1 : year;
    const dateStr = formatYMD(nextYear, nextMonth, i);
    days.push({
      day: i,
      dateString: dateStr,
      isCurrentMonth: false,
      isSelected: dateStr === props.modelValue,
      isToday: dateStr === todayString,
      disabled: isDateDisabled(dateStr),
    });
  }

  return days;
});

const isDateDisabled = (dateStr) => {
  if (props.minDate && dateStr < props.minDate) return true;
  if (props.maxDate && dateStr > props.maxDate) return true;
  return false;
};

// Navigation actions
const prevMonth = () => {
  if (viewMonth.value === 0) {
    viewMonth.value = 11;
    viewYear.value--;
  } else {
    viewMonth.value--;
  }
};

const nextMonth = () => {
  if (viewMonth.value === 11) {
    viewMonth.value = 0;
    viewYear.value++;
  } else {
    viewMonth.value++;
  }
};

const prevYear = () => {
  viewYear.value--;
};

const nextYear = () => {
  viewYear.value++;
};

// Selection actions
const selectDate = (dayObj) => {
  if (dayObj.disabled) return;
  emit('update:modelValue', dayObj.dateString);
  emit('change', dayObj.dateString);
  isOpen.value = false;
};

const selectToday = () => {
  if (isDateDisabled(todayString)) return;
  emit('update:modelValue', todayString);
  emit('change', todayString);
  isOpen.value = false;
};

const addDays = (daysCount) => {
  const target = new Date();
  target.setDate(target.getDate() + daysCount);
  const dateStr = formatYMD(target.getFullYear(), target.getMonth(), target.getDate());
  if (isDateDisabled(dateStr)) return;
  emit('update:modelValue', dateStr);
  emit('change', dateStr);
  isOpen.value = false;
};

const clear = (e) => {
  if (e) e.stopPropagation();
  emit('update:modelValue', '');
  emit('change', '');
};

// Click outside handling
const handleClickOutside = (e) => {
  if (datepickerRef.value && !datepickerRef.value.contains(e.target)) {
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
  <div ref="datepickerRef" class="relative inline-block w-full text-left">
    <!-- Trigger Button / Field -->
    <div
      role="button"
      tabindex="0"
      @click="toggle"
      @keydown.space.prevent="toggle"
      @keydown.enter.prevent="toggle"
      :class="[
        inputClass,
        size === 'xs' ? 'px-2 py-1 text-xs' : size === 'sm' ? 'px-2.5 py-1.5 text-xs' : 'px-3 py-2 text-sm',
        'w-full flex items-center justify-between gap-2 rounded-lg border bg-slate-50 dark:bg-[#1E1E2C] border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white transition-all cursor-pointer select-none font-mono focus:outline-none focus:ring-1 focus:ring-[#F29F67] hover:border-slate-300 dark:hover:border-white/[0.15] group',
        disabled ? 'opacity-50 cursor-not-allowed' : '',
        isOpen ? 'ring-1 ring-[#F29F67] border-[#F29F67]' : ''
      ]"
    >
      <div class="flex items-center gap-2 truncate">
        <CalendarIcon
          :class="[
            'w-4 h-4 text-slate-400 transition-colors flex-shrink-0',
            isOpen ? 'text-[#F29F67]' : 'group-hover:text-slate-600 dark:group-hover:text-slate-300'
          ]"
        />
        <span v-if="displayValue" class="truncate font-semibold text-slate-900 dark:text-slate-100">
          {{ displayValue }}
        </span>
        <span v-else class="text-slate-400 dark:text-slate-500 font-normal truncate">
          {{ placeholder }}
        </span>
      </div>

      <!-- Action Icons (Clear / Dropdown) -->
      <div class="flex items-center gap-1 flex-shrink-0">
        <button
          v-if="clearable && modelValue && !disabled"
          type="button"
          @click="clear"
          title="Clear date"
          class="p-0.5 rounded-full hover:bg-slate-200 dark:hover:bg-white/10 text-slate-400 hover:text-rose-500 transition-colors cursor-pointer"
        >
          <X class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Calendar Popover Menu -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        :class="[
          actualPosition === 'top' ? 'bottom-full mb-2' : 'top-full mt-2',
          actualAlign === 'right' ? 'right-0' : actualAlign === 'center' ? 'left-1/2 -translate-x-1/2' : 'left-0',
          'absolute z-[60] w-72 max-w-[calc(100vw-2rem)] rounded-xl bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] shadow-2xl p-3 backdrop-blur-xl focus:outline-none'
        ]"
      >
        <!-- Calendar Header (Month / Year Navigation) -->
        <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-slate-100 dark:border-white/[0.06]">
          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="prevYear"
              class="p-1 rounded-md text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer"
              title="Previous Year"
            >
              <ChevronsLeft class="w-3.5 h-3.5" />
            </button>
            <button
              type="button"
              @click="prevMonth"
              class="p-1 rounded-md text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer"
              title="Previous Month"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Current Month & Year Display -->
          <div class="text-xs font-bold text-slate-900 dark:text-white font-mono tracking-wide">
            {{ monthNames[viewMonth] }} {{ viewYear }}
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              @click="nextMonth"
              class="p-1 rounded-md text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer"
              title="Next Month"
            >
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
            <button
              type="button"
              @click="nextYear"
              class="p-1 rounded-md text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer"
              title="Next Year"
            >
              <ChevronsRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Weekdays Header -->
        <div class="grid grid-cols-7 gap-1 text-center mb-1">
          <div
            v-for="d in dayNames"
            :key="d"
            class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 font-mono py-1"
          >
            {{ d }}
          </div>
        </div>

        <!-- Days Grid -->
        <div class="grid grid-cols-7 gap-1">
          <button
            v-for="(item, idx) in calendarDays"
            :key="idx"
            type="button"
            @click="selectDate(item)"
            :disabled="item.disabled"
            :class="[
              'h-8 w-8 mx-auto rounded-lg text-xs font-mono flex items-center justify-center transition-all cursor-pointer relative',
              item.disabled
                ? 'opacity-25 cursor-not-allowed'
                : item.isSelected
                ? 'bg-[#F29F67] text-slate-950 font-bold shadow-xs'
                : item.isCurrentMonth
                ? 'text-slate-800 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-[#262638] hover:text-[#F29F67]'
                : 'text-slate-400 dark:text-slate-600 hover:bg-slate-100 dark:hover:bg-[#262638]',
              item.isToday && !item.isSelected
                ? 'ring-1 ring-[#F29F67]/50 font-bold text-[#E08A50] dark:text-[#F29F67]'
                : ''
            ]"
          >
            {{ item.day }}
          </button>
        </div>

        <!-- Quick Action Footer -->
        <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-white/[0.06] flex items-center justify-between text-[11px] font-mono">
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              @click="selectToday"
              class="px-2 py-1 rounded-md text-xs font-semibold bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 hover:bg-[#F29F67]/15 hover:text-[#E08A50] dark:hover:text-[#F29F67] transition-colors cursor-pointer"
            >
              Today
            </button>
            <button
              type="button"
              @click="addDays(7)"
              class="px-2 py-1 rounded-md text-xs text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#262638] hover:text-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer"
              title="7 days from now"
            >
              +7d
            </button>
            <button
              type="button"
              @click="addDays(30)"
              class="px-2 py-1 rounded-md text-xs text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#262638] hover:text-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer"
              title="30 days from now"
            >
              +30d
            </button>
          </div>

          <button
            v-if="clearable && modelValue"
            type="button"
            @click="clear"
            class="px-2 py-1 rounded-md text-xs text-rose-500 hover:bg-rose-500/10 transition-colors cursor-pointer"
          >
            Clear
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>
