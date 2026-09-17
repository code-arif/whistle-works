<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Admin/Layouts/AdminLayout.vue';
import Breadcrumb from '@/Admin/Components/Common/Breadcrumb.vue';
import Modal from '@/Admin/Components/Common/Modal.vue';
import ConfirmationModal from '@/Admin/Components/Common/ConfirmationModal.vue';
import {
  BookOpen,
  UserCheck,
  Award,
  Users,
  Save,
  Plus,
  Trash2,
  Edit2,
  Upload,
  Image as ImageIcon,
  Compass,
  Target,
  Sparkles
} from 'lucide-vue-next';

const props = defineProps({
  data: {
    type: Object,
    required: true,
  },
  activeTab: {
    type: String,
    default: 'overview',
  }
});

const currentTab = ref(props.activeTab || 'overview');

const tabs = [
  { id: 'overview', name: 'General & Mission', icon: Compass },
  { id: 'owner', name: 'Founder & Leadership', icon: UserCheck },
  { id: 'features', name: 'Feature Highlights', icon: Award },
  { id: 'team', name: 'Our Team', icon: Users },
];

const switchTab = (tabId) => {
  currentTab.value = tabId;
  router.get('/admin/v2/cms/about', { tab: tabId }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
};

/* =========================================================
   1. OVERVIEW & MISSION FORMS
========================================================= */
// Page Title Form
const pageTitleForm = useForm({
  title: props.data.pageTitle?.title || '',
  description: props.data.pageTitle?.description || '',
});
const submitPageTitle = () => {
  pageTitleForm.post('/admin/v2/cms/about/page-title', { preserveScroll: true });
};

// Mission Form
const missionForm = useForm({
  title: props.data.mission?.title || '',
  description: props.data.mission?.description || '',
});
const submitMission = () => {
  missionForm.post('/admin/v2/cms/about/mission', { preserveScroll: true });
};

// Key to Excellence Form
const excellenceForm = useForm({
  title: props.data.keyToExcellence?.title || '',
  description: props.data.keyToExcellence?.description || '',
});
const submitExcellence = () => {
  excellenceForm.post('/admin/v2/cms/about/key-to-excellence', { preserveScroll: true });
};

// Bottom Description Form
const bottomDescForm = useForm({
  description: props.data.bottomDescription?.description || '',
});
const submitBottomDesc = () => {
  bottomDescForm.post('/admin/v2/cms/about/bottom-description', { preserveScroll: true });
};

// Getting Started Form
const gettingStartedForm = useForm({
  title: props.data.gettingStarted?.title || '',
  description: props.data.gettingStarted?.description || '',
});
const submitGettingStarted = () => {
  gettingStartedForm.post('/admin/v2/cms/about/getting-started', { preserveScroll: true });
};

/* =========================================================
   2. FOUNDER & OWNER INFO FORM
========================================================= */
const ownerPreview = ref(props.data.owner?.image || null);
const ownerForm = useForm({
  name: props.data.owner?.name || '',
  designation: props.data.owner?.designation || '',
  experience: props.data.owner?.experience || '',
  bio: props.data.owner?.bio || '',
  image: null,
  stat_1_value: props.data.owner?.stats?.[0]?.value || '',
  stat_1_label: props.data.owner?.stats?.[0]?.label || '',
  stat_2_value: props.data.owner?.stats?.[1]?.value || '',
  stat_2_label: props.data.owner?.stats?.[1]?.label || '',
  stat_3_value: props.data.owner?.stats?.[2]?.value || '',
  stat_3_label: props.data.owner?.stats?.[2]?.label || '',
});

const handleOwnerFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    ownerForm.image = file;
    ownerPreview.value = URL.createObjectURL(file);
  }
};

const submitOwner = () => {
  ownerForm.post('/admin/v2/cms/about/owner-info', { preserveScroll: true });
};

/* =========================================================
   3. FEATURE CARDS (ABOUT HIGHLIGHTS)
========================================================= */
const showFeatureModal = ref(false);
const editingFeature = ref(null);
const featurePreview = ref(null);

const featureForm = useForm({
  title: '',
  description: '',
  order: 0,
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
  featureForm.order = card.order || 0;
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
    featureForm.post(`/admin/v2/cms/about/feature-cards/${editingFeature.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { showFeatureModal.value = false; },
    });
  } else {
    featureForm.post('/admin/v2/cms/about/feature-cards', {
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
  router.delete(`/admin/v2/cms/about/feature-cards/${featureToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteFeatureModal.value = false;
      featureToDelete.value = null;
    }
  });
};

