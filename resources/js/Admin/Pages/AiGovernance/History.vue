<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Breadcrumb from '../../Components/Common/Breadcrumb.vue';
import Modal from '../../Components/Common/Modal.vue';
import Dropdown from '../../Components/Common/Dropdown.vue';
import {
  ArrowLeft,
  Sparkles,
  User as UserIcon,
  Bot,
  Activity,
  Cpu,
  Coins,
  Wrench,
  Search,
  Sliders,
  Lock,
  Unlock,
  CheckCircle2,
  Calendar,
  Clock,
  ChevronLeft,
  ChevronRight,
  Inbox,
  Filter,
  RefreshCw,
  Terminal,
  ShieldAlert
} from 'lucide-vue-next';

const props = defineProps({
  targetUser: {
    type: Object,
    required: true,
  },
  quota: {
    type: Object,
    required: true,
  },
  history: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    default: () => ({
      total_queries_all_time: 0,
      total_tokens_all_time: 0,
      total_tools_used: 0,
      monthly_queries: 0,
      monthly_limit: 50,
      monthly_tokens: 0,
    }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  }
});

// Filters
const searchQuery = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const sortDir = ref(props.filters.sort_dir || 'desc');
const perPage = ref(Number(props.filters.per_page) || 20);

const roleOptions = [
  { label: 'All Message Roles', value: '' },
  { label: 'User Queries', value: 'user' },
  { label: 'AI Assistant', value: 'assistant' },
  { label: 'System Prompts', value: 'system' },
];

const sortOptions = [
  { label: 'Newest First', value: 'desc' },
  { label: 'Oldest First', value: 'asc' },
];

const perPageOptions = [
  { label: '10 / page', value: 10 },
  { label: '20 / page', value: 20 },
  { label: '50 / page', value: 50 },
  { label: '100 / page', value: 100 },
];

const applyFilters = () => {
  router.get(
    `/admin/v2/ai-governance/users/${props.targetUser.id}/history`,
    {
      search: searchQuery.value || undefined,
      role: roleFilter.value || undefined,
      sort_dir: sortDir.value !== 'desc' ? sortDir.value : undefined,
      per_page: perPage.value !== 20 ? perPage.value : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const clearFilters = () => {
  searchQuery.value = '';
  roleFilter.value = '';
  sortDir.value = 'desc';
  perPage.value = 20;
  applyFilters();
};

const goToPage = (url) => {
  if (!url) return;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  });
};

// Edit User Quota Modal
const isQuotaModalOpen = ref(false);
const quotaForm = useForm({
  monthly_query_limit: props.quota.monthly_query_limit,
  plan_tier: props.quota.plan_tier,
  queries_used_this_month: props.quota.queries_used_this_month,
  reset_usage: false,
});

const userTierOptions = [
  { label: 'Free (Standard)', value: 'free' },
  { label: 'Pro (Power Director)', value: 'pro' },
  { label: 'Enterprise (Unlimited/Custom)', value: 'enterprise' },
];

const openQuotaModal = () => {
  quotaForm.monthly_query_limit = props.quota.monthly_query_limit;
  quotaForm.plan_tier = props.quota.plan_tier;
  quotaForm.queries_used_this_month = props.quota.queries_used_this_month;
  quotaForm.reset_usage = false;
  isQuotaModalOpen.value = true;
};

const submitUserQuota = () => {
  quotaForm.post(`/admin/v2/ai-governance/users/${props.targetUser.id}/quota`, {
    preserveScroll: true,
    onSuccess: () => {
      isQuotaModalOpen.value = false;
    }
  });
};

// Block/Unblock Modal
const isBlockModalOpen = ref(false);
const blockForm = useForm({
  is_blocked: !props.quota.is_blocked,
  reason: props.quota.block_reason || '',
});

const openBlockModal = (blockState) => {
  blockForm.is_blocked = blockState;
  blockForm.reason = props.quota.block_reason || '';
  isBlockModalOpen.value = true;
};

const submitBlockToggle = () => {
  blockForm.post(`/admin/v2/ai-governance/users/${props.targetUser.id}/block`, {
    preserveScroll: true,
    onSuccess: () => {
      isBlockModalOpen.value = false;
    }
  });
};

// Helpers
const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  });
};
</script>

