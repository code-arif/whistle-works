<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/Common/DataTable.vue';
import Modal from '../../Components/Common/Modal.vue';
import ConfirmationModal from '../../Components/Common/ConfirmationModal.vue';
import {
  Tent,
  Plus,
  Edit2,
  Trash2,
  Eye,
  CheckCircle2,
  Calendar,
  DollarSign,
  MapPin,
  User as UserIcon,
  Layers,
  Upload,
  Image as ImageIcon,
  Clock,
  ExternalLink,
  ShieldAlert
} from 'lucide-vue-next';

const props = defineProps({
  camps: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  metrics: {
    type: Object,
    default: () => ({
      total: 0,
      active: 0,
      upcoming: 0,
      avg_price: 0,
    }),
  },
  directors: {
    type: Array,
    default: () => [],
  },
  sportsTypes: {
    type: Array,
    default: () => [],
  },
});

// DataTable Columns Definition
const columns = [
  { key: 'camp_info', label: 'Camp Details', sortable: true, align: 'left' },
  { key: 'director', label: 'Assigned Director', align: 'left' },
  { key: 'sports_type', label: 'Sport', align: 'left' },
  { key: 'dates', label: 'Camp Schedule', sortable: true, align: 'left' },
  { key: 'price', label: 'Price Fee', sortable: true, align: 'left' },
  { key: 'status', label: 'Status', sortable: true, align: 'center', class: 'w-24' },
  { key: 'actions', label: 'Actions', align: 'right', class: 'w-28' },
];

const statusOptions = [
  { label: 'All Camps', value: '' },
  { label: 'Active Only', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

// Modal States
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isViewModalOpen = ref(false);
const isDeleteModalOpen = ref(false);

const viewingCamp = ref(null);
const editingCamp = ref(null);
const itemToDelete = ref(null);

// Image Preview State
const imagePreview = ref(null);

// Inertia Forms
const createForm = useForm({
  director_id: '',
  sports_type_id: '',
  camp_name: '',
  location: '',
  address: '',
  start_date: '',
  end_date: '',
  camp_details: '',
  price: '',
  camp_logo: null,
  status: 'active',
});

const editForm = useForm({
  _method: 'POST',
  director_id: '',
  sports_type_id: '',
  camp_name: '',
  location: '',
  address: '',
  start_date: '',
  end_date: '',
  camp_details: '',
  price: '',
  camp_logo: null,
  status: 'active',
});

// Helpers
const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};

// Logo Upload Handler
const handleFileChange = (e, formType = 'create') => {
  const file = e.target.files[0];
  if (!file) return;

  if (formType === 'create') {
    createForm.camp_logo = file;
  } else {
    editForm.camp_logo = file;
  }

  const reader = new FileReader();
  reader.onload = (event) => {
    imagePreview.value = event.target.result;
  };
  reader.readAsDataURL(file);
};

const clearImage = (formType = 'create') => {
  imagePreview.value = null;
  if (formType === 'create') {
    createForm.camp_logo = null;
  } else {
    editForm.camp_logo = null;
  }
};

// Create Modal
const openCreateModal = () => {
  createForm.reset();
  createForm.clearErrors();
  if (props.directors.length > 0) createForm.director_id = props.directors[0].id;
  if (props.sportsTypes.length > 0) createForm.sports_type_id = props.sportsTypes[0].id;
  imagePreview.value = null;
  isCreateModalOpen.value = true;
};

const submitCreate = () => {
  createForm.post('/admin/v2/camps', {
    preserveScroll: true,
    onSuccess: () => {
      isCreateModalOpen.value = false;
      createForm.reset();
      imagePreview.value = null;
    },
  });
};

// Edit Modal
const openEditModal = (camp) => {
  editingCamp.value = camp;
  editForm.clearErrors();
  editForm.director_id = camp.director_id;
  editForm.sports_type_id = camp.sports_type_id;
  editForm.camp_name = camp.camp_name;
  editForm.location = camp.location;
  editForm.address = camp.address || '';
  editForm.start_date = camp.start_date;
  editForm.end_date = camp.end_date;
  editForm.camp_details = camp.camp_details || '';
  editForm.price = camp.price;
  editForm.status = camp.status;
  editForm.camp_logo = null;
  imagePreview.value = camp.camp_logo || null;
  isEditModalOpen.value = true;
};

const submitEdit = () => {
  if (!editingCamp.value) return;

  editForm.post(`/admin/v2/camps/${editingCamp.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isEditModalOpen.value = false;
      editForm.reset();
      imagePreview.value = null;
      editingCamp.value = null;
    },
  });
};

// View Details Modal
const openViewModal = (camp) => {
  viewingCamp.value = camp;
  isViewModalOpen.value = true;
};

// Delete Modal
const openDeleteModal = (camp) => {
  itemToDelete.value = camp;
  isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
  if (!itemToDelete.value) return;

  router.delete(`/admin/v2/camps/${itemToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteModalOpen.value = false;
      itemToDelete.value = null;
    },
  });
};

