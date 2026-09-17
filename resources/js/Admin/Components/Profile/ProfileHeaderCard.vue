<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  Camera, 
  Mail, 
  Phone, 
  Calendar, 
  Loader2
} from 'lucide-vue-next';

const props = defineProps({
  profile: {
    type: Object,
    required: true
  }
});

const fileInput = ref(null);
const previewUrl = ref(null);

const avatarForm = useForm({
  avatar: null,
});

const triggerFileSelect = () => {
  if (fileInput.value) {
    fileInput.value.click();
  }
};

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  // Local instant preview
  previewUrl.value = URL.createObjectURL(file);
  avatarForm.avatar = file;

  avatarForm.post('/admin/v2/profile/avatar', {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      // previewUrl will be updated from refreshed props
    },
    onError: () => {
      previewUrl.value = null;
    }
  });
};
</script>

<template>
  <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-6 shadow-sm relative overflow-hidden">
    <!-- Top subtle ambient gradient background accent -->
    <div class="absolute top-0 left-0 right-0 h-28 bg-gradient-to-r from-[#F29F67]/20 via-[#34B1AA]/15 to-transparent pointer-events-none"></div>

    <div class="relative flex flex-col sm:flex-row items-center sm:items-end justify-between gap-6 pt-6">
      
      <!-- Avatar & Core Identity -->
      <div class="flex flex-col sm:flex-row items-center sm:items-end gap-5 text-center sm:text-left">
        
        <!-- Avatar Wrapper with Camera Trigger -->
        <div class="relative group cursor-pointer" @click="triggerFileSelect">
          <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-xl overflow-hidden ring-4 ring-white dark:ring-[#1E1E2C] shadow-lg bg-gradient-to-tr from-[#F29F67] to-[#E0B50F] flex items-center justify-center text-white font-bold text-3xl">
            <img 
              v-if="previewUrl || profile.avatar" 
              :src="previewUrl || profile.avatar" 
              :alt="profile.full_name"
              class="w-full h-full object-cover" 
            />
            <span v-else>{{ profile.full_name?.charAt(0) || 'A' }}</span>
          </div>

          <!-- Upload overlay -->
          <div class="absolute inset-0 rounded-xl bg-black/50 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white transition-opacity duration-150 backdrop-blur-xs">
            <Loader2 v-if="avatarForm.processing" class="w-6 h-6 animate-spin" />
            <template v-else>
              <Camera class="w-6 h-6 mb-1 text-white" />
              <span class="text-[10px] font-medium uppercase tracking-wider">Change</span>
            </template>
          </div>

          <!-- Bottom Camera Badge Button -->
          <button 
            type="button" 
            class="absolute -bottom-1 -right-1 w-8 h-8 rounded-lg bg-[#F29F67] hover:bg-[#E08A50] text-slate-950 shadow-md flex items-center justify-center border-2 border-white dark:border-[#1E1E2C] transition-colors"
            title="Update Profile Picture"
          >
            <Camera class="w-4 h-4" />
          </button>

          <input 
            ref="fileInput" 
            type="file" 
            class="hidden" 
            accept="image/png,image/jpeg,image/webp" 
            @change="handleFileChange"
          />
        </div>

        <!-- Identity Details -->
        <div>
          <div class="flex items-center justify-center sm:justify-start gap-2.5 flex-wrap mb-1.5">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
              {{ profile.full_name }}
            </h2>
          </div>

          <div class="flex items-center justify-center sm:justify-start gap-4 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
            <span class="flex items-center gap-1 font-mono">
              <Mail class="w-3.5 h-3.5 text-slate-400" />
              {{ profile.email }}
            </span>
            <span v-if="profile.phone" class="flex items-center gap-1 font-mono">
              <Phone class="w-3.5 h-3.5 text-slate-400" />
              {{ profile.phone }}
            </span>
            <span v-if="profile.created_at" class="flex items-center gap-1">
              <Calendar class="w-3.5 h-3.5 text-slate-400" />
              Joined {{ profile.created_at }}
            </span>
          </div>
        </div>
      </div>

    </div>

    <!-- Error notice if avatar fails -->
    <p v-if="avatarForm.errors.avatar" class="text-xs text-rose-500 mt-3 text-center sm:text-left">
      {{ avatarForm.errors.avatar }}
    </p>
  </div>
</template>