<template>
  <AdminLayout title="User AI Audit & Conversation History">
    <Head :title="`AI History: ${targetUser.first_name} ${targetUser.last_name} - Whistle-Works Admin`" />

    <Breadcrumb :items="[
      { label: 'System Governance' },
      { label: 'AI Governance', href: '/admin/v2/ai-governance' },
      { label: `${targetUser.first_name} ${targetUser.last_name}` }
    ]" />

    <!-- 1. Header Profile & Quick Action Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-1">
      <div class="flex items-center gap-3.5 min-w-0">
        <!-- Back Button -->
        <Link
          href="/admin/v2/ai-governance"
          class="p-2 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] hover:bg-slate-50 dark:hover:bg-[#32324A] text-slate-600 dark:text-slate-300 shadow-xs transition-colors flex-shrink-0"
          title="Back to AI Governance"
        >
          <ArrowLeft class="w-4 h-4" />
        </Link>

        <!-- User Avatar & Details -->
        <div class="w-11 h-11 rounded-full bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center justify-center text-[#E08A50] dark:text-[#F29F67] font-bold text-base flex-shrink-0 overflow-hidden">
          <img v-if="targetUser.avatar" :src="targetUser.avatar" :alt="targetUser.first_name" class="w-full h-full object-cover" />
          <span v-else>{{ targetUser.first_name ? targetUser.first_name.charAt(0).toUpperCase() : 'U' }}</span>
        </div>

        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-lg sm:text-xl font-bold font-display tracking-tight text-slate-900 dark:text-white truncate">
              {{ targetUser.first_name }} {{ targetUser.last_name }}
            </h1>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20">
              {{ quota.plan_tier }}
            </span>
            <span 
              v-if="quota.is_blocked" 
              class="inline-flex items-center gap-1 text-[11px] font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 px-2 py-0.5 rounded-full"
            >
              <Lock class="w-3 h-3" /> Blocked
            </span>
            <span 
              v-else 
              class="inline-flex items-center gap-1 text-[11px] font-semibold bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border border-[#34B1AA]/20 px-2 py-0.5 rounded-full"
            >
              <CheckCircle2 class="w-3 h-3" /> Active
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 truncate mt-0.5">
            {{ targetUser.email }} • User ID: #{{ targetUser.id }}
          </p>
        </div>
      </div>

      <!-- Quick Action CTAs -->
      <div class="flex items-center gap-2 self-start md:self-auto flex-shrink-0">
        <button
          @click="openQuotaModal"
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] hover:bg-slate-50 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-xs transition-colors cursor-pointer"
        >
          <Sliders class="w-3.5 h-3.5 text-[#F29F67]" />
          <span>Adjust Quota</span>
        </button>

        <button
          v-if="!quota.is_blocked"
          @click="openBlockModal(true)"
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 text-xs font-semibold shadow-xs transition-colors cursor-pointer"
        >
          <Lock class="w-3.5 h-3.5" />
          <span>Block User</span>
        </button>

        <button
          v-else
          @click="openBlockModal(false)"
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#34B1AA]/10 hover:bg-[#34B1AA]/20 text-[#2B9B95] dark:text-[#34B1AA] border border-[#34B1AA]/20 text-xs font-semibold shadow-xs transition-colors cursor-pointer"
        >
          <Unlock class="w-3.5 h-3.5" />
          <span>Restore Access</span>
        </button>
      </div>
    </div>

    <!-- 2. Header KPI Overview Bar -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
      
      <!-- Metric 1: All-Time Queries -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Total User Queries</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white">
            {{ stats.total_queries_all_time.toLocaleString() }}
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center justify-center text-[#F29F67]">
          <Activity class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Metric 2: All-Time Tokens -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Tokens Consumed</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#2B9B95] dark:text-[#34B1AA]">
            {{ stats.total_tokens_all_time.toLocaleString() }}
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 flex items-center justify-center text-[#34B1AA]">
          <Cpu class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Metric 3: Tool Executions -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Tool Invocations</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#3B8FF3]">
            {{ stats.total_tools_used.toLocaleString() }}
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 flex items-center justify-center text-[#3B8FF3]">
          <Wrench class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Metric 4: Monthly Quota Usage -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Monthly Allowance</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-amber-600 dark:text-[#E0B50F]">
            {{ stats.monthly_queries }} <span class="text-xs font-normal text-slate-400">/ {{ stats.monthly_limit }}</span>
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#E0B50F]/10 border border-[#E0B50F]/20 flex items-center justify-center text-[#E0B50F]">
          <Coins class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

    </div>

    <!-- 3. Audit Trail Container -->
    <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col transition-colors overflow-hidden">
      
      <!-- Table Top Header -->
      <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <div class="flex items-center gap-2">
            <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white tracking-tight">
              Conversational Stream & Interaction Logs
            </h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20">
              {{ history.total || 0 }} logged turns
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Full chronological audit trail of prompts, responses, tool calls, and token usage
          </p>
        </div>
      </div>

      <!-- Filter Toolbar -->
      <div class="p-3 sm:p-4 bg-slate-50/60 dark:bg-[#1E1E2C]/50 border-b border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
        
        <!-- Search Input -->
        <div class="relative w-full sm:w-72">
          <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 pointer-events-none" />
          <input 
            type="text" 
            v-model="searchQuery"
            @keyup.enter="applyFilters"
            placeholder="Search within conversation text..."
            class="w-full pl-8 pr-7 py-1.5 text-xs rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition-all shadow-2xs"
          />
        </div>

        <!-- Filter Controls -->
        <div class="flex flex-wrap items-center gap-2">
          <Dropdown
            v-model="roleFilter"
            :options="roleOptions"
            size="sm"
            placeholder="All Roles"
            button-class="bg-white dark:bg-[#262638]"
            @change="applyFilters"
          />

          <Dropdown
            v-model="sortDir"
            :options="sortOptions"
            size="sm"
            placeholder="Sort Order"
            button-class="bg-white dark:bg-[#262638]"
            @change="applyFilters"
          />

          <Dropdown
            v-model="perPage"
            :options="perPageOptions"
            size="sm"
            placeholder="Per Page"
            button-class="bg-white dark:bg-[#262638]"
            @change="applyFilters"
          />

          <button 
            @click="applyFilters"
            class="px-3.5 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs font-bold rounded-md shadow-xs transition-all cursor-pointer"
          >
            Filter
          </button>

          <button 
            v-if="searchQuery || roleFilter || sortDir !== 'desc' || perPage !== 20"
            @click="clearFilters"
            class="px-3 py-1.5 rounded-md border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#1E1E2C] text-slate-600 dark:text-slate-300 text-xs transition-colors cursor-pointer"
          >
            Reset
          </button>
        </div>
      </div>

      <!-- Messages Stream List -->
      <div class="p-4 sm:p-6 space-y-4">
        
        <!-- Empty State -->
        <div v-if="!history.data || history.data.length === 0" class="py-16 text-center">
          <div class="max-w-xs mx-auto flex flex-col items-center justify-center text-center space-y-2.5">
            <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center text-slate-400 dark:text-slate-500">
              <Inbox class="w-6 h-6" />
            </div>
            <div>
              <h4 class="text-sm font-bold text-slate-900 dark:text-white">No conversation logs found</h4>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                {{ searchQuery ? `No matches found for "${searchQuery}".` : 'This user has not initiated any AI coach conversations yet.' }}
              </p>
            </div>
            <button 
              v-if="searchQuery || roleFilter" 
              @click="clearFilters" 
              class="mt-1 px-3 py-1.5 text-xs rounded-md bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] hover:bg-[#F29F67]/20 border border-[#F29F67]/30 transition-colors cursor-pointer"
            >
              Clear Filter
            </button>
          </div>
        </div>

        <!-- Timeline Log Item -->
        <div 
          v-for="msg in history.data" 
          :key="msg.id"
          :class="[
            msg.role === 'user' 
              ? 'bg-slate-50 dark:bg-[#1E1E2C] border-slate-200 dark:border-white/[0.08]' 
              : msg.role === 'assistant'
                ? 'bg-[#F29F67]/5 dark:bg-[#262638] border-slate-200 dark:border-white/[0.12] ring-1 ring-[#F29F67]/20'
                : 'bg-slate-100/60 dark:bg-[#181824] border-slate-200 dark:border-white/[0.06]',
            'rounded-lg border p-4 sm:p-5 space-y-3 transition-all'
          ]"
        >
          <!-- Log Header -->
          <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/60 dark:border-white/[0.06] pb-2.5">
            
            <!-- Role Identifier -->
            <div class="flex items-center gap-2.5">
              <div 
                :class="[
                  msg.role === 'user' 
                    ? 'bg-slate-200 dark:bg-white/[0.08] text-slate-700 dark:text-slate-300' 
                    : msg.role === 'assistant'
                      ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/30'
                      : 'bg-slate-200 dark:bg-white/[0.06] text-slate-500',
                  'w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0'
                ]"
              >
                <UserIcon v-if="msg.role === 'user'" class="w-4 h-4" />
                <Sparkles v-else-if="msg.role === 'assistant'" class="w-4 h-4" />
                <Terminal v-else class="w-4 h-4" />
              </div>

              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider font-mono">
                    {{ msg.role === 'user' ? 'Director Prompt' : msg.role === 'assistant' ? 'AI Coach Response' : 'System Prompt' }}
                  </span>
                  <span v-if="msg.session_id" class="text-[10px] font-mono text-slate-400">
                    • Session #{{ msg.session_id }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Metadata Pills -->
            <div class="flex items-center gap-2 text-[11px] font-mono text-slate-400">
              <span class="inline-flex items-center gap-1 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] px-2 py-0.5 rounded text-[10px]">
                <Cpu class="w-3 h-3 text-[#34B1AA]" />
                <span>{{ (msg.tokens_used || 0).toLocaleString() }} tok</span>
              </span>

              <span class="inline-flex items-center gap-1 text-[11px] text-slate-400">
                <Clock class="w-3 h-3" />
                <span>{{ formatDate(msg.created_at) }}</span>
              </span>
            </div>

          </div>

          <!-- Message Body -->
          <div class="text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-wrap font-sans">
            {{ msg.content }}
          </div>

          <!-- Tool Executions / Widget Payload Details (If Any) -->
          <div v-if="msg.tools_called && msg.tools_called.length > 0" class="pt-2 border-t border-slate-200/50 dark:border-white/[0.05] flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-mono text-slate-400 flex items-center gap-1">
              <Wrench class="w-3 h-3 text-[#F29F67]" />
              <span>Tools Executed:</span>
            </span>
            <span 
              v-for="tool in msg.tools_called" 
              :key="tool" 
              class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20"
            >
              {{ tool }}
            </span>
          </div>

          <!-- Widget Payload Indicator (If Any) -->
          <div v-if="msg.widget_type" class="text-[10px] font-mono text-slate-400">
            UI Widget Rendered: <span class="text-[#3B8FF3] font-semibold">{{ msg.widget_type }}</span>
          </div>

        </div>

      </div>

      <!-- Pagination Footer -->
      <div v-if="history.links && history.links.length > 3" class="p-4 sm:p-5 border-t border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50/40 dark:bg-[#1E1E2C]/40">
        <div class="font-mono text-[11px]">
          Showing <span class="font-semibold text-slate-900 dark:text-white">{{ history.from || 0 }}</span> to <span class="font-semibold text-slate-900 dark:text-white">{{ history.to || 0 }}</span> of <span class="font-semibold text-slate-900 dark:text-white">{{ history.total || 0 }}</span> entries
        </div>

        <div class="flex items-center gap-1">
          <template v-for="(link, idx) in history.links" :key="idx">
            <button 
              v-if="idx === 0" 
              @click="goToPage(link.url)" 
              :disabled="!link.url"
              class="p-1.5 rounded-md border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#262638] disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
              title="Previous Page"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
            </button>

            <button 
              v-else-if="idx === history.links.length - 1" 
              @click="goToPage(link.url)" 
              :disabled="!link.url"
              class="p-1.5 rounded-md border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#262638] disabled:opacity-40 disabled:pointer-events-none transition-colors cursor-pointer"
              title="Next Page"
            >
              <ChevronRight class="w-3.5 h-3.5" />
            </button>

            <button 
              v-else 
              @click="goToPage(link.url)" 
              :disabled="!link.url || link.active"
              :class="[
                link.active 
                  ? 'bg-[#F29F67] text-slate-950 font-bold border-[#F29F67] shadow-2xs' 
                  : 'border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#262638] text-slate-700 dark:text-slate-300',
                'min-w-[28px] h-7 px-2 rounded-md border text-xs font-mono transition-colors cursor-pointer flex items-center justify-center'
              ]"
              v-html="link.label"
            />
          </template>
        </div>
      </div>

    </div>

    <!-- ADJUST USER AI ALLOWANCE MODAL -->
    <Modal :show="isQuotaModalOpen" @close="isQuotaModalOpen = false" max-width="md" title="Adjust User AI Allowance">
      <form @submit.prevent="submitUserQuota" class="space-y-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Plan Tier</label>
          <Dropdown
            v-model="quotaForm.plan_tier"
            :options="userTierOptions"
            size="sm"
            align="left"
            class="w-full block"
            button-class="w-full !px-3 !py-2 !text-xs !font-sans justify-between"
            menu-class="w-full min-w-full"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Monthly Query Limit</label>
          <input 
            type="number" 
            v-model.number="quotaForm.monthly_query_limit"
            class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition-all"
          />
        </div>

        <div class="pt-0.5">
          <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox" v-model="quotaForm.reset_usage" class="rounded border-slate-300 dark:border-white/[0.1] text-[#F29F67] focus:ring-[#F29F67]" />
            <span class="text-xs text-slate-600 dark:text-slate-300">Reset monthly queries count to 0 now</span>
          </label>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-end gap-2.5">
          <button 
            type="button" 
            @click="isQuotaModalOpen = false" 
            class="px-3.5 py-1.5 border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#1E1E2C] rounded-md text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            class="px-4 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 font-bold rounded-md text-xs shadow-xs transition-all cursor-pointer"
          >
            Save Quota
          </button>
        </div>
      </form>
    </Modal>

    <!-- BLOCK / UNBLOCK MODAL -->
    <Modal :show="isBlockModalOpen" @close="isBlockModalOpen = false" max-width="md" :title="blockForm.is_blocked ? 'Block Director from AI Features' : 'Restore AI Access'">
      <div class="space-y-3.5">
        <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
          {{ blockForm.is_blocked ? 'The user will be immediately restricted from making queries to the AI Coach.' : 'The user will be able to resume using the AI Coach normally.' }}
        </p>

        <form @submit.prevent="submitBlockToggle" class="space-y-3.5">
          <div v-if="blockForm.is_blocked">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reason for Restriction</label>
            <input 
              type="text" 
              v-model="blockForm.reason" 
              placeholder="e.g., Excessive quota abuse or unpaid invoice"
              class="w-full px-3 py-2 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#1E1E2C] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
            />
          </div>

          <div class="pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-end gap-2.5">
            <button 
              type="button" 
              @click="isBlockModalOpen = false" 
              class="px-3.5 py-1.5 border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#1E1E2C] rounded-md text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :class="[
                blockForm.is_blocked ? 'bg-rose-600 hover:bg-rose-700 text-white' : 'bg-[#34B1AA] hover:bg-[#2B9B95] text-slate-950 font-bold', 
                'px-4 py-1.5 rounded-md text-xs font-bold shadow-xs transition-all cursor-pointer'
              ]"
            >
              {{ blockForm.is_blocked ? 'Confirm Block' : 'Confirm Unblock' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

  </AdminLayout>
</template>
