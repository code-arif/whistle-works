<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import CouponKpiGrid from '../../Components/Coupons/CouponKpiGrid.vue';
import CouponTable from '../../Components/Coupons/CouponTable.vue';
import CouponModal from '../../Components/Coupons/CouponModal.vue';
import ConfirmationModal from '../../Components/Common/ConfirmationModal.vue';
import Dropdown from '../../Components/Common/Dropdown.vue';

import {
  Ticket,
  Plus,
  Search,
  Filter,
  RefreshCw,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  coupons: {
    type: Object,
    required: true,
  },
  camps: {
    type: Array,
    default: () => [],
  },
  referees: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

// Filters state
const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || 'all');
const typeFilter = ref(props.filters.type || 'all');
const campFilter = ref(props.filters.camp_id || 'all');

const statusOptions = [
  { label: 'All Statuses', value: 'all' },
  { label: 'Active Only', value: 'active' },
  { label: 'Inactive Only', value: 'inactive' },
];

const typeOptions = [
  { label: 'All Types', value: 'all' },
  { label: 'Percentage (%)', value: 'percentage' },
  { label: 'Fixed Dollar ($)', value: 'fixed' },
];

const campOptions = computed(() => [
  { label: 'All Camps', value: 'all' },
  ...props.camps.map((c) => ({ label: c.camp_name, value: c.id })),
]);

// Modals state
const isModalOpen = ref(false);
const editingCoupon = ref(null);
const isDeleteModalOpen = ref(false);
const deletingCoupon = ref(null);
const isDeleting = ref(false);

// Debounced search / filter watcher
let filterTimeout = null;
const applyFilters = () => {
  clearTimeout(filterTimeout);
  filterTimeout = setTimeout(() => {
    router.get(
      '/admin/v2/coupons',
      {
        search: searchQuery.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        camp_id: campFilter.value !== 'all' ? campFilter.value : undefined,
        page: 1,
      },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  }, 350);
};

watch([searchQuery, statusFilter, typeFilter, campFilter], () => {
  applyFilters();
});

// Open Create Modal
const openCreateModal = () => {
  editingCoupon.value = null;
  isModalOpen.value = true;
};

// Open Edit Modal
const openEditModal = (coupon) => {
  editingCoupon.value = coupon;
  isModalOpen.value = true;
};

// Open Delete Modal
const confirmDelete = (coupon) => {
  deletingCoupon.value = coupon;
  isDeleteModalOpen.value = true;
};

// Open Status Confirmation Modal
const isStatusModalOpen = ref(false);
const statusCoupon = ref(null);
const isStatusUpdating = ref(false);

const confirmStatusToggle = (coupon) => {
  statusCoupon.value = coupon;
  isStatusModalOpen.value = true;
};

// Execute Status Toggle
const handleStatusToggle = () => {
  if (!statusCoupon.value) return;

  isStatusUpdating.value = true;
  router.post(
    `/admin/v2/coupons/${statusCoupon.value.id}/status`,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        isStatusModalOpen.value = false;
        statusCoupon.value = null;
      },
      onFinish: () => {
        isStatusUpdating.value = false;
      },
    }
  );
};

