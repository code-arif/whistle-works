<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Admin/Components/Common/Breadcrumb.vue';
import Modal from '@/Admin/Components/Common/Modal.vue';
import ConfirmationModal from '@/Admin/Components/Common/ConfirmationModal.vue';
import {
  Sparkles,
  Tent,
  Settings2,
  Handshake,
  Layers,
  Quote,
  Save,
  Plus,
  Trash2,
  Edit2,
  ExternalLink,
  CheckCircle2,
  XCircle,
  Upload,
  Image as ImageIcon
} from 'lucide-vue-next';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  activeTab: {
    type: String,
    default: 'hero',
  }
});

const currentTab = ref(props.activeTab || 'hero');

const tabs = [
  { id: 'hero', name: 'Hero Section', icon: Sparkles },
  { id: 'training-camp', name: 'Training Camp', icon: Tent },
  { id: 'operations', name: 'Operations', icon: Settings2 },
  { id: 'partners', name: 'Partners & Logos', icon: Handshake },
  { id: 'features', name: 'Features', icon: Layers },
  { id: 'testimonials', name: 'Testimonials', icon: Quote },
];

const switchTab = (tabId) => {
  currentTab.value = tabId;
  router.get('/admin/v2/cms/home', { tab: tabId }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
};

/* =========================================================
   1. HERO SECTION FORM
========================================================= */
const heroForm = useForm({
  title: props.data.hero?.title || '',
  description: props.data.hero?.description || '',
});

const submitHero = () => {
  heroForm.post('/admin/v2/cms/home/hero', { preserveScroll: true });
};

/* =========================================================
   2. TRAINING CAMP FORM
========================================================= */
const campForm = useForm({
  title: props.data.trainingCamp?.title || '',
  description: props.data.trainingCamp?.description || '',
});

const submitCamp = () => {
  campForm.post('/admin/v2/cms/home/training-camp', { preserveScroll: true });
};

/* =========================================================
   3. OPERATIONS FORM
========================================================= */
const operationsForm = useForm({
  title: props.data.operations?.title || '',
  description: props.data.operations?.description || '',
});

const submitOperations = () => {
  operationsForm.post('/admin/v2/cms/home/operations', { preserveScroll: true });
};

/* =========================================================
   4. PARTNERS & SLIDERS
========================================================= */
const partnerHeaderForm = useForm({
  title: props.data.partnerHeader?.title || '',
  description: props.data.partnerHeader?.description || '',
});

const submitPartnerHeader = () => {
  partnerHeaderForm.post('/admin/v2/cms/home/partners/header', { preserveScroll: true });
};

// Slider modal & actions
const showSliderModal = ref(false);
const editingSlider = ref(null);
const sliderPreview = ref(null);

const sliderForm = useForm({
  image: null,
  link: '',
  status: true,
});

const openCreateSlider = () => {
  editingSlider.value = null;
  sliderPreview.value = null;
  sliderForm.reset();
  sliderForm.clearErrors();
  sliderForm.status = true;
  showSliderModal.value = true;
};

const openEditSlider = (slider) => {
  editingSlider.value = slider;
  sliderPreview.value = slider.image;
  sliderForm.reset();
  sliderForm.clearErrors();
  sliderForm.link = slider.link || '';
  sliderForm.status = slider.status;
  showSliderModal.value = true;
};

const handleSliderFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    sliderForm.image = file;
    sliderPreview.value = URL.createObjectURL(file);
  }
};

const saveSlider = () => {
  if (editingSlider.value) {
    sliderForm.post(`/admin/v2/cms/home/partners/slider/${editingSlider.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { showSliderModal.value = false; },
    });
  } else {
    sliderForm.post('/admin/v2/cms/home/partners/slider', {
      preserveScroll: true,
      onSuccess: () => { showSliderModal.value = false; },
    });
  }
};

const toggleSlider = (slider) => {
  router.post(`/admin/v2/cms/home/partners/slider/${slider.id}/status`, {}, { preserveScroll: true });
};

// Slider Delete
const sliderToDelete = ref(null);
const showDeleteSliderModal = ref(false);

const confirmDeleteSlider = (slider) => {
  sliderToDelete.value = slider;
  showDeleteSliderModal.value = true;
};

const executeDeleteSlider = () => {
  if (!sliderToDelete.value) return;
  router.delete(`/admin/v2/cms/home/partners/slider/${sliderToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteSliderModal.value = false;
      sliderToDelete.value = null;
    }
  });
};

