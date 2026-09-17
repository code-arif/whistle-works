<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Admin/Components/Common/Breadcrumb.vue';
import Dropdown from '@/Admin/Components/Common/Dropdown.vue';
import {
  Terminal,
  RefreshCw,
  Copy,
  Check,
  Download,
  Trash2,
  Search,
  AlertCircle,
  AlertTriangle,
  Info,
  Bug,
  ChevronDown,
  ChevronUp,
  FileText,
  Clock,
  HardDrive,
  Layers,
  X
} from 'lucide-vue-next';

const props = defineProps({
  files: {
    type: Array,
    required: true,
    default: () => [],
  },
  selectedFile: {
    type: String,
    required: true,
    default: 'laravel.log',
  },
  entries: {
    type: Array,
    required: true,
    default: () => [],
  },
  stats: {
    type: Object,
    required: true,
    default: () => ({
      total: 0,
      errors: 0,
      warnings: 0,
      infos: 0,
      debugs: 0,
    }),
  },
  fileInfo: {
    type: Object,
    required: true,
    default: () => ({
      name: 'laravel.log',
      size_formatted: '0 B',
      updated_at: '',
      is_empty: false,
    }),
  },
});

// State
const currentFile = ref(props.selectedFile);
const localEntries = ref([...props.entries]);
const localStats = ref({ ...props.stats });
const localFileInfo = ref({ ...props.fileInfo });
const localFiles = ref([...props.files]);

const searchQuery = ref('');
const selectedLevel = ref('ALL');
const expandedEntries = ref(new Set());
const copiedId = ref(null);
const bulkCopied = ref(false);
const isRefreshing = ref(false);
const autoRefreshInterval = ref('off'); // 'off', '5', '10', '30'
const showClearModal = ref(false);
const toastMessage = ref('');
let autoRefreshTimer = null;

// Watch props updates from Inertia navigation
watch(() => props.entries, (newVal) => {
  localEntries.value = [...newVal];
});
watch(() => props.stats, (newVal) => {
  localStats.value = { ...newVal };
});
watch(() => props.fileInfo, (newVal) => {
  localFileInfo.value = { ...newVal };
});
watch(() => props.files, (newVal) => {
  localFiles.value = [...newVal];
});
watch(() => props.selectedFile, (newVal) => {
  currentFile.value = newVal;
});

// Toast notification helper
const showToast = (msg) => {
  toastMessage.value = msg;
  setTimeout(() => {
    if (toastMessage.value === msg) {
      toastMessage.value = '';
    }
  }, 2500);
};

// Switch file
const switchFile = (filename) => {
  if (currentFile.value === filename && !isRefreshing.value) return;
  currentFile.value = filename;
  expandedEntries.value.clear();
  fetchLogData(filename);
};

