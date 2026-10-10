<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import Breadcrumb from '../../Components/Common/Breadcrumb.vue';
import Modal from '../../Components/Common/Modal.vue';
import Dropdown from '../../Components/Common/Dropdown.vue';
import {
  Sparkles,
  Cpu,
  Sliders,
  Users,
  BarChart3,
  Key,
  ShieldAlert,
  ShieldCheck,
  CheckCircle2,
  AlertTriangle,
  RefreshCw,
  Search,
  Eye,
  EyeOff,
  History,
  Lock,
  Unlock,
  Coins,
  TrendingUp,
  Activity,
  Check,
  ChevronLeft,
  ChevronRight,
  Inbox
} from 'lucide-vue-next';

const props = defineProps({
  metrics: {
    type: Object,
    required: true,
  },
  settings: {
    type: Object,
    required: true,
  },
  quotas: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  }
});

// Active Tab
const activeTab = ref('overview'); // 'overview' | 'config' | 'users'

// Settings Form
const showOpenAiKey = ref(false);
const showGeminiKey = ref(false);

const settingsForm = useForm({
  ai_provider: props.settings.ai_provider || 'openai',
  openai_api_key: '',
  openai_model: props.settings.openai_model || 'gpt-4o',
  gemini_api_key: '',
  gemini_model: props.settings.gemini_model || 'gemini-1.5-pro',
  default_monthly_quota: props.settings.default_monthly_quota || 50,
  enable_ai_coach: props.settings.enable_ai_coach,
  ai_system_prompt_override: props.settings.ai_system_prompt_override || '',
});

const submitSettings = () => {
  settingsForm.post('/admin/v2/ai-governance/settings', {
    preserveScroll: true,
    onSuccess: () => {
      settingsForm.openai_api_key = '';
      settingsForm.gemini_api_key = '';
    }
  });
};

// Users Filtering & Search
const search = ref(props.filters.search || '');
const planFilter = ref(props.filters.plan_tier || '');
const blockFilter = ref(props.filters.is_blocked !== undefined && props.filters.is_blocked !== null ? String(props.filters.is_blocked) : '');

const planOptions = [
  { label: 'All Tiers', value: '' },
  { label: 'Free Tier', value: 'free' },
  { label: 'Pro Tier', value: 'pro' },
  { label: 'Enterprise Tier', value: 'enterprise' },
];

const blockOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Active Only', value: '0' },
  { label: 'Blocked Only', value: '1' },
];

const openAiModelOptions = [
  { label: 'gpt-4o (High Intelligence & Fast Function Calling)', value: 'gpt-4o' },
  { label: 'gpt-4o-mini (Cost-Optimized)', value: 'gpt-4o-mini' },
  { label: 'gpt-4-turbo', value: 'gpt-4-turbo' },
];

const geminiModelOptions = [
  { label: 'gemini-3.1-flash-lite (Recommended / Ultra Fast)', value: 'gemini-3.1-flash-lite' },
  { label: 'gemini-3.5-flash-lite (Fast & Responsive)', value: 'gemini-3.5-flash-lite' },
  { label: 'gemini-3.8-flash (Latest Intelligence)', value: 'gemini-3.8-flash' },
  { label: 'gemini-2.5-pro (Deep Reasoning)', value: 'gemini-2.5-pro' },
];

const userTierOptions = [
  { label: 'Free (Standard)', value: 'free' },
  { label: 'Pro (Power Director)', value: 'pro' },
  { label: 'Enterprise (Unlimited/Custom)', value: 'enterprise' },
];

