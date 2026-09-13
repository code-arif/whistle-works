<script setup>
import { Server, Database } from 'lucide-vue-next';
import { router } from '@inertiajs/vue3';

defineProps({
  systemInfo: {
    type: Object,
    default: () => ({}),
  },
});

const purgeCache = () => {
  router.post('/admin/v2/dashboard/refresh', {}, { preserveScroll: true });
};
</script>

<template>
  <div class="rounded-lg bg-slate-900/60 border border-slate-800/80 p-3.5 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-xs font-mono text-slate-400">
    <div class="flex items-center gap-3 sm:gap-4 flex-wrap">
      <div class="flex items-center gap-2">
        <Server class="w-4 h-4 text-indigo-400 flex-shrink-0" />
        <span>PHP: {{ systemInfo.php_version }} &bull; Laravel: {{ systemInfo.laravel_version }}</span>
      </div>
      <span class="hidden sm:inline">&bull;</span>
      <div class="flex items-center gap-2">
        <Database class="w-4 h-4 text-amber-400 flex-shrink-0" />
        <span>Cache: {{ systemInfo.cache_driver }} (300s)</span>
      </div>
    </div>

    <div class="flex items-center gap-2 text-slate-500 text-[11px] w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-800/80">
      <span>Cached: {{ systemInfo.last_cached_at ? new Date(systemInfo.last_cached_at).toLocaleTimeString() : 'Live' }}</span>
      <button 
        @click="purgeCache" 
        class="text-indigo-400 hover:text-indigo-300 underline underline-offset-2 ml-2 transition-colors cursor-pointer"
      >
        Purge Cache
      </button>
    </div>
  </div>
</template>
