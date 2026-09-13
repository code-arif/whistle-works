<script setup>
import { ref, onMounted, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import {
  Sun,
  Moon,
  RotateCw,
  Clock,
  Settings,
  LogOut,
  PanelLeftClose,
  PanelLeft
} from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
  },
  isSidebarOpen: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['toggle-sidebar']);

const page = usePage();
const user = computed(() => page.props.auth?.user || { name: 'Executive Admin', email: 'admin@whistleworks.org', role: 'Super Admin' });

const isDark = ref(true);
const isRefreshing = ref(false);
const currentTime = ref('');
const currentDate = ref('');
const showUserDropdown = ref(false);

const toggleTheme = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('theme', 'light');
  }
};

const updateTime = () => {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  currentDate.value = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
};

const refreshDashboard = () => {
  isRefreshing.value = true;
  router.post('/admin/v2/dashboard/refresh', {}, {
    preserveScroll: true,
    onFinish: () => {
      setTimeout(() => {
        isRefreshing.value = false;
      }, 500);
    }
  });
};

onMounted(() => {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'light') {
    isDark.value = false;
    document.documentElement.classList.remove('dark');
  } else {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  }

  updateTime();
  setInterval(updateTime, 1000);
});
</script>

<template>
  <header class="h-14 sticky top-0 z-30 bg-white/80 dark:bg-[#0B0F17]/85 border-b border-slate-200 dark:border-slate-800/80 backdrop-blur-xl flex items-center justify-between px-4 sm:px-6 lg:px-8 transition-colors duration-150">
    
    <!-- Left: Distinctive Panel Collapse Toggle & Page Title -->
    <div class="flex items-center gap-2.5 sm:gap-3.5 min-w-0">
      <button 
        @click="$emit('toggle-sidebar')" 
        class="p-1.5 rounded-md text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white bg-slate-100 dark:bg-slate-900/60 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition-all duration-150 flex-shrink-0 cursor-pointer shadow-2xs"
        :title="isSidebarOpen ? 'Collapse Navigation' : 'Expand Navigation'"
      >
        <component 
          :is="isSidebarOpen ? PanelLeftClose : PanelLeft" 
          class="w-4 h-4 text-indigo-600 dark:text-indigo-400 transition-transform duration-150" 
        />
      </button>

      <div class="flex items-center gap-2 min-w-0">
        <h1 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white truncate">
          {{ title }}
        </h1>
        <span class="px-2 py-0.5 text-[9px] font-mono font-semibold rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hidden xs:inline-flex items-center gap-1 flex-shrink-0">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
          Live 150k+
        </span>
      </div>
    </div>

    <!-- Right: Time, Cache Purge, Theme Switch, Profile -->
    <div class="flex items-center gap-2 sm:gap-2.5 flex-shrink-0">
      
      <!-- Live Real-time Clock (Desktop only) -->
      <div class="hidden xl:flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 text-[11px] font-mono text-slate-700 dark:text-slate-300">
        <Clock class="w-3 h-3 text-indigo-600 dark:text-indigo-400" />
        <span>{{ currentDate }} &bull; {{ currentTime }}</span>
      </div>

      <!-- Instant Cache Bust Button -->
      <button 
        @click="refreshDashboard" 
        :disabled="isRefreshing"
        title="Bust Redis Cache & Refresh Metrics"
        class="p-1.5 rounded-md bg-slate-100 dark:bg-slate-900/90 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-300 transition-all duration-150 active:scale-95 disabled:opacity-50 cursor-pointer shadow-2xs"
      >
        <RotateCw :class="['w-3.5 h-3.5', isRefreshing ? 'animate-spin text-indigo-500' : '']" />
      </button>

      <!-- Fully Functional Dark / Light Mode Toggle Button -->
      <button 
        @click="toggleTheme" 
        class="p-1.5 rounded-md bg-slate-100 dark:bg-slate-900/90 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 transition-all duration-150 cursor-pointer shadow-2xs"
        :title="isDark ? 'Switch to Executive Light Mode' : 'Switch to Luxury Dark Mode'"
      >
        <Sun v-if="isDark" class="w-3.5 h-3.5 text-amber-400 hover:rotate-45 transition-transform duration-200" />
        <Moon v-else class="w-3.5 h-3.5 text-indigo-600 hover:-rotate-12 transition-transform duration-200" />
      </button>

      <!-- User Profile Dropdown -->
      <div class="relative">
        <button 
          @click="showUserDropdown = !showUserDropdown"
          class="flex items-center gap-2 p-1 pr-2 sm:pr-2.5 rounded-md bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-150 cursor-pointer"
        >
          <div class="w-6 h-6 rounded bg-gradient-to-tr from-indigo-600 to-amber-500 flex items-center justify-center font-bold text-[11px] text-white shadow-2xs flex-shrink-0">
            {{ user.name ? user.name.charAt(0).toUpperCase() : 'A' }}
          </div>
          <div class="text-left hidden lg:block">
            <p class="text-[11px] font-semibold text-slate-900 dark:text-slate-200 truncate max-w-[100px] leading-tight">{{ user.name }}</p>
            <p class="text-[9px] text-slate-500 uppercase tracking-wider font-mono leading-tight">{{ user.role }}</p>
          </div>
        </button>

        <!-- Dropdown Menu -->
        <div 
          v-show="showUserDropdown" 
          @click.outside="showUserDropdown = false"
          class="absolute right-0 mt-1.5 w-48 rounded-lg bg-white dark:bg-[#0F172A] border border-slate-200 dark:border-slate-800 shadow-xl p-1 z-50 backdrop-blur-xl animate-in fade-in zoom-in-95 duration-150"
        >
          <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800/80 mb-1">
            <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">{{ user.name }}</p>
            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate">{{ user.email }}</p>
          </div>
          <a 
            href="/admin/setting/profile" 
            class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors"
          >
            <Settings class="w-3.5 h-3.5 text-slate-400" />
            <span>Account Profile</span>
          </a>
          <a 
            href="/logout" 
            class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors"
          >
            <LogOut class="w-3.5 h-3.5 text-rose-500" />
            <span>Sign Out</span>
          </a>
        </div>
      </div>

    </div>
  </header>
</template>