const applyFilters = () => {
  router.get('/admin/v2/ai-governance', {
    search: search.value || undefined,
    plan_tier: planFilter.value || undefined,
    is_blocked: blockFilter.value !== '' ? blockFilter.value : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const clearSearch = () => {
  search.value = '';
  planFilter.value = '';
  blockFilter.value = '';
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
const selectedUserQuota = ref(null);
const quotaForm = useForm({
  monthly_query_limit: 50,
  plan_tier: 'free',
  queries_used_this_month: 0,
  reset_usage: false,
});

const openQuotaModal = (quota) => {
  selectedUserQuota.value = quota;
  quotaForm.monthly_query_limit = quota.monthly_query_limit;
  quotaForm.plan_tier = quota.plan_tier;
  quotaForm.queries_used_this_month = quota.queries_used_this_month;
  quotaForm.reset_usage = false;
  isQuotaModalOpen.value = true;
};

const submitUserQuota = () => {
  if (!selectedUserQuota.value) return;
  quotaForm.post(`/admin/v2/ai-governance/users/${selectedUserQuota.value.user_id}/quota`, {
    preserveScroll: true,
    onSuccess: () => {
      isQuotaModalOpen.value = false;
    }
  });
};

// Block/Unblock User Modal
const isBlockModalOpen = ref(false);
const targetUser = ref(null);
const blockForm = useForm({
  is_blocked: false,
  reason: '',
});

const openBlockModal = (quota, blockState) => {
  targetUser.value = quota;
  blockForm.is_blocked = blockState;
  blockForm.reason = quota.block_reason || '';
  isBlockModalOpen.value = true;
};

const submitBlockToggle = () => {
  if (!targetUser.value) return;
  blockForm.post(`/admin/v2/ai-governance/users/${targetUser.value.user_id}/block`, {
    preserveScroll: true,
    onSuccess: () => {
      isBlockModalOpen.value = false;
    }
  });
};

// User Usage Trend Line Graph Modal
const isGraphModalOpen = ref(false);
const graphLoading = ref(false);
const graphTargetQuota = ref(null);
const graphData = ref({
  labels: [],
  queries: [],
  tokens: [],
  total_queries_period: 0,
  total_tokens_period: 0,
  monthly_queries: 0,
  monthly_limit: 50,
  monthly_tokens: 0,
  user: null,
});
const graphDays = ref(14);
const graphMetric = ref('queries'); // 'queries' | 'tokens'
const hoveredPoint = ref(null);

const graphTargetName = computed(() => {
  if (!graphTargetQuota.value) return 'User';
  const u = graphTargetQuota.value.user;
  return u ? `${u.first_name} ${u.last_name}` : `User #${graphTargetQuota.value.user_id}`;
});

const fetchUsageGraph = async (userId, days = 14) => {
  graphLoading.value = true;
  hoveredPoint.value = null;
  try {
    const res = await fetch(`/admin/v2/ai-governance/users/${userId}/trend?days=${days}`);
    const data = await res.json();
    if (data.success) {
      graphData.value = data.data;
    }
  } catch (err) {
    console.error('Failed to load user usage trend', err);
  } finally {
    graphLoading.value = false;
  }
};

const openUsageGraphModal = (quota) => {
  graphTargetQuota.value = quota;
  graphDays.value = 14;
  graphMetric.value = 'queries';
  hoveredPoint.value = null;
  isGraphModalOpen.value = true;
  fetchUsageGraph(quota.user_id, 14);
};

const setGraphDays = (days) => {
  graphDays.value = days;
  if (graphTargetQuota.value) {
    fetchUsageGraph(graphTargetQuota.value.user_id, days);
  }
};

// SVG Line Chart Coordinate Helpers (600x200 viewbox)
const chartWidth = 560;
const chartHeight = 160;
const chartPaddingX = 20;
const chartPaddingY = 20;

const currentMetricSeries = computed(() => {
  return graphMetric.value === 'queries'
    ? graphData.value.queries || []
    : graphData.value.tokens || [];
});

const maxMetricValue = computed(() => {
  const series = currentMetricSeries.value;
  if (!series || series.length === 0) return 10;
  const max = Math.max(...series);
  return max > 0 ? max : 10;
});

const chartPoints = computed(() => {
  const series = currentMetricSeries.value;
  const labels = graphData.value.labels || [];
  if (!series || series.length === 0) return [];

  const maxVal = maxMetricValue.value;
  const count = series.length;
  const stepX = count > 1 ? (chartWidth - chartPaddingX * 2) / (count - 1) : 0;

  return series.map((val, idx) => {
    const x = chartPaddingX + idx * stepX;
    const normY = val / maxVal;
    const y = chartHeight - chartPaddingY - normY * (chartHeight - chartPaddingY * 2);
    return {
      x: Number(x.toFixed(1)),
      y: Number(y.toFixed(1)),
      value: val,
      label: labels[idx] || '',
      queries: graphData.value.queries ? graphData.value.queries[idx] : 0,
      tokens: graphData.value.tokens ? graphData.value.tokens[idx] : 0,
    };
  });
});

const polylinePoints = computed(() => {
  return chartPoints.value.map((p) => `${p.x},${p.y}`).join(' ');
});

const areaPathD = computed(() => {
  const pts = chartPoints.value;
  if (pts.length === 0) return '';
  const first = pts[0];
  const last = pts[pts.length - 1];
  const baseY = chartHeight - chartPaddingY;

  const pointsD = pts.map((p, idx) => `${idx === 0 ? 'M' : 'L'} ${p.x} ${p.y}`).join(' ');
  return `${pointsD} L ${last.x} ${baseY} L ${first.x} ${baseY} Z`;
});
</script>

<template>
  <AdminLayout title="AI Governance & Analytics">
    <Head title="AI Coach Governance - Whistle-Works Admin" />

    <Breadcrumb :items="[{ label: 'System Governance' }, { label: 'AI Coach & Governance' }]" />

    <!-- 1. Header & Global Engine Status -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold font-display tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
          <Sparkles class="w-6 h-6 text-[#F29F67]" />
          AI Coach & Analytics Governance
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
          Configure dynamic LLM keys, monitor token consumption, manage monthly user quotas, and audit queries.
        </p>
      </div>

      <!-- Global Engine Status Badge -->
      <div class="flex items-center gap-3 self-start sm:self-auto">
        <div 
          :class="[
            settings.enable_ai_coach 
              ? 'bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border-[#34B1AA]/20' 
              : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            'px-3.5 py-1.5 rounded-full border text-xs font-semibold flex items-center gap-2 shadow-2xs'
          ]"
        >
          <span :class="[settings.enable_ai_coach ? 'bg-[#34B1AA]' : 'bg-rose-500', 'w-2 h-2 rounded-full animate-pulse']"></span>
          <span>AI Engine: {{ settings.enable_ai_coach ? 'Active & Operational' : 'Disabled' }}</span>
        </div>
      </div>
    </div>

    <!-- 2. Header KPI Overview Bar (Matched to Camp List / Standard Admin Design) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
      
      <!-- Metric 1: Monthly Queries -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Monthly Queries</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white">
            {{ metrics.metrics?.total_queries_month?.toLocaleString() || 0 }}
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center justify-center text-[#F29F67]">
          <Activity class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Metric 2: Tokens Consumed -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Tokens Consumed</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#2B9B95] dark:text-[#34B1AA]">
            {{ metrics.metrics?.total_tokens_month?.toLocaleString() || 0 }}
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 flex items-center justify-center text-[#34B1AA]">
          <Cpu class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Metric 3: Active AI Directors -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Active AI Directors</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#3B8FF3]">
            {{ metrics.metrics?.active_users_month || 0 }}
            <span class="text-xs font-normal text-slate-400">/ {{ metrics.metrics?.total_ai_users || 0 }}</span>
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 flex items-center justify-center text-[#3B8FF3]">
          <Users class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <!-- Metric 4: Est. API Cost -->
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Est. API Cost</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-amber-600 dark:text-[#E0B50F]">
            ${{ Number(metrics.metrics?.estimated_cost_usd || 0).toFixed(2) }}
          </p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#E0B50F]/10 border border-[#E0B50F]/20 flex items-center justify-center text-[#E0B50F]">
          <Coins class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

    </div>

    <!-- 3. Navigation Tabs -->
    <div class="flex border-b border-slate-200 dark:border-white/[0.08] gap-1 sm:gap-2">
      <button
        @click="activeTab = 'overview'"
        :class="[
          activeTab === 'overview'
            ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] dark:border-[#F29F67] font-bold'
            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
          'flex items-center gap-2 py-3 px-3.5 sm:px-4 border-b-2 font-medium text-xs sm:text-sm transition-colors cursor-pointer'
        ]"
      >
        <BarChart3 class="w-4 h-4" />
        <span>Analytics & Usage Overview</span>
      </button>

      <button
        @click="activeTab = 'config'"
        :class="[
          activeTab === 'config'
            ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] dark:border-[#F29F67] font-bold'
            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
          'flex items-center gap-2 py-3 px-3.5 sm:px-4 border-b-2 font-medium text-xs sm:text-sm transition-colors cursor-pointer'
        ]"
      >
        <Sliders class="w-4 h-4" />
        <span>Provider & Key Configuration</span>
      </button>

      <button
        @click="activeTab = 'users'"
        :class="[
          activeTab === 'users'
            ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] dark:border-[#F29F67] font-bold'
            : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200',
          'flex items-center gap-2 py-3 px-3.5 sm:px-4 border-b-2 font-medium text-xs sm:text-sm transition-colors cursor-pointer'
        ]"
      >
        <Users class="w-4 h-4" />
        <span>User Quotas & Access Control ({{ quotas.total || 0 }})</span>
      </button>
    </div>

    <!-- TAB 1: Analytics & Overview -->
    <div v-if="activeTab === 'overview'" class="space-y-5 sm:space-y-6">
      <!-- 14-Day Usage Trend Visualization -->
      <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs p-4 sm:p-5 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold font-display text-slate-900 dark:text-white tracking-tight">14-Day Query Volume Trend</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Daily conversational turns and tool executions</p>
          </div>
          <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] px-2.5 py-1 rounded-md">
            Last 14 Days
          </span>
        </div>

        <div class="h-44 flex items-end gap-2 pt-6 pb-2 px-2 border-b border-slate-100 dark:border-white/[0.06]">
          <div 
            v-for="(label, idx) in metrics.chart_data?.labels || []" 
            :key="idx" 
            class="flex-1 flex flex-col items-center gap-2 group relative"
          >
            <div 
              class="w-full bg-[#F29F67] hover:bg-[#E08A50] rounded-t transition-all duration-300 relative cursor-pointer"
              :style="{ height: `${Math.max(8, ((metrics.chart_data.queries[idx] || 0) / (Math.max(...(metrics.chart_data.queries || [1]), 1))) * 120)}px` }"
            >
              <!-- Tooltip -->
              <div class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 dark:bg-[#181824] border border-slate-700 dark:border-white/[0.1] text-white text-[11px] font-mono font-semibold py-1 px-2.5 rounded-md opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg pointer-events-none z-10">
                {{ metrics.chart_data.queries[idx] }} queries ({{ (metrics.chart_data.tokens[idx] || 0).toLocaleString() }} tok)
              </div>
            </div>
            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono font-medium truncate w-full text-center">{{ label }}</span>
          </div>
        </div>
      </div>

      <!-- Top Active Users Table -->
      <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-white/[0.08] flex items-center justify-between">
          <div>
            <h3 class="text-base font-bold font-display text-slate-900 dark:text-white tracking-tight">Most Active AI Directors</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Top contributors by request count this billing cycle</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs min-w-[650px]">
            <thead class="bg-slate-50/80 dark:bg-[#1E1E2C]/80 border-b border-slate-200 dark:border-white/[0.08] text-slate-500 dark:text-slate-400 font-mono uppercase text-[10px]">
              <tr>
                <th class="py-3 px-4 font-semibold tracking-wider">Director</th>
                <th class="py-3 px-4 font-semibold tracking-wider">Plan Tier</th>
                <th class="py-3 px-4 font-semibold tracking-wider text-center">Queries Used</th>
                <th class="py-3 px-4 font-semibold tracking-wider text-center">Tokens Used</th>
                <th class="py-3 px-4 font-semibold tracking-wider text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr 
                v-for="top in metrics.top_users" 
                :key="top.user_id" 
                class="hover:bg-slate-50/80 dark:hover:bg-[#2D2D42]/80 transition-colors"
              >
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-900 dark:text-white">{{ top.name }}</div>
                  <div class="text-[11px] text-slate-400">{{ top.email }}</div>
                </td>
                <td class="py-3 px-4">
                  <span class="inline-block px-2 py-0.5 rounded text-[11px] font-mono font-bold uppercase bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20">
                    {{ top.plan_tier }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center font-bold font-mono text-slate-900 dark:text-white">
                  {{ top.queries_used }}
                </td>
                <td class="py-3 px-4 text-center font-mono text-slate-500 dark:text-slate-400">
                  {{ (top.tokens_used || 0).toLocaleString() }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span 
                    v-if="top.is_blocked" 
                    class="inline-flex items-center gap-1 text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 px-2.5 py-0.5 rounded-full"
                  >
                    <Lock class="w-3 h-3" /> Blocked
                  </span>
                  <span 
                    v-else 
                    class="inline-flex items-center gap-1 text-xs font-semibold bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border border-[#34B1AA]/20 px-2.5 py-0.5 rounded-full"
                  >
                    <CheckCircle2 class="w-3 h-3" /> Active
                  </span>
                </td>
              </tr>
              <tr v-if="!metrics.top_users || metrics.top_users.length === 0">
                <td colspan="5" class="py-8 text-center text-slate-400 text-xs">
                  No active users recorded this period.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- TAB 2: Provider & Key Configuration -->
    <div v-if="activeTab === 'config'" class="space-y-6">
      <form @submit.prevent="submitSettings" class="space-y-6">
        <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs p-5 sm:p-6 space-y-6">
          <div>
            <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white flex items-center gap-2">
              <Sliders class="w-5 h-5 text-[#F29F67]" />
              Primary LLM Provider & Credentials
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Select your LLM intelligence provider and securely manage API keys. Keys are stored encrypted in the database.
            </p>
          </div>

          <!-- Provider Selector Radios -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label 
              :class="[
                settingsForm.ai_provider === 'openai' 
                  ? 'border-[#F29F67] ring-1 ring-[#F29F67] bg-[#F29F67]/5 dark:bg-[#F29F67]/10' 
                  : 'border-slate-200 dark:border-white/[0.08] bg-slate-50/50 dark:bg-[#1E1E2C]/50 hover:border-slate-300 dark:hover:border-white/[0.15]',
                'p-4 rounded-lg border cursor-pointer transition-all flex items-start gap-3 shadow-2xs'
              ]"
            >
              <input type="radio" v-model="settingsForm.ai_provider" value="openai" class="mt-1 text-[#F29F67] focus:ring-[#F29F67]" />
              <div>
                <div class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-2">
                  <span>OpenAI Engine</span>
                  <span v-if="settings.has_openai_key" class="text-[10px] font-mono font-bold bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border border-[#34B1AA]/20 px-2 py-0.5 rounded">Key Configured</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">GPT-4o, GPT-4o-mini with native multi-tool calling.</p>
              </div>
            </label>

            <label 
              :class="[
                settingsForm.ai_provider === 'gemini' 
                  ? 'border-[#F29F67] ring-1 ring-[#F29F67] bg-[#F29F67]/5 dark:bg-[#F29F67]/10' 
                  : 'border-slate-200 dark:border-white/[0.08] bg-slate-50/50 dark:bg-[#1E1E2C]/50 hover:border-slate-300 dark:hover:border-white/[0.15]',
                'p-4 rounded-lg border cursor-pointer transition-all flex items-start gap-3 shadow-2xs'
              ]"
            >
              <input type="radio" v-model="settingsForm.ai_provider" value="gemini" class="mt-1 text-[#F29F67] focus:ring-[#F29F67]" />
              <div>
                <div class="font-bold text-xs sm:text-sm text-slate-900 dark:text-white flex items-center gap-2">
                  <span>Google Gemini Engine</span>
                  <span v-if="settings.has_gemini_key" class="text-[10px] font-mono font-bold bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border border-[#34B1AA]/20 px-2 py-0.5 rounded">Key Configured</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Gemini Flash / Pro models with fast speed and reasoning.</p>
              </div>
            </label>
          </div>

          <!-- OpenAI Settings -->
          <div v-if="settingsForm.ai_provider === 'openai'" class="space-y-4 pt-4 border-t border-slate-100 dark:border-white/[0.08]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">OpenAI API Key</label>
                <div class="relative">
                  <input 
                    :type="showOpenAiKey ? 'text' : 'password'"
                    v-model="settingsForm.openai_api_key"
                    :placeholder="settings.openai_api_key ? `Current: ${settings.openai_api_key}` : 'sk-...'"
                    class="w-full px-3.5 py-2 rounded-md text-xs sm:text-sm border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#1E1E2C] text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                  />
                  <button type="button" @click="showOpenAiKey = !showOpenAiKey" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <Eye v-if="!showOpenAiKey" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Model Selection</label>
                <Dropdown
                  v-model="settingsForm.openai_model"
                  :options="openAiModelOptions"
                  size="sm"
                  align="left"
                  class="w-full"
                  button-class="w-full justify-between"
                />
              </div>
            </div>
          </div>

          <!-- Gemini Settings -->
          <div v-if="settingsForm.ai_provider === 'gemini'" class="space-y-4 pt-4 border-t border-slate-100 dark:border-white/[0.08]">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Google Gemini API Key</label>
                <div class="relative">
                  <input 
                    :type="showGeminiKey ? 'text' : 'password'"
                    v-model="settingsForm.gemini_api_key"
                    :placeholder="settings.gemini_api_key ? `Current: ${settings.gemini_api_key}` : 'AIzaSy...'"
                    class="w-full px-3.5 py-2 rounded-md text-xs sm:text-sm border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#1E1E2C] text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
                  />
                  <button type="button" @click="showGeminiKey = !showGeminiKey" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
                    <Eye v-if="!showGeminiKey" class="w-4 h-4" />
                    <EyeOff v-else class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Gemini Model</label>
                <Dropdown
                  v-model="settingsForm.gemini_model"
                  :options="geminiModelOptions"
                  size="sm"
                  align="left"
                  class="w-full"
                  button-class="w-full justify-between"
                />
              </div>
            </div>
          </div>

          <!-- Global Defaults & Guardrails -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-slate-100 dark:border-white/[0.08]">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Default Monthly Queries Per User</label>
              <input 
                type="number"
                v-model.number="settingsForm.default_monthly_quota"
                class="w-full px-3.5 py-2 rounded-md text-xs sm:text-sm border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#1E1E2C] text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
              />
              <p class="text-[11px] text-slate-400">Baseline monthly allowance for free tier directors</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Master AI Kill Switch</label>
              <div class="flex items-center gap-3 pt-2">
                <button
                  type="button"
                  @click="settingsForm.enable_ai_coach = !settingsForm.enable_ai_coach"
                  :title="`Click to switch ${settingsForm.enable_ai_coach ? 'off' : 'on'}`"
                  :class="[
                    settingsForm.enable_ai_coach ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
                  ]"
                >
                  <span
                    :class="[
                      settingsForm.enable_ai_coach ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
                <span class="text-xs font-medium text-slate-700 dark:text-slate-300">
                  {{ settingsForm.enable_ai_coach ? 'Enabled & Operational' : 'Disabled (Users Blocked)' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Custom System Prompt Override -->
          <div class="space-y-1.5 pt-4 border-t border-slate-100 dark:border-white/[0.08]">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Custom System Prompt Override</label>
              <span class="text-[11px] text-slate-400">Leave blank to use Whistle Works default coaching persona</span>
            </div>
            <textarea 
              v-model="settingsForm.ai_system_prompt_override"
              rows="4"
              placeholder="You are the Whistle Works AI Coach..."
              class="w-full p-3 rounded-md text-xs font-mono border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#1E1E2C] text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
            ></textarea>
          </div>

          <div class="flex justify-end pt-2">
            <button 
              type="submit" 
              :disabled="settingsForm.processing"
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs sm:text-sm font-bold shadow-xs transition-all cursor-pointer disabled:opacity-50"
            >
              <RefreshCw v-if="settingsForm.processing" class="w-4 h-4 animate-spin" />
              <span>Save AI Configuration</span>
            </button>
          </div>
        </div>
      </form>
    </div>

    <!-- TAB 3: User Quotas & Access Control -->
    <div v-if="activeTab === 'users'" class="space-y-4">
      
      <!-- Quotas Data Card Component -->
      <div class="rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex flex-col transition-colors overflow-hidden">
        
        <!-- Table Top Header -->
        <div class="p-4 sm:p-5 border-b border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white tracking-tight">
                Director AI Quotas & Restrictions
              </h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20">
                {{ quotas.total || 0 }} accounts
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Manage custom query quotas, monitor real-time monthly usage, and restrict user access
            </p>
          </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="p-3 sm:p-4 bg-slate-50/60 dark:bg-[#1E1E2C]/50 border-b border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
          <div class="relative w-full sm:w-72">
            <Search class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500 pointer-events-none" />
            <input 
              type="text" 
              v-model="search"
              @keyup.enter="applyFilters"
              placeholder="Search director name, email..."
              class="w-full pl-8 pr-7 py-1.5 text-xs rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] transition-all shadow-2xs"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <Dropdown
              v-model="planFilter"
              :options="planOptions"
              size="sm"
              placeholder="All Tiers"
              button-class="bg-white dark:bg-[#262638]"
              @change="applyFilters"
            />

            <Dropdown
              v-model="blockFilter"
              :options="blockOptions"
              size="sm"
              placeholder="All Statuses"
              button-class="bg-white dark:bg-[#262638]"
              @change="applyFilters"
            />

            <button 
              @click="applyFilters"
              class="px-3.5 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs font-bold rounded-md shadow-xs transition-all cursor-pointer"
            >
              Filter
            </button>
          </div>
        </div>

        <!-- Quotas Table Surface -->
        <div class="relative overflow-x-auto">
          <table class="w-full text-left text-xs min-w-[700px]">
            <thead class="bg-slate-50/80 dark:bg-[#1E1E2C]/80 border-b border-slate-200 dark:border-white/[0.08] text-slate-500 dark:text-slate-400 font-mono uppercase text-[10px]">
              <tr>
                <th class="py-3.5 px-4 font-semibold tracking-wider">Director</th>
                <th class="py-3.5 px-4 font-semibold tracking-wider">Plan Tier</th>
                <th class="py-3.5 px-4 font-semibold tracking-wider text-center">Monthly Limit</th>
                <th class="py-3.5 px-4 font-semibold tracking-wider text-center">Queries Used</th>
                <th class="py-3.5 px-4 font-semibold tracking-wider text-center">Tokens Used</th>
                <th class="py-3.5 px-4 font-semibold tracking-wider text-center">Access Status</th>
                <th class="py-3.5 px-4 font-semibold tracking-wider text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/[0.06]">
              <tr 
                v-for="q in quotas.data" 
                :key="q.id" 
                class="hover:bg-slate-50/80 dark:hover:bg-[#2D2D42]/80 transition-colors"
              >
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-900 dark:text-white">
                    {{ q.user ? `${q.user.first_name} ${q.user.last_name}` : `User #${q.user_id}` }}
                  </div>
                  <div class="text-[11px] text-slate-400">{{ q.user ? q.user.email : 'N/A' }}</div>
                </td>
                <td class="py-3 px-4">
                  <span class="inline-block px-2 py-0.5 rounded text-[11px] font-mono font-bold uppercase bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] border border-[#F29F67]/20">
                    {{ q.plan_tier }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center font-mono font-bold text-slate-900 dark:text-white">
                  {{ q.monthly_query_limit }}
                </td>
                <td class="py-3 px-4 text-center font-mono">
                  <span :class="[q.queries_used_this_month >= q.monthly_query_limit ? 'text-rose-500 font-bold' : 'text-slate-800 dark:text-slate-200 font-medium']">
                    {{ q.queries_used_this_month }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                  {{ (q.tokens_used_this_month || 0).toLocaleString() }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span 
                    v-if="q.is_blocked" 
                    class="inline-flex items-center gap-1 text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 px-2.5 py-0.5 rounded-full"
                    :title="q.block_reason"
                  >
                    <Lock class="w-3 h-3" /> Blocked
                  </span>
                  <span 
                    v-else 
                    class="inline-flex items-center gap-1 text-xs font-semibold bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border border-[#34B1AA]/20 px-2.5 py-0.5 rounded-full"
                  >
                    <CheckCircle2 class="w-3 h-3" /> Active
                  </span>
                </td>
                <td class="py-3 px-4 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button 
                      @click="openQuotaModal(q)"
                      class="p-1.5 text-slate-400 hover:text-[#F29F67] hover:bg-[#F29F67]/10 dark:hover:bg-[#1E1E2C] rounded-md transition-colors cursor-pointer"
                      title="Edit Quota & Tier"
                    >
                      <Sliders class="w-4 h-4" />
                    </button>

                    <!-- [FUTURE FEATURE]: Dedicated User AI History Page Link (Uncomment when needed)
                    <Link 
                      :href="`/admin/v2/ai-governance/users/${q.user_id}/history`"
                      class="p-1.5 text-slate-400 hover:text-[#3B8FF3] hover:bg-[#3B8FF3]/10 dark:hover:bg-[#1E1E2C] rounded-md transition-colors cursor-pointer inline-flex items-center justify-center"
                      title="Audit Conversation History"
                    >
                      <History class="w-4 h-4" />
                    </Link>
                    -->

                    <!-- Usage Trend Line Graph Modal Button -->
                    <button 
                      @click="openUsageGraphModal(q)"
                      class="p-1.5 text-slate-400 hover:text-[#2B9B95] hover:bg-[#34B1AA]/10 dark:hover:bg-[#1E1E2C] rounded-md transition-colors cursor-pointer inline-flex items-center justify-center"
                      title="View AI Usage Trend Graph"
                    >
                      <TrendingUp class="w-4 h-4" />
                    </button>

                    <button 
                      v-if="!q.is_blocked"
                      @click="openBlockModal(q, true)"
                      class="p-1.5 text-slate-400 hover:text-rose-500 hover:bg-rose-500/10 dark:hover:bg-[#1E1E2C] rounded-md transition-colors cursor-pointer"
                      title="Block User from AI"
                    >
                      <Lock class="w-4 h-4" />
                    </button>

                    <button 
                      v-else
                      @click="openBlockModal(q, false)"
                      class="p-1.5 text-[#34B1AA] hover:text-[#2B9B95] hover:bg-[#34B1AA]/10 dark:hover:bg-[#1E1E2C] rounded-md transition-colors cursor-pointer"
                      title="Unblock User"
                    >
                      <Unlock class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Empty State -->
              <tr v-if="!quotas.data || quotas.data.length === 0">
                <td colspan="7" class="py-12 px-4 text-center">
                  <div class="max-w-xs mx-auto flex flex-col items-center justify-center text-center space-y-2.5">
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center text-slate-400 dark:text-slate-500">
                      <Inbox class="w-5 h-5" />
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-900 dark:text-white">No records found</h4>
                      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        {{ search ? `No results matching "${search}".` : 'No user quotas available.' }}
                      </p>
                    </div>
                    <button 
                      v-if="search" 
                      @click="clearSearch" 
                      class="mt-1 px-3 py-1.5 text-xs rounded-md bg-[#F29F67]/10 text-[#E08A50] dark:text-[#F29F67] hover:bg-[#F29F67]/20 border border-[#F29F67]/30 transition-colors cursor-pointer"
                    >
                      Clear Filter
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Table Pagination Footer -->
        <div v-if="quotas.links && quotas.links.length > 3" class="p-4 sm:p-5 border-t border-slate-100 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 bg-slate-50/40 dark:bg-[#1E1E2C]/40">
          <div class="font-mono text-[11px]">
            Showing <span class="font-semibold text-slate-900 dark:text-white">{{ quotas.from || 0 }}</span> to <span class="font-semibold text-slate-900 dark:text-white">{{ quotas.to || 0 }}</span> of <span class="font-semibold text-slate-900 dark:text-white">{{ quotas.total || 0 }}</span> entries
          </div>

          <div class="flex items-center gap-1">
            <template v-for="(link, idx) in quotas.links" :key="idx">
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
                v-else-if="idx === quotas.links.length - 1" 
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
    </div>

    <!-- EDIT QUOTA MODAL -->
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
            placeholder="50"
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

    <!-- BLOCK/UNBLOCK MODAL -->
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

    <!-- USER USAGE TREND LINE GRAPH MODAL -->
    <Modal :show="isGraphModalOpen" @close="isGraphModalOpen = false" max-width="2xl" :title="`AI Usage Trend: ${graphTargetName}`">
      <div class="space-y-4">
        
        <!-- Summary Stats Pills -->
        <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
          <div class="p-3 rounded-lg bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] space-y-1">
            <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 uppercase tracking-wider">
              <span>Period Queries</span>
              <Activity class="w-3.5 h-3.5 text-[#F29F67]" />
            </div>
            <p class="text-base sm:text-lg font-bold font-display text-slate-900 dark:text-white">
              {{ (graphData.total_queries_period || 0).toLocaleString() }}
            </p>
          </div>

          <div class="p-3 rounded-lg bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] space-y-1">
            <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 uppercase tracking-wider">
              <span>Period Tokens</span>
              <Cpu class="w-3.5 h-3.5 text-[#34B1AA]" />
            </div>
            <p class="text-base sm:text-lg font-bold font-display text-[#2B9B95] dark:text-[#34B1AA]">
              {{ (graphData.total_tokens_period || 0).toLocaleString() }}
            </p>
          </div>

          <div class="p-3 rounded-lg bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] space-y-1">
            <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 uppercase tracking-wider">
              <span>Monthly Allowance</span>
              <Coins class="w-3.5 h-3.5 text-[#E0B50F]" />
            </div>
            <p class="text-base sm:text-lg font-bold font-display text-amber-600 dark:text-[#E0B50F]">
              {{ graphData.monthly_queries || 0 }} <span class="text-xs font-normal text-slate-400">/ {{ graphData.monthly_limit || 50 }}</span>
            </p>
          </div>
        </div>

        <!-- Metric & Timeframe Controls Toolbar -->
        <div class="flex flex-wrap items-center justify-between gap-2.5 pt-1">
          <!-- Metric Switcher -->
          <div class="flex items-center p-0.5 rounded-md bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <button
              type="button"
              @click="graphMetric = 'queries'"
              :class="[
                graphMetric === 'queries'
                  ? 'bg-[#F29F67] text-slate-950 font-bold shadow-2xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'px-2.5 py-1 text-xs rounded transition-all cursor-pointer flex items-center gap-1.5'
              ]"
            >
              <Activity class="w-3 h-3" />
              <span>Queries Count</span>
            </button>

            <button
              type="button"
              @click="graphMetric = 'tokens'"
              :class="[
                graphMetric === 'tokens'
                  ? 'bg-[#34B1AA] text-slate-950 font-bold shadow-2xs'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                'px-2.5 py-1 text-xs rounded transition-all cursor-pointer flex items-center gap-1.5'
              ]"
            >
              <Cpu class="w-3 h-3" />
              <span>Tokens Volume</span>
            </button>
          </div>

          <!-- Timeframe Selector -->
          <div class="flex items-center gap-1">
            <button
              v-for="days in [7, 14, 30]"
              :key="days"
              type="button"
              @click="setGraphDays(days)"
              :class="[
                graphDays === days
                  ? 'bg-[#F29F67]/15 text-[#E08A50] dark:text-[#F29F67] border-[#F29F67]/30 font-bold'
                  : 'border-slate-200 dark:border-white/[0.08] text-slate-500 hover:text-slate-900 dark:hover:text-white',
                'px-2.5 py-1 rounded-md border text-xs font-mono transition-colors cursor-pointer'
              ]"
            >
              {{ days }} Days
            </button>
          </div>
        </div>

        <!-- Line Graph Canvas Surface -->
        <div class="relative rounded-lg bg-slate-50/70 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] p-3 sm:p-4 overflow-hidden">
          
          <!-- Loading Overlay -->
          <div v-if="graphLoading" class="absolute inset-0 bg-white/70 dark:bg-[#1E1E2C]/80 backdrop-blur-2xs z-20 flex items-center justify-center">
            <RefreshCw class="w-6 h-6 animate-spin text-[#F29F67]" />
          </div>

          <!-- Active Hovered Point Info Pill -->
          <div class="flex items-center justify-between text-xs pb-2 border-b border-slate-200/60 dark:border-white/[0.06] mb-2">
            <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
              {{ graphMetric === 'queries' ? 'Daily Prompt & Query Turn Trends' : 'Daily Token Consumption Trends' }}
            </span>
            <span v-if="hoveredPoint" class="font-mono text-xs font-bold text-slate-900 dark:text-white bg-white dark:bg-[#262638] px-2 py-0.5 rounded border border-slate-200 dark:border-white/[0.08] shadow-2xs">
              {{ hoveredPoint.label }}: <span :class="graphMetric === 'queries' ? 'text-[#F29F67]' : 'text-[#34B1AA]'">{{ hoveredPoint.value.toLocaleString() }} {{ graphMetric }}</span> ({{ hoveredPoint.tokens.toLocaleString() }} tok)
            </span>
            <span v-else class="text-[10px] font-mono text-slate-400">
              Hover on nodes for details
            </span>
          </div>

          <!-- Interactive SVG Line Chart -->
          <div class="w-full overflow-x-auto">
            <svg 
              viewBox="0 0 560 160" 
              class="w-full h-40 overflow-visible select-none"
            >
              <defs>
                <!-- Queries Gradient -->
                <linearGradient id="userQueriesGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#F29F67" stop-opacity="0.35" />
                  <stop offset="100%" stop-color="#F29F67" stop-opacity="0.0" />
                </linearGradient>

                <!-- Tokens Gradient -->
                <linearGradient id="userTokensGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#34B1AA" stop-opacity="0.35" />
                  <stop offset="100%" stop-color="#34B1AA" stop-opacity="0.0" />
                </linearGradient>
              </defs>

              <!-- Horizontal Gridlines -->
              <line x1="20" y1="20" x2="540" y2="20" stroke="currentColor" class="text-slate-200 dark:text-white/[0.06]" stroke-dasharray="3 3" />
              <line x1="20" y1="80" x2="540" y2="80" stroke="currentColor" class="text-slate-200 dark:text-white/[0.06]" stroke-dasharray="3 3" />
              <line x1="20" y1="140" x2="540" y2="140" stroke="currentColor" class="text-slate-200 dark:text-white/[0.08]" />

              <!-- Gradient Area Fill Under Line -->
              <path 
                v-if="areaPathD"
                :d="areaPathD" 
                :fill="graphMetric === 'queries' ? 'url(#userQueriesGradient)' : 'url(#userTokensGradient)'"
                class="transition-all duration-300"
              />

              <!-- Trend Polyline -->
              <polyline 
                v-if="polylinePoints"
                fill="none" 
                :stroke="graphMetric === 'queries' ? '#F29F67' : '#34B1AA'" 
                stroke-width="2.5" 
                stroke-linecap="round" 
                stroke-linejoin="round"
                :points="polylinePoints"
                class="transition-all duration-300"
              />

              <!-- Interactive Node Circles -->
              <g v-for="(p, idx) in chartPoints" :key="idx" class="cursor-pointer">
                <!-- Outer Hover Target -->
                <circle 
                  :cx="p.x" 
                  :cy="p.y" 
                  r="9" 
                  fill="transparent" 
                  @mouseenter="hoveredPoint = p" 
                  @mouseleave="hoveredPoint = null"
                />
                <!-- Visible Circle Node -->
                <circle 
                  :cx="p.x" 
                  :cy="p.y" 
                  :r="hoveredPoint === p ? 5.5 : 3.5" 
                  :fill="hoveredPoint === p ? '#ffffff' : (graphMetric === 'queries' ? '#F29F67' : '#34B1AA')"
                  :stroke="graphMetric === 'queries' ? '#F29F67' : '#34B1AA'"
                  stroke-width="2"
                  class="transition-all duration-150"
                  @mouseenter="hoveredPoint = p" 
                  @mouseleave="hoveredPoint = null"
                />
              </g>
            </svg>
          </div>

          <!-- X-Axis Date Labels -->
          <div class="flex justify-between items-center px-1 pt-1.5 border-t border-slate-200/60 dark:border-white/[0.06]">
            <span 
              v-for="(label, idx) in graphData.labels || []" 
              :key="idx"
              v-show="graphDays <= 14 || idx % 2 === 0 || idx === (graphData.labels.length - 1)"
              class="text-[9px] sm:text-[10px] font-mono text-slate-400 dark:text-slate-500"
            >
              {{ label }}
            </span>
          </div>

        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-end">
          <button 
            type="button" 
            @click="isGraphModalOpen = false" 
            class="px-4 py-1.5 border border-slate-200 dark:border-white/[0.08] hover:bg-slate-100 dark:hover:bg-[#1E1E2C] rounded-md text-xs font-semibold text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
          >
            Close
          </button>
        </div>

      </div>
    </Modal>

  </AdminLayout>
</template>