/* =========================================================
   5. FEATURES SECTION
========================================================= */
const featureHeaderForm = useForm({
  title: props.data.featuresHeader?.title || '',
  description: props.data.featuresHeader?.description || '',
});

const submitFeatureHeader = () => {
  featureHeaderForm.post('/admin/v2/cms/home/features/header', { preserveScroll: true });
};

// Feature Card modal & actions
const showFeatureModal = ref(false);
const editingFeature = ref(null);
const featurePreview = ref(null);

const featureForm = useForm({
  title: '',
  description: '',
  image: null,
});

const openCreateFeature = () => {
  editingFeature.value = null;
  featurePreview.value = null;
  featureForm.reset();
  featureForm.clearErrors();
  showFeatureModal.value = true;
};

const openEditFeature = (card) => {
  editingFeature.value = card;
  featurePreview.value = card.image;
  featureForm.reset();
  featureForm.clearErrors();
  featureForm.title = card.title || '';
  featureForm.description = card.description || '';
  showFeatureModal.value = true;
};

const handleFeatureFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    featureForm.image = file;
    featurePreview.value = URL.createObjectURL(file);
  }
};

const saveFeature = () => {
  if (editingFeature.value) {
    featureForm.post(`/admin/v2/cms/home/features/card/${editingFeature.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { showFeatureModal.value = false; },
    });
  } else {
    featureForm.post('/admin/v2/cms/home/features/card', {
      preserveScroll: true,
      onSuccess: () => { showFeatureModal.value = false; },
    });
  }
};

const featureToDelete = ref(null);
const showDeleteFeatureModal = ref(false);

const confirmDeleteFeature = (card) => {
  featureToDelete.value = card;
  showDeleteFeatureModal.value = true;
};

const executeDeleteFeature = () => {
  if (!featureToDelete.value) return;
  router.delete(`/admin/v2/cms/home/features/card/${featureToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteFeatureModal.value = false;
      featureToDelete.value = null;
    }
  });
};

/* =========================================================
   6. TESTIMONIALS SECTION
========================================================= */
const testimonialHeaderForm = useForm({
  title: props.data.testimonialHeader?.title || '',
  description: props.data.testimonialHeader?.description || '',
});

const submitTestimonialHeader = () => {
  testimonialHeaderForm.post('/admin/v2/cms/home/testimonials/header', { preserveScroll: true });
};

// Testimonial modal & actions
const showTestimonialModal = ref(false);
const editingTestimonial = ref(null);
const testimonialPreview = ref(null);

const testimonialForm = useForm({
  author_name: '',
  designation: '',
  review_text: '',
  author_avatar: null,
});

const openCreateTestimonial = () => {
  editingTestimonial.value = null;
  testimonialPreview.value = null;
  testimonialForm.reset();
  testimonialForm.clearErrors();
  showTestimonialModal.value = true;
};

const openEditTestimonial = (item) => {
  editingTestimonial.value = item;
  testimonialPreview.value = item.author_avatar;
  testimonialForm.reset();
  testimonialForm.clearErrors();
  testimonialForm.author_name = item.author_name || '';
  testimonialForm.designation = item.designation || '';
  testimonialForm.review_text = item.review_text || '';
  showTestimonialModal.value = true;
};

const handleTestimonialFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    testimonialForm.author_avatar = file;
    testimonialPreview.value = URL.createObjectURL(file);
  }
};

const saveTestimonial = () => {
  if (editingTestimonial.value) {
    testimonialForm.post(`/admin/v2/cms/home/testimonials/card/${editingTestimonial.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { showTestimonialModal.value = false; },
    });
  } else {
    testimonialForm.post('/admin/v2/cms/home/testimonials/card', {
      preserveScroll: true,
      onSuccess: () => { showTestimonialModal.value = false; },
    });
  }
};

const testimonialToDelete = ref(null);
const showDeleteTestimonialModal = ref(false);

const confirmDeleteTestimonial = (item) => {
  testimonialToDelete.value = item;
  showDeleteTestimonialModal.value = true;
};

const executeDeleteTestimonial = () => {
  if (!testimonialToDelete.value) return;
  router.delete(`/admin/v2/cms/home/testimonials/card/${testimonialToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteTestimonialModal.value = false;
      testimonialToDelete.value = null;
    }
  });
};
</script>

