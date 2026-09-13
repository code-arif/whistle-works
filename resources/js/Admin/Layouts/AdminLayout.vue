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
  <div class="min-h-screen bg-[#070A0F] text-slate-100 dark:bg-[#070A0F] dark:text-slate-100 flex transition-colors duration-300">
    
    <!-- Modular Sidebar -->
    <AdminSidebar 
      :is-open="isSidebarOpen" 
      @close="isSidebarOpen = false" 
    />

    <!-- Main Content Container -->
    <div class="flex-1 flex flex-col min-w-0">
      
      <!-- Modular Header -->
      <AdminHeader 
        :title="title" 
        @toggle-sidebar="toggleSidebar" 
      />

      <!-- Flash Notifications -->
      <div v-if="flash.success || flash.error" class="px-6 lg:px-8 pt-4">
        <div 
          v-if="flash.success" 
          class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center justify-between"
        >
          <span>{{ flash.success }}</span>
        </div>
        <div 
          v-if="flash.error" 
          class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center justify-between"
        >
          <span>{{ flash.error }}</span>
        </div>
      </div>

      <!-- Main Slot -->
      <main class="flex-1 p-6 lg:p-8 space-y-8">
        <slot />
      </main>

      <!-- Modular Footer -->
      <AdminFooter />

    </div>
  </div>
</template>
