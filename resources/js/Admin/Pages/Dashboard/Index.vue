<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import ExecutiveHero from '@/Admin/Components/Dashboard/ExecutiveHero.vue';
import FinancialKpiGrid from '@/Admin/Components/Dashboard/FinancialKpiGrid.vue';
import RevenueAnalyticsChart from '@/Admin/Components/Dashboard/RevenueAnalyticsChart.vue';
import CommunityRoleDistribution from '@/Admin/Components/Dashboard/CommunityRoleDistribution.vue';
import TopCampsTable from '@/Admin/Components/Dashboard/TopCampsTable.vue';
import RecentPaymentsFeed from '@/Admin/Components/Dashboard/RecentPaymentsFeed.vue';
import SystemHealthBar from '@/Admin/Components/Dashboard/SystemHealthBar.vue';

defineProps({
  userStats: { type: Object, default: () => ({}) },
  revenueStats: { type: Object, default: () => ({}) },
  campStats: { type: Object, default: () => ({}) },
  gameSlotStats: { type: Object, default: () => ({}) },
  paymentStats: { type: Object, default: () => ({}) },
  monthlyRevenue: { type: Object, default: () => ({ labels: [], revenue: [], fees: [], transactions: [] }) },
  userGrowth: { type: Object, default: () => ({ labels: [], data: [] }) },
  topCamps: { type: Array, default: () => [] },
  recentPayments: { type: Array, default: () => [] },
  dailyActivities: { type: Array, default: () => [] },
  systemInfo: { type: Object, default: () => ({}) },
  error: { type: String, default: null },
});
</script>

<template>
  <AdminLayout title="Executive Overview">
    <Head title="Executive Dashboard - Whistle-Works" />

    <!-- Error Alert (If Any) -->
    <div v-if="error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
      {{ error }}
    </div>

    <!-- 1. Executive BI Hero Banner -->
    <ExecutiveHero 
      :user-total="userStats.total"
      :payment-success-rate="paymentStats.rate ?? 100"
    />

    <!-- 2. Financial & Operational KPI Cards Grid -->
    <FinancialKpiGrid 
      :revenue-stats="revenueStats"
      :user-stats="userStats"
      :camp-stats="campStats"
      :game-slot-stats="gameSlotStats"
    />

    <!-- 3. Revenue Analytics Chart & Role Split -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <RevenueAnalyticsChart 
          :monthly-revenue="monthlyRevenue"
          :total-revenue="revenueStats.totalRevenue"
        />
      </div>
      <div>
        <CommunityRoleDistribution 
          :user-stats="userStats"
        />
      </div>
    </div>

    <!-- 4. Top Camps & Recent Payments Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2">
        <TopCampsTable 
          :top-camps="topCamps"
        />
      </div>
      <div>
        <RecentPaymentsFeed 
          :recent-payments="recentPayments"
        />
      </div>
    </div>

    <!-- 5. System Diagnostics & Cache Bar -->
    <SystemHealthBar 
      :system-info="systemInfo"
    />

  </AdminLayout>
</template>
