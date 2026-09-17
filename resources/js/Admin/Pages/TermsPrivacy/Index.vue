<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Admin/Components/Common/Breadcrumb.vue';
import RichTextEditor from '@/Admin/Components/Common/RichTextEditor.vue';
import {
  ShieldCheck,
  FileText,
  Save,
  Clock
} from 'lucide-vue-next';

const props = defineProps({
  documents: {
    type: Object,
    required: true,
  },
  activeTab: {
    type: String,
    default: 'privacy',
  }
});

const currentTab = ref(props.activeTab || 'privacy');

const tabs = [
  {
    id: 'privacy',
    name: 'Privacy Policy',
    icon: ShieldCheck,
    subtitle: 'User privacy rights, GDPR/CCPA disclosures, cookie consent, and data handling policies.',
  },
  {
    id: 'terms',
    name: 'Terms & Conditions',
    icon: FileText,
    subtitle: 'Referee conduct agreements, sports camp liability terms, payment dispute rules, and platform usage regulations.',
  }
];

const currentDoc = computed(() => {
  return props.documents[currentTab.value] || { content: '', updated_at: 'Not yet configured' };
});

const form = useForm({
  type: currentTab.value,
  description: currentDoc.value.content || '',
});

// Update form content when tab switches or document updates
watch(currentTab, (newTab) => {
  form.type = newTab;
  form.description = props.documents[newTab]?.content || '';
});

const switchTab = (tabId) => {
  currentTab.value = tabId;
  router.get('/admin/v2/terms-privacy', { tab: tabId }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
};

const submit = () => {
  form.post('/admin/v2/terms-privacy', {
    preserveScroll: true,
  });
};
</script>

<template>
  <AdminLayout>
    <Head title="Terms & Privacy Management" />

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Executive Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <!-- Breadcrumb path -->
          <Breadcrumb :items="[{ label: 'Governance' }, { label: 'Terms & Privacy' }]" />

          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            Terms & Privacy Management
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Configure platform policies, referee legal agreements, and privacy compliance disclosures.
          </p>
        </div>
      </div>

      <!-- Tab Switcher Navigation Bar -->
      <div class="border-b border-slate-200 dark:border-white/[0.08] overflow-x-auto no-scrollbar">
        <nav class="flex space-x-2 min-w-max pb-1" aria-label="Document Tabs">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="switchTab(tab.id)"
            :class="[
              currentTab === tab.id
                ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border-b-2 border-[#F29F67] font-semibold'
                : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100/60 dark:hover:bg-white/[0.04] border-b-2 border-transparent font-medium',
              'group flex items-center gap-2 px-4 py-2.5 rounded-t-lg text-xs sm:text-sm transition-all duration-150 cursor-pointer'
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

      <!-- Active Tab Card Header & Editor Form -->
      <form @submit.prevent="submit" class="space-y-6">
        
        <!-- Document Meta Card -->
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            
            <div class="flex items-center gap-3.5">
              <div class="w-10 h-10 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67] shrink-0">
                <ShieldCheck v-if="currentTab === 'privacy'" class="w-5 h-5" />
                <FileText v-else class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">
                  {{ currentTab === 'privacy' ? 'Privacy Policy Document' : 'Terms & Conditions Document' }}
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ tabs.find(t => t.id === currentTab)?.subtitle }}
                </p>
              </div>
            </div>

            <!-- Last updated badge -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-md bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] text-xs font-mono text-slate-600 dark:text-slate-400 self-start sm:self-auto shrink-0">
              <Clock class="w-3.5 h-3.5 text-[#F29F67]" />
              <span>Last Saved: <strong class="text-slate-900 dark:text-slate-200 font-semibold">{{ currentDoc.updated_at }}</strong></span>
            </div>

          </div>

          <!-- Rich Text Editor Container -->
          <div class="mt-6">
            <RichTextEditor 
              v-model="form.description" 
              :placeholder="`Enter full formatted ${currentTab === 'privacy' ? 'Privacy Policy' : 'Terms & Conditions'} content here...`"
            />
            <p v-if="form.errors.description" class="text-xs text-rose-500 mt-2">
              {{ form.errors.description }}
            </p>
          </div>

          <!-- Action Footer Bar -->
          <div class="flex items-center justify-between gap-3 pt-5 mt-6 border-t border-slate-100 dark:border-white/[0.06]">
            
            <p class="text-xs text-slate-400 hidden sm:block">
              Content is instantly available across Mobile Apps and Web API endpoints.
            </p>

            <div class="flex items-center gap-3 ml-auto">
              <button 
                type="submit" 
                :disabled="form.processing"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-bold text-slate-950 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] shadow-xs disabled:opacity-60 transition-all cursor-pointer"
              >
                <Save class="w-4 h-4" />
                <span>{{ form.processing ? 'Saving Document...' : (currentTab === 'privacy' ? 'Save Privacy Policy' : 'Save Terms & Conditions') }}</span>
              </button>
            </div>

          </div>

        </div>

      </form>

    </div>
  </AdminLayout>
</template>
