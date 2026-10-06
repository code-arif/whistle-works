<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  Building2, 
  Globe, 
  Mail, 
  Phone, 
  MapPin, 
  Save, 
  UploadCloud, 
  Image as ImageIcon,
  CheckCircle2,
  AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const form = useForm({
  name: props.settings.name || '',
  title: props.settings.title || '',
  description: props.settings.description || '',
  keywords: props.settings.keywords || '',
  author: props.settings.author || '',
  phone: props.settings.phone || '',
  email: props.settings.email || '',
  address: props.settings.address || '',
  copyright: props.settings.copyright || '',
  logo_width: props.settings.logo_width || 180,
  logo_height: props.settings.logo_height || 50,
  logo: null,
  favicon: null,
  thumbnail: null,
});

const logoPreview = ref(props.settings.logo || null);
const faviconPreview = ref(props.settings.favicon || null);
const thumbnailPreview = ref(props.settings.thumbnail || null);

const handleFileSelect = (event, field) => {
  const file = event.target.files[0];
  if (!file) return;

  form[field] = file;

  const reader = new FileReader();
  reader.onload = (e) => {
    if (field === 'logo') logoPreview.value = e.target.result;
    if (field === 'favicon') faviconPreview.value = e.target.result;
    if (field === 'thumbnail') thumbnailPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const submit = () => {
  form.post('/admin/v2/settings/general', {
    preserveScroll: true,
    forceFormData: true,
  });
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Section 1: Brand & Site Identity -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67]">
          <Building2 class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Brand & Site Identity</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Configure your primary organization name, meta titles, and SEO metadata.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            App / Organization Name <span class="text-rose-500">*</span>
          </label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="Whistle Works"
          />
          <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.name }}</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Site Meta Title
          </label>
          <input
            v-model="form.title"
            type="text"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="Official Referee & Camp Management Portal"
          />
          <span v-if="form.errors.title" class="text-xs text-rose-500 mt-1 block">{{ form.errors.title }}</span>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Meta Description
          </label>
          <textarea
            v-model="form.description"
            rows="2"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors resize-none"
            placeholder="Executive referee assignments, training camp schedules, and sport evaluations..."
          ></textarea>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Meta Keywords
          </label>
          <input
            v-model="form.keywords"
            type="text"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="referee, sports, camp, whistle works"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Author
          </label>
          <input
            v-model="form.author"
            type="text"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="Whistle Works Executive Team"
          />
        </div>
      </div>
    </div>

    <!-- Section 2: Visual Branding & Assets -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#3B8FF3]/10 flex items-center justify-center text-[#3B8FF3]">
          <ImageIcon class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Branding Assets & Imagery</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Upload high-resolution logos, favicons, and social thumbnail cards.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Main Logo -->
        <div class="space-y-3">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Primary Logo
          </label>
          <div class="border-2 border-dashed border-slate-200 dark:border-white/[0.1] rounded-xl p-4 flex flex-col items-center justify-center text-center bg-slate-50/50 dark:bg-[#262638]/50 hover:border-[#F29F67]/50 transition-colors">
            <div v-if="logoPreview" class="mb-3 max-h-16 flex items-center justify-center overflow-hidden">
              <img :src="logoPreview" alt="Logo Preview" class="max-h-14 max-w-full object-contain" />
            </div>
            <div v-else class="w-12 h-12 rounded-lg bg-slate-200/60 dark:bg-white/[0.06] flex items-center justify-center text-slate-400 mb-2">
              <UploadCloud class="w-6 h-6" />
            </div>
            <label class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-medium text-[#F29F67] bg-[#F29F67]/10 hover:bg-[#F29F67]/20 transition-colors">
              <span>{{ logoPreview ? 'Replace Logo' : 'Upload Logo' }}</span>
              <input type="file" accept="image/*" class="hidden" @change="(e) => handleFileSelect(e, 'logo')" />
            </label>
            <p class="text-[10px] text-slate-400 mt-2">PNG, SVG, or WEBP (Max 5MB)</p>
          </div>

          <div class="grid grid-cols-2 gap-2 pt-1">
            <div>
              <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-1">Width (px)</span>
              <input 
                v-model="form.logo_width" 
                type="number" 
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md text-slate-900 dark:text-white"
              />
            </div>
            <div>
              <span class="text-[11px] text-slate-500 dark:text-slate-400 block mb-1">Height (px)</span>
              <input 
                v-model="form.logo_height" 
                type="number" 
                class="w-full px-2.5 py-1.5 text-xs bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md text-slate-900 dark:text-white"
              />
            </div>
          </div>
        </div>

        <!-- Favicon -->
        <div class="space-y-3">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Browser Favicon
          </label>
          <div class="border-2 border-dashed border-slate-200 dark:border-white/[0.1] rounded-xl p-4 flex flex-col items-center justify-center text-center bg-slate-50/50 dark:bg-[#262638]/50 hover:border-[#F29F67]/50 transition-colors">
            <div v-if="faviconPreview" class="mb-3 w-10 h-10 flex items-center justify-center p-1 bg-white dark:bg-[#1E1E2C] rounded-lg border border-slate-200 dark:border-white/[0.08] shadow-xs">
              <img :src="faviconPreview" alt="Favicon Preview" class="w-8 h-8 object-contain" />
            </div>
            <div v-else class="w-12 h-12 rounded-lg bg-slate-200/60 dark:bg-white/[0.06] flex items-center justify-center text-slate-400 mb-2">
              <UploadCloud class="w-6 h-6" />
            </div>
            <label class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-medium text-[#F29F67] bg-[#F29F67]/10 hover:bg-[#F29F67]/20 transition-colors">
              <span>{{ faviconPreview ? 'Replace Favicon' : 'Upload Favicon' }}</span>
              <input type="file" accept="image/png,image/x-icon,image/jpeg,image/webp" class="hidden" @change="(e) => handleFileSelect(e, 'favicon')" />
            </label>
            <p class="text-[10px] text-slate-400 mt-2">ICO, PNG, or WEBP (Square recommended)</p>
          </div>
        </div>

        <!-- Thumbnail -->
        <div class="space-y-3">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Social Share Thumbnail
          </label>
          <div class="border-2 border-dashed border-slate-200 dark:border-white/[0.1] rounded-xl p-4 flex flex-col items-center justify-center text-center bg-slate-50/50 dark:bg-[#262638]/50 hover:border-[#F29F67]/50 transition-colors">
            <div v-if="thumbnailPreview" class="mb-3 max-h-16 flex items-center justify-center overflow-hidden rounded-md">
              <img :src="thumbnailPreview" alt="Thumbnail Preview" class="max-h-14 max-w-full object-cover rounded" />
            </div>
            <div v-else class="w-12 h-12 rounded-lg bg-slate-200/60 dark:bg-white/[0.06] flex items-center justify-center text-slate-400 mb-2">
              <UploadCloud class="w-6 h-6" />
            </div>
            <label class="cursor-pointer px-3 py-1.5 rounded-lg text-xs font-medium text-[#F29F67] bg-[#F29F67]/10 hover:bg-[#F29F67]/20 transition-colors">
              <span>{{ thumbnailPreview ? 'Replace Thumbnail' : 'Upload Thumbnail' }}</span>
              <input type="file" accept="image/*" class="hidden" @change="(e) => handleFileSelect(e, 'thumbnail')" />
            </label>
            <p class="text-[10px] text-slate-400 mt-2">1200x630px recommended for OpenGraph</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 3: Contact Details & Legal -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#34B1AA]/10 flex items-center justify-center text-[#34B1AA]">
          <Phone class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Contact Information & Legal</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Display information in invoices, emails, and platform footers.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Support Email
          </label>
          <div class="relative">
            <Mail class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="form.email"
              type="email"
              class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="support@whistleworks.org"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Support Phone
          </label>
          <div class="relative">
            <Phone class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="form.phone"
              type="text"
              class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="+1 (800) 555-0199"
            />
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Official Address
          </label>
          <div class="relative">
            <MapPin class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="form.address"
              type="text"
              class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="123 Referee Blvd, Suite 400, Austin, TX 78701"
            />
          </div>
        </div>

        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Copyright Notice
          </label>
          <input
            v-model="form.copyright"
            type="text"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="© 2026 Whistle Works Inc. All rights reserved."
          />
        </div>
      </div>
    </div>

    <!-- Submit Button Bar -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button
        type="submit"
        :disabled="form.processing"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-bold text-slate-950 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] shadow-xs disabled:opacity-60 transition-all cursor-pointer"
      >
        <Save class="w-4 h-4" />
        <span>{{ form.processing ? 'Saving Changes...' : 'Save General Settings' }}</span>
      </button>
    </div>
  </form>
</template>