/* =========================================================
   4. OUR TEAM SECTION
========================================================= */
// Team Section Header
const teamHeaderForm = useForm({
  title: props.data.teamHeader?.title || '',
  description: props.data.teamHeader?.description || '',
});
const submitTeamHeader = () => {
  teamHeaderForm.post('/admin/v2/cms/about/team/header', { preserveScroll: true });
};

// Team Member Modal & CRUD
const showTeamModal = ref(false);
const editingMember = ref(null);
const memberPreview = ref(null);

const memberForm = useForm({
  title: '', // Name
  sub_title: '', // Role / Designation
  description: '', // Bio
  image: null,
});

const openCreateMember = () => {
  editingMember.value = null;
  memberPreview.value = null;
  memberForm.reset();
  memberForm.clearErrors();
  showTeamModal.value = true;
};

const openEditMember = (member) => {
  editingMember.value = member;
  memberPreview.value = member.image;
  memberForm.reset();
  memberForm.clearErrors();
  memberForm.title = member.title || '';
  memberForm.sub_title = member.sub_title || '';
  memberForm.description = member.description || '';
  showTeamModal.value = true;
};

const handleMemberFile = (e) => {
  const file = e.target.files[0];
  if (file) {
    memberForm.image = file;
    memberPreview.value = URL.createObjectURL(file);
  }
};

const saveMember = () => {
  if (editingMember.value) {
    memberForm.post(`/admin/v2/cms/about/team/members/${editingMember.value.id}`, {
      preserveScroll: true,
      onSuccess: () => { showTeamModal.value = false; },
    });
  } else {
    memberForm.post('/admin/v2/cms/about/team/members', {
      preserveScroll: true,
      onSuccess: () => { showTeamModal.value = false; },
    });
  }
};

const memberToDelete = ref(null);
const showDeleteMemberModal = ref(false);

const confirmDeleteMember = (member) => {
  memberToDelete.value = member;
  showDeleteMemberModal.value = true;
};