// Fetch data asynchronously without full page reload
const fetchLogData = async (filename = currentFile.value, silent = false) => {
  if (!silent) isRefreshing.value = true;
  try {
    const res = await fetch(`/admin/v2/logs/data?file=${encodeURIComponent(filename)}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      }
    });
    if (!res.ok) throw new Error('Failed to fetch logs');
    const data = await res.json();
    if (data.success) {
      localEntries.value = data.entries;
      localStats.value = data.stats;
      localFileInfo.value = data.fileInfo;
      localFiles.value = data.files;
      currentFile.value = data.selectedFile;
      if (!silent) {
        showToast(`Refreshed ${data.selectedFile}`);
      }
    }
  } catch (error) {
    console.error('Log fetch error:', error);
    if (!silent) showToast('Failed to refresh logs');
  } finally {
    if (!silent) isRefreshing.value = false;
  }
};

// Auto Refresh Timer Management
const updateAutoRefresh = (val) => {
  autoRefreshInterval.value = val;
  if (autoRefreshTimer) {
    clearInterval(autoRefreshTimer);
    autoRefreshTimer = null;
  }
  if (val !== 'off') {
    const seconds = parseInt(val, 10);
    autoRefreshTimer = setInterval(() => {
      fetchLogData(currentFile.value, true);
    }, seconds * 1000);
    showToast(`Auto-refresh set to every ${seconds}s`);
  } else {
    showToast('Auto-refresh disabled');
  }
};

onMounted(() => {
  // auto-refresh off by default
});

onUnmounted(() => {
  if (autoRefreshTimer) {
    clearInterval(autoRefreshTimer);
  }
});

// Filtering
const filteredEntries = computed(() => {
  let list = localEntries.value;

  // Level filter
  if (selectedLevel.value !== 'ALL') {
    if (selectedLevel.value === 'ERROR') {
      list = list.filter(e => ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'].includes(e.level));
    } else if (selectedLevel.value === 'WARNING') {
      list = list.filter(e => e.level === 'WARNING');
    } else if (selectedLevel.value === 'INFO') {
      list = list.filter(e => ['INFO', 'NOTICE'].includes(e.level));
    } else if (selectedLevel.value === 'DEBUG') {
      list = list.filter(e => e.level === 'DEBUG');
    }
  }

  // Search filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(e => 
      (e.message && e.message.toLowerCase().includes(q)) ||
      (e.timestamp && e.timestamp.toLowerCase().includes(q)) ||
      (e.level && e.level.toLowerCase().includes(q)) ||
      (e.stack_trace && e.stack_trace.toLowerCase().includes(q))
    );
  }

  return list;
});

// Expand/Collapse Stack Trace
const toggleExpand = (id) => {
  if (expandedEntries.value.has(id)) {
    expandedEntries.value.delete(id);
  } else {
    expandedEntries.value.add(id);
  }
};

const expandAll = () => {
  filteredEntries.value.forEach(e => {
    if (e.stack_trace) expandedEntries.value.add(e.id);
  });
};

const collapseAll = () => {
  expandedEntries.value.clear();
};

// Copy individual entry
const copyEntry = (entry) => {
  let text = `[${entry.timestamp}] ${entry.environment}.${entry.level}: ${entry.message}`;
  if (entry.stack_trace) {
    text += `\n\n[stacktrace]\n${entry.stack_trace}`;
  }
  navigator.clipboard.writeText(text);
  copiedId.value = entry.id;
  showToast(`Log entry #${entry.id} copied to clipboard`);
  setTimeout(() => {
    if (copiedId.value === entry.id) {
      copiedId.value = null;
    }
  }, 2000);
};

// Copy all filtered entries
const copyAllVisible = () => {
  if (!filteredEntries.value.length) return;
  const content = filteredEntries.value.map(e => {
    let text = `[${e.timestamp}] ${e.environment}.${e.level}: ${e.message}`;
    if (e.stack_trace) {
      text += `\n[stacktrace]\n${e.stack_trace}`;
    }
    return text;
  }).join('\n\n' + '='.repeat(60) + '\n\n');

  navigator.clipboard.writeText(content);
  bulkCopied.value = true;
  showToast(`Copied ${filteredEntries.value.length} log entries to clipboard`);
  setTimeout(() => {
    bulkCopied.value = false;
  }, 2500);
};

// Clear log confirmation
const confirmClearLog = () => {
  router.post('/admin/v2/logs/clear', { file: currentFile.value }, {
    preserveScroll: true,
    onSuccess: () => {
      showClearModal.value = false;
      fetchLogData(currentFile.value);
    }
  });
};

// Helper for Level Badges
const getLevelBadgeClass = (level) => {
  switch (level) {
    case 'ERROR':
    case 'CRITICAL':
    case 'EMERGENCY':
    case 'ALERT':
      return 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30';
    case 'WARNING':
      return 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30';
    case 'INFO':
    case 'NOTICE':
      return 'bg-sky-500/15 text-sky-600 dark:text-sky-400 border-sky-500/30';
    case 'DEBUG':
      return 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border-purple-500/30';
    default:
      return 'bg-slate-500/15 text-slate-600 dark:text-slate-400 border-slate-500/30';
  }
};

const autoRefreshOptions = [
  { label: 'Auto: Off', value: 'off' },
  { label: 'Auto: 5s', value: '5' },
  { label: 'Auto: 10s', value: '10' },
  { label: 'Auto: 30s', value: '30' },
];

const fileOptions = computed(() => {
  return localFiles.value.map(f => ({
    label: `${f.name} (${f.size_formatted})`,
    value: f.name,
  }));
});
</script>

<template>
  <AdminLayout>
    <Head title="System Logs Monitor" />

    <!-- Floating Toast Notification -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="transform translate-y-2 opacity-0"
      enter-to-class="transform translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="transform translate-y-0 opacity-100"
      leave-to-class="transform translate-y-2 opacity-0"
    >
      <div 
        v-if="toastMessage" 
        class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-2.5 rounded-md bg-slate-900/95 dark:bg-white/95 text-white dark:text-slate-950 text-xs font-mono shadow-2xl backdrop-blur-md border border-white/10 dark:border-slate-900/10"
      >
        <Check class="w-4 h-4 text-[#F29F67]" />
        <span>{{ toastMessage }}</span>
      </div>
    </Transition>

    <div class="space-y-5 max-w-7xl mx-auto pb-12">
      <!-- Breadcrumb & Header Title -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <Breadcrumb :items="[
            { label: 'System Governance', href: '/admin/v2/settings' },
            { label: 'System Logs' }
          ]" />
          <div class="flex items-center gap-3">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
              <Terminal class="w-6 h-6 text-[#F29F67]" />
              <span>Real-Time Logs Monitor</span>
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Monitor and inspect Laravel application exceptions, errors, and background system events.
          </p>
        </div>

        <!-- Top Right File Actions Bar (Single Row) -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0 flex-nowrap">
          <!-- Auto-Refresh Dropdown -->
          <Dropdown
            :model-value="autoRefreshInterval"
            @update:model-value="updateAutoRefresh"
            :options="autoRefreshOptions"
            size="sm"
            align="right"
            menu-class="w-36"
            button-class="whitespace-nowrap"
          />

          <!-- Refresh Button -->
          <button
            type="button"
            @click="fetchLogData(currentFile)"
            :disabled="isRefreshing"
            class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-xs font-mono font-semibold rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#2A2A3D] text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer shadow-2xs disabled:opacity-50 shrink-0 whitespace-nowrap"
            title="Refresh current log file"
          >
            <RefreshCw :class="['w-3.5 h-3.5', isRefreshing ? 'animate-spin text-[#F29F67]' : '']" />
            <span class="hidden md:inline">Refresh</span>
          </button>

          <!-- Download Raw File -->
          <a
            :href="`/admin/v2/logs/download?file=${encodeURIComponent(currentFile)}`"
            class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-xs font-mono font-semibold rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#2A2A3D] text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer shadow-2xs shrink-0 whitespace-nowrap"
            title="Download raw log file"
          >
            <Download class="w-3.5 h-3.5 text-slate-400" />
            <span class="hidden md:inline">Download</span>
          </a>

          <!-- Clear File Button -->
          <button
            type="button"
            @click="showClearModal = true"
            class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 text-xs font-mono font-semibold rounded-md bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 transition-colors cursor-pointer shadow-2xs shrink-0 whitespace-nowrap"
            title="Clear all contents from this log file"
          >
            <Trash2 class="w-3.5 h-3.5" />
            <span class="hidden md:inline">Clear Log</span>
          </button>
        </div>
      </div>

      <!-- Log File Selector Bar (Guaranteed Single Row) -->
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-2.5 sm:p-3 shadow-xs">
        <div class="flex items-center justify-between gap-3 overflow-x-auto scrollbar-none">
          <!-- Channel Switcher Tabs -->
          <div class="flex items-center gap-2 shrink-0 flex-nowrap">
            <button
              v-for="file in localFiles"
              :key="file.name"
              type="button"
              @click="switchFile(file.name)"
              :class="[
                currentFile === file.name
                  ? 'bg-[#F29F67] text-slate-950 font-bold shadow-xs'
                  : 'bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#32324A]',
                'inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-mono transition-all cursor-pointer shrink-0 whitespace-nowrap'
              ]"
            >
              <Terminal v-if="file.name === 'laravel.log'" class="w-3.5 h-3.5 shrink-0" />
              <FileText v-else class="w-3.5 h-3.5 shrink-0" />

              <span>{{ file.name === 'laravel.log' ? 'Laravel System (laravel.log)' : file.name }}</span>

              <span 
                :class="[
                  currentFile === file.name 
                    ? 'bg-black/20 text-slate-950 font-semibold' 
                    : 'bg-white dark:bg-white/10 text-slate-500 dark:text-slate-400',
                  'px-1.5 py-0.5 rounded text-[10px]'
                ]"
              >
                {{ file.size_formatted }}
              </span>
            </button>
          </div>

          <!-- Active File Meta Info Badge -->
          <div class="flex items-center gap-2.5 text-xs font-mono text-slate-500 dark:text-slate-400 shrink-0 bg-slate-50 dark:bg-[#161622] px-3 py-1.5 rounded-md border border-slate-200/60 dark:border-white/[0.04] whitespace-nowrap">
            <span class="inline-flex items-center gap-1.5">
              <HardDrive class="w-3.5 h-3.5 text-slate-400" />
              <span>Target: <strong class="text-[#F29F67]">{{ currentFile }}</strong></span>
            </span>
            <span class="text-slate-300 dark:text-white/20">•</span>
            <span class="inline-flex items-center gap-1.5">
              <Clock class="w-3.5 h-3.5 text-slate-400" />
              <span>Updated: <strong class="text-slate-800 dark:text-slate-200">{{ localFileInfo.updated_at }}</strong></span>
            </span>
          </div>
        </div>
      </div>

      <!-- Quick Summary Stats Chips -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <!-- Total -->
        <div 
          @click="selectedLevel = 'ALL'"
          :class="[
            selectedLevel === 'ALL' ? 'ring-2 ring-[#F29F67]' : '',
            'bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-3 sm:p-3.5 cursor-pointer hover:border-[#F29F67]/50 transition-all shadow-xs'
          ]"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Entries</span>
            <FileText class="w-4 h-4 text-slate-400" />
          </div>
          <div class="text-xl font-bold font-mono text-slate-900 dark:text-white mt-1">
            {{ localStats.total }}
          </div>
        </div>

        <!-- Errors -->
        <div 
          @click="selectedLevel = 'ERROR'"
          :class="[
            selectedLevel === 'ERROR' ? 'ring-2 ring-rose-500' : '',
            'bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-3 sm:p-3.5 cursor-pointer hover:border-rose-500/50 transition-all shadow-xs'
          ]"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider text-rose-600 dark:text-rose-400">Errors</span>
            <AlertCircle class="w-4 h-4 text-rose-500" />
          </div>
          <div class="text-xl font-bold font-mono text-rose-600 dark:text-rose-400 mt-1">
            {{ localStats.errors }}
          </div>
        </div>

        <!-- Warnings -->
        <div 
          @click="selectedLevel = 'WARNING'"
          :class="[
            selectedLevel === 'WARNING' ? 'ring-2 ring-amber-500' : '',
            'bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-3 sm:p-3.5 cursor-pointer hover:border-amber-500/50 transition-all shadow-xs'
          ]"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider text-amber-600 dark:text-amber-400">Warnings</span>
            <AlertTriangle class="w-4 h-4 text-amber-500" />
          </div>
          <div class="text-xl font-bold font-mono text-amber-600 dark:text-amber-400 mt-1">
            {{ localStats.warnings }}
          </div>
        </div>

        <!-- Infos -->
        <div 
          @click="selectedLevel = 'INFO'"
          :class="[
            selectedLevel === 'INFO' ? 'ring-2 ring-sky-500' : '',
            'bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-3 sm:p-3.5 cursor-pointer hover:border-sky-500/50 transition-all shadow-xs'
          ]"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider text-sky-600 dark:text-sky-400">Info</span>
            <Info class="w-4 h-4 text-sky-500" />
          </div>
          <div class="text-xl font-bold font-mono text-sky-600 dark:text-sky-400 mt-1">
            {{ localStats.infos }}
          </div>
        </div>

        <!-- Debugs -->
        <div 
          @click="selectedLevel = 'DEBUG'"
          :class="[
            selectedLevel === 'DEBUG' ? 'ring-2 ring-purple-500' : '',
            'bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-3 sm:p-3.5 cursor-pointer hover:border-purple-500/50 transition-all shadow-xs'
          ]"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-mono uppercase tracking-wider text-purple-600 dark:text-purple-400">Debug</span>
            <Bug class="w-4 h-4 text-purple-500" />
          </div>
          <div class="text-xl font-bold font-mono text-purple-600 dark:text-purple-400 mt-1">
            {{ localStats.debugs }}
          </div>
        </div>
      </div>

      <!-- Controls & Filter Toolbar -->
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-3.5 sm:p-4 shadow-xs space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <!-- Keyword Search Input -->
          <div class="relative flex-1">
            <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Filter by keyword, exception, file path, timestamp..."
              class="w-full pl-10 pr-9 py-2 text-xs font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-hidden focus:ring-1 focus:ring-[#F29F67] text-slate-900 dark:text-white placeholder:text-slate-400 transition-colors"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-white cursor-pointer"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Actions: Copy All Filtered / Expand All -->
          <div class="flex items-center gap-2 shrink-0">
            <!-- Copy All Filtered -->
            <button
              type="button"
              @click="copyAllVisible"
              :disabled="!filteredEntries.length"
              class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-mono font-semibold rounded-md bg-slate-100 dark:bg-[#262638] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer disabled:opacity-50"
              title="Copy all currently filtered log lines"
            >
              <Check v-if="bulkCopied" class="w-3.5 h-3.5 text-emerald-500" />
              <Copy v-else class="w-3.5 h-3.5 text-slate-400" />
              <span>{{ bulkCopied ? 'All Copied!' : `Copy (${filteredEntries.length})` }}</span>
            </button>

            <!-- Toggle Expand / Collapse -->
            <button
              type="button"
              @click="expandedEntries.size > 0 ? collapseAll() : expandAll()"
              class="inline-flex items-center gap-1 px-3 py-2 text-xs font-mono font-semibold rounded-md bg-slate-100 dark:bg-[#262638] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer"
            >
              <component :is="expandedEntries.size > 0 ? ChevronUp : ChevronDown" class="w-3.5 h-3.5 text-slate-400" />
              <span>{{ expandedEntries.size > 0 ? 'Collapse All' : 'Expand Traces' }}</span>
            </button>
          </div>
        </div>

        <!-- Level Pills -->
        <div class="flex items-center gap-1.5 flex-wrap pt-1 border-t border-slate-100 dark:border-white/[0.06]">
          <span class="text-[11px] font-mono uppercase tracking-wider text-slate-400 mr-1.5">Level:</span>
          <button
            v-for="lvl in ['ALL', 'ERROR', 'WARNING', 'INFO', 'DEBUG']"
            :key="lvl"
            type="button"
            @click="selectedLevel = lvl"
            :class="[
              selectedLevel === lvl
                ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border-[#F29F67]/40 font-bold'
                : 'bg-slate-100 dark:bg-[#262638]/60 text-slate-600 dark:text-slate-400 border-transparent hover:bg-slate-200 dark:hover:bg-[#32324A]',
              'px-2.5 py-1 text-xs font-mono rounded-md border transition-all cursor-pointer'
            ]"
          >
            {{ lvl }}
            <span class="ml-1 text-[10px] opacity-75">
              ({{ lvl === 'ALL' ? localStats.total : lvl === 'ERROR' ? localStats.errors : lvl === 'WARNING' ? localStats.warnings : lvl === 'INFO' ? localStats.infos : localStats.debugs }})
            </span>
          </button>
        </div>
      </div>

      <!-- Logs Stream Console -->
      <div class="space-y-2.5">
        <!-- Empty State -->
        <div 
          v-if="!filteredEntries.length" 
          class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-md p-12 text-center shadow-xs"
        >
          <div class="w-12 h-12 mx-auto rounded-md bg-slate-100 dark:bg-[#262638] flex items-center justify-center text-slate-400 dark:text-slate-500 mb-3">
            <Terminal class="w-6 h-6" />
          </div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">No log entries found</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-sm mx-auto">
            {{ searchQuery ? 'No log items match your search filter criteria. Try clearing the search query.' : (localFileInfo.is_empty ? `The log file [${currentFile}] is currently empty or has not recorded any events yet.` : 'No entries available under this level filter.') }}
          </p>
          <button
            v-if="searchQuery || selectedLevel !== 'ALL'"
            type="button"
            @click="searchQuery = ''; selectedLevel = 'ALL'"
            class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-mono font-semibold rounded-md bg-[#F29F67] text-slate-950 hover:bg-[#E08A50] transition-colors cursor-pointer"
          >
            Clear Filters
          </button>
        </div>

        <!-- Log Entry Card -->
        <div
          v-for="entry in filteredEntries"
          :key="entry.id"
          class="group bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] hover:border-slate-300 dark:hover:border-white/[0.15] rounded-md p-3.5 sm:p-4 transition-all shadow-2xs"
        >
          <!-- Entry Header Bar -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 mb-2.5 border-b border-slate-100 dark:border-white/[0.06]">
            <div class="flex items-center gap-2 flex-wrap">
              <!-- Severity Level Badge -->
              <span 
                :class="[
                  getLevelBadgeClass(entry.level),
                  'px-2 py-0.5 text-[11px] font-mono font-bold rounded border'
                ]"
              >
                {{ entry.level }}
              </span>

              <!-- Environment Tag -->
              <span class="px-2 py-0.5 text-[10px] font-mono rounded bg-slate-100 dark:bg-[#262638] text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-white/[0.06]">
                {{ entry.environment }}
              </span>

              <!-- Timestamp -->
              <span class="text-xs font-mono text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <Clock class="w-3 h-3 text-slate-400 shrink-0" />
                <span>{{ entry.timestamp }}</span>
              </span>
            </div>

            <!-- Action Buttons for this Entry -->
            <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
              <!-- Toggle Stack Trace if present -->
              <button
                v-if="entry.stack_trace"
                type="button"
                @click="toggleExpand(entry.id)"
                class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-mono rounded bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#32324A] transition-colors cursor-pointer"
              >
                <component :is="expandedEntries.has(entry.id) ? ChevronUp : ChevronDown" class="w-3 h-3" />
                <span>{{ expandedEntries.has(entry.id) ? 'Hide Trace' : 'Stack Trace' }}</span>
              </button>

              <!-- Single Copy Button -->
              <button
                type="button"
                @click="copyEntry(entry)"
                class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-mono rounded bg-slate-100 dark:bg-[#262638] text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-[#32324A] transition-colors cursor-pointer"
                title="Copy log entry to clipboard"
              >
                <Check v-if="copiedId === entry.id" class="w-3 h-3 text-emerald-500" />
                <Copy v-else class="w-3 h-3 text-slate-400" />
                <span>{{ copiedId === entry.id ? 'Copied' : 'Copy' }}</span>
              </button>
            </div>
          </div>

          <!-- Message Body -->
          <div class="font-mono text-xs sm:text-sm text-slate-900 dark:text-slate-100 break-words leading-relaxed select-text">
            {{ entry.message }}
          </div>

          <!-- Expandable Stack Trace Drawer -->
          <div v-if="entry.stack_trace && expandedEntries.has(entry.id)" class="mt-3 pt-3 border-t border-slate-100 dark:border-white/[0.06]">
            <div class="flex items-center justify-between pb-2">
              <span class="text-[11px] font-mono text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                <Layers class="w-3.5 h-3.5 text-rose-500" />
                <span>Exception Stack Trace & Execution Context</span>
              </span>
              <button
                type="button"
                @click="copyEntry(entry)"
                class="text-[10px] font-mono text-[#F29F67] hover:underline cursor-pointer flex items-center gap-1"
              >
                <Copy class="w-3 h-3" />
                <span>Copy Trace</span>
              </button>
            </div>
            <pre class="p-3.5 rounded-md bg-slate-950 dark:bg-[#14141E] text-slate-300 font-mono text-[11px] leading-relaxed overflow-x-auto border border-white/[0.08] shadow-inner select-text whitespace-pre-wrap">{{ entry.stack_trace }}</pre>
          </div>
        </div>
      </div>
    </div>

    <!-- Clear Log Confirmation Modal -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div 
        v-if="showClearModal" 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        @click.self="showClearModal = false"
      >
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-6 max-w-md w-full shadow-2xl space-y-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-md bg-rose-500/10 flex items-center justify-center text-rose-500 shrink-0">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">Clear Log File?</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">This action permanently empties the selected log file.</p>
            </div>
          </div>

          <div class="p-3 rounded-md bg-slate-50 dark:bg-[#262638] text-xs font-mono text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.06]">
            Target File: <strong class="text-[#F29F67]">{{ currentFile }}</strong>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="showClearModal = false"
              class="px-4 py-2 text-xs font-mono font-semibold rounded-md bg-slate-100 dark:bg-[#262638] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="confirmClearLog"
              class="px-4 py-2 text-xs font-mono font-semibold rounded-md bg-rose-600 hover:bg-rose-700 text-white transition-colors cursor-pointer shadow-xs"
            >
              Yes, Clear File
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>
