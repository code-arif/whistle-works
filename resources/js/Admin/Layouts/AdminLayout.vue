<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminSidebar from './Partials/AdminSidebar.vue';
import AdminHeader from './Partials/AdminHeader.vue';
import AdminFooter from './Partials/AdminFooter.vue';
import { X, CheckCircle2, AlertCircle } from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
  },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
const isSidebarOpen = ref(true);
const isFlashDismissed = ref(false);
let flashTimeout = null;

const scheduleAutoDismiss = () => {
  if (flashTimeout) clearTimeout(flashTimeout);
  isFlashDismissed.value = false;
  if (flash.value.success || flash.value.error) {
    flashTimeout = setTimeout(() => {
      isFlashDismissed.value = true;
    }, 4500);
  }
};

watch(() => [flash.value.success, flash.value.error], () => {
  scheduleAutoDismiss();
}, { deep: true, immediate: true });

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

      <!-- Flash Notifications (Auto-dismissing and closable) -->
      <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="!isFlashDismissed && (flash.success || flash.error)" class="px-4 sm:px-6 lg:px-8 pt-4">
          <!-- Success Notification -->
          <div 
            v-if="flash.success" 
            class="p-3.5 rounded-lg bg-[#34B1AA]/10 border border-[#34B1AA]/30 text-[#2B9B95] dark:text-[#34B1AA] text-xs flex items-center justify-between shadow-xs font-medium"
          >
            <div class="flex items-center gap-2">
              <CheckCircle2 class="w-4 h-4 shrink-0 text-[#34B1AA]" />
              <span>{{ flash.success }}</span>
            </div>
            <button 
              type="button" 
              @click="isFlashDismissed = true" 
              class="p-1 hover:bg-[#34B1AA]/20 rounded-md transition-colors cursor-pointer text-[#2B9B95] dark:text-[#34B1AA]"
              title="Dismiss notification"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Error Notification -->
          <div 
            v-if="flash.error" 
            class="p-3.5 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs flex items-center justify-between shadow-xs"
          >
            <div class="flex items-center gap-2">
              <AlertCircle class="w-4 h-4 shrink-0 text-rose-500" />
              <span>{{ flash.error }}</span>
            </div>
            <button 
              type="button" 
              @click="isFlashDismissed = true" 
              class="p-1 hover:bg-rose-500/20 rounded-md transition-colors cursor-pointer text-rose-700 dark:text-rose-300"
              title="Dismiss notification"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </Transition>

      <!-- Main Slot -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 space-y-5 sm:space-y-6">
        <slot />
      </main>

      <!-- Modular Footer -->
      <AdminFooter />

    </div>
  </div>
</template>
