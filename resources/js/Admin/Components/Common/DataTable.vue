<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Dropdown from './Dropdown.vue';
import {
  Search,
  X,
  ChevronLeft,
  ChevronRight,
  ArrowUpDown,
  ArrowUp,
  ArrowDown,
  Filter,
  Inbox
} from 'lucide-vue-next';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
    // Format: [{ key: 'id', label: 'ID', sortable: true, align: 'left', class: '' }]
  },
  rows: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
      from: 0,
      to: 0,
      links: []
    }),
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
      status: '',
      sort_by: 'id',
      sort_order: 'desc',
      per_page: 10,
    }),
  },
  baseUrl: {
    type: String,
    required: true,
  },
  searchPlaceholder: {
    type: String,
    default: 'Search records...',
  },
  statusOptions: {
    type: Array,
    default: () => [
      { label: 'All Sports', value: '' },
      { label: 'Active Only', value: 'active' },
      { label: 'Inactive', value: 'inactive' },
    ],
  },
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  }
});

const searchQuery = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const currentSortBy = ref(props.filters.sort_by || 'id');
const currentSortOrder = ref(props.filters.sort_order || 'desc');
const currentPerPage = ref(Number(props.filters.per_page) || 10);
const isNavigating = ref(false);

const perPageOptions = [
  { label: '10 / page', value: 10 },
  { label: '25 / page', value: 25 },
  { label: '50 / page', value: 50 },
  { label: '100 / page', value: 100 },
];

let debounceTimer = null;

const applyFilters = (page = 1) => {
  isNavigating.value = true;
  router.get(
    props.baseUrl,
    {
      search: searchQuery.value || undefined,
      status: selectedStatus.value || undefined,
      sort_by: currentSortBy.value,
      sort_order: currentSortOrder.value,
      per_page: currentPerPage.value,
      page: page !== 1 ? page : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onFinish: () => {
        isNavigating.value = false;
      },
    }
  );
};

const handleSearchInput = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    applyFilters(1);
  }, 350);
};

const clearSearch = () => {
  searchQuery.value = '';
  applyFilters(1);
};

const handleStatusChange = (val) => {
  selectedStatus.value = val;
  applyFilters(1);
};

const handlePerPageChange = (val) => {
  currentPerPage.value = val;
  applyFilters(1);
};

const handleSort = (column) => {
  if (!column.sortable) return;
  if (currentSortBy.value === column.key) {
    currentSortOrder.value = currentSortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    currentSortBy.value = column.key;
    currentSortOrder.value = 'asc';
  }
  applyFilters(1);
};

