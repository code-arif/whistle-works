<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminSidebar from './Partials/AdminSidebar.vue';
import AdminHeader from './Partials/AdminHeader.vue';
import AdminFooter from './Partials/AdminFooter.vue';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
  },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const isSidebarOpen = ref(true);

const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value;
};
</script>

<template>
  <div class="min-h-screen bg-slate-100/70 text-slate-900 dark:bg-[#1E1E2C] dark:text-slate-100 flex transition-colors duration-200 relative">
    
    <!-- Mobile Backdrop Overlay -->
    <div 
      v-if="isSidebarOpen" 
      @click="isSidebarOpen = false" 
      class="fixed inset-0 bg-slate-900/50 dark:bg-black/70 backdrop-blur-xs z-30 lg:hidden transition-opacity"
    ></div>

    <!-- Modular Sidebar -->
    <AdminSidebar 
      :is-open="isSidebarOpen" 
      @close="isSidebarOpen = false" 
    />

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0">
      
      <!-- Modular Header with Distinctive Panel Toggle -->
      <AdminHeader 
        :title="title" 
        :is-sidebar-open="isSidebarOpen"
        @toggle-sidebar="toggleSidebar" 
      />

      <!-- Flash Notifications -->
      <div v-if="flash.success || flash.error" class="px-4 sm:px-6 lg:px-8 pt-4">
        <div 
          v-if="flash.success" 
          class="p-3.5 rounded-lg bg-[#34B1AA]/10 border border-[#34B1AA]/30 text-[#2B9B95] dark:text-[#34B1AA] text-xs flex items-center justify-between shadow-xs font-medium"
        >
          <span>{{ flash.success }}</span>
        </div>
        <div 
          v-if="flash.error" 
          class="p-3.5 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs flex items-center justify-between shadow-xs"
        >
          <span>{{ flash.error }}</span>
        </div>
      </div>

      <!-- Main Slot -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-5 sm:space-y-6">
        <slot />
      </main>

      <!-- Modular Footer -->
      <AdminFooter />

    </div>
  </div>
</template>