const executeDeleteMember = () => {
  if (!memberToDelete.value) return;
  router.delete(`/admin/v2/cms/about/team/members/${memberToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteMemberModal.value = false;
      memberToDelete.value = null;
    }
  });
};
</script>

<template>
  <AdminLayout>
    <Head title="About Page CMS" />

    <div class="space-y-6 max-w-7xl mx-auto pb-12">
      <!-- Executive Header Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <Breadcrumb :items="[{ label: 'Content & CMS' }, { label: 'About Page CMS' }]" />

          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
            About Page CMS
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Manage company mission, leadership biographies, key pillars of excellence, and referee coaching team.
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
           TAB 1: GENERAL & MISSION
      ==================================================== -->
      <div v-show="currentTab === 'overview'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- 1. Page Title Section -->
          <form @submit.prevent="submitPageTitle" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Compass class="w-4 h-4 text-[#F29F67]" />
                Main Page Header
              </h3>
              <button
                type="submit"
                :disabled="pageTitleForm.processing"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
              >
                <Save class="w-3.5 h-3.5" />
                <span>Save</span>
              </button>
            </div>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Page Title <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="pageTitleForm.title"
                  type="text"
                  required
                  placeholder="e.g. About Whistle Works"
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Subtitle / Brief Description
                </label>
                <textarea
                  v-model="pageTitleForm.description"
                  rows="3"
                  placeholder="Brief introductory statement..."
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                ></textarea>
              </div>
            </div>
          </form>

          <!-- 2. Our Mission Section -->
          <form @submit.prevent="submitMission" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Target class="w-4 h-4 text-[#F29F67]" />
                Our Mission Section
              </h3>
              <button
                type="submit"
                :disabled="missionForm.processing"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
              >
                <Save class="w-3.5 h-3.5" />
                <span>Save</span>
              </button>
            </div>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Mission Title <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="missionForm.title"
                  type="text"
                  required
                  placeholder="e.g. Empowering Next-Gen Athletic Officials"
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Mission Statement <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="missionForm.description"
                  rows="3"
                  required
                  placeholder="Elaborate on the core mission..."
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                ></textarea>
              </div>
            </div>
          </form>

          <!-- 3. Key to Excellence Section -->
          <form @submit.prevent="submitExcellence" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Sparkles class="w-4 h-4 text-[#F29F67]" />
                Key to Excellence Section
              </h3>
              <button
                type="submit"
                :disabled="excellenceForm.processing"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
              >
                <Save class="w-3.5 h-3.5" />
                <span>Save</span>
              </button>
            </div>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Section Title <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="excellenceForm.title"
                  type="text"
                  required
                  placeholder="e.g. The Pillars of Officiating Mastery"
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Excellence Description <span class="text-rose-500">*</span>
                </label>
                <textarea
                  v-model="excellenceForm.description"
                  rows="3"
                  required
                  placeholder="Detail how Whistle Works fosters discipline and precision..."
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                ></textarea>
              </div>
            </div>
          </form>

          <!-- 4. Getting Started Section -->
          <form @submit.prevent="submitGettingStarted" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Compass class="w-4 h-4 text-[#F29F67]" />
                Getting Started Banner
              </h3>
              <button
                type="submit"
                :disabled="gettingStartedForm.processing"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
              >
                <Save class="w-3.5 h-3.5" />
                <span>Save</span>
              </button>
            </div>
            <div class="space-y-3">
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Banner Title <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="gettingStartedForm.title"
                  type="text"
                  required
                  placeholder="e.g. Ready to Take the Court?"
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                />
              </div>
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">
                  Banner Description
                </label>
                <textarea
                  v-model="gettingStartedForm.description"
                  rows="3"
                  placeholder="Call to action text inviting users to register..."
                  class="w-full px-3 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                ></textarea>
              </div>
            </div>
          </form>

          <!-- 5. Bottom Description Section (Full Width) -->
          <form @submit.prevent="submitBottomDesc" class="lg:col-span-2 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 shadow-xs space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-3">
              <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  <BookOpen class="w-4 h-4 text-[#F29F67]" />
                  Bottom Description Block
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  Concluding statement and organizational details presented at the base of the About page.
                </p>
              </div>
              <button
                type="submit"
                :disabled="bottomDescForm.processing"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
              >
                <Save class="w-3.5 h-3.5" />
                <span>Save Block</span>
              </button>
            </div>
            <div>
              <textarea
                v-model="bottomDescForm.description"
                rows="4"
                required
                placeholder="Write the full closing remarks and accreditation information..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
            </div>
          </form>
        </div>
      </div>

      <!-- ====================================================
           TAB 2: FOUNDER / OWNER PROFILE
      ==================================================== -->
      <div v-show="currentTab === 'owner'" class="space-y-6">
        <form @submit.prevent="submitOwner" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-6">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <UserCheck class="w-4 h-4 text-[#F29F67]" />
                Founder & Executive Leadership Profile
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage the founder's biography, credentials, portrait photo, and key metrics shown on the about page.
              </p>
            </div>
            <button
              type="submit"
              :disabled="ownerForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ ownerForm.processing ? 'Saving...' : 'Save Profile' }}</span>
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left: Owner Image Upload -->
            <div class="flex flex-col items-center justify-center p-5 bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md text-center">
              <div class="w-32 h-32 rounded-md overflow-hidden border-2 border-[#F29F67]/40 mb-3 bg-white dark:bg-[#262638] flex items-center justify-center">
                <img
                  v-if="ownerPreview"
                  :src="ownerPreview"
                  alt="Founder Photo"
                  class="w-full h-full object-cover"
                />
                <UserCheck v-else class="w-12 h-12 text-slate-400" />
              </div>
              <label class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#F29F67]/20 hover:bg-[#F29F67]/30 text-[#E08A50] text-xs font-semibold rounded-md cursor-pointer transition-colors">
                <Upload class="w-3.5 h-3.5" />
                <span>Upload Portrait</span>
                <input type="file" accept="image/*" class="hidden" @change="handleOwnerFile" />
              </label>
              <p class="text-[11px] text-slate-400 mt-2">Recommended: 800x800px, PNG or WebP</p>
              <p v-if="ownerForm.errors.image" class="text-rose-500 text-xs mt-1">{{ ownerForm.errors.image }}</p>
            </div>

            <!-- Right: Details Form -->
            <div class="md:col-span-2 space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Full Name <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="ownerForm.name"
                    type="text"
                    required
                    placeholder="e.g. John Doe"
                    class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                  />
                  <p v-if="ownerForm.errors.name" class="text-rose-500 text-xs mt-1">{{ ownerForm.errors.name }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                    Designation / Title <span class="text-rose-500">*</span>
                  </label>
                  <input
                    v-model="ownerForm.designation"
                    type="text"
                    required
                    placeholder="e.g. Founder & Lead Camp Director"
                    class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                  />
                  <p v-if="ownerForm.errors.designation" class="text-rose-500 text-xs mt-1">{{ ownerForm.errors.designation }}</p>
                </div>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Experience Summary (Badge)
                </label>
                <input
                  v-model="ownerForm.experience"
                  type="text"
                  placeholder="e.g. 25+ Years NCAA Division 1 Experience"
                  class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                />
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                  Full Biography
                </label>
                <textarea
                  v-model="ownerForm.bio"
                  rows="4"
                  placeholder="Detailed background, achievements, and officiating legacy..."
                  class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Key Statistics Section -->
          <div class="border-t border-slate-100 dark:border-white/[0.06] pt-5 space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
              Highlight Statistics (3 Key Badges)
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <!-- Stat 1 -->
              <div class="p-3 bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md space-y-2">
                <span class="text-[10px] font-bold uppercase text-[#E08A50] dark:text-[#F29F67]">Statistic 1</span>
                <div>
                  <label class="block text-[11px] text-slate-500 mb-1">Value (e.g. 25+)</label>
                  <input
                    v-model="ownerForm.stat_1_value"
                    type="text"
                    placeholder="25+"
                    class="w-full px-2.5 py-1.5 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded text-xs text-slate-900 dark:text-white"
                  />
                </div>
                <div>
                  <label class="block text-[11px] text-slate-500 mb-1">Label (e.g. Years Experience)</label>
                  <input
                    v-model="ownerForm.stat_1_label"
                    type="text"
                    placeholder="Years Officiating"
                    class="w-full px-2.5 py-1.5 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded text-xs text-slate-900 dark:text-white"
                  />
                </div>
              </div>

              <!-- Stat 2 -->
              <div class="p-3 bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md space-y-2">
                <span class="text-[10px] font-bold uppercase text-[#E08A50] dark:text-[#F29F67]">Statistic 2</span>
                <div>
                  <label class="block text-[11px] text-slate-500 mb-1">Value (e.g. 500+)</label>
                  <input
                    v-model="ownerForm.stat_2_value"
                    type="text"
                    placeholder="500+"
                    class="w-full px-2.5 py-1.5 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded text-xs text-slate-900 dark:text-white"
                  />
                </div>
                <div>
                  <label class="block text-[11px] text-slate-500 mb-1">Label (e.g. Trainees Coached)</label>
                  <input
                    v-model="ownerForm.stat_2_label"
                    type="text"
                    placeholder="Referees Trained"
                    class="w-full px-2.5 py-1.5 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded text-xs text-slate-900 dark:text-white"
                  />
                </div>
              </div>

              <!-- Stat 3 -->
              <div class="p-3 bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md space-y-2">
                <span class="text-[10px] font-bold uppercase text-[#E08A50] dark:text-[#F29F67]">Statistic 3</span>
                <div>
                  <label class="block text-[11px] text-slate-500 mb-1">Value (e.g. 10+)</label>
                  <input
                    v-model="ownerForm.stat_3_value"
                    type="text"
                    placeholder="10+"
                    class="w-full px-2.5 py-1.5 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded text-xs text-slate-900 dark:text-white"
                  />
                </div>
                <div>
                  <label class="block text-[11px] text-slate-500 mb-1">Label (e.g. Athletic Conferences)</label>
                  <input
                    v-model="ownerForm.stat_3_label"
                    type="text"
                    placeholder="Conferences Served"
                    class="w-full px-2.5 py-1.5 bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded text-xs text-slate-900 dark:text-white"
                  />
                </div>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- ====================================================
           TAB 3: FEATURE HIGHLIGHTS
      ==================================================== -->
      <div v-show="currentTab === 'features'" class="space-y-6">
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Award class="w-4 h-4 text-[#F29F67]" />
                About Page Feature Highlights
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Total {{ props.data.featureCards?.length || 0 }} feature cards showcasing organizational pillars.
              </p>
            </div>
            <button
              @click="openCreateFeature"
              class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer"
            >
              <Plus class="w-4 h-4" />
              <span>Add Feature Item</span>
            </button>
          </div>

          <div v-if="props.data.featureCards?.length === 0" class="py-12 text-center text-slate-400 text-sm">
            <Award class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
            No feature cards created yet. Click "Add Feature Item" to add one.
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="card in props.data.featureCards"
              :key="card.id"
              class="bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md p-4 flex flex-col justify-between hover:border-[#F29F67]/50 transition-all group"
            >
              <div>
                <div class="flex items-start justify-between gap-3 mb-3">
                  <div class="w-10 h-10 rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] flex items-center justify-center p-1.5 shrink-0">
                    <img v-if="card.image" :src="card.image" alt="Icon" class="max-h-full max-w-full object-contain" />
                    <Award v-else class="w-5 h-5 text-[#F29F67]" />
                  </div>
                  <div class="flex items-center gap-1">
                    <button
                      @click="openEditFeature(card)"
                      class="p-1 rounded text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                      title="Edit Item"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="confirmDeleteFeature(card)"
                      class="p-1 rounded text-rose-500 hover:bg-rose-500/10 transition-colors cursor-pointer"
                      title="Delete Item"
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

              <div class="pt-3 mt-3 border-t border-slate-200 dark:border-white/[0.06] flex items-center justify-between text-[11px] text-slate-400">
                <span>Order: {{ card.order || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ====================================================
           TAB 4: OUR TEAM
      ==================================================== -->
      <div v-show="currentTab === 'team'" class="space-y-6">
        <!-- Team Header Form -->
        <form @submit.prevent="submitTeamHeader" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <Users class="w-4 h-4 text-[#F29F67]" />
                Our Team Section Header
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Headline and subtitle introducing the coaching staff and referee clinicians.
              </p>
            </div>
            <button
              type="submit"
              :disabled="teamHeaderForm.processing"
              class="inline-flex items-center gap-2 px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              <Save class="w-4 h-4" />
              <span>{{ teamHeaderForm.processing ? 'Saving...' : 'Save Header' }}</span>
            </button>
          </div>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Title <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="teamHeaderForm.title"
                type="text"
                required
                placeholder="e.g. Meet Our Expert Clinicians & Instructors"
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              />
              <p v-if="teamHeaderForm.errors.title" class="text-rose-500 text-xs mt-1">{{ teamHeaderForm.errors.title }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                Section Description
              </label>
              <textarea
                v-model="teamHeaderForm.description"
                rows="3"
                placeholder="Introduction to our veteran referees and instructors..."
                class="w-full px-3.5 py-2.5 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
              ></textarea>
              <p v-if="teamHeaderForm.errors.description" class="text-rose-500 text-xs mt-1">{{ teamHeaderForm.errors.description }}</p>
            </div>
          </div>
        </form>

        <!-- Team Members Grid -->
        <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-lg p-5 sm:p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 dark:border-white/[0.06] pb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Team Members</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Total {{ props.data.teamMembers?.length || 0 }} team members active.
              </p>
            </div>
            <button
              @click="openCreateMember"
              class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-colors cursor-pointer"
            >
              <Plus class="w-4 h-4" />
              <span>Add Team Member</span>
            </button>
          </div>

          <div v-if="props.data.teamMembers?.length === 0" class="py-12 text-center text-slate-400 text-sm">
            <Users class="w-10 h-10 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
            No team members added yet. Click "Add Team Member" to add one.
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div
              v-for="member in props.data.teamMembers"
              :key="member.id"
              class="bg-slate-50 dark:bg-[#161622] border border-slate-200 dark:border-white/[0.06] rounded-md p-4 flex flex-col items-center text-center justify-between hover:border-[#F29F67]/50 transition-all group"
            >
              <div class="w-full">
                <div class="flex justify-end gap-1 mb-2">
                  <button
                    @click="openEditMember(member)"
                    class="p-1 rounded text-slate-500 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-white/[0.08] transition-colors cursor-pointer"
                    title="Edit Member"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="confirmDeleteMember(member)"
                    class="p-1 rounded text-rose-500 hover:bg-rose-500/10 transition-colors cursor-pointer"
                    title="Delete Member"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>

                <div class="w-20 h-20 mx-auto rounded-full overflow-hidden border-2 border-slate-200 dark:border-white/[0.08] mb-3 bg-white dark:bg-[#262638] flex items-center justify-center">
                  <img v-if="member.image" :src="member.image" :alt="member.title" class="w-full h-full object-cover" />
                  <Users v-else class="w-8 h-8 text-slate-400" />
                </div>

                <h4 class="font-bold text-slate-900 dark:text-white text-sm">
                  {{ member.title }}
                </h4>
                <p class="text-xs text-[#E08A50] dark:text-[#F29F67] font-medium mt-0.5">
                  {{ member.sub_title }}
                </p>
                <p v-if="member.description" class="text-xs text-slate-500 dark:text-slate-400 mt-2 line-clamp-2">
                  {{ member.description }}
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
    <!-- Feature Modal -->
    <Modal :show="showFeatureModal" :title="editingFeature ? 'Edit Feature Highlight' : 'Add Feature Highlight'" @close="showFeatureModal = false">
      <form @submit.prevent="saveFeature" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Title <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="featureForm.title"
            type="text"
            required
            placeholder="e.g. Intensive Court Drills"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="featureForm.errors.title" class="text-rose-500 text-xs mt-1">{{ featureForm.errors.title }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Description
          </label>
          <textarea
            v-model="featureForm.description"
            rows="3"
            placeholder="Describe this highlight feature..."
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          ></textarea>
          <p v-if="featureForm.errors.description" class="text-rose-500 text-xs mt-1">{{ featureForm.errors.description }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Display Order
          </label>
          <input
            v-model="featureForm.order"
            type="number"
            min="0"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
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
            {{ featureForm.processing ? 'Saving...' : 'Save Item' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Feature Modal -->
    <ConfirmationModal
      :show="showDeleteFeatureModal"
      title="Delete Feature Highlight"
      message="Are you sure you want to permanently delete this feature item?"
      confirm-text="Delete Item"
      type="danger"
      @close="showDeleteFeatureModal = false"
      @confirm="executeDeleteFeature"
    />

    <!-- Team Member Modal -->
    <Modal :show="showTeamModal" :title="editingMember ? 'Edit Team Member' : 'Add Team Member'" @close="showTeamModal = false">
      <form @submit.prevent="saveMember" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Member Name <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="memberForm.title"
            type="text"
            required
            placeholder="e.g. David Sterling"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="memberForm.errors.title" class="text-rose-500 text-xs mt-1">{{ memberForm.errors.title }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Role / Designation <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="memberForm.sub_title"
            type="text"
            required
            placeholder="e.g. Senior Camp Clinician"
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          />
          <p v-if="memberForm.errors.sub_title" class="text-rose-500 text-xs mt-1">{{ memberForm.errors.sub_title }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Brief Bio / Experience
          </label>
          <textarea
            v-model="memberForm.description"
            rows="3"
            placeholder="Experience background, officiating credentials..."
            class="w-full px-3.5 py-2 bg-white dark:bg-[#161622] border border-slate-200 dark:border-white/[0.08] text-slate-900 dark:text-white rounded-md text-xs sm:text-sm focus:outline-hidden focus:border-[#F29F67]"
          ></textarea>
          <p v-if="memberForm.errors.description" class="text-rose-500 text-xs mt-1">{{ memberForm.errors.description }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Member Photo (Optional)
          </label>
          <div class="flex items-center gap-4">
            <div v-if="memberPreview" class="w-14 h-14 rounded-full overflow-hidden border border-slate-200 dark:border-white/[0.08] bg-slate-50 dark:bg-[#161622] flex items-center justify-center shrink-0">
              <img :src="memberPreview" alt="Preview" class="w-full h-full object-cover" />
            </div>
            <input
              type="file"
              accept="image/*"
              @change="handleMemberFile"
              class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-[#F29F67]/20 file:text-[#E08A50] hover:file:bg-[#F29F67]/30"
            />
          </div>
          <p v-if="memberForm.errors.image" class="text-rose-500 text-xs mt-1">{{ memberForm.errors.image }}</p>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 dark:border-white/[0.06]">
          <button
            type="button"
            @click="showTeamModal = false"
            class="px-4 py-2 border border-slate-200 dark:border-white/[0.08] text-slate-600 dark:text-slate-300 text-xs rounded-md hover:bg-slate-50 dark:hover:bg-white/[0.04]"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="memberForm.processing"
            class="px-4 py-2 bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 font-bold text-xs rounded-md shadow-xs disabled:opacity-50"
          >
            {{ memberForm.processing ? 'Saving...' : 'Save Member' }}
          </button>
        </div>
      </form>
    </Modal>

    <!-- Delete Member Modal -->
    <ConfirmationModal
      :show="showDeleteMemberModal"
      title="Delete Team Member"
      message="Are you sure you want to permanently delete this team member from the roster?"
      confirm-text="Delete Member"
      type="danger"
      @close="showDeleteMemberModal = false"
      @confirm="executeDeleteMember"
    />
  </AdminLayout>
</template>