const goToPage = (url) => {
  if (!url) return;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col transition-colors duration-150 overflow-hidden">
    
    <!-- 1. Table Top Header: Title, Subtitle & Primary Action -->
    <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="min-w-0 space-y-0.5">
        <slot name="header-left">
          <div class="flex items-center gap-2">
            <h3 v-if="title" class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white tracking-tight">
              {{ title }}
            </h3>
            <span v-if="pagination.total !== undefined" class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20">
              {{ pagination.total }} {{ pagination.total === 1 ? 'record' : 'records' }}
            </span>
          </div>
          <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400">
            {{ subtitle }}
          </p>
        </slot>
      </div>

      <!-- Primary Action CTA (e.g. + New Sport) -->
      <div v-if="$slots.actions" class="flex items-center gap-2 flex-shrink-0 self-start sm:self-auto">
        <slot name="actions" />
      </div>
    </div>

    <!-- 2. Table Filter Toolbar: Search, Status Tabs & Page Size -->
    <div class="p-3 sm:p-4 bg-slate-50/60 dark:bg-[#1E1E2C]/50 border-b border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
      
      <!-- Left Filters: Search Box + Status Filter Tabs -->
      <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2.5 flex-1 min-w-0">
        
        <!-- Live Search Input -->
        <div class="relative w-full sm:w-64 lg:w-72">
          <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 pointer-events-none" />
          <input 
            v-model="searchQuery" 
            @input="handleSearchInput" 
            type="text" 
            :placeholder="searchPlaceholder"
            class="w-full pl-8 pr-7 py-1.5 text-xs rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition-all shadow-2xs"
          />
          <button 
            v-if="searchQuery" 
            @click="clearSearch" 
            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-white cursor-pointer"
          >
            <X class="w-3 h-3" />
          </button>
        </div>

        <!-- Status Filter Segmented Tabs -->
        <div v-if="statusOptions.length > 0" class="flex items-center rounded-md bg-white dark:bg-[#262638] p-0.5 border border-slate-200 dark:border-white/[0.08] flex-shrink-0 shadow-2xs overflow-x-auto">
          <button 
            v-for="opt in statusOptions" 
            :key="opt.value" 
            @click="handleStatusChange(opt.value)"
            :class="[
              selectedStatus === opt.value 
                ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] shadow-2xs font-bold' 
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200',
              'px-2.5 py-1 text-[11px] rounded transition-all cursor-pointer whitespace-nowrap'
            ]"
          >
            {{ opt.label }}
          </button>
        </div>

      </div>

      <!-- Right Toolbar Controls: Per Page Dropdown -->
      <div class="flex items-center justify-between sm:justify-end gap-2 flex-shrink-0">
        <span class="text-[11px] text-slate-400 dark:text-slate-500 sm:hidden">Rows per page:</span>
        <Dropdown
          v-model="currentPerPage"
          :options="perPageOptions"
          @change="handlePerPageChange"
        />
      </div>

    </div>

    <!-- Data Table Surface -->
    <div class="relative overflow-x-auto">
      
      <!-- Navigating Loading Overlay -->
      <div 
        v-if="isNavigating" 
        class="absolute inset-0 bg-white/40 dark:bg-[#1E1E2C]/40 backdrop-blur-2xs z-20 flex items-center justify-center transition-opacity"
      >
        <div class="w-5 h-5 border-2 border-[#F29F67] border-t-transparent rounded-full animate-spin"></div>
      </div>

      <table class="w-full text-left text-xs min-w-[650px]">
        
        <!-- Table Header -->
        <thead class="bg-slate-50/80 dark:bg-[#1E1E2C]/80 border-b border-slate-200 dark:border-white/[0.08] text-slate-500 dark:text-slate-400 font-mono uppercase text-[10px]">
          <tr>
            <th 
              v-for="col in columns" 
              :key="col.key" 
              :class="[
                col.class || '',
                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                col.sortable ? 'cursor-pointer hover:text-slate-900 dark:hover:text-white select-none' : '',
                'py-3.5 px-4 font-semibold tracking-wider transition-colors'
              ]"
              @click="handleSort(col)"
            >
              <div class="inline-flex items-center gap-1.5" :class="col.align === 'right' ? 'justify-end' : col.align === 'center' ? 'justify-center' : 'justify-start'">
                <span>{{ col.label }}</span>
                <span v-if="col.sortable" class="text-slate-400 dark:text-slate-500">
                  <ArrowUp v-if="currentSortBy === col.key && currentSortOrder === 'asc'" class="w-3 h-3 text-[#F29F67]" />
                  <ArrowDown v-else-if="currentSortBy === col.key && currentSortOrder === 'desc'" class="w-3 h-3 text-[#F29F67]" />
                  <ArrowUpDown v-else class="w-3 h-3 opacity-40 hover:opacity-100" />
                </span>
              </div>
            </th>
          </tr>
        </thead>

        <!-- Table Body -->
        <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
          <tr 
            v-for="(row, rowIndex) in rows" 
            :key="row.id || rowIndex"
            class="group hover:bg-slate-50/80 dark:hover:bg-[#2D2D42]/80 transition-colors"
          >
            <td 
              v-for="col in columns" 
              :key="col.key" 
              :class="[
                col.class || '',
                col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
                'py-3 px-4 text-slate-800 dark:text-slate-200 transition-colors'
              ]"
            >
              <!-- Dynamic Scoped Slot by column key -->
              <slot :name="`cell(${col.key})`" :row="row" :value="row[col.key]" :index="rowIndex">
                {{ row[col.key] !== null && row[col.key] !== undefined ? row[col.key] : '—' }}
              </slot>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-if="rows.length === 0">
            <td :colspan="columns.length" class="py-12 px-4 text-center">
              <div class="max-w-xs mx-auto flex flex-col items-center justify-center text-center space-y-2.5">
                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center text-slate-400 dark:text-slate-500">
                  <Inbox class="w-5 h-5" />
                </div>
                <div>
                  <h4 class="text-sm font-bold text-slate-900 dark:text-white">No records found</h4>
                  <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    {{ searchQuery ? `No results matching "${searchQuery}".` : 'No data records available in this list.' }}
                  </p>
                </div>
                <slot name="empty-action">
                  <button 
                    v-if="searchQuery" 
                    @click="clearSearch" 
                    class="mt-1 px-3 py-1.5 text-xs rounded-md bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] hover:bg-[#F29F67]/20 border border-[#F29F67]/30 transition-colors cursor-pointer"
                  >
                    Clear Filter
                  </button>
                </slot>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Table Pagination Footer -->
    <div class="p-4 sm:p-5 border-t border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50/40 dark:bg-[#1E1E2C]/40">
      
      <!-- Record Range Info -->
      <div class="font-mono text-[11px]">
        Showing <span class="font-semibold text-slate-900 dark:text-white">{{ pagination.from || 0 }}</span> to <span class="font-semibold text-slate-900 dark:text-white">{{ pagination.to || 0 }}</span> of <span class="font-semibold text-slate-900 dark:text-white">{{ pagination.total || 0 }}</span> entries
      </div>

      <!-- Pagination Controls -->
      <div v-if="pagination.links && pagination.links.length > 3" class="flex items-center gap-1">
        <template v-for="(link, idx) in pagination.links" :key="idx">
          
          <!-- Previous Button -->
          <button 
            v-if="idx === 0" 
            @click="goToPage(link.url)" 
            :disabled="!link.url"
            class="p-1.5 rounded-md border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#262638] disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
            title="Previous Page"
          >
            <ChevronLeft class="w-3.5 h-3.5" />
          </button>

          <!-- Next Button -->
          <button 
            v-else-if="idx === pagination.links.length - 1" 
            @click="goToPage(link.url)" 
            :disabled="!link.url"
            class="p-1.5 rounded-md border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#262638] disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
            title="Next Page"
          >
            <ChevronRight class="w-3.5 h-3.5" />
          </button>

          <!-- Numeric / Ellipsis Pages -->
          <button 
            v-else 
            @click="goToPage(link.url)" 
            :disabled="!link.url || link.active"
            :class="[
              link.active 
                ? 'bg-[#F29F67] text-slate-950 font-bold border-[#F29F67] shadow-2xs' 
                : 'border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#262638] text-slate-700 dark:text-slate-300',
              'min-w-[28px] h-7 px-2 rounded-md border text-xs font-mono transition-colors cursor-pointer flex items-center justify-center'
            ]"
            v-html="link.label"
          >
          </button>
        </template>
      </div>

    </div>

  </div>
</template>
