<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import DataTable from '../../Components/Common/DataTable.vue';
import Breadcrumb from '../../Components/Common/Breadcrumb.vue';
import Modal from '../../Components/Common/Modal.vue';
import ConfirmationModal from '../../Components/Common/ConfirmationModal.vue';
import Dropdown from '../../Components/Common/Dropdown.vue';
import DatePicker from '../../Components/Common/DatePicker.vue';
import CampLocationPicker from '../../Components/Camps/CampLocationPicker.vue';
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
  ShieldAlert,
  Globe,
  Crosshair,
  Copy,
  Users,
  Check,
  X,
  Shield,
  Award
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
  timezones: {
    type: Array,
    default: () => [],
  },
  coordinateMappings: {
    type: Array,
    default: () => [],
  },
  googleMapsApiKey: {
    type: String,
    default: '',
  },
});

// DataTable Columns Definition
const columns = [
  { key: 'camp_info', label: 'Camp Details', sortable: true, align: 'left' },
  { key: 'director', label: 'Assigned Director', align: 'left' },
  { key: 'sports_type', label: 'Sport', align: 'left' },
  { key: 'dates', label: 'Schedule & Timezone', sortable: true, align: 'left' },
  { key: 'price', label: 'Pricing Breakdown', sortable: true, align: 'left' },
  { key: 'operations', label: 'Roster & Operations', align: 'center' },
  { key: 'status', label: 'Status', sortable: true, align: 'center', class: 'w-24' },
  { key: 'actions', label: 'Actions', align: 'right', class: 'w-36' },
];

