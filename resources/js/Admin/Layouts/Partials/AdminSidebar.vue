<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  LayoutDashboard,
  Tent,
  Users,
  CreditCard,
  Ticket,
  Settings,
  Shield,
  Layers,
  ChevronRight,
  ExternalLink,
  Sparkles,
  CalendarCheck,
  X
} from 'lucide-vue-next';

defineProps({
  isOpen: {
    type: Boolean,
    default: true,
  },
});

defineEmits(['close']);

const page = usePage();
const currentUrl = computed(() => page.url);

const isCurrent = (href) => {
  if (href === '/admin/v2/dashboard' && (currentUrl.value === '/admin/v2' || currentUrl.value === '/admin/v2/' || currentUrl.value.startsWith('/admin/v2/dashboard'))) {
    return true;
  }
  return currentUrl.value.startsWith(href);
};

const navGroups = [
  {
    name: 'Core Overview',
    items: [
      { name: 'Dashboard', href: '/admin/v2/dashboard', icon: LayoutDashboard, v2: true },
    ]
  },
  {
    name: 'Camp Operations',
    items: [
      { name: 'Camps Management', href: '/admin/v2/camps', icon: Tent, v2: true },
      { name: 'Sports Types', href: '/admin/v2/sports-types', icon: Layers, v2: true },
      { name: 'Game Schedules', href: '/admin/camps', icon: CalendarCheck, v2: false },
    ]
  },
  {
    name: 'Monetization & Users',
    items: [
      { name: 'User Directory', href: '/admin/v2/users', icon: Users, v2: true },
      { name: 'Payment Monitor', href: '/admin/monitor', icon: CreditCard, v2: false },
      { name: 'Discount Coupons', href: '/admin/coupon', icon: Ticket, v2: false },
    ]
  },
  {
    name: 'System Governance',
    items: [
      { name: 'Roles & Permissions', href: '/admin/roles', icon: Shield, v2: false },
      { name: 'Global Settings', href: '/admin/setting/general', icon: Settings, v2: false },
    ]
  }
];
</script>

<template>
  <aside 
    :class="[
      isOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0 lg:w-16',
      'fixed lg:sticky top-0 h-screen z-40 flex flex-col justify-between transition-all duration-200 ease-in-out',
      'bg-white dark:bg-[#1E1E2C] border-r border-slate-200 dark:border-white/[0.08] backdrop-blur-xl shadow-xl'
    ]"
  >
    <!-- Brand Logo Header (Aligned with h-14 Header) -->
    <div>
      <div class="h-14 flex items-center justify-between px-4 sm:px-5 border-b border-slate-200 dark:border-white/[0.08]">
        <Link href="/admin/v2/dashboard" class="flex items-center gap-2.5 group">
          <div class="w-7 h-7 rounded-md bg-gradient-to-tr from-[#F29F67] via-[#E0B50F] to-[#3B8FF3] p-[1.5px] shadow-2xs group-hover:shadow-[#F29F67]/30 transition-all duration-150">
            <div class="w-full h-full bg-white dark:bg-[#1E1E2C] rounded-[5px] flex items-center justify-center">
              <Sparkles class="w-3.5 h-3.5 text-[#F29F67] group-hover:text-[#E0B50F] transition-colors" />
            </div>
          </div>
          <div v-show="isOpen" class="flex flex-col">
            <span class="font-bold text-sm tracking-tight text-slate-900 dark:text-white leading-tight">
              WHISTLE WORKS
            </span>
            <div class="flex items-center gap-1.5">
              <span class="text-[9px] uppercase font-bold tracking-widest text-[#E08A50] dark:text-[#F29F67] font-mono leading-none">Executive V2</span>
              <span class="w-1.5 h-1.5 rounded-full bg-[#34B1AA] animate-pulse"></span>
            </div>
          </div>
        </Link>
        <button @click="$emit('close')" class="lg:hidden p-1 rounded-md text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer">
          <X class="w-4 h-4" />
        </button>
      </div>

      <!-- Navigation Links -->
      <div class="px-2.5 sm:px-3 py-4 space-y-4 overflow-y-auto max-h-[calc(100vh-120px)]">
        <div v-for="group in navGroups" :key="group.name" class="space-y-0.5">
          <p v-show="isOpen" class="px-2.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-mono">
            {{ group.name }}
          </p>
          <div class="space-y-0.5 pt-0.5">
            <Link
              v-for="item in group.items"
              :key="item.name"
              :href="item.href"
              :class="[
                isCurrent(item.href) 
                  ? 'bg-[#F29F67]/10 dark:bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border-l-2 border-[#F29F67] font-semibold shadow-2xs' 
                  : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-[#262638] hover:text-slate-900 dark:hover:text-slate-100',
                'group flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs sm:text-sm transition-all duration-150'
              ]"
            >
              <div class="flex items-center gap-2.5">
                <component 
                  :is="item.icon" 
                  :class="[
                    isCurrent(item.href) ? 'text-[#F29F67]' : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-200',
                    'w-4 h-4 transition-colors flex-shrink-0'
                  ]" 
                />
                <span v-show="isOpen" class="truncate">{{ item.name }}</span>
              </div>
              <div v-show="isOpen" class="flex items-center gap-1.5">
                <span 
                  v-if="item.v2" 
                  :class="[
                    isCurrent(item.href)
                      ? 'bg-[#F29F67]/20 text-[#E08A50] dark:text-[#F29F67] border-[#F29F67]/40'
                      : 'bg-slate-100 dark:bg-[#262638] text-slate-500 dark:text-slate-400 border-slate-200 dark:border-white/[0.08]',
                    'px-1.5 py-0.5 text-[9px] font-semibold uppercase font-mono rounded border'
                  ]"
                >
                  Vue 3
                </span>
                <span 
                  v-else 
                  class="px-1.5 py-0.5 text-[9px] font-semibold uppercase font-mono rounded bg-slate-100 dark:bg-[#262638] text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-transparent"
                >
                  V1
                </span>
              </div>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Switch to Legacy Blade Admin -->
    <div class="p-2.5 sm:p-3 border-t border-slate-200 dark:border-white/[0.08] bg-slate-50/50 dark:bg-[#181824]">
      <a 
        href="/admin/dashboard" 
        class="flex items-center justify-between px-2.5 py-1.5 rounded-md text-xs font-medium text-amber-800 dark:text-[#E0B50F] bg-amber-500/10 hover:bg-amber-500/20 border border-[#E0B50F]/30 transition-all duration-150"
      >
        <div class="flex items-center gap-2 truncate">
          <ExternalLink class="w-3.5 h-3.5 text-[#E0B50F] flex-shrink-0" />
          <span v-show="isOpen" class="truncate">Legacy Admin</span>
        </div>
        <ChevronRight v-show="isOpen" class="w-3.5 h-3.5 text-[#E0B50F] flex-shrink-0" />
      </a>
    </div>
  </aside>
</template>