// Toggle Status Switcher
const toggleStatus = (camp) => {
  router.post(`/admin/v2/camps/${camp.id}/status`, {}, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>

<template>
  <AdminLayout title="Camps & Programs Management">
    <Head title="Camps Management - Whistle-Works Admin" />

    <!-- 1. Header KPI Overview Bar -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
      
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Total Camps</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white">{{ metrics.total }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center justify-center text-[#F29F67]">
          <Tent class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Active Camps</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#2B9B95] dark:text-[#34B1AA]">{{ metrics.active }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 flex items-center justify-center text-[#34B1AA]">
          <CheckCircle2 class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Upcoming</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#3B8FF3]">{{ metrics.upcoming }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 flex items-center justify-center text-[#3B8FF3]">
          <Calendar class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Average Price</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-amber-600 dark:text-[#E0B50F]">{{ formatCurrency(metrics.avg_price) }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#E0B50F]/10 border border-[#E0B50F]/20 flex items-center justify-center text-[#E0B50F]">
          <DollarSign class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

    </div>

    <!-- 2. Reusable DataTable Component for Camps -->
    <DataTable
      title="Camps & Programs Directory"
      subtitle="Manage sports clinics, scheduling dates, director assignments, and registration fees"
      :columns="columns"
      :rows="camps.data"
      :pagination="camps"
      :filters="filters"
      :status-options="statusOptions"
      base-url="/admin/v2/camps"
      search-placeholder="Search by camp name, location or address..."
    >
      <!-- Action Slot: New Camp Button -->
      <template #actions>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs font-bold shadow-xs transition-all cursor-pointer"
        >
          <Plus class="w-3.5 h-3.5 stroke-[2.5]" />
          <span>New Camp</span>
        </button>
      </template>

      <!-- Custom Cell: Camp Info (Logo + Name + Location) -->
      <template #cell(camp_info)="{ row }">
        <div class="flex items-center gap-3 min-w-[200px] max-w-xs">
          <div class="w-10 h-10 rounded-md bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] p-1 flex items-center justify-center overflow-hidden flex-shrink-0">
            <img 
              v-if="row.camp_logo" 
              :src="row.camp_logo" 
              :alt="row.camp_name" 
              class="w-full h-full object-cover rounded-xs"
            />
            <Tent v-else class="w-5 h-5 text-slate-400" />
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-slate-900 dark:text-slate-100 truncate hover:text-[#F29F67] transition-colors cursor-pointer" @click="openViewModal(row)">
              {{ row.camp_name }}
            </p>
            <div class="flex items-center gap-1 text-[11px] text-slate-400 truncate">
              <MapPin class="w-3 h-3 text-slate-400 flex-shrink-0" />
              <span class="truncate">{{ row.location || 'Location not set' }}</span>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Cell: Assigned Director -->
      <template #cell(director)="{ row }">
        <div class="flex items-center gap-2.5 min-w-[160px]">
          <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold text-[10px] overflow-hidden flex-shrink-0">
            <img 
              v-if="row.director_avatar" 
              :src="row.director_avatar" 
              :alt="row.director_name" 
              class="w-full h-full object-cover"
            />
            <span v-else>{{ row.director_name ? row.director_name.charAt(0).toUpperCase() : 'D' }}</span>
          </div>
          <div class="min-w-0">
            <p class="font-medium text-slate-900 dark:text-slate-200 truncate">{{ row.director_name }}</p>
            <p class="text-[10px] text-slate-400 truncate">{{ row.director_email }}</p>
          </div>
        </div>
      </template>

      <!-- Custom Cell: Sport -->
      <template #cell(sports_type)="{ row }">
        <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-700 dark:text-slate-300 text-[11px] font-medium">
          <img v-if="row.sports_type_icon" :src="row.sports_type_icon" alt="" class="w-3.5 h-3.5 object-contain" />
          <Layers v-else class="w-3 h-3 text-slate-400" />
          <span>{{ row.sports_type_name }}</span>
        </div>
      </template>

      <!-- Custom Cell: Camp Schedule -->
      <template #cell(dates)="{ row }">
        <div class="min-w-[140px] text-xs">
          <p class="font-medium text-slate-800 dark:text-slate-200 font-mono text-[11px]">
            {{ row.formatted_start }} &rarr; {{ row.formatted_end }}
          </p>
          <span class="inline-flex items-center gap-1 text-[10px] font-mono text-slate-400">
            <Clock class="w-2.5 h-2.5" />
            <span>{{ row.duration_days }} {{ row.duration_days === 1 ? 'day' : 'days' }}</span>
          </span>
        </div>
      </template>

      <!-- Custom Cell: Price Fee -->
      <template #cell(price)="{ row }">
        <span class="font-mono font-bold text-[#2B9B95] dark:text-[#34B1AA]">
          {{ formatCurrency(row.price) }}
        </span>
      </template>

      <!-- Custom Cell: Status Switcher -->
      <template #cell(status)="{ row }">
        <div class="flex items-center justify-center">
          <button
            @click="toggleStatus(row)"
            :title="`Click to switch to ${row.status === 'active' ? 'inactive' : 'active'}`"
            :class="[
              row.status === 'active' ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none'
            ]"
          >
            <span
              :class="[
                row.status === 'active' ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>
      </template>

      <!-- Custom Cell: Action Buttons -->
      <template #cell(actions)="{ row }">
        <div class="flex items-center justify-end gap-1.5">
          <button
            @click="openViewModal(row)"
            class="p-1.5 rounded-md text-[#2B9B95] dark:text-[#34B1AA] bg-[#34B1AA]/10 hover:bg-[#34B1AA]/20 border border-[#34B1AA]/30 dark:border-[#34B1AA]/30 transition-all duration-150 cursor-pointer shadow-2xs"
            title="View Camp Details"
          >
            <Eye class="w-3.5 h-3.5" />
          </button>
          <button
            @click="openEditModal(row)"
            class="p-1.5 rounded-md text-[#3B8FF3] bg-[#3B8FF3]/10 hover:bg-[#3B8FF3]/20 border border-[#3B8FF3]/30 dark:border-[#3B8FF3]/30 transition-all duration-150 cursor-pointer shadow-2xs"
            title="Edit Camp"
          >
            <Edit2 class="w-3.5 h-3.5" />
          </button>
          <button
            @click="openDeleteModal(row)"
            class="p-1.5 rounded-md text-rose-600 dark:text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/30 dark:border-rose-500/30 transition-all duration-150 cursor-pointer shadow-2xs"
            title="Delete Camp"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </template>
    </DataTable>

    <!-- CREATE CAMP MODAL -->
    <Modal
      :show="isCreateModalOpen"
      title="Create New Camp / Clinic"
      max-width="xl"
      @close="isCreateModalOpen = false"
    >
      <form @submit.prevent="submitCreate" class="space-y-4">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <!-- Camp Name -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Camp Title / Name <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="createForm.camp_name"
              type="text"
              placeholder="e.g. Elite Basketball Summer Camp 2026"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
            <p v-if="createForm.errors.camp_name" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.camp_name }}
            </p>
          </div>

          <!-- Director Selector -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Assign Director <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="createForm.director_id"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all cursor-pointer"
              required
            >
              <option value="" disabled>Select a director</option>
              <option v-for="dir in directors" :key="dir.id" :value="dir.id">
                {{ dir.name }} ({{ dir.email }})
              </option>
            </select>
            <p v-if="createForm.errors.director_id" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.director_id }}
            </p>
          </div>

          <!-- Sports Type Selector -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Sport Type <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="createForm.sports_type_id"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all cursor-pointer"
              required
            >
              <option value="" disabled>Select a sport</option>
              <option v-for="st in sportsTypes" :key="st.id" :value="st.id">
                {{ st.name }} (+${{ st.sports_fee }} fee)
              </option>
            </select>
            <p v-if="createForm.errors.sports_type_id" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.sports_type_id }}
            </p>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Start Date <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="createForm.start_date"
              type="date"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
            <p v-if="createForm.errors.start_date" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.start_date }}
            </p>
          </div>

          <!-- End Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              End Date <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="createForm.end_date"
              type="date"
              :min="createForm.start_date"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
            <p v-if="createForm.errors.end_date" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.end_date }}
            </p>
          </div>

          <!-- Location (City, State) -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Location City/State <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="createForm.location"
              type="text"
              placeholder="e.g. Austin, TX"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
            <p v-if="createForm.errors.location" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.location }}
            </p>
          </div>

          <!-- Price -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Base Price ($) <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <DollarSign class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                v-model="createForm.price"
                type="number"
                step="0.01"
                min="0"
                placeholder="150.00"
                class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
                required
              />
            </div>
            <p v-if="createForm.errors.price" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.price }}
            </p>
          </div>

          <!-- Address -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Venue Full Address
            </label>
            <input
              v-model="createForm.address"
              type="text"
              placeholder="e.g. 100 Main St, Sports Complex Court #2"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            />
          </div>

          <!-- Camp Details -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Camp Description & Guidelines
            </label>
            <textarea
              v-model="createForm.camp_details"
              rows="3"
              placeholder="Provide information regarding camp requirements, referee attire, evaluation formats..."
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            ></textarea>
          </div>

          <!-- Camp Logo Upload -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Camp Brand Logo (PNG, JPG, WebP)
            </label>
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-[#1E1E2C] border border-dashed border-slate-300 dark:border-white/[0.15] flex items-center justify-center overflow-hidden flex-shrink-0">
                <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
                <ImageIcon v-else class="w-6 h-6 text-slate-400" />
              </div>
              <div>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] text-xs font-medium cursor-pointer transition-colors">
                  <Upload class="w-3.5 h-3.5 text-[#F29F67]" />
                  <span>Upload Logo</span>
                  <input type="file" accept="image/*" @change="(e) => handleFileChange(e, 'create')" class="hidden" />
                </label>
                <button v-if="imagePreview" @click="clearImage('create')" type="button" class="ml-2 text-xs text-rose-500 hover:underline">
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-end gap-2.5">
          <button
            @click="isCreateModalOpen = false"
            type="button"
            class="px-3.5 py-2 text-xs font-medium rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="createForm.processing"
            class="px-4 py-2 text-xs font-bold rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 shadow-xs transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
          >
            <span v-if="createForm.processing" class="w-3.5 h-3.5 border-2 border-slate-950/30 border-t-slate-950 rounded-full animate-spin"></span>
            <span>Create Camp</span>
          </button>
        </div>

      </form>
    </Modal>

    <!-- EDIT CAMP MODAL -->
    <Modal
      :show="isEditModalOpen"
      title="Edit Camp / Clinic"
      max-width="xl"
      @close="isEditModalOpen = false"
    >
      <form @submit.prevent="submitEdit" class="space-y-4">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <!-- Camp Name -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Camp Title / Name <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="editForm.camp_name"
              type="text"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
            <p v-if="editForm.errors.camp_name" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.camp_name }}
            </p>
          </div>

          <!-- Director Selector -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Assign Director <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="editForm.director_id"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all cursor-pointer"
              required
            >
              <option v-for="dir in directors" :key="dir.id" :value="dir.id">
                {{ dir.name }} ({{ dir.email }})
              </option>
            </select>
          </div>

          <!-- Sports Type Selector -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Sport Type <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="editForm.sports_type_id"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all cursor-pointer"
              required
            >
              <option v-for="st in sportsTypes" :key="st.id" :value="st.id">
                {{ st.name }} (+${{ st.sports_fee }} fee)
              </option>
            </select>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Start Date <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="editForm.start_date"
              type="date"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
          </div>

          <!-- End Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              End Date <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="editForm.end_date"
              type="date"
              :min="editForm.start_date"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
          </div>

          <!-- Location -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Location City/State <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="editForm.location"
              type="text"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
              required
            />
          </div>

          <!-- Price -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Price ($) <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <DollarSign class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                v-model="editForm.price"
                type="number"
                step="0.01"
                min="0"
                class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
                required
              />
            </div>
          </div>

          <!-- Address -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Venue Full Address
            </label>
            <input
              v-model="editForm.address"
              type="text"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            />
          </div>

          <!-- Camp Details -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Camp Description & Guidelines
            </label>
            <textarea
              v-model="editForm.camp_details"
              rows="3"
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            ></textarea>
          </div>

          <!-- Camp Logo Upload -->
          <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Camp Brand Logo
            </label>
            <div class="flex items-center gap-3">
              <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-[#1E1E2C] border border-dashed border-slate-300 dark:border-white/[0.15] flex items-center justify-center overflow-hidden flex-shrink-0">
                <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="w-full h-full object-cover" />
                <ImageIcon v-else class="w-6 h-6 text-slate-400" />
              </div>
              <div>
                <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] text-xs font-medium cursor-pointer transition-colors">
                  <Upload class="w-3.5 h-3.5 text-[#F29F67]" />
                  <span>{{ imagePreview ? 'Replace Logo' : 'Upload Logo' }}</span>
                  <input type="file" accept="image/*" @change="(e) => handleFileChange(e, 'edit')" class="hidden" />
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-100 dark:border-white/[0.08] flex items-center justify-end gap-2.5">
          <button
            @click="isEditModalOpen = false"
            type="button"
            class="px-3.5 py-2 text-xs font-medium rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="editForm.processing"
            class="px-4 py-2 text-xs font-bold rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 shadow-xs transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-50"
          >
            <span v-if="editForm.processing" class="w-3.5 h-3.5 border-2 border-slate-950/30 border-t-slate-950 rounded-full animate-spin"></span>
            <span>Save Changes</span>
          </button>
        </div>

      </form>
    </Modal>

    <!-- VIEW CAMP DETAILS MODAL -->
    <Modal
      :show="isViewModalOpen"
      title="Camp Overview & Details"
      max-width="lg"
      @close="isViewModalOpen = false"
    >
      <div v-if="viewingCamp" class="space-y-4">
        
        <!-- Header Banner -->
        <div class="flex items-start gap-3.5 p-3.5 rounded-lg bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
          <div class="w-14 h-14 rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] p-1 flex items-center justify-center overflow-hidden flex-shrink-0">
            <img v-if="viewingCamp.camp_logo" :src="viewingCamp.camp_logo" alt="" class="w-full h-full object-cover rounded-xs" />
            <Tent v-else class="w-6 h-6 text-[#F29F67]" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white truncate">{{ viewingCamp.camp_name }}</h4>
              <span 
                :class="[
                  viewingCamp.status === 'active' ? 'bg-[#34B1AA]/10 text-[#2B9B95] dark:text-[#34B1AA] border-[#34B1AA]/30' : 'bg-slate-100 dark:bg-[#262638] text-slate-500 border-slate-200 dark:border-white/[0.08]',
                  'px-2 py-0.5 rounded text-[10px] font-mono border uppercase font-semibold flex-shrink-0'
                ]"
              >
                {{ viewingCamp.status }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 flex items-center gap-1">
              <MapPin class="w-3 h-3 text-[#F29F67]" />
              <span>{{ viewingCamp.location }} &bull; {{ viewingCamp.address || 'Address not specified' }}</span>
            </p>
          </div>
        </div>

        <!-- Grid of Key Specs -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 text-xs">
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Sport</p>
            <p class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.sports_type_name }}</p>
          </div>
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Duration</p>
            <p class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.duration_days }} Days</p>
          </div>
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Registration Fee</p>
            <p class="font-bold font-mono text-[#2B9B95] dark:text-[#34B1AA] mt-0.5">{{ formatCurrency(viewingCamp.price) }}</p>
          </div>
        </div>

        <!-- Schedule Dates -->
        <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-between text-xs">
          <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
            <Calendar class="w-4 h-4 text-[#3B8FF3]" />
            <span>Dates: <strong class="font-mono">{{ viewingCamp.formatted_start }}</strong> to <strong class="font-mono">{{ viewingCamp.formatted_end }}</strong></span>
          </div>
        </div>

        <!-- Assigned Director -->
        <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center font-bold text-xs">
            <img v-if="viewingCamp.director_avatar" :src="viewingCamp.director_avatar" class="w-full h-full object-cover rounded-full" />
            <span v-else>{{ viewingCamp.director_name ? viewingCamp.director_name.charAt(0) : 'D' }}</span>
          </div>
          <div class="min-w-0 flex-1 text-xs">
            <p class="font-semibold text-slate-900 dark:text-white truncate">{{ viewingCamp.director_name }} (Camp Director)</p>
            <p class="text-slate-500 dark:text-slate-400 truncate text-[11px]">{{ viewingCamp.director_email }}</p>
          </div>
        </div>

        <!-- Description -->
        <div v-if="viewingCamp.camp_details" class="space-y-1">
          <p class="text-[11px] font-mono text-slate-400 uppercase">Camp Description</p>
          <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-xs text-slate-700 dark:text-slate-300 leading-relaxed max-h-36 overflow-y-auto">
            {{ viewingCamp.camp_details }}
          </div>
        </div>

      </div>

      <template #footer>
        <button
          @click="isViewModalOpen = false"
          type="button"
          class="px-4 py-2 text-xs font-medium rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] transition-colors cursor-pointer"
        >
          Close
        </button>
      </template>
    </Modal>

    <!-- DELETE CONFIRMATION MODAL -->
    <ConfirmationModal
      :show="isDeleteModalOpen"
      title="Delete Camp Program"
      :message="`Are you sure you want to permanently delete '${itemToDelete?.camp_name}'? This will remove all associated camp records.`"
      confirm-text="Delete Camp"
      type="danger"
      @close="isDeleteModalOpen = false"
      @confirm="confirmDelete"
    />

  </AdminLayout>
</template>
