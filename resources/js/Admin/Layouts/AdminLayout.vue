<script setup>
import { ref } from 'vue';
import AdminSidebar from './Partials/AdminSidebar.vue';
import AdminHeader from './Partials/AdminHeader.vue';
import AdminFooter from './Partials/AdminFooter.vue';
import Toast from '@/Admin/Components/Common/Toast.vue';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
  },
});

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

      <!-- Main Slot -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-5 sm:space-y-6">
        <slot />
      </main>

      <!-- Modular Footer -->
      <AdminFooter />

    </div>

    <!-- Global Floating Toast Notifications (All pages & flash messages) -->
    <Toast />
  </div>
</template>