// Execute Delete
const handleDelete = () => {
  if (!deletingCoupon.value) return;

  isDeleting.value = true;
  router.delete(`/admin/v2/coupons/${deletingCoupon.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteModalOpen.value = false;
      deletingCoupon.value = null;
    },
    onFinish: () => {
      isDeleting.value = false;
    },
  });
};

// Pagination Handler
const goToPage = (pageUrl) => {
  if (pageUrl) {
    router.get(pageUrl, {}, { preserveState: true, preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Discount Coupons - Executive V2" />

  <AdminLayout>
    <div class="space-y-6">
      
      <!-- Executive Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-white/[0.08]">
        <div>
          <div class="flex items-center gap-2">
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
              Discount Coupons & Campaigns
            </h1>
            <span class="px-2 py-0.5 text-[10px] font-mono font-bold uppercase tracking-wider rounded bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
              Promotions
            </span>
          </div>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Manage promotional codes, camp discounts, and referee-specific grants
          </p>
        </div>

        <!-- Add Coupon Primary Action Button -->
        <button
          @click="openCreateModal"
          type="button"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs font-bold shadow-xs transition-all cursor-pointer self-start sm:self-auto"
        >
          <Plus class="w-3.5 h-3.5 stroke-[2.5]" />
          <span>Add New Coupon</span>
        </button>
      </div>

      <!-- 1. KPI Grid -->
      <CouponKpiGrid :stats="stats" />

      <!-- 2. Coupons List Section -->
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg shadow-xs overflow-hidden">
        
        <!-- Search & Filters Header Toolbar -->
        <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-white/[0.08] flex flex-col md:flex-row md:items-center justify-between gap-3">
          
          <!-- Search Box -->
          <div class="relative w-full md:w-80">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search by code, camp, or referee..."
              class="w-full pl-9 pr-4 py-1.5 text-xs rounded-md border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#262638] text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67]"
            />
          </div>

          <!-- Dropdown Filter Controls -->
          <div class="flex flex-wrap items-center gap-2.5">
            <Dropdown
              v-model="statusFilter"
              :options="statusOptions"
              size="sm"
              placeholder="Status"
            />

            <Dropdown
              v-model="typeFilter"
              :options="typeOptions"
              size="sm"
              placeholder="Type"
            />

            <Dropdown
              v-model="campFilter"
              :options="campOptions"
              size="sm"
              placeholder="Camp"
            />
          </div>
        </div>

        <!-- Coupons Table -->
        <CouponTable
          :coupons="coupons"
          @edit="openEditModal"
          @delete="confirmDelete"
          @toggle-status="confirmStatusToggle"
        />

        <!-- Pagination Controls Footer -->
        <div 
          v-if="coupons.links && coupons.links.length > 3" 
          class="p-4 border-t border-slate-200 dark:border-white/[0.08] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400"
        >
          <div>
            Showing <span class="font-bold text-slate-900 dark:text-white">{{ coupons.from || 0 }}</span> 
            to <span class="font-bold text-slate-900 dark:text-white">{{ coupons.to || 0 }}</span> 
            of <span class="font-bold text-slate-900 dark:text-white">{{ coupons.total || 0 }}</span> coupons
          </div>

          <div class="flex items-center gap-1">
            <button
              v-for="(link, lIdx) in coupons.links"
              :key="lIdx"
              @click="goToPage(link.url)"
              :disabled="!link.url || link.active"
              v-html="link.label"
              :class="[
                'px-2.5 py-1.5 rounded text-xs font-mono font-medium transition-colors cursor-pointer',
                link.active
                  ? 'bg-[#F29F67] text-slate-950 font-bold'
                  : link.url
                    ? 'hover:bg-slate-100 dark:hover:bg-[#262638] text-slate-700 dark:text-slate-300'
                    : 'text-slate-300 dark:text-slate-600 opacity-50 cursor-not-allowed'
              ]"
            ></button>
          </div>
        </div>

      </div>

    </div>

    <!-- Create / Edit Coupon Modal -->
    <CouponModal
      :show="isModalOpen"
      :coupon="editingCoupon"
      :camps="camps"
      :referees="referees"
      @close="isModalOpen = false"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :show="isDeleteModalOpen"
      title="Delete Promotional Coupon"
      :message="`Are you sure you want to permanently delete coupon '${deletingCoupon?.code}'? Any pending checkouts with this code will no longer be eligible.`"
      confirmText="Yes, Delete Coupon"
      type="danger"
      :isLoading="isDeleting"
      @close="isDeleteModalOpen = false"
      @confirm="handleDelete"
    />

    <!-- Status Change Confirmation Modal -->
    <ConfirmationModal
      :show="isStatusModalOpen"
      :title="statusCoupon?.status === 'active' ? 'Deactivate Coupon' : 'Activate Coupon'"
      :message="`Are you sure you want to ${statusCoupon?.status === 'active' ? 'deactivate' : 'activate'} coupon '${statusCoupon?.code}'? ${statusCoupon?.status === 'active' ? 'Users will not be able to redeem this coupon at checkout while inactive.' : 'Users will be able to apply this discount coupon at checkout.'}`"
      :confirmText="statusCoupon?.status === 'active' ? 'Yes, Deactivate' : 'Yes, Activate'"
      :type="statusCoupon?.status === 'active' ? 'warning' : 'info'"
      :isLoading="isStatusUpdating"
      @close="isStatusModalOpen = false"
      @confirm="handleStatusToggle"
    />

  </AdminLayout>
</template>