const statusOptions = [
  { label: 'All Camps', value: '' },
  { label: 'Active Only', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

const directorOptions = computed(() => {
  return props.directors.map((dir) => ({
    label: `${dir.name} (${dir.email})`,
    value: dir.id,
  }));
});

const sportsTypeOptions = computed(() => {
  return props.sportsTypes.map((st) => ({
    label: `${st.name} (+$${st.sports_fee} fee)`,
    value: st.id,
  }));
});

const timezoneOptions = computed(() => {
  return (props.timezones || []).map((tz) => ({
    label: tz.label || tz.name || tz.value,
    value: tz.value,
  }));
});

// Timezone Label Resolver
const getTimezoneLabel = (tzValue) => {
  if (!tzValue) return 'Not Set';
  const found = props.timezones.find((t) => t.value === tzValue);
  return found ? (found.name || found.label) : tzValue;
};

// Sports fee calculator for live price previews
const getSelectedSportFee = (sportsTypeId) => {
  const sport = props.sportsTypes.find((st) => String(st.id) === String(sportsTypeId));
  return sport ? Number(sport.sports_fee || 0) : 0;
};

// Modal States
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const isViewModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isDuplicateModalOpen = ref(false);

const viewingCamp = ref(null);
const editingCamp = ref(null);
const itemToDelete = ref(null);
const itemToDuplicate = ref(null);

const createPickerRef = ref(null);
const editPickerRef = ref(null);

// Image Preview State
const imagePreview = ref(null);

// Inertia Forms
const createForm = useForm({
  director_id: '',
  sports_type_id: '',
  camp_name: '',
  location: '',
  address: '',
  latitude: null,
  longitude: null,
  timezone: 'America/New_York',
  start_date: '',
  end_date: '',
  camp_details: '',
  price: '',
  camp_logo: null,
  status: 'active',
  publish_ranking_for_evaluators: true,
  hide_evaluator_name_from_referees: false,
  hide_ranking_numbers_from_referees: false,
  publish_ranking_for_referees: false,
});

const editForm = useForm({
  _method: 'POST',
  director_id: '',
  sports_type_id: '',
  camp_name: '',
  location: '',
  address: '',
  latitude: null,
  longitude: null,
  timezone: 'America/New_York',
  start_date: '',
  end_date: '',
  camp_details: '',
  price: '',
  camp_logo: null,
  status: 'active',
  publish_ranking_for_evaluators: true,
  hide_evaluator_name_from_referees: false,
  hide_ranking_numbers_from_referees: false,
  publish_ranking_for_referees: false,
});

// Reactive total prices
const createTotalPrice = computed(() => {
  const base = parseFloat(createForm.price) || 0;
  const fee = getSelectedSportFee(createForm.sports_type_id);
  return base + fee;
});

const editTotalPrice = computed(() => {
  const base = parseFloat(editForm.price) || 0;
  const fee = getSelectedSportFee(editForm.sports_type_id);
  return base + fee;
});

// Coordinate Timezone Auto-detector
const detectTimezoneFromCoords = (lat, lng) => {
  if (!lat || !lng) return null;
  const numLat = parseFloat(lat);
  const numLng = parseFloat(lng);
  for (const region of props.coordinateMappings || []) {
    if (
      numLat >= region.lat_min &&
      numLat <= region.lat_max &&
      numLng >= region.lng_min &&
      numLng <= region.lng_max
    ) {
      return region.timezone;
    }
  }
  return null;
};

const autoDetectCreateTimezone = () => {
  const detected = detectTimezoneFromCoords(createForm.latitude, createForm.longitude);
  if (detected) {
    createForm.timezone = detected;
  }
};

const autoDetectEditTimezone = () => {
  const detected = detectTimezoneFromCoords(editForm.latitude, editForm.longitude);
  if (detected) {
    editForm.timezone = detected;
  }
};

// Auto-detect timezone when coordinates first set in create form
watch(
  () => [createForm.latitude, createForm.longitude],
  ([lat, lng]) => {
    if (lat && lng && (!createForm.timezone || createForm.timezone === 'America/New_York')) {
      const detected = detectTimezoneFromCoords(lat, lng);
      if (detected) createForm.timezone = detected;
    }
  }
);

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
  createForm.latitude = null;
  createForm.longitude = null;
  createForm.timezone = props.timezones?.[0]?.value || 'America/New_York';
  createForm.publish_ranking_for_evaluators = true;
  createForm.hide_evaluator_name_from_referees = false;
  createForm.hide_ranking_numbers_from_referees = false;
  createForm.publish_ranking_for_referees = false;
  if (props.directors.length > 0) createForm.director_id = props.directors[0].id;
  if (props.sportsTypes.length > 0) createForm.sports_type_id = props.sportsTypes[0].id;
  imagePreview.value = null;
  isCreateModalOpen.value = true;
  nextTick(() => {
    createPickerRef.value?.refreshMap();
  });
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
  editForm.latitude = camp.latitude || null;
  editForm.longitude = camp.longitude || null;
  editForm.timezone = camp.timezone || 'America/New_York';
  editForm.start_date = camp.start_date;
  editForm.end_date = camp.end_date;
  editForm.camp_details = camp.camp_details || '';
  // Populate with base price so the editor modifies base price consistently
  editForm.price = camp.base_price !== undefined ? camp.base_price : camp.price;
  editForm.status = camp.status;
  editForm.publish_ranking_for_evaluators = camp.publish_ranking_for_evaluators !== undefined ? camp.publish_ranking_for_evaluators : true;
  editForm.hide_evaluator_name_from_referees = !!camp.hide_evaluator_name_from_referees;
  editForm.hide_ranking_numbers_from_referees = !!camp.hide_ranking_numbers_from_referees;
  editForm.publish_ranking_for_referees = !!camp.publish_ranking_for_referees;
  editForm.camp_logo = null;
  imagePreview.value = camp.camp_logo || null;
  isEditModalOpen.value = true;
  nextTick(() => {
    editPickerRef.value?.refreshMap();
  });
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

// Duplicate Modal
const openDuplicateModal = (camp) => {
  itemToDuplicate.value = camp;
  isDuplicateModalOpen.value = true;
};

const confirmDuplicate = () => {
  if (!itemToDuplicate.value) return;

  router.post(`/admin/v2/camps/${itemToDuplicate.value.id}/duplicate`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      isDuplicateModalOpen.value = false;
      itemToDuplicate.value = null;
    },
  });
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

    <Breadcrumb :items="[{ label: 'Management' }, { label: 'Camps' }]" />

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
              <a
                v-if="row.latitude && row.longitude"
                :href="`https://www.google.com/maps?q=${row.latitude},${row.longitude}`"
                target="_blank"
                rel="noopener noreferrer"
                class="text-slate-400 hover:text-[#3B8FF3] ml-0.5 inline-flex items-center transition-colors"
                title="View on Google Maps"
                @click.stop
              >
                <ExternalLink class="w-2.5 h-2.5" />
              </a>
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

      <!-- Custom Cell: Camp Schedule & Timezone -->
      <template #cell(dates)="{ row }">
        <div class="min-w-[145px] text-xs space-y-1">
          <p class="font-medium text-slate-800 dark:text-slate-200 font-mono text-[11px]">
            {{ row.formatted_start }} &rarr; {{ row.formatted_end }}
          </p>
          <div class="flex items-center gap-1.5 flex-wrap">
            <span class="inline-flex items-center gap-1 text-[10px] font-mono text-slate-400">
              <Clock class="w-2.5 h-2.5" />
              <span>{{ row.duration_days }} {{ row.duration_days === 1 ? 'day' : 'days' }}</span>
            </span>
            <span
              v-if="row.timezone"
              class="inline-flex items-center gap-1 px-1.5 py-0.2 rounded text-[10px] font-mono bg-[#3B8FF3]/10 text-[#3B8FF3] border border-[#3B8FF3]/20"
              :title="`${row.timezone_display_name} (${row.timezone})`"
            >
              <Globe class="w-2.5 h-2.5" />
              <span>{{ row.timezone_offset }}</span>
            </span>
          </div>
        </div>
      </template>

      <!-- Custom Cell: Price Fee -->
      <template #cell(price)="{ row }">
        <div class="min-w-[110px] text-xs">
          <p class="font-mono font-bold text-[#2B9B95] dark:text-[#34B1AA]">
            {{ formatCurrency(row.total_price || row.price) }}
          </p>
          <p class="text-[10px] font-mono text-slate-400">
            Base: {{ formatCurrency(row.base_price) }} + Fee: {{ formatCurrency(row.sports_fee) }}
          </p>
        </div>
      </template>

      <!-- Custom Cell: Roster & Operations -->
      <template #cell(operations)="{ row }">
        <div class="flex items-center justify-center gap-1.5 flex-wrap text-xs">
          <span
            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-700 dark:text-slate-300 font-mono text-[10px]"
            :title="`${row.checked_in_referees_count} Checked-in / Registered Referees`"
          >
            <Users class="w-2.5 h-2.5 text-[#3B8FF3]" />
            <span>{{ row.checked_in_referees_count }} Refs</span>
          </span>
          <span
            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-100 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-700 dark:text-slate-300 font-mono text-[10px]"
            :title="`${row.evaluator_registrations_count} Assigned Evaluators`"
          >
            <Award class="w-2.5 h-2.5 text-[#F29F67]" />
            <span>{{ row.evaluator_registrations_count }} Evals</span>
          </span>
        </div>
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
            @click="openDuplicateModal(row)"
            class="p-1.5 rounded-md text-amber-600 dark:text-amber-400 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition-all duration-150 cursor-pointer shadow-2xs"
            title="Duplicate Camp"
          >
            <Copy class="w-3.5 h-3.5" />
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
      title="Create New Camp"
      max-width="2xl"
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
            <Dropdown
              v-model="createForm.director_id"
              :options="directorOptions"
              placeholder="Select a director"
              size="sm"
              align="left"
              class="w-full block"
              button-class="w-full !px-3 !py-2 !text-xs !font-sans"
              menu-class="w-full min-w-full"
            />
            <p v-if="createForm.errors.director_id" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.director_id }}
            </p>
          </div>

          <!-- Sports Type Selector -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Sport Type <span class="text-rose-500">*</span>
            </label>
            <Dropdown
              v-model="createForm.sports_type_id"
              :options="sportsTypeOptions"
              placeholder="Select sport"
              size="sm"
              align="left"
              class="w-full block"
              button-class="w-full !px-3 !py-2 !text-xs !font-sans"
              menu-class="w-full min-w-full"
            />
            <p v-if="createForm.errors.sports_type_id" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.sports_type_id }}
            </p>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Start Date <span class="text-rose-500">*</span>
            </label>
            <DatePicker
              v-model="createForm.start_date"
              placeholder="Select start date"
              size="sm"
              position="auto"
              align="left"
              :clearable="false"
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
            <DatePicker
              v-model="createForm.end_date"
              placeholder="Select end date"
              size="sm"
              position="auto"
              align="left"
              :min-date="createForm.start_date"
              :clearable="false"
            />
            <p v-if="createForm.errors.end_date" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.end_date }}
            </p>
          </div>

          <!-- Camp Timezone -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                Timezone <span class="text-rose-500">*</span>
              </label>
              <button
                v-if="createForm.latitude && createForm.longitude"
                type="button"
                @click="autoDetectCreateTimezone"
                class="inline-flex items-center gap-1 text-[10px] text-[#3B8FF3] hover:underline cursor-pointer font-mono"
                title="Auto-detect timezone from pin coordinates"
              >
                <Crosshair class="w-2.5 h-2.5" />
                <span>Detect</span>
              </button>
            </div>
            <Dropdown
              v-model="createForm.timezone"
              :options="timezoneOptions"
              placeholder="Select timezone"
              size="sm"
              align="left"
              class="w-full block"
              button-class="w-full !px-3 !py-2 !text-xs !font-sans"
              menu-class="w-full min-w-full"
            />
            <p v-if="createForm.errors.timezone" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.timezone }}
            </p>
          </div>

          <!-- Base Registration Price -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                Base Registration Price ($) <span class="text-rose-500">*</span>
              </label>
              <span v-if="createTotalPrice > 0" class="text-[10px] font-mono font-bold text-[#2B9B95] dark:text-[#34B1AA]">
                Total: {{ formatCurrency(createTotalPrice) }}
              </span>
            </div>
            <div class="relative">
              <DollarSign class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                v-model="createForm.price"
                type="number"
                step="0.01"
                min="0"
                placeholder="150.00"
                class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all shadow-2xs"
                required
              />
            </div>
            <p v-if="createForm.errors.price" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ createForm.errors.price }}
            </p>
            <p class="mt-1 text-[10px] font-mono text-slate-400">
              + Sport Fee: {{ formatCurrency(getSelectedSportFee(createForm.sports_type_id)) }}
            </p>
          </div>

          <!-- Google Map Location & Address Picker -->
          <div class="sm:col-span-2">
            <CampLocationPicker
              ref="createPickerRef"
              v-model:location="createForm.location"
              v-model:address="createForm.address"
              v-model:latitude="createForm.latitude"
              v-model:longitude="createForm.longitude"
              :api-key="googleMapsApiKey"
              :error-location="createForm.errors.location"
              :error-address="createForm.errors.address"
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
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all shadow-2xs"
            ></textarea>
          </div>

          <!-- Camp Ranking & Visibility Controls -->
          <div class="sm:col-span-2 pt-2 border-t border-slate-100 dark:border-white/[0.08]">
            <div class="mb-2.5">
              <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                <ShieldAlert class="w-3.5 h-3.5 text-[#F29F67]" />
                <span>Evaluation & Ranking Permissions</span>
              </h4>
              <p class="text-[11px] text-slate-400">Configure participant visibility for scores, leaderboards, and evaluator anonymity</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              
              <!-- 1. Publish rankings to evaluators -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Publish for Evaluators
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Permits camp evaluators to view referee score leaderboards and evaluations.
                  </p>
                </div>
                <button
                  type="button"
                  @click="createForm.publish_ranking_for_evaluators = !createForm.publish_ranking_for_evaluators"
                  :class="[
                    createForm.publish_ranking_for_evaluators ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      createForm.publish_ranking_for_evaluators ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

              <!-- 2. Publish rankings to referees -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Publish for Referees
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Makes final rankings and placement accessible to registered referees.
                  </p>
                </div>
                <button
                  type="button"
                  @click="createForm.publish_ranking_for_referees = !createForm.publish_ranking_for_referees"
                  :class="[
                    createForm.publish_ranking_for_referees ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      createForm.publish_ranking_for_referees ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

              <!-- 3. Hide evaluator name from referees -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Hide Evaluator Names
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Anonymizes evaluator identities on scorecards reviewed by referees.
                  </p>
                </div>
                <button
                  type="button"
                  @click="createForm.hide_evaluator_name_from_referees = !createForm.hide_evaluator_name_from_referees"
                  :class="[
                    createForm.hide_evaluator_name_from_referees ? 'bg-[#F29F67]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      createForm.hide_evaluator_name_from_referees ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

              <!-- 4. Hide ranking numbers from referees -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Hide Ranking Numbers
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Conceals exact ranking numbers (e.g. #1, #2), showing evaluation notes only.
                  </p>
                </div>
                <button
                  type="button"
                  @click="createForm.hide_ranking_numbers_from_referees = !createForm.hide_ranking_numbers_from_referees"
                  :class="[
                    createForm.hide_ranking_numbers_from_referees ? 'bg-[#F29F67]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      createForm.hide_ranking_numbers_from_referees ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

            </div>
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
      title="Edit Camp"
      max-width="2xl"
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
            <Dropdown
              v-model="editForm.director_id"
              :options="directorOptions"
              placeholder="Select a director"
              size="sm"
              align="left"
              class="w-full block"
              button-class="w-full !px-3 !py-2 !text-xs !font-sans"
              menu-class="w-full min-w-full"
            />
            <p v-if="editForm.errors.director_id" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.director_id }}
            </p>
          </div>

          <!-- Sports Type Selector -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Sport Type <span class="text-rose-500">*</span>
            </label>
            <Dropdown
              v-model="editForm.sports_type_id"
              :options="sportsTypeOptions"
              placeholder="Select sport"
              size="sm"
              align="left"
              class="w-full block"
              button-class="w-full !px-3 !py-2 !text-xs !font-sans"
              menu-class="w-full min-w-full"
            />
            <p v-if="editForm.errors.sports_type_id" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.sports_type_id }}
            </p>
          </div>

          <!-- Start Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Start Date <span class="text-rose-500">*</span>
            </label>
            <DatePicker
              v-model="editForm.start_date"
              placeholder="Select start date"
              size="sm"
              position="auto"
              align="left"
              :clearable="false"
            />
            <p v-if="editForm.errors.start_date" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.start_date }}
            </p>
          </div>

          <!-- End Date -->
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
              End Date <span class="text-rose-500">*</span>
            </label>
            <DatePicker
              v-model="editForm.end_date"
              placeholder="Select end date"
              size="sm"
              position="auto"
              align="left"
              :min-date="editForm.start_date"
              :clearable="false"
            />
            <p v-if="editForm.errors.end_date" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.end_date }}
            </p>
          </div>

          <!-- Camp Timezone -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                Timezone <span class="text-rose-500">*</span>
              </label>
              <button
                v-if="editForm.latitude && editForm.longitude"
                type="button"
                @click="autoDetectEditTimezone"
                class="inline-flex items-center gap-1 text-[10px] text-[#3B8FF3] hover:underline cursor-pointer font-mono"
                title="Auto-detect timezone from pin coordinates"
              >
                <Crosshair class="w-2.5 h-2.5" />
                <span>Detect</span>
              </button>
            </div>
            <Dropdown
              v-model="editForm.timezone"
              :options="timezoneOptions"
              placeholder="Select timezone"
              size="sm"
              align="left"
              class="w-full block"
              button-class="w-full !px-3 !py-2 !text-xs !font-sans"
              menu-class="w-full min-w-full"
            />
            <p v-if="editForm.errors.timezone" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.timezone }}
            </p>
          </div>

          <!-- Base Registration Price -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                Base Registration Price ($) <span class="text-rose-500">*</span>
              </label>
              <span v-if="editTotalPrice > 0" class="text-[10px] font-mono font-bold text-[#2B9B95] dark:text-[#34B1AA]">
                Total: {{ formatCurrency(editTotalPrice) }}
              </span>
            </div>
            <div class="relative">
              <DollarSign class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                v-model="editForm.price"
                type="number"
                step="0.01"
                min="0"
                placeholder="150.00"
                class="w-full pl-8 pr-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white font-mono placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all shadow-2xs"
                required
              />
            </div>
            <p v-if="editForm.errors.price" class="mt-1 text-[11px] text-rose-500 font-medium">
              {{ editForm.errors.price }}
            </p>
            <p class="mt-1 text-[10px] font-mono text-slate-400">
              + Sport Fee: {{ formatCurrency(getSelectedSportFee(editForm.sports_type_id)) }}
            </p>
          </div>

          <!-- Google Map Location & Address Picker -->
          <div class="sm:col-span-2">
            <CampLocationPicker
              ref="editPickerRef"
              v-model:location="editForm.location"
              v-model:address="editForm.address"
              v-model:latitude="editForm.latitude"
              v-model:longitude="editForm.longitude"
              :api-key="googleMapsApiKey"
              :error-location="editForm.errors.location"
              :error-address="editForm.errors.address"
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
              class="w-full px-3 py-2 text-xs rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white placeholder-slate-400 focus:ring-1 focus:ring-[#F29F67] focus:border-[#F29F67] focus:outline-none transition-all shadow-2xs"
            ></textarea>
          </div>

          <!-- Camp Ranking & Visibility Controls -->
          <div class="sm:col-span-2 pt-2 border-t border-slate-100 dark:border-white/[0.08]">
            <div class="mb-2.5">
              <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                <ShieldAlert class="w-3.5 h-3.5 text-[#F29F67]" />
                <span>Evaluation & Ranking Permissions</span>
              </h4>
              <p class="text-[11px] text-slate-400">Configure participant visibility for scores, leaderboards, and evaluator anonymity</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              
              <!-- 1. Publish rankings to evaluators -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Publish for Evaluators
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Permits camp evaluators to view referee score leaderboards and evaluations.
                  </p>
                </div>
                <button
                  type="button"
                  @click="editForm.publish_ranking_for_evaluators = !editForm.publish_ranking_for_evaluators"
                  :class="[
                    editForm.publish_ranking_for_evaluators ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      editForm.publish_ranking_for_evaluators ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

              <!-- 2. Publish rankings to referees -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Publish for Referees
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Makes final rankings and placement accessible to registered referees.
                  </p>
                </div>
                <button
                  type="button"
                  @click="editForm.publish_ranking_for_referees = !editForm.publish_ranking_for_referees"
                  :class="[
                    editForm.publish_ranking_for_referees ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      editForm.publish_ranking_for_referees ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

              <!-- 3. Hide evaluator name from referees -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Hide Evaluator Names
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Anonymizes evaluator identities on scorecards reviewed by referees.
                  </p>
                </div>
                <button
                  type="button"
                  @click="editForm.hide_evaluator_name_from_referees = !editForm.hide_evaluator_name_from_referees"
                  :class="[
                    editForm.hide_evaluator_name_from_referees ? 'bg-[#F29F67]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      editForm.hide_evaluator_name_from_referees ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

              <!-- 4. Hide ranking numbers from referees -->
              <div class="p-3 rounded-lg bg-slate-50/80 dark:bg-[#1E1E2C]/80 border border-slate-200 dark:border-white/[0.08] flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <label class="text-xs font-semibold text-slate-800 dark:text-slate-200 block">
                    Hide Ranking Numbers
                  </label>
                  <p class="text-[10px] text-slate-400 mt-0.5 leading-snug">
                    Conceals exact ranking numbers (e.g. #1, #2), showing evaluation notes only.
                  </p>
                </div>
                <button
                  type="button"
                  @click="editForm.hide_ranking_numbers_from_referees = !editForm.hide_ranking_numbers_from_referees"
                  :class="[
                    editForm.hide_ranking_numbers_from_referees ? 'bg-[#F29F67]' : 'bg-slate-300 dark:bg-[#36364E]',
                    'relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none mt-0.5'
                  ]"
                >
                  <span
                    :class="[
                      editForm.hide_ranking_numbers_from_referees ? 'translate-x-4 bg-white' : 'translate-x-0 bg-white dark:bg-slate-300',
                      'pointer-events-none inline-block h-4 w-4 transform rounded-full shadow ring-0 transition duration-200 ease-in-out'
                    ]"
                  />
                </button>
              </div>

            </div>
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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Sport</p>
            <p class="font-semibold text-slate-900 dark:text-white mt-0.5 truncate">{{ viewingCamp.sports_type_name }}</p>
          </div>
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Duration</p>
            <p class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.duration_days }} Days</p>
          </div>
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Timezone</p>
            <div class="flex items-center gap-1 mt-0.5">
              <span class="font-semibold text-slate-900 dark:text-white truncate text-[11px]">{{ viewingCamp.timezone_display_name || viewingCamp.timezone }}</span>
              <span class="px-1 py-0.2 rounded text-[9px] font-mono bg-[#3B8FF3]/10 text-[#3B8FF3] border border-[#3B8FF3]/20 flex-shrink-0">{{ viewingCamp.timezone_offset }}</span>
            </div>
          </div>
          <div class="p-2.5 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08]">
            <p class="text-[10px] text-slate-400 uppercase font-mono">Total Reg Fee</p>
            <p class="font-bold font-mono text-[#2B9B95] dark:text-[#34B1AA] mt-0.5">{{ formatCurrency(viewingCamp.total_price || viewingCamp.price) }}</p>
          </div>
        </div>

        <!-- Schedule Dates -->
        <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-between text-xs">
          <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
            <Calendar class="w-4 h-4 text-[#3B8FF3]" />
            <span>Dates: <strong class="font-mono text-slate-900 dark:text-white">{{ viewingCamp.formatted_start }}</strong> to <strong class="font-mono text-slate-900 dark:text-white">{{ viewingCamp.formatted_end }}</strong></span>
          </div>
          <span class="text-[11px] text-slate-400 font-mono">Camp Local Timezone</span>
        </div>

        <!-- Pricing Financial Breakdown -->
        <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] space-y-2">
          <div class="flex items-center justify-between">
            <h5 class="text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <DollarSign class="w-3.5 h-3.5 text-[#2B9B95]" />
              <span>Financial Fee Breakdown</span>
            </h5>
            <span class="text-[11px] font-mono text-slate-400">Paid by Registrants</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-center text-xs">
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05]">
              <p class="text-[10px] text-slate-400">Base Registration</p>
              <p class="font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ formatCurrency(viewingCamp.base_price) }}</p>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05]">
              <p class="text-[10px] text-slate-400">Sport Admin Fee</p>
              <p class="font-bold font-mono text-[#3B8FF3] mt-0.5">{{ formatCurrency(viewingCamp.sports_fee) }}</p>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-[#2B9B95]/30">
              <p class="text-[10px] text-slate-400">Total Price</p>
              <p class="font-bold font-mono text-[#2B9B95] dark:text-[#34B1AA] mt-0.5">{{ formatCurrency(viewingCamp.total_price || viewingCamp.price) }}</p>
            </div>
          </div>
        </div>

        <!-- Camp Operations & Rosters -->
        <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] space-y-2">
          <h5 class="text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
            <Users class="w-3.5 h-3.5 text-[#3B8FF3]" />
            <span>Rosters & Operational Metrics</span>
          </h5>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05]">
              <p class="text-[10px] text-slate-400">Referees</p>
              <p class="font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.checked_in_referees_count }} Active</p>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05]">
              <p class="text-[10px] text-slate-400">Evaluators</p>
              <p class="font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.evaluator_registrations_count }} Assigned</p>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05]">
              <p class="text-[10px] text-slate-400">Game Crews</p>
              <p class="font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.crews_count }} Crews</p>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05]">
              <p class="text-[10px] text-slate-400">Schedule</p>
              <p class="font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ viewingCamp.has_schedule ? (viewingCamp.schedule_status || 'Configured') : 'Not Created' }}</p>
            </div>
          </div>
        </div>

        <!-- Evaluation & Ranking Permissions Summary -->
        <div class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] space-y-2">
          <h5 class="text-[11px] font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
            <ShieldAlert class="w-3.5 h-3.5 text-[#F29F67]" />
            <span>Evaluation & Ranking Permissions</span>
          </h5>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05] flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-300 text-[11px]">Publish for Evaluators</span>
              <span :class="viewingCamp.publish_ranking_for_evaluators ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-slate-100 dark:bg-[#1E1E2C] text-slate-400 border-slate-200 dark:border-white/[0.08]'" class="px-2 py-0.5 rounded text-[10px] font-mono border uppercase font-semibold">
                {{ viewingCamp.publish_ranking_for_evaluators ? 'Enabled' : 'Disabled' }}
              </span>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05] flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-300 text-[11px]">Publish for Referees</span>
              <span :class="viewingCamp.publish_ranking_for_referees ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-slate-100 dark:bg-[#1E1E2C] text-slate-400 border-slate-200 dark:border-white/[0.08]'" class="px-2 py-0.5 rounded text-[10px] font-mono border uppercase font-semibold">
                {{ viewingCamp.publish_ranking_for_referees ? 'Published' : 'Hidden' }}
              </span>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05] flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-300 text-[11px]">Evaluator Names</span>
              <span :class="viewingCamp.hide_evaluator_name_from_referees ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' : 'bg-slate-100 dark:bg-[#1E1E2C] text-slate-500 border-slate-200 dark:border-white/[0.08]'" class="px-2 py-0.5 rounded text-[10px] font-mono border uppercase font-semibold">
                {{ viewingCamp.hide_evaluator_name_from_referees ? 'Anonymized' : 'Visible' }}
              </span>
            </div>
            <div class="p-2 rounded bg-white dark:bg-[#262638] border border-slate-200/80 dark:border-white/[0.05] flex items-center justify-between">
              <span class="text-slate-600 dark:text-slate-300 text-[11px]">Ranking Numbers</span>
              <span :class="viewingCamp.hide_ranking_numbers_from_referees ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20' : 'bg-slate-100 dark:bg-[#1E1E2C] text-slate-500 border-slate-200 dark:border-white/[0.08]'" class="px-2 py-0.5 rounded text-[10px] font-mono border uppercase font-semibold">
                {{ viewingCamp.hide_ranking_numbers_from_referees ? 'Concealed' : 'Visible' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Venue & GPS Coordinates -->
        <div v-if="viewingCamp.latitude && viewingCamp.longitude" class="p-3 rounded-md bg-slate-50 dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] flex items-center justify-between text-xs">
          <div class="flex items-center gap-2 text-slate-700 dark:text-slate-300">
            <MapPin class="w-4 h-4 text-[#F29F67]" />
            <span>Coordinates: <strong class="font-mono text-slate-900 dark:text-white">{{ viewingCamp.latitude }}, {{ viewingCamp.longitude }}</strong></span>
          </div>
          <a
            :href="`https://www.google.com/maps?q=${viewingCamp.latitude},${viewingCamp.longitude}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1 text-[11px] font-medium text-[#3B8FF3] hover:underline"
          >
            <span>View on Google Maps</span>
            <ExternalLink class="w-3 h-3" />
          </a>
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

    <!-- DUPLICATE CONFIRMATION MODAL -->
    <ConfirmationModal
      :show="isDuplicateModalOpen"
      title="Duplicate Camp Program"
      :message="`Are you sure you want to duplicate '${itemToDuplicate?.camp_name}'? A copy with '(Copy)' appended will be created as an inactive draft.`"
      confirm-text="Duplicate Camp"
      type="warning"
      @close="isDuplicateModalOpen = false"
      @confirm="confirmDuplicate"
    />

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
