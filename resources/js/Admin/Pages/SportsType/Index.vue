<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/Common/DataTable.vue';
import Modal from '../../Components/Common/Modal.vue';
import ConfirmationModal from '../../Components/Common/ConfirmationModal.vue';
import {
  Layers,
  Plus,
  Edit2,
  Trash2,
  CheckCircle2,
  PauseCircle,
  Tent,
  Upload,
  Image as ImageIcon,
  DollarSign,
  AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  sportsTypes: {
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
      inactive: 0,
      total_camps: 0,
    }),
  },
});

// DataTable Column Configuration
const columns = [
  { key: 'icon', label: 'Icon', align: 'center', class: 'w-16' },
  { key: 'sports_name', label: 'Sport Title', sortable: true, align: 'left' },
  { key: 'sports_fee', label: 'Sports Fee', sortable: true, align: 'left' },
  { key: 'camps_count', label: 'Linked Camps', sortable: true, align: 'center' },
  { key: 'status', label: 'Status', sortable: true, align: 'center', class: 'w-28' },
  { key: 'created_at', label: 'Created On', sortable: true, align: 'left', class: 'w-32' },
  { key: 'actions', label: 'Actions', align: 'right', class: 'w-24' },
];

const statusOptions = [
  { label: 'All Sports', value: '' },
  { label: 'Active Only', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

// Modal States
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);
const editingItem = ref(null);

// Image Preview State
const imagePreview = ref(null);

// Inertia Forms
const createForm = useForm({
  sports_name: '',
  sports_fee: '',
  icon: null,
  status: 'active',
});

const editForm = useForm({
  _method: 'POST',
  sports_name: '',
  sports_fee: '',
  icon: null,
  status: 'active',
});

// Format Currency
const formatCurrency = (val) => {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(val || 0);
};

// File Input Handlers
const handleFileChange = (e, formType = 'create') => {
  const file = e.target.files[0];
  if (!file) return;

  if (formType === 'create') {
    createForm.icon = file;
  } else {
    editForm.icon = file;
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
    createForm.icon = null;
  } else {
    editForm.icon = null;
  }
};

// Modal Actions
const openCreateModal = () => {
  createForm.reset();
  createForm.clearErrors();
  imagePreview.value = null;
  isCreateModalOpen.value = true;
};

const submitCreate = () => {
  createForm.post('/admin/v2/sports-types', {
    preserveScroll: true,
    onSuccess: () => {
      isCreateModalOpen.value = false;
      createForm.reset();
      imagePreview.value = null;
    },
  });
};

const openEditModal = (item) => {
  editingItem.value = item;
  editForm.clearErrors();
  editForm.sports_name = item.sports_name;
  editForm.sports_fee = item.sports_fee;
  editForm.status = item.status;
  editForm.icon = null;
  imagePreview.value = item.icon || null;
  isEditModalOpen.value = true;
};

const submitEdit = () => {
  if (!editingItem.value) return;

  editForm.post(`/admin/v2/sports-types/${editingItem.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isEditModalOpen.value = false;
      editForm.reset();
      imagePreview.value = null;
      editingItem.value = null;
    },
  });
};

const openDeleteModal = (item) => {
  itemToDelete.value = item;
  isDeleteModalOpen.value = true;
};

const confirmDelete = () => {
  if (!itemToDelete.value) return;

  router.delete(`/admin/v2/sports-types/${itemToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteModalOpen.value = false;
      itemToDelete.value = null;
    },
  });
};

// Quick Status Toggle
const toggleStatus = (item) => {
  router.post(`/admin/v2/sports-types/${item.id}/status`, {}, {
    preserveScroll: true,
    preserveState: true,
  });
};
</script>

