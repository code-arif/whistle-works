<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import GeneralSettingsTab from '@/Admin/Components/Settings/GeneralSettingsTab.vue';
import StripeSettingsTab from '@/Admin/Components/Settings/StripeSettingsTab.vue';
import MailSettingsTab from '@/Admin/Components/Settings/MailSettingsTab.vue';
import IntegrationsTab from '@/Admin/Components/Settings/IntegrationsTab.vue';
import SystemSettingsTab from '@/Admin/Components/Settings/SystemSettingsTab.vue';
import { 
  Building2, 
  CreditCard, 
  Mail, 
  Globe, 
  Sliders, 
  Settings as SettingsIcon,
  ShieldCheck,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  activeTab: {
    type: String,
    default: 'general',
  }
});

const currentTab = ref(props.activeTab);

const tabs = [
  { id: 'general', name: 'General & Branding', icon: Building2, desc: 'Site metadata, logo & address' },
  { id: 'stripe', name: 'Payment & Stripe', icon: CreditCard, desc: 'API keys & webhooks' },
  { id: 'mail', name: 'Mail & SMTP', icon: Mail, desc: 'Email transport & test dispatch' },
  { id: 'integrations', name: 'Integrations', icon: Globe, desc: 'OAuth, Maps & Twilio SMS' },
  { id: 'system', name: 'System & Environment', icon: Sliders, desc: 'Diagnostics, cache & switches' },
];

const switchTab = (tabId) => {
  currentTab.value = tabId;
  router.get('/admin/v2/settings', { tab: tabId }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
};
</script>

<template>
  <AdminLayout>
    <Head title="System & Global Settings" />

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Executive Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs uppercase font-mono font-bold tracking-widest text-[#F29F67]">System Governance</span>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              Live Architecture
            </span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            System & Global Settings
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Central executive hub for configuring platform branding, payment gateways, mail transport, and third-party integrations.
          </p>
        </div>
      </div>

      <!-- Navigation Tabs Bar -->
      <div class="border-b border-slate-200 dark:border-white/[0.08] overflow-x-auto no-scrollbar">
        <nav class="flex space-x-1 sm:space-x-2 min-w-max pb-1" aria-label="Settings Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="switchTab(tab.id)"
            :class="[
              currentTab === tab.id
                ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border-b-2 border-[#F29F67] font-semibold'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100/60 dark:hover:bg-white/[0.04] border-b-2 border-transparent font-medium',
              'group flex items-center gap-2 px-3.5 py-2.5 rounded-t-lg text-xs sm:text-sm transition-all duration-150 cursor-pointer'
            ]"
          >
            <component 
              :is="tab.icon" 
              :class="[
                currentTab === tab.id ? 'text-[#F29F67]' : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300',
                'w-4 h-4 transition-colors'
              ]" 
            />
            <span>{{ tab.name }}</span>
          </button>
        </nav>
      </div>

      <!-- Tab Content Panels -->
      <div>
        <GeneralSettingsTab 
          v-if="currentTab === 'general'" 
          :settings="settings.general" 
        />

        <StripeSettingsTab 
          v-else-if="currentTab === 'stripe'" 
          :settings="settings.stripe" 
        />

        <MailSettingsTab 
          v-else-if="currentTab === 'mail'" 
          :settings="settings.mail" 
        />

        <IntegrationsTab 
          v-else-if="currentTab === 'integrations'" 
          :settings="settings.integrations" 
        />

        <SystemSettingsTab 
          v-else-if="currentTab === 'system'" 
          :settings="settings.system" 
        />
      </div>
    </div>
  </AdminLayout>
</template>
