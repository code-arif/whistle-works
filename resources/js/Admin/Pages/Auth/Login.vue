<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, Sparkles, ArrowRight, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
  canResetPassword: {
    type: Boolean,
    default: true,
  },
  status: {
    type: String,
    default: null,
  },
});

const page = usePage();
const showPassword = ref(false);
const logoError = ref(false);

const siteLogo = computed(() => {
  const logo = page.props.settings?.logo;
  if (!logo || typeof logo !== 'string') return null;
  if (logo.startsWith('http') || logo.startsWith('data:')) return logo;
  return '/' + logo.replace(/^\//, '');
});

const siteTitle = computed(() => page.props.settings?.site_title || 'WHISTLE WORKS');

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Sign In — Executive Portal" />

  <div class="min-h-screen relative flex flex-col justify-center items-center p-4 sm:p-6 bg-[#12121A] text-slate-100 selection:bg-[#F29F67]/30 selection:text-[#F29F67] overflow-hidden">
    
    <!-- Background Ambient Glow & Mesh Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-[#F29F67]/15 via-[#E0B50F]/10 to-transparent rounded-full blur-3xl opacity-60"></div>
      <div class="absolute -bottom-40 right-10 w-[450px] h-[450px] bg-gradient-to-br from-[#3B8FF3]/15 to-transparent rounded-full blur-3xl opacity-50"></div>
      <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
    </div>

    <!-- Centered Unified Login Card -->
    <div class="relative w-full max-w-[420px]">
      <div class="bg-[#1E1E2C] border border-white/[0.08] rounded-lg shadow-2xl p-6 sm:p-8 backdrop-blur-xl">
        
        <!-- Integrated Header: Brand + Title -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center justify-center gap-2.5 mb-4">
            <div class="w-10 h-10 rounded-md bg-white dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] p-1.5 flex items-center justify-center shadow-xs overflow-hidden shrink-0">
              <img 
                v-if="siteLogo && !logoError" 
                :src="siteLogo" 
                :alt="siteTitle" 
                @error="logoError = true"
                class="w-full h-full object-contain"
              />
              <Sparkles v-else class="w-5 h-5 text-[#F29F67]" />
            </div>
            <span class="font-bold text-base tracking-tight text-white uppercase font-display">
              {{ siteTitle }}
            </span>
          </div>

          <h1 class="text-lg sm:text-xl font-bold tracking-tight text-white">
            Welcome back
          </h1>
          <p class="text-xs text-slate-400 mt-1">
            Please enter your details to sign in
          </p>
        </div>

        <!-- Status Notification (e.g., password reset successful) -->
        <div v-if="status" class="mb-5 p-3 rounded-md bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs flex items-center gap-2">
          <ShieldCheck class="w-4 h-4 shrink-0 text-emerald-400" />
          <span>{{ status }}</span>
        </div>

        <!-- General Form Error Alert -->
        <div v-if="form.errors.email && !form.errors.password" class="mb-5 p-3 rounded-md bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
          {{ form.errors.email }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          
          <!-- Email Input -->
          <div>
            <label for="email" class="block text-xs font-semibold text-slate-300 mb-1.5">
              Email Address
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autofocus
                autocomplete="username"
                placeholder="admin@whistleworks.org"
                :class="[
                  'w-full pl-9 pr-3.5 py-2.5 bg-[#161622] border rounded-md text-xs sm:text-sm text-white placeholder-slate-500 transition-all duration-150 focus:outline-hidden',
                  form.errors.email ? 'border-rose-500 focus:border-rose-500' : 'border-white/[0.08] focus:border-[#F29F67]'
                ]"
              />
            </div>
            <p v-if="form.errors.email && form.errors.password" class="mt-1.5 text-xs text-rose-400">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Input -->
          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label for="password" class="block text-xs font-semibold text-slate-300">
                Password
              </label>
              <Link
                v-if="canResetPassword"
                href="/forgot-password"
                class="text-[11px] font-medium text-[#F29F67] hover:text-[#E08A50] transition-colors"
              >
                Forgot password?
              </Link>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <Lock class="w-4 h-4" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                :class="[
                  'w-full pl-9 pr-10 py-2.5 bg-[#161622] border rounded-md text-xs sm:text-sm text-white placeholder-slate-500 transition-all duration-150 focus:outline-hidden',
                  form.errors.password ? 'border-rose-500 focus:border-rose-500' : 'border-white/[0.08] focus:border-[#F29F67]'
                ]"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200 transition-colors cursor-pointer"
                title="Toggle password visibility"
              >
                <component :is="showPassword ? EyeOff : Eye" class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember Me Checkbox -->
          <div class="flex items-center justify-between pt-0.5">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                v-model="form.remember"
                type="checkbox"
                class="w-4 h-4 rounded-sm border-white/[0.15] bg-[#161622] text-[#F29F67] focus:ring-[#F29F67] focus:ring-offset-[#1E1E2C] cursor-pointer"
              />
              <span class="text-xs text-slate-300">Keep me signed in</span>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-2.5 px-4 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] text-slate-950 font-bold text-xs sm:text-sm rounded-md shadow-xs transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <span v-if="form.processing" class="w-4 h-4 border-2 border-slate-950/30 border-t-slate-950 rounded-full animate-spin"></span>
              <span>{{ form.processing ? 'Signing In...' : 'Sign In' }}</span>
              <ArrowRight v-if="!form.processing" class="w-4 h-4 stroke-[2.5]" />
            </button>
          </div>
        </form>

        <!-- Security Footer inside Card -->
        <div class="mt-6 pt-5 border-t border-white/[0.06] flex items-center justify-center gap-1.5 text-[11px] text-slate-400">
          <ShieldCheck class="w-3.5 h-3.5 text-[#34B1AA]" />
          <span>Encrypted Session</span>
        </div>
      </div>

      <!-- Outer Minimal Copyright -->
      <div class="mt-5 text-center text-xs text-slate-500">
        &copy; {{ new Date().getFullYear() }} {{ siteTitle }}. All rights reserved.
      </div>
    </div>
  </div>
</template>
