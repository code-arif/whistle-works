<script setup>
import { useForm } from '@inertiajs/vue3';
import { 
  User, 
  Mail, 
  Phone, 
  MapPin, 
  FileText, 
  Save, 
  Check 
} from 'lucide-vue-next';

const props = defineProps({
  profile: {
    type: Object,
    required: true
  }
});

const form = useForm({
  first_name: props.profile.first_name || '',
  last_name: props.profile.last_name || '',
  email: props.profile.email || '',
  phone: props.profile.phone || '',
  address: props.profile.address || '',
  biography: props.profile.biography || '',
});

const submit = () => {
  form.post('/admin/v2/profile/update', {
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-6 shadow-sm">
    <!-- Header -->
    <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-100 dark:border-white/[0.06]">
      <div class="w-9 h-9 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67]">
        <User class="w-4.5 h-4.5" />
      </div>
      <div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Personal & Contact Details</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Update your account name, contact coordinates, and admin profile.</p>
      </div>
    </div>

    <!-- Form -->
    <form @submit.prevent="submit" class="space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
        
        <!-- First Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            First Name <span class="text-rose-500">*</span>
          </label>
          <input 
            v-model="form.first_name" 
            type="text" 
            required 
            placeholder="First Name"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.first_name }"
          />
          <p v-if="form.errors.first_name" class="text-xs text-rose-500 mt-1">
            {{ form.errors.first_name }}
          </p>
        </div>

        <!-- Last Name -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Last Name
          </label>
          <input 
            v-model="form.last_name" 
            type="text" 
            placeholder="Last Name"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.last_name }"
          />
          <p v-if="form.errors.last_name" class="text-xs text-rose-500 mt-1">
            {{ form.errors.last_name }}
          </p>
        </div>

        <!-- Email Address -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Email Address <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input 
              v-model="form.email" 
              type="email" 
              required 
              placeholder="admin@whistleworks.org"
              class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.email }"
            />
            <Mail class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
          </div>
          <p v-if="form.errors.email" class="text-xs text-rose-500 mt-1">
            {{ form.errors.email }}
          </p>
        </div>

        <!-- Phone Number -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Phone Number
          </label>
          <div class="relative">
            <input 
              v-model="form.phone" 
              type="text" 
              placeholder="+1 (555) 000-0000"
              class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.phone }"
            />
            <Phone class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
          </div>
          <p v-if="form.errors.phone" class="text-xs text-rose-500 mt-1">
            {{ form.errors.phone }}
          </p>
        </div>

        <!-- Address -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Office / Physical Address
          </label>
          <div class="relative">
            <input 
              v-model="form.address" 
              type="text" 
              placeholder="123 Referee Blvd, Suite 400"
              class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.address }"
            />
            <MapPin class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
          </div>
          <p v-if="form.errors.address" class="text-xs text-rose-500 mt-1">
            {{ form.errors.address }}
          </p>
        </div>

        <!-- Biography -->
        <div class="sm:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Short Biography
          </label>
          <textarea 
            v-model="form.biography" 
            rows="3" 
            placeholder="Brief bio or role responsibility notes..."
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors resize-none"
            :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.biography }"
          ></textarea>
          <p v-if="form.errors.biography" class="text-xs text-rose-500 mt-1">
            {{ form.errors.biography }}
          </p>
        </div>

      </div>

      <!-- Action Footer -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <button 
          type="submit" 
          :disabled="form.processing"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-bold text-slate-950 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] shadow-xs disabled:opacity-60 transition-all cursor-pointer"
        >
          <Save class="w-4 h-4" />
          <span>{{ form.processing ? 'Saving Details...' : 'Save Profile Changes' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>