<template>
  <AdminLayout title="Sports Types Management">
    <Head title="Sports Types - Whistle-Works Admin" />
    
    <!-- Header Overview & KPI Bar -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4">
      
      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Total Sports</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-slate-900 dark:text-white">{{ metrics.total }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#F29F67]/10 border border-[#F29F67]/20 flex items-center justify-center text-[#F29F67]">
          <Layers class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Active Disciplines</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#2B9B95] dark:text-[#34B1AA]">{{ metrics.active }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 flex items-center justify-center text-[#34B1AA]">
          <CheckCircle2 class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Inactive</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-amber-600 dark:text-[#E0B50F]">{{ metrics.inactive }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#E0B50F]/10 border border-[#E0B50F]/20 flex items-center justify-center text-[#E0B50F]">
          <PauseCircle class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

      <div class="p-4 rounded-lg bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] shadow-xs flex items-center justify-between">
        <div class="space-y-1">
          <p class="text-[11px] font-mono text-slate-500 uppercase tracking-wider">Associated Camps</p>
          <p class="text-xl sm:text-2xl font-bold font-display text-[#3B8FF3]">{{ metrics.total_camps }}</p>
        </div>
        <div class="w-9 h-9 rounded-md bg-[#3B8FF3]/10 border border-[#34B1AA]/20 flex items-center justify-center text-[#3B8FF3]">
          <Tent class="w-4 h-4 sm:w-5 sm:h-5" />
        </div>
      </div>

    </div>

    <!-- Main Reusable DataTable Component -->
    <DataTable
      title="Sports Directory"
      subtitle="Manage sports disciplines, platform registration fees, and camp categories"
      :columns="columns"
      :rows="sportsTypes.data"
      :pagination="sportsTypes"
      :filters="filters"
      :status-options="statusOptions"
      base-url="/admin/v2/sports-types"
      search-placeholder="Search sport name..."
    >
      <!-- Action Slot: Add New Sports Type Button -->
      <template #actions>
        <button
          @click="openCreateModal"
          class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-md bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 text-xs font-bold shadow-xs transition-all cursor-pointer"
        >
          <Plus class="w-3.5 h-3.5 stroke-[2.5]" />
          <span>New Sport</span>
        </button>
      </template>

      <!-- Custom Cell: Icon -->
      <template #cell(icon)="{ row }">
        <div class="flex items-center justify-center">
          <div class="w-8 h-8 rounded-md bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] p-1 flex items-center justify-center overflow-hidden flex-shrink-0">
            <img 
              v-if="row.icon" 
              :src="row.icon" 
              :alt="row.sports_name" 
              class="w-full h-full object-contain"
            />
            <Layers v-else class="w-4 h-4 text-slate-400" />
          </div>
        </div>
      </template>

      <!-- Custom Cell: Sport Name -->
      <template #cell(sports_name)="{ row }">
        <div class="min-w-0">
          <p class="font-semibold text-slate-900 dark:text-slate-100 truncate hover:text-[#F29F67] transition-colors">
            {{ row.sports_name }}
          </p>
          <p class="text-[10px] text-slate-400 font-mono">ID: #{{ row.id }}</p>
        </div>
      </template>

      <!-- Custom Cell: Sports Fee -->
      <template #cell(sports_fee)="{ row }">
        <span class="font-mono font-medium text-slate-800 dark:text-slate-200">
          {{ formatCurrency(row.sports_fee) }}
        </span>
      </template>

      <!-- Custom Cell: Linked Camps Count -->
      <template #cell(camps_count)="{ row }">
        <span 
          :class="[
            row.camps_count > 0 
              ? 'bg-[#3B8FF3]/10 text-[#3B8FF3] border-[#3B8FF3]/30' 
              : 'bg-slate-100 dark:bg-[#1E1E2C] text-slate-500 border-slate-200 dark:border-white/[0.08]',
            'px-2 py-0.5 rounded-full text-[10px] font-mono font-semibold border inline-flex items-center justify-center min-w-[24px]'
          ]"
        >
          {{ row.camps_count }} {{ row.camps_count === 1 ? 'Camp' : 'Camps' }}
        </span>
      </template>

      <!-- Custom Cell: Status Toggle Switch -->
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

      <!-- Custom Cell: Actions -->
      <template #cell(actions)="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            @click="openEditModal(row)"
            class="p-1.5 rounded-md text-slate-500 hover:text-[#3B8FF3] hover:bg-slate-100 dark:hover:bg-[#1E1E2C] transition-colors cursor-pointer"
            title="Edit Sports Type"
          >
            <Edit2 class="w-3.5 h-3.5" />
          </button>
          <button
            @click="openDeleteModal(row)"
            class="p-1.5 rounded-md text-slate-500 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition-colors cursor-pointer"
            title="Delete Sports Type"
          >
            <Trash2 class="w-3.5 h-3.5" />
          </button>
        </div>
      </template>
    </DataTable>

    <!-- CREATE SPORTS TYPE MODAL -->
    <Modal
      :show="isCreateModalOpen"
      title="Create New Sports Type"
      max-width="md"
      @close="isCreateModalOpen = false"
    >
      <form @submit.prevent="submitCreate" class="space-y-4">
        
        <!-- Sports Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Sports Name <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="createForm.sports_name"
            type="text"
            placeholder="e.g. Basketball, Football, Baseball"
            class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            required
          />
          <p v-if="createForm.errors.sports_name" class="mt-1 text-[11px] text-rose-500 font-medium">
            {{ createForm.errors.sports_name }}
          </p>
        </div>

        <!-- Sports Fee -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Registration Fee ($)
          </label>
          <div class="relative">
            <DollarSign class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              v-model="createForm.sports_fee"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            />
          </div>
          <p v-if="createForm.errors.sports_fee" class="mt-1 text-[11px] text-rose-500 font-medium">
            {{ createForm.errors.sports_fee }}
          </p>
        </div>

        <!-- Icon Upload with Live Preview -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Sports Icon / Artwork (SVG, PNG, JPG)
          </label>
          
          <div class="flex items-center gap-3">
            <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-[#1E1E2C] border border-dashed border-slate-300 dark:border-white/[0.15] flex items-center justify-center overflow-hidden flex-shrink-0 relative group">
              <img 
                v-if="imagePreview" 
                :src="imagePreview" 
                alt="Preview" 
                class="w-full h-full object-contain p-1"
              />
              <ImageIcon v-else class="w-6 h-6 text-slate-400" />
            </div>

            <div class="flex-1">
              <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] text-xs font-medium cursor-pointer transition-colors">
                <Upload class="w-3.5 h-3.5 text-[#F29F67]" />
                <span>{{ imagePreview ? 'Replace Icon' : 'Upload Icon' }}</span>
                <input 
                  type="file" 
                  accept="image/*" 
                  @change="(e) => handleFileChange(e, 'create')" 
                  class="hidden"
                />
              </label>
              <button 
                v-if="imagePreview" 
                @click="clearImage('create')" 
                type="button" 
                class="ml-2 text-xs text-rose-500 hover:underline"
              >
                Remove
              </button>
              <p class="text-[10px] text-slate-400 mt-1">Recommended size: 64x64 or SVG format (Max 5MB)</p>
            </div>
          </div>
          <p v-if="createForm.errors.icon" class="mt-1 text-[11px] text-rose-500 font-medium">
            {{ createForm.errors.icon }}
          </p>
        </div>

        <!-- Status Radio -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Status
          </label>
          <div class="flex items-center gap-4 text-xs">
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input 
                v-model="createForm.status" 
                type="radio" 
                value="active" 
                class="text-[#F29F67] focus:ring-[#F29F67]"
              />
              <span class="text-slate-800 dark:text-slate-200">Active</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input 
                v-model="createForm.status" 
                type="radio" 
                value="inactive" 
                class="text-[#F29F67] focus:ring-[#F29F67]"
              />
              <span class="text-slate-800 dark:text-slate-200">Inactive</span>
            </label>
          </div>
        </div>

        <!-- Modal Footer Buttons -->
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
            <span>Create Sport</span>
          </button>
        </div>

      </form>
    </Modal>

    <!-- EDIT SPORTS TYPE MODAL -->
    <Modal
      :show="isEditModalOpen"
      title="Edit Sports Type"
      max-width="md"
      @close="isEditModalOpen = false"
    >
      <form @submit.prevent="submitEdit" class="space-y-4">
        
        <!-- Sports Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Sports Name <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="editForm.sports_name"
            type="text"
            class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            required
          />
          <p v-if="editForm.errors.sports_name" class="mt-1 text-[11px] text-rose-500 font-medium">
            {{ editForm.errors.sports_name }}
          </p>
        </div>

        <!-- Sports Fee -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Registration Fee ($)
          </label>
          <div class="relative">
            <DollarSign class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              v-model="editForm.sports_fee"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all"
            />
          </div>
          <p v-if="editForm.errors.sports_fee" class="mt-1 text-[11px] text-rose-500 font-medium">
            {{ editForm.errors.sports_fee }}
          </p>
        </div>

        <!-- Icon Upload with Live Preview -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Sports Icon / Artwork
          </label>
          
          <div class="flex items-center gap-3">
            <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-[#1E1E2C] border border-dashed border-slate-300 dark:border-white/[0.15] flex items-center justify-center overflow-hidden flex-shrink-0">
              <img 
                v-if="imagePreview" 
                :src="imagePreview" 
                alt="Preview" 
                class="w-full h-full object-contain p-1"
              />
              <ImageIcon v-else class="w-6 h-6 text-slate-400" />
            </div>

            <div class="flex-1">
              <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-slate-100 dark:bg-[#1E1E2C] hover:bg-slate-200 dark:hover:bg-[#32324A] text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-white/[0.08] text-xs font-medium cursor-pointer transition-colors">
                <Upload class="w-3.5 h-3.5 text-[#F29F67]" />
                <span>{{ imagePreview ? 'Replace Icon' : 'Upload New Icon' }}</span>
                <input 
                  type="file" 
                  accept="image/*" 
                  @change="(e) => handleFileChange(e, 'edit')" 
                  class="hidden"
                />
              </label>
              <p class="text-[10px] text-slate-400 mt-1">Leave empty to keep existing icon</p>
            </div>
          </div>
          <p v-if="editForm.errors.icon" class="mt-1 text-[11px] text-rose-500 font-medium">
            {{ editForm.errors.icon }}
          </p>
        </div>

        <!-- Status Radio -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
            Status
          </label>
          <div class="flex items-center gap-4 text-xs">
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input 
                v-model="editForm.status" 
                type="radio" 
                value="active" 
                class="text-[#F29F67] focus:ring-[#F29F67]"
              />
              <span class="text-slate-800 dark:text-slate-200">Active</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input 
                v-model="editForm.status" 
                type="radio" 
                value="inactive" 
                class="text-[#F29F67] focus:ring-[#F29F67]"
              />
              <span class="text-slate-800 dark:text-slate-200">Inactive</span>
            </label>
          </div>
        </div>

        <!-- Modal Footer Buttons -->
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

    <!-- DELETE CONFIRMATION MODAL -->
    <ConfirmationModal
      :show="isDeleteModalOpen"
      title="Delete Sports Type"
      :message="itemToDelete?.camps_count > 0 
        ? `Warning: '${itemToDelete?.sports_name}' has ${itemToDelete?.camps_count} associated camps and cannot be deleted until those camps are reassigned.` 
        : `Are you sure you want to delete '${itemToDelete?.sports_name}'? This action cannot be undone.`"
      confirm-text="Delete Sports Type"
      type="danger"
      @close="isDeleteModalOpen = false"
      @confirm="confirmDelete"
    />

  </AdminLayout>
</template>
