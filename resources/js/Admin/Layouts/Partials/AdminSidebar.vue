<script setup>
import { Link } from '@inertiajs/vue3';
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

const navGroups = [
  {
    name: 'Core Overview',
    items: [
      { name: 'Dashboard', href: '/admin/v2/dashboard', icon: LayoutDashboard, current: true, v2: true },
    ]
  },
  {
    name: 'Camp Operations',
    items: [
      { name: 'Camps Management', href: '/admin/camps', icon: Tent, current: false, v2: false },
      { name: 'Sports Types', href: '/admin/sports-type', icon: Layers, current: false, v2: false },
      { name: 'Game Schedules', href: '/admin/camps', icon: CalendarCheck, current: false, v2: false },
    ]
  },
  {
    name: 'Monetization & Users',
    items: [
      { name: 'User Directory', href: '/admin/users/list', icon: Users, current: false, v2: false },
      { name: 'Payment Monitor', href: '/admin/monitor', icon: CreditCard, current: false, v2: false },
      { name: 'Discount Coupons', href: '/admin/coupon', icon: Ticket, current: false, v2: false },
    ]
  },
  {
    name: 'System Governance',
    items: [
      { name: 'Roles & Permissions', href: '/admin/roles', icon: Shield, current: false, v2: false },
      { name: 'Global Settings', href: '/admin/setting/general', icon: Settings, current: false, v2: false },
    ]
  }
];
</script>

<template>
  <aside 
    :class="[
      isOpen ? 'translate-x-0 w-72' : '-translate-x-full lg:translate-x-0 lg:w-20',
      'fixed lg:sticky top-0 h-screen z-40 flex flex-col justify-between transition-all duration-300 ease-in-out',
      'bg-[#0B0F17]/90 dark:bg-[#0B0F17]/95 border-r border-slate-800/80 backdrop-blur-xl shadow-2xl'
    ]"
  >
    <!-- Brand Logo Header -->
    <div>
      <div class="h-20 flex items-center justify-between px-6 border-b border-slate-800/80">
        <Link href="/admin/v2/dashboard" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-amber-400 p-[2px] shadow-lg shadow-indigo-500/20 group-hover:shadow-indigo-500/40 transition-all duration-300">
            <div class="w-full h-full bg-[#0B0F17] rounded-[10px] flex items-center justify-center">
              <Sparkles class="w-5 h-5 text-indigo-400 group-hover:text-amber-300 transition-colors" />
            </div>
          </div>
          <div v-show="isOpen" class="flex flex-col">
            <span class="font-display font-bold text-lg tracking-tight bg-gradient-to-r from-white via-slate-200 to-slate-400 bg-clip-text text-transparent">
              WHISTLE WORKS
            </span>
            <div class="flex items-center gap-1.5">
              <span class="text-[10px] uppercase font-bold tracking-widest text-amber-400/90 font-mono">Executive V2</span>
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            </div>
          </div>
        </Link>
        <button @click="$emit('close')" class="lg:hidden text-slate-400 hover:text-white">
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Navigation Links -->
      <div class="px-4 py-6 space-y-6 overflow-y-auto max-h-[calc(100vh-160px)]">
        <div v-for="group in navGroups" :key="group.name" class="space-y-1">
          <p v-show="isOpen" class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 font-mono">
            {{ group.name }}
          </p>
          <div class="space-y-1 pt-1">
            <Link
              v-for="item in group.items"
              :key="item.name"
              :href="item.href"
              :class="[
                item.current 
                  ? 'bg-gradient-to-r from-indigo-600/20 to-indigo-500/5 text-indigo-300 border-l-2 border-indigo-500 shadow-sm' 
                  : 'text-slate-400 hover:bg-slate-800/50 hover:text-slate-200',
                'group flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200'
              ]"
            >
              <div class="flex items-center gap-3">
                <component 
                  :is="item.icon" 
                  :class="[
                    item.current ? 'text-indigo-400' : 'text-slate-500 group-hover:text-slate-300',
                    'w-5 h-5 transition-colors'
                  ]" 
                />
                <span v-show="isOpen">{{ item.name }}</span>
              </div>
              <div v-show="isOpen" class="flex items-center gap-1.5">
                <span 
                  v-if="item.v2" 
                  class="px-1.5 py-0.5 text-[9px] font-semibold uppercase font-mono rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"
                >
                  Vue 3
                </span>
                <span 
                  v-else 
                  class="px-1.5 py-0.5 text-[9px] font-semibold uppercase font-mono rounded bg-slate-800 text-slate-400"
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
    <div class="p-4 border-t border-slate-800/80 bg-[#070A0F]/50">
      <a 
        href="/admin/dashboard" 
        class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-medium text-amber-300/80 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 transition-all duration-200"
      >
        <div class="flex items-center gap-2">
          <ExternalLink class="w-4 h-4 text-amber-400" />
          <span v-show="isOpen">Legacy Admin (Blade)</span>
        </div>
        <ChevronRight v-show="isOpen" class="w-3.5 h-3.5 text-amber-400" />
      </a>
    </div>
  </aside>
</template>
