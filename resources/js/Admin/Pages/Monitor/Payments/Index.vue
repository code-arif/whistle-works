<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import PaymentKpiGrid from '../../../Components/Monitor/Payments/PaymentKpiGrid.vue';
import RevenueTrendChart from '../../../Components/Monitor/Payments/RevenueTrendChart.vue';
import QuickSummaryCard from '../../../Components/Monitor/Payments/QuickSummaryCard.vue';
import PaymentsDataTable from '../../../Components/Monitor/Payments/PaymentsDataTable.vue';
import AttemptsDataTable from '../../../Components/Monitor/Payments/AttemptsDataTable.vue';
import RegistrationsDataTable from '../../../Components/Monitor/Payments/RegistrationsDataTable.vue';
import CouponsDataTable from '../../../Components/Monitor/Payments/CouponsDataTable.vue';

import {
  CreditCard,
  RotateCw,
  Search,
  Filter,
  ListFilter,
  ChevronLeft,
  ChevronRight,
  TrendingUp,
  Sparkles
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  revenueChart: {
    type: Object,
    required: true,
  },
  topCoupons: {
    type: Array,
    default: () => [],
  },
  activeTab: {
    type: String,
    default: 'payments',
  },
  tableData: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const searchQuery = ref(props.filters.search || '');
const isRefreshing = ref(false);
const chartLoading = ref(false);

// Debounced search watcher
let searchTimeout = null;
watch(searchQuery, (newVal) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    router.get(
      '/admin/v2/monitor/payments',
      {
        ...props.filters,
        search: newVal,
        page: 1,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  }, 400);
});

// Switch active table tab
const switchTab = (tabName) => {
  router.get(
    '/admin/v2/monitor/payments',
    {
      ...props.filters,
      tab: tabName,
      page: 1,
    },
    {
      preserveState: true,
      preserveScroll: true,
    }
  );
};

// Update chart months period (6M, 12M, 24M)
const onUpdateMonths = (monthsVal) => {
  chartLoading.value = true;
  router.get(
    '/admin/v2/monitor/payments',
    {
      ...props.filters,
      months: monthsVal,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => {
        chartLoading.value = false;
      }
    }
  );
};

// Refresh stats cache burst
const handleRefresh = () => {
  isRefreshing.value = true;
  router.post(
    '/admin/v2/monitor/payments/refresh',
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        isRefreshing.value = false;
      },
    }
  );
};

// Handle pagination page click
const goToPage = (pageUrl) => {
  if (pageUrl) {
    router.get(pageUrl, {}, { preserveState: true, preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Payment Monitor - Executive V2" />

  <AdminLayout>
    <div class="space-y-6">

      <!-- Executive Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-white/[0.08]">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
              Payment Monitor & Analytics
            </h1>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Real-time financial performance, stripe transactions, referee attendance & coupon redemptions
          </p>
        </div>

        <!-- Refresh & Quick Actions -->
        <div class="flex items-center gap-3 self-start sm:self-auto">
          <button
            @click="handleRefresh"
            :disabled="isRefreshing"
            class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold rounded-md bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-[#262638] transition-all shadow-2xs disabled:opacity-50 cursor-pointer"
          >
            <RotateCw class="w-3.5 h-3.5 text-[#F29F67]" :class="{ 'animate-spin': isRefreshing }" />
            <span>{{ isRefreshing ? 'Refreshing...' : 'Refresh Stats' }}</span>
          </button>
        </div>
      </div>

      <!-- 1. Executive KPI Cards Row -->
      <PaymentKpiGrid :stats="stats" />

      <!-- 2. Interactive Analytics Chart + System Pulse Summary Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5">
        <div class="lg:col-span-2">
          <RevenueTrendChart
            :chartData="revenueChart"
            :loading="chartLoading"
            @update-months="onUpdateMonths"
          />
        </div>
        <div class="lg:col-span-1">
          <QuickSummaryCard :stats="stats" :topCoupons="topCoupons" />
        </div>
      </div>

      <!-- 3. Detailed Audit Logs Data Tables Section -->
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg shadow-xs overflow-hidden">
        
        <!-- Table Header & Controls -->
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-white/[0.08] flex flex-col md:flex-row md:items-center justify-between gap-4">
          
          <!-- Tab Buttons -->
          <div class="inline-flex p-1 rounded-md bg-slate-100 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.06] self-start">
            <button
              v-for="t in [
                { id: 'payments', label: 'Successful Payments' },
                { id: 'attempts', label: 'Payment Attempts' },
                { id: 'registrations', label: 'Referee Check-ins' },
                { id: 'coupons', label: 'Coupons Log' }
              ]"
              :key="t.id"
              @click="switchTab(t.id)"
              :class="[
                'px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all duration-150 cursor-pointer',
                activeTab === t.id
                  ? 'bg-white dark:bg-[#1E1E2C] text-slate-900 dark:text-white shadow-2xs font-bold'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
              ]"
            >
              {{ t.label }}
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full md:w-72">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by camp, user, coupon..."
              class="w-full pl-9 pr-4 py-1.5 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#262638] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
            />
          </div>
        </div>

        <!-- Active Data Table Render -->
        <div class="p-1 sm:p-2">
          <PaymentsDataTable v-if="activeTab === 'payments'" :payments="tableData" />
          <AttemptsDataTable v-else-if="activeTab === 'attempts'" :attempts="tableData" />
          <RegistrationsDataTable v-else-if="activeTab === 'registrations'" :registrations="tableData" />
          <CouponsDataTable v-else-if="activeTab === 'coupons'" :coupons="tableData" />
        </div>

        <!-- Pagination Controls Footer -->
        <div 
          v-if="tableData.links && tableData.links.length > 3" 
          class="p-4 border-t border-slate-200 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400"
        >
          <div>
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ tableData.from || 0 }}</span> 
            to <span class="font-bold text-slate-900 dark:text-white">{{ tableData.to || 0 }}</span> 
            of <span class="font-bold text-slate-900 dark:text-white">{{ tableData.total || 0 }}</span> entries
          </div>

          <div class="flex items-center gap-1">
            <button
              v-for="(link, lIdx) in tableData.links"
              :key="lIdx"
              @click="goToPage(link.url)"
              :disabled="!link.url || link.active"
              v-html="link.label"
              :class="[
                'px-2.5 py-1.5 rounded text-xs font-mono font-medium transition-colors cursor-pointer',
                link.active
                  ? 'bg-emerald-500 text-white font-bold'
                  : link.url
                    ? 'hover:bg-slate-100 dark:hover:bg-[#262638] text-slate-700 dark:text-slate-300'
                    : 'text-slate-300 dark:text-slate-600 opacity-50 cursor-not-allowed'
              ]"
            ></button>
          </div>
        </div>

      </div>

    </div>
  </AdminLayout>
</template>
