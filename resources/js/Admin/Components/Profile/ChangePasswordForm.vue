<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  KeyRound, 
  Lock, 
  Eye, 
  EyeOff, 
  ShieldCheck, 
  CheckCircle2, 
  AlertTriangle 
} from 'lucide-vue-next';

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
  form.post('/admin/v2/profile/password', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    }
  });
};
</script>

<template>
  <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-6 shadow-sm">
    <!-- Header -->
    <div class="flex items-center gap-3 pb-4 mb-6 border-b border-slate-100 dark:border-white/[0.06]">
      <div class="w-10 h-10 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67] shrink-0">
        <KeyRound class="w-5 h-5" />
      </div>
      <div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">Security & Password</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Ensure your executive credentials remain protected with a strong passphrase.</p>
      </div>
    </div>

    <!-- Security Tips Alert -->
    <div class="p-3.5 rounded-lg bg-[#3B8FF3]/10 border border-[#3B8FF3]/20 flex items-start gap-3 mb-6">
      <ShieldCheck class="w-4 h-4 text-[#3B8FF3] shrink-0 mt-0.5" />
      <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
        Password must be at least <strong>8 characters</strong> long. For maximum security, include a combination of letters, numbers, and special symbols.
      </p>
    </div>

    <!-- Form -->
    <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
      
      <!-- Current Password -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Current Password <span class="text-rose-500">*</span>
          </label>
          <button 
            type="button" 
            @click="showCurrentPassword = !showCurrentPassword"
            class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
          >
            <EyeOff v-if="showCurrentPassword" class="w-3 h-3" />
            <Eye v-else class="w-3 h-3" />
            <span>{{ showCurrentPassword ? 'Hide' : 'Reveal' }}</span>
          </button>
        </div>
        <div class="relative">
          <input 
            v-model="form.current_password" 
            :type="showCurrentPassword ? 'text' : 'password'" 
            required 
            placeholder="••••••••"
            class="w-full pl-9 pr-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.current_password }"
          />
          <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
        </div>
        <p v-if="form.errors.current_password" class="text-xs text-rose-500 mt-1">
          {{ form.errors.current_password }}
        </p>
      </div>

      <!-- New Password -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            New Password <span class="text-rose-500">*</span>
          </label>
          <button 
            type="button" 
            @click="showNewPassword = !showNewPassword"
            class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
          >
            <EyeOff v-if="showNewPassword" class="w-3 h-3" />
            <Eye v-else class="w-3 h-3" />
            <span>{{ showNewPassword ? 'Hide' : 'Reveal' }}</span>
          </button>
        </div>
        <div class="relative">
          <input 
            v-model="form.password" 
            :type="showNewPassword ? 'text' : 'password'" 
            required 
            minlength="8"
            placeholder="••••••••"
            class="w-full pl-9 pr-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.password }"
          />
          <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
        </div>
        <p v-if="form.errors.password" class="text-xs text-rose-500 mt-1">
          {{ form.errors.password }}
        </p>
      </div>

      <!-- Confirm New Password -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
            Confirm New Password <span class="text-rose-500">*</span>
          </label>
          <button 
            type="button" 
            @click="showConfirmPassword = !showConfirmPassword"
            class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
          >
            <EyeOff v-if="showConfirmPassword" class="w-3 h-3" />
            <Eye v-else class="w-3 h-3" />
            <span>{{ showConfirmPassword ? 'Hide' : 'Reveal' }}</span>
          </button>
        </div>
        <div class="relative">
          <input 
            v-model="form.password_confirmation" 
            :type="showConfirmPassword ? 'text' : 'password'" 
            required 
            minlength="8"
            placeholder="••••••••"
            class="w-full pl-9 pr-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-md focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            :class="{ 'border-rose-500 focus:ring-rose-500/50': form.errors.password_confirmation }"
          />
          <Lock class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" />
        </div>
        <p v-if="form.errors.password_confirmation" class="text-xs text-rose-500 mt-1">
          {{ form.errors.password_confirmation }}
        </p>
      </div>

      <!-- Action Footer -->
      <div class="flex items-center justify-end gap-3 pt-2">
        <button 
          type="submit" 
          :disabled="form.processing"
          class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-bold text-slate-950 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] shadow-xs disabled:opacity-60 transition-all cursor-pointer"
        >
          <Lock class="w-4 h-4" />
          <span>{{ form.processing ? 'Updating Password...' : 'Update Password' }}</span>
        </button>
      </div>
    </form>
  </div>
</template>