<template>
  <AdminLayout>
    <Head title="Home Page CMS" />

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Executive Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <Breadcrumb :items="[{ label: 'Content & CMS' }, { label: 'Home Page CMS' }]" />

          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            Home Page CMS
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Manage public landing page hero headlines, partner logos, feature cards, operations, and testimonials.
          </p>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-1 sm:gap-2 overflow-x-auto pb-1 border-b border-slate-200 dark:border-white/[0.08] no-scrollbar">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="switchTab(tab.id)"
          :class="[
            currentTab === tab.id
              ? 'border-[#F29F67] text-[#E08A50] dark:text-[#F29F67] bg-[#F29F67]/10'
              : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:border-slate-300 dark:hover:border-white/20',
            'flex items-center gap-2 px-3.5 py-2.5 text-xs sm:text-sm font-semibold border-b-2 rounded-t-lg transition-all duration-150 whitespace-nowrap cursor-pointer'
          ]"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          <span>{{ tab.name }}</span>
        </button>
      </div>

      <!-- ====================================================
           TAB 1: HERO SECTION
      ==================================================== -->
      <div v-show="currentTab === 'hero'" class="space-y-6">
        <form @submit.prevent="submitHero" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Sparkles class="w-4 h-4 text-[#F29F67]" />
                Main Hero Section
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Primary headline and marketing description shown at the top of the home page.
              </p>
            </div>
            <button
              type="submit"
              :disabled="heroForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ heroForm.processing ? 'Saving...' : 'Save Changes' }}</span>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Hero Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="heroForm.title"
                type="text"
                required
                placeholder="e.g. Elevate Your Officiating to New Heights"
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              />
              <p v-if="heroForm.errors.title" class="text-rose-500 text-xs mt-1">{{ heroForm.errors.title }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Hero Subtitle / Description
              </label>
              <textarea
                v-model="heroForm.description"
                rows="4"
                placeholder="Write compelling introduction text for prospective referee trainees..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
              <p v-if="heroForm.errors.description" class="text-rose-500 text-xs mt-1">{{ heroForm.errors.description }}</p>
            </div>
          </div>
        </form>
      </div>

      <!-- ====================================================
           TAB 2: TRAINING CAMP SECTION
      ==================================================== -->
      <div v-show="currentTab === 'training-camp'" class="space-y-6">
        <form @submit.prevent="submitCamp" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Tent class="w-4 h-4 text-[#F29F67]" />
                Training Camp Section
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Section header and description introducing camp registrations and clinics.
              </p>
            </div>
            <button
              type="submit"
              :disabled="campForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ campForm.processing ? 'Saving...' : 'Save Changes' }}</span>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="campForm.title"
                type="text"
                required
                placeholder="e.g. Comprehensive Referee Training Camps"
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              />
              <p v-if="campForm.errors.title" class="text-rose-500 text-xs mt-1">{{ campForm.errors.title }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Description
              </label>
              <textarea
                v-model="campForm.description"
                rows="4"
                placeholder="Explain the benefits, intensive coaching, and live drills provided in the camps..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
              <p v-if="campForm.errors.description" class="text-rose-500 text-xs mt-1">{{ campForm.errors.description }}</p>
            </div>
          </div>
        </form>
      </div>

      <!-- ====================================================
           TAB 3: OPERATIONS SECTION
      ==================================================== -->
      <div v-show="currentTab === 'operations'" class="space-y-6">
        <form @submit.prevent="submitOperations" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-5">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Settings2 class="w-4 h-4 text-[#F29F67]" />
                Camp Operations Section
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Headline and description explaining operational guidelines and equipment standards.
              </p>
            </div>
            <button
              type="submit"
              :disabled="operationsForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ operationsForm.processing ? 'Saving...' : 'Save Changes' }}</span>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="operationsForm.title"
                type="text"
                required
                placeholder="e.g. Seamless Operational Standards"
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              />
              <p v-if="operationsForm.errors.title" class="text-rose-500 text-xs mt-1">{{ operationsForm.errors.title }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Description
              </label>
              <textarea
                v-model="operationsForm.description"
                rows="4"
                placeholder="Describe how camps operate efficiently from registration to court performance..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
              <p v-if="operationsForm.errors.description" class="text-rose-500 text-xs mt-1">{{ operationsForm.errors.description }}</p>
            </div>
          </div>
        </form>
      </div>

      <!-- ====================================================
           TAB 4: PARTNERS & LOGOS
      ==================================================== -->
      <div v-show="currentTab === 'partners'" class="space-y-6">
        <!-- Partner Section Header Form -->
        <form @submit.prevent="submitPartnerHeader" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Handshake class="w-4 h-4 text-[#F29F67]" />
                Partners Section Header
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Headline introducing official athletic partners, sponsors, and league affiliates.
              </p>
            </div>
            <button
              type="submit"
              :disabled="partnerHeaderForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ partnerHeaderForm.processing ? 'Saving...' : 'Save Header' }}</span>
            </button>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Section Title <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="partnerHeaderForm.title"
              type="text"
              required
              placeholder="e.g. Trusted by Premier Leagues and Sports Associations"
              class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
            />
            <p v-if="partnerHeaderForm.errors.title" class="text-rose-500 text-xs mt-1">{{ partnerHeaderForm.errors.title }}</p>
          </div>
        </form>

        <!-- Partner Logos Grid -->
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Partner Logos & Brands</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Total {{ props.data.sliders?.length || 0 }} partner logos in active rotation.
              </p>
            </div>
            <button
              @click="openCreateSlider"
              class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer"
            >
              <Plus class="w-4 h-4" />
              <span>Add Partner Logo</span>
            </button>
          </div>

          <div v-if="props.data.sliders?.length === 0" class="py-12 text-center text-slate-400 text-sm">
            <ImageIcon class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
            No partner logos uploaded yet. Click "Add Partner Logo" to upload.
          </div>

          <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <div
              v-for="slider in props.data.sliders"
              :key="slider.id"
              class="relative group bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md p-3 flex flex-col items-center justify-between gap-3 hover:border-[#F29F67]/50 transition-all"
            >
              <!-- Status Micro-badge -->
              <span
                :class="[
                  slider.status ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20',
                  'absolute top-2 left-2 text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded border'
                ]"
              >
                {{ slider.status ? 'Active' : 'Hidden' }}
              </span>

              <!-- Action Menu -->
              <div class="absolute top-2 right-2 flex items-center gap-1 opacity-90 sm:opacity-0 group-hover:opacity-100 transition-opacity">
                <button
                  @click="openEditSlider(slider)"
                  title="Edit Logo"
                  class="p-1 rounded bg-white dark:bg-[#262638] text-slate-600 dark:text-slate-300 hover:text-white hover:bg-[#F29F67] border border-slate-200 dark:border-white/[0.08] shadow-xs cursor-pointer"
                >
                  <Edit2 class="w-3.5 h-3.5" />
                </button>
                <button
                  @click="confirmDeleteSlider(slider)"
                  title="Delete Logo"
                  class="p-1 rounded bg-white dark:bg-[#262638] text-rose-500 hover:text-white hover:bg-rose-500 border border-slate-200 dark:border-white/[0.08] shadow-xs cursor-pointer"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>

              <div class="w-full h-20 flex items-center justify-center p-2 mt-4">
                <img
                  v-if="slider.image"
                  :src="slider.image"
                  alt="Partner Logo"
                  class="max-h-full max-w-full object-contain filter grayscale group-hover:grayscale-0 transition-all"
                />
                <ImageIcon v-else class="w-8 h-8 text-slate-400" />
              </div>

              <div class="w-full pt-2 border-t border-slate-200 dark:border-white/[0.06] flex items-center justify-between text-xs">
                <a
                  v-if="slider.link"
                  :href="slider.link"
                  target="_blank"
                  class="text-[11px] text-slate-500 hover:text-[#F29F67] truncate max-w-[100px] flex items-center gap-1"
                >
                  <ExternalLink class="w-3 h-3 shrink-0" />
                  Website
                </a>
                <span v-else class="text-[11px] text-slate-400">No link</span>

                <button
                  @click="toggleSlider(slider)"
                  :class="[
                    slider.status ? 'text-emerald-500 hover:text-emerald-600' : 'text-slate-400 hover:text-slate-300',
                    'font-medium text-[11px] cursor-pointer'
                  ]"
                >
                  {{ slider.status ? 'Disable' : 'Enable' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TAB 5: FEATURES SECTION
      ==================================================== -->
      <div v-show="currentTab === 'features'" class="space-y-6">
        <!-- Feature Header Form -->
        <form @submit.prevent="submitFeatureHeader" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Layers class="w-4 h-4 text-[#F29F67]" />
                Features Section Header
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Headline and subtitle introducing platform core advantages and features.
              </p>
            </div>
            <button
              type="submit"
              :disabled="featureHeaderForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ featureHeaderForm.processing ? 'Saving...' : 'Save Header' }}</span>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="featureHeaderForm.title"
                type="text"
                required
                placeholder="e.g. Why Whistle Works Sets the Gold Standard"
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              />
              <p v-if="featureHeaderForm.errors.title" class="text-rose-500 text-xs mt-1">{{ featureHeaderForm.errors.title }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Description
              </label>
              <textarea
                v-model="featureHeaderForm.description"
                rows="3"
                placeholder="Briefly highlight why referees and camp organizers choose Whistle Works..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
              <p v-if="featureHeaderForm.errors.description" class="text-rose-500 text-xs mt-1">{{ featureHeaderForm.errors.description }}</p>
            </div>
          </div>
        </form>

        <!-- Feature Cards Grid -->
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Feature Highlight Cards</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Total {{ props.data.featuresCards?.length || 0 }} highlight cards displayed on the landing page.
              </p>
            </div>
            <button
              @click="openCreateFeature"
              class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer"
            >
              <Plus class="w-4 h-4" />
              <span>Add Feature Card</span>
            </button>
          </div>

          <div v-if="props.data.featuresCards?.length === 0" class="py-12 text-center text-slate-400 text-sm">
            <Layers class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
            No feature cards created yet. Click "Add Feature Card" to create one.
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="card in props.data.featuresCards"
              :key="card.id"
              class="bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md p-4 flex flex-col justify-between hover:border-[#F29F67]/50 transition-all group"
            >
              <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="w-10 h-10 rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center p-1.5 shrink-0">
                    <img v-if="card.image" :src="card.image" alt="Icon" class="max-h-full max-w-full object-contain" />
                    <Layers v-else class="w-5 h-5 text-[#F29F67]" />
                  </div>
                  <div class="flex items-center gap-1">
                    <button
                      @click="openEditFeature(card)"
                      class="p-1 rounded text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                      title="Edit Card"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="confirmDeleteFeature(card)"
                      class="p-1 rounded text-rose-500 hover:bg-rose-500/10 transition-colors cursor-pointer"
                      title="Delete Card"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>

                <h4 class="font-bold text-slate-900 dark:text-white text-sm leading-snug">
                  {{ card.title }}
                </h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 line-clamp-3 leading-relaxed">
                  {{ card.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TAB 6: TESTIMONIALS SECTION
      ==================================================== -->
      <div v-show="currentTab === 'testimonials'" class="space-y-6">
        <!-- Testimonial Header Form -->
        <form @submit.prevent="submitTestimonialHeader" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Quote class="w-4 h-4 text-[#F29F67]" />
                Testimonials Section Header
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Headline and subtitle for referee and camp alumni reviews.
              </p>
            </div>
            <button
              type="submit"
              :disabled="testimonialHeaderForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ testimonialHeaderForm.processing ? 'Saving...' : 'Save Header' }}</span>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="testimonialHeaderForm.title"
                type="text"
                required
                placeholder="e.g. What Official Referees Say About Whistle Works"
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              />
              <p v-if="testimonialHeaderForm.errors.title" class="text-rose-500 text-xs mt-1">{{ testimonialHeaderForm.errors.title }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Description
              </label>
              <textarea
                v-model="testimonialHeaderForm.description"
                rows="3"
                placeholder="Brief introduction to the real feedback and success stories..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
              <p v-if="testimonialHeaderForm.errors.description" class="text-rose-500 text-xs mt-1">{{ testimonialHeaderForm.errors.description }}</p>
            </div>
          </div>
        </form>

        <!-- Testimonial Cards Grid -->
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Referee Testimonials</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Total {{ props.data.testimonials?.length || 0 }} client reviews active.
              </p>
            </div>
            <button
              @click="openCreateTestimonial"
              class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer"
            >
              <Plus class="w-4 h-4" />
              <span>Add Testimonial</span>
            </button>
          </div>

          <div v-if="props.data.testimonials?.length === 0" class="py-12 text-center text-slate-400 text-sm">
            <Quote class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
            No testimonials added yet. Click "Add Testimonial" to add one.
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="item in props.data.testimonials"
              :key="item.id"
              class="bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md p-4 flex flex-col justify-between hover:border-[#F29F67]/50 transition-all group"
            >
              <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="item.author_avatar"
                      :src="item.author_avatar"
                      :alt="item.author_name"
                      class="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-white/[0.08]"
                    />
                    <div v-else class="w-10 h-10 rounded-full bg-slate-200 dark:bg-[#262638] flex items-center justify-center font-bold text-xs text-slate-700 dark:text-slate-300">
                      {{ item.author_name?.charAt(0) || 'U' }}
                    </div>
                    <div>
                      <h4 class="font-bold text-slate-900 dark:text-white text-sm leading-tight">
                        {{ item.author_name }}
                      </h4>
                      <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ item.designation || 'Referee Official' }}
                      </p>
                    </div>
                  </div>

                  <div class="flex items-center gap-1">
                    <button
                      @click="openEditTestimonial(item)"
                      class="p-1 rounded text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                      title="Edit Testimonial"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="confirmDeleteTestimonial(item)"
                      class="p-1 rounded text-rose-500 hover:bg-rose-500/10 transition-colors cursor-pointer"
                      title="Delete Testimonial"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 italic line-clamp-4 leading-relaxed">
                  "{{ item.review_text }}"
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ====================================================
         MODALS & DIALOGS
    ==================================================== -->
    <!-- Partner Logo Modal -->
    <Modal :show="showSliderModal" :title="editingSlider ? 'Edit Partner Logo' : 'Add Partner Logo'" @close="showSliderModal = false">
      <form @submit.prevent="saveSlider" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Partner Logo Image {{ editingSlider ? '(Optional if unchanged)' : '*' }}
          </label>
          <div class="flex items-center gap-4">
            <div v-if="sliderPreview" class="w-20 h-16 rounded border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#161622] flex items-center justify-center p-2 shrink-0">
              <img :src="sliderPreview" alt="Preview" class="max-h-full max-w-full object-contain" />
            </div>
            <input
              type="file"
              accept="image/*"
              @change="handleSliderFile"
              :required="!editingSlider"
              class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#F29F67]/20 file:text-[#E08A50] hover:file:bg-[#F29F67]/30"
            />
          </div>
          <p v-if="sliderForm.errors.image" class="text-rose-500 text-xs mt-1">{{ sliderForm.errors.image }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Website URL (Optional)
          </label>
          <input
            v-model="sliderForm.link"
            type="url"
            placeholder="https://example.com"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="sliderForm.errors.link" class="text-rose-500 text-xs mt-1">{{ sliderForm.errors.link }}</p>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <input
            id="slider_status"
            v-model="sliderForm.status"
            type="checkbox"
            class="rounded border-slate-300 dark:border-white/20 text-[#F29F67] focus:ring-[#F29F67]"
          />
          <label for="slider_status" class="text-xs text-slate-700 dark:text-slate-300 font-medium">
            Active and visible on landing page
          </label>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
          <button
            type="button"
            @click="showSliderModal = false"
            class="px-4 py-2 border border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-300 text-xs rounded-md hover:bg-slate-50 dark:hover:bg-white/[0.04]"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="sliderForm.processing"
            class="px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs disabled:opacity-50"
          >
            {{ sliderForm.processing ? 'Saving...' : 'Save Logo' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Partner Modal -->
    <ConfirmationModal
      :show="showDeleteSliderModal"
      title="Delete Partner Logo"
      message="Are you sure you want to permanently delete this partner logo from the rotation?"
      confirm-text="Delete Logo"
      type="danger"
      @close="showDeleteSliderModal = false"
      @confirm="executeDeleteSlider"
    />

    <!-- Feature Card Modal -->
    <Modal :show="showFeatureModal" :title="editingFeature ? 'Edit Feature Card' : 'Add Feature Card'" @close="showFeatureModal = false">
      <form @submit.prevent="saveFeature" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Card Title <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="featureForm.title"
            type="text"
            required
            placeholder="e.g. Real-Time Video Breakdown"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="featureForm.errors.title" class="text-rose-500 text-xs mt-1">{{ featureForm.errors.title }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Card Description
          </label>
          <textarea
            v-model="featureForm.description"
            rows="3"
            placeholder="Explain the specific value proposition of this feature..."
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          ></textarea>
          <p v-if="featureForm.errors.description" class="text-rose-500 text-xs mt-1">{{ featureForm.errors.description }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Feature Icon / Image (Optional)
          </label>
          <div class="flex items-center gap-4">
            <div v-if="featurePreview" class="w-14 h-14 rounded border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#161622] flex items-center justify-center p-2 shrink-0">
              <img :src="featurePreview" alt="Preview" class="max-h-full max-w-full object-contain" />
            </div>
            <input
              type="file"
              accept="image/*"
              @change="handleFeatureFile"
              class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#F29F67]/20 file:text-[#E08A50] hover:file:bg-[#F29F67]/30"
            />
          </div>
          <p v-if="featureForm.errors.image" class="text-rose-500 text-xs mt-1">{{ featureForm.errors.image }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
          <button
            type="button"
            @click="showFeatureModal = false"
            class="px-4 py-2 border border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-300 text-xs rounded-md hover:bg-slate-50 dark:hover:bg-white/[0.04]"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="featureForm.processing"
            class="px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs disabled:opacity-50"
          >
            {{ featureForm.processing ? 'Saving...' : 'Save Card' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Feature Modal -->
    <ConfirmationModal
      :show="showDeleteFeatureModal"
      title="Delete Feature Card"
      message="Are you sure you want to permanently delete this feature highlight card?"
      confirm-text="Delete Card"
      type="danger"
      @close="showDeleteFeatureModal = false"
      @confirm="executeDeleteFeature"
    />

    <!-- Testimonial Modal -->
    <Modal :show="showTestimonialModal" :title="editingTestimonial ? 'Edit Testimonial' : 'Add Testimonial'" @close="showTestimonialModal = false">
      <form @submit.prevent="saveTestimonial" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Author / Referee Name <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="testimonialForm.author_name"
            type="text"
            required
            placeholder="e.g. Marcus Vance"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="testimonialForm.errors.author_name" class="text-rose-500 text-xs mt-1">{{ testimonialForm.errors.author_name }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Designation / Role
          </label>
          <input
            v-model="testimonialForm.designation"
            type="text"
            placeholder="e.g. NCAA Division 1 Official"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="testimonialForm.errors.designation" class="text-rose-500 text-xs mt-1">{{ testimonialForm.errors.designation }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Review / Testimonial Text
          </label>
          <textarea
            v-model="testimonialForm.review_text"
            rows="4"
            placeholder="Write the review feedback..."
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          ></textarea>
          <p v-if="testimonialForm.errors.review_text" class="text-rose-500 text-xs mt-1">{{ testimonialForm.errors.review_text }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Author Avatar (Optional)
          </label>
          <div class="flex items-center gap-4">
            <div v-if="testimonialPreview" class="w-14 h-14 rounded-full overflow-hidden border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#161622] flex items-center justify-center shrink-0">
              <img :src="testimonialPreview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <input
              type="file"
              accept="image/*"
              @change="handleTestimonialFile"
              class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#F29F67]/20 file:text-[#E08A50] hover:file:bg-[#F29F67]/30"
            />
          </div>
          <p v-if="testimonialForm.errors.author_avatar" class="text-rose-500 text-xs mt-1">{{ testimonialForm.errors.author_avatar }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
          <button
            type="button"
            @click="showTestimonialModal = false"
            class="px-4 py-2 border border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-300 text-xs rounded-md hover:bg-slate-50 dark:hover:bg-white/[0.04]"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="testimonialForm.processing"
            class="px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs disabled:opacity-50"
          >
            {{ testimonialForm.processing ? 'Saving...' : 'Save Testimonial' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Testimonial Modal -->
    <ConfirmationModal
      :show="showDeleteTestimonialModal"
      title="Delete Testimonial"
      message="Are you sure you want to permanently delete this testimonial review?"
      confirm-text="Delete Review"
      type="danger"
      @close="showDeleteTestimonialModal = false"
      @confirm="executeDeleteTestimonial"
    />
  </AdminLayout>
</template>
