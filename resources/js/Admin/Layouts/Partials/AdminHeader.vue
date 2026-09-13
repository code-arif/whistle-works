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
  Menu
} from 'lucide-vue-next';

defineProps({
  title: {
    type: String,
    default: 'Dashboard',
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
  <header class="h-16 sm:h-20 sticky top-0 z-30 bg-[#0B0F17]/85 border-b border-slate-800/80 backdrop-blur-xl flex items-center justify-between px-4 sm:px-6 lg:px-8">
    
    <!-- Left: Sidebar Toggle & Page Title -->
    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
      <button 
        @click="$emit('toggle-sidebar')" 
        class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors flex-shrink-0"
        title="Toggle Menu"
      >
        <Menu class="w-5 h-5" />
      </button>
      <div class="min-w-0">
        <h1 class="text-base sm:text-lg font-bold font-display text-white flex items-center gap-2 truncate">
          <span class="truncate">{{ title }}</span>
          <span class="px-2 py-0.5 text-[10px] font-mono font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hidden xs:flex items-center gap-1 flex-shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
            Live 150k+
          </span>
        </h1>
        <p class="text-[11px] text-slate-400 hidden sm:block truncate">
          Whistle-Works Enterprise Intelligence & Control Center
        </p>
      </div>
    </div>

    <!-- Right: Time, Cache Purge, Theme Switch, Profile -->
    <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
      
      <!-- Live Real-time Clock (Desktop only) -->
      <div class="hidden xl:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-900/90 border border-slate-800 text-xs font-mono text-slate-300">
        <Clock class="w-3.5 h-3.5 text-indigo-400" />
        <span>{{ currentDate }} &bull; {{ currentTime }}</span>
      </div>

      <!-- Instant Cache Bust Button -->
      <button 
        @click="refreshDashboard" 
        :disabled="isRefreshing"
        title="Bust Redis Cache & Refresh Metrics"
        class="p-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-indigo-300 transition-all duration-200 active:scale-95 disabled:opacity-50"
      >
        <RotateCw :class="['w-4 h-4', isRefreshing ? 'animate-spin text-indigo-400' : '']" />
      </button>

      <!-- Theme Switcher -->
      <button 
        @click="toggleTheme" 
        class="p-2 rounded-lg bg-slate-900/90 hover:bg-slate-800 border border-slate-800 text-slate-300 hover:text-amber-300 transition-all duration-200"
        title="Toggle Luxury Theme"
      >
        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
        <Moon v-else class="w-4 h-4 text-indigo-400" />
      </button>

      <!-- User Profile Dropdown -->
      <div class="relative">
        <button 
          @click="showUserDropdown = !showUserDropdown"
          class="flex items-center gap-2.5 p-1.5 pr-2 sm:pr-3 rounded-lg bg-slate-900/90 border border-slate-800 hover:border-slate-700 transition-all duration-200"
        >
          <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-md bg-gradient-to-tr from-indigo-600 to-amber-500 flex items-center justify-center font-bold text-xs text-white shadow-sm flex-shrink-0">
            {{ user.name ? user.name.charAt(0).toUpperCase() : 'A' }}
          </div>
          <div class="text-left hidden lg:block">
            <p class="text-xs font-semibold text-slate-200 truncate max-w-[120px]">{{ user.name }}</p>
            <p class="text-[10px] text-slate-500 uppercase tracking-wider font-mono">{{ user.role }}</p>
          </div>
        </button>

        <!-- Dropdown Menu -->
        <div 
          v-show="showUserDropdown" 
          @click.outside="showUserDropdown = false"
          class="absolute right-0 mt-2 w-52 rounded-xl bg-[#0F172A] border border-slate-800 shadow-xl p-1.5 z-50 backdrop-blur-xl animate-in fade-in zoom-in-95 duration-150"
        >
          <div class="px-3 py-2 border-b border-slate-800/80 mb-1">
            <p class="text-xs font-semibold text-white truncate">{{ user.name }}</p>
            <p class="text-[11px] text-slate-400 truncate">{{ user.email }}</p>
          </div>
          <a 
            href="/admin/setting/profile" 
            class="flex items-center gap-2 px-3 py-2 rounded-md text-xs text-slate-300 hover:bg-slate-800 hover:text-white transition-colors"
          >
            <Settings class="w-4 h-4 text-slate-400" />
            <span>Account Profile</span>
          </a>
          <a 
            href="/logout" 
            class="flex items-center gap-2 px-3 py-2 rounded-md text-xs text-rose-400 hover:bg-rose-500/10 transition-colors"
          >
            <LogOut class="w-4 h-4 text-rose-400" />
            <span>Sign Out</span>
          </a>
        </div>
      </div>

    </div>
  </header>
</template>
