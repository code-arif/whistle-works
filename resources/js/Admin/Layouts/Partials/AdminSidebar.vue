<script setup>
import { ref, computed, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  LayoutDashboard,
  Tent,
  Users,
  CreditCard,
  Ticket,
  Settings,
  Shield,
  FileText,
  Layers,
  LayoutTemplate,
  BookOpen,
  Sparkles,
  Terminal,
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

const logoError = ref(false);
const siteLogo = computed(() => {
  const logo = page.props.settings?.logo;
  if (!logo || typeof logo !== 'string') return null;
  if (logo.startsWith('http') || logo.startsWith('data:')) return logo;
  return '/' + logo.replace(/^\//, '');
});
const siteTitle = computed(() => page.props.settings?.site_title || 'WHISTLE WORKS');

watch(() => page.props.settings?.logo, () => {
  logoError.value = false;
});

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
      { name: 'Dashboard', href: '/admin/v2/dashboard', icon: LayoutDashboard },
    ]
  },
  {
    name: 'Camp Operations',
    items: [
      { name: 'Camps Management', href: '/admin/v2/camps', icon: Tent },
      { name: 'Sports Types', href: '/admin/v2/sports-types', icon: Layers },
    ]
  },
  {
    name: 'Monetization & Users',
    items: [
      { name: 'User Directory', href: '/admin/v2/users', icon: Users },
      { name: 'Payment Monitor', href: '/admin/v2/monitor/payments', icon: CreditCard },
      { name: 'Discount Coupons', href: '/admin/v2/coupons', icon: Ticket },
    ]
  },
  {
    name: 'Content & CMS',
    items: [
      { name: 'Home Page CMS', href: '/admin/v2/cms/home', icon: LayoutTemplate },
      { name: 'About Page CMS', href: '/admin/v2/cms/about', icon: BookOpen },
    ]
  },
  {
    name: 'System Governance',
    items: [
      { name: 'Roles & Permissions', href: '/admin/v2/roles', icon: Shield },
      { name: 'Terms & Privacy', href: '/admin/v2/terms-privacy', icon: FileText },
      { name: 'Global Settings', href: '/admin/v2/settings', icon: Settings },
      { name: 'System Logs', href: '/admin/v2/logs', icon: Terminal },
    ]
  }
];
</script>

<template>
  <aside 
    :class="[
      isOpen ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0 lg:w-16',
      'fixed lg:sticky top-0 h-screen z-40 flex flex-col transition-all duration-200 ease-in-out',
      'bg-white dark:bg-[#1E1E2C] border-r border-slate-200 dark:border-white/[0.08] backdrop-blur-xl shadow-xl'
    ]"
  >
    <!-- Brand Logo Header (Aligned with h-14 Header) -->
    <div 
      :class="[
        isOpen ? 'px-4 sm:px-5 justify-between' : 'px-0 justify-center',
        'h-14 flex items-center border-b border-slate-200 dark:border-white/[0.08] shrink-0'
      ]"
    >
      <Link href="/admin/v2/dashboard" class="flex items-center gap-2.5 group min-w-0">
        <!-- Site Logo Container -->
        <div class="w-8 h-8 rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] p-1 flex items-center justify-center overflow-hidden shrink-0 shadow-2xs group-hover:border-[#F29F67]/50 transition-colors">
          <img 
            v-if="siteLogo && !logoError" 
            :src="siteLogo" 
            :alt="siteTitle" 
            @error="logoError = true"
            class="w-full h-full object-contain"
          />
          <Sparkles v-else class="w-4 h-4 text-[#F29F67]" />
        </div>

        <span 
          v-show="isOpen" 
          class="font-bold text-sm tracking-tight text-slate-900 dark:text-white leading-tight truncate uppercase"
        >
          {{ siteTitle }}
        </span>
      </Link>
      <button 
        v-if="isOpen" 
        @click="$emit('close')" 
        class="lg:hidden p-1 rounded-md text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-[#262638] transition-colors cursor-pointer shrink-0"
      >
        <X class="w-4 h-4" />
      </button>
    </div>

    <!-- Navigation Links -->
    <div class="flex-1 px-3 py-5 space-y-5 overflow-y-auto min-h-0">
      <div v-for="group in navGroups" :key="group.name" class="space-y-1">
        <p v-show="isOpen" class="px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 font-mono mb-2">
          {{ group.name }}
        </p>
        <div class="space-y-1">
          <Link
            v-for="item in group.items"
            :key="item.name"
            :href="item.href"
            :title="!isOpen ? item.name : undefined"
            :class="[
              isCurrent(item.href) 
                ? 'bg-gradient-to-r from-[#F29F67]/15 via-[#F29F67]/10 to-transparent dark:from-[#F29F67]/20 dark:via-[#F29F67]/10 dark:to-transparent text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/25 font-semibold shadow-xs' 
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-white/[0.05] hover:text-slate-900 dark:hover:text-slate-100 border border-transparent',
              isOpen ? 'px-3 py-2.5 gap-3' : 'px-0 py-2.5 justify-center',
              'relative group flex items-center rounded-lg text-[13px] sm:text-sm transition-all duration-150'
            ]"
          >
            <!-- Floating Active Left Accent Bar -->
            <span 
              v-if="isCurrent(item.href)" 
              class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-5 rounded-r-full bg-[#F29F67] shadow-[0_0_8px_rgba(242,159,103,0.7)]"
            ></span>

            <component 
              :is="item.icon" 
              :class="[
                isCurrent(item.href) 
                  ? 'text-[#F29F67]' 
                  : 'text-slate-400 dark:text-slate-500 group-hover:text-slate-700 dark:group-hover:text-slate-200',
                'w-[18px] h-[18px] transition-colors flex-shrink-0'
              ]" 
            />
            <span v-show="isOpen" class="truncate">{{ item.name }}</span>

            <!-- Active Status Micro-dot -->
            <span 
              v-if="isCurrent(item.href) && isOpen" 
              class="ml-auto w-1.5 h-1.5 rounded-full bg-[#F29F67] shadow-[0_0_8px_rgba(242,159,103,0.8)] shrink-0"
            ></span>
          </Link>
        </div>
      </div>
    </div>
  </aside>
</template>
