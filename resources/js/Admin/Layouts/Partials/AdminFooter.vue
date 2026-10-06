<script setup>
const currentYear = new Date().getFullYear();
</script>

<template>
  <footer class="border-t border-slate-200 dark:border-white/[0.08] py-4 px-4 sm:px-6 lg:px-8 text-xs text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white/40 dark:bg-[#1E1E2C]/80 transition-colors duration-150">
    <div class="flex items-center gap-2">
      <span>&copy; {{ currentYear }} Whistle-Works, Inc. All rights reserved.</span>
    </div>
    
    <div class="flex items-center gap-4 text-xs font-medium text-slate-500 dark:text-slate-400">
      <a href="/admin/faq" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
        Support
      </a>
      <span>&bull;</span>
      <a href="/admin/cms/privecyandterms/privacy" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
        Privacy Policy
      </a>
      <span>&bull;</span>
      <a href="/admin/cms/privecyandterms/terms" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
        Terms
      </a>
    </div>
  </footer>
</template>
