<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { Mail, Lock, Eye, EyeOff, Loader2, Sparkles, ArrowRight, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
  email: {
    type: String,
    default: '',
  },
});

const page = usePage();
const showPassword = ref(false);
const showConfirmPassword = ref(false);

const siteLogo = computed(() => {
  const logo = page.props.settings?.logo;
  if (!logo || typeof logo !== 'string') return null;
  if (logo.startsWith('http') || logo.startsWith('data:')) return logo;
  return '/' + logo.replace(/^\//, '');
});

const siteTitle = computed(() => page.props.settings?.site_title || 'WHISTLE WORKS');

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/reset-password', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Reset Password — Set New Credentials" />

  <div class="min-h-screen relative flex flex-col justify-center items-center p-4 sm:p-6 bg-[#12121A] text-slate-100 selection:bg-[#F29F67]/30 selection:text-[#F29F67] overflow-hidden">
    <!-- Ambient Background Glow -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-[#F29F67]/15 via-[#E0B50F]/10 to-transparent rounded-full blur-3xl opacity-60"></div>
      <div class="absolute -bottom-40 right-10 w-[450px] h-[450px] bg-gradient-to-br from-[#3B8FF3]/15 to-transparent rounded-full blur-3xl opacity-50"></div>
      <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
    </div>

    <!-- Centered Card -->
    <div class="relative w-full max-w-[420px]">
      <div class="bg-[#1E1E2C] border border-white/[0.08] rounded-lg shadow-2xl p-6 sm:p-8 backdrop-blur-xl">
        
        <!-- Brand Header -->
        <div class="text-center mb-6">
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-white/[0.04] border border-white/[0.08] shadow-inner mb-3">
            <img
              v-if="siteLogo"
              :src="siteLogo"
              :alt="siteTitle"
              class="w-7 h-7 object-contain"
            />
            <Sparkles v-else class="w-6 h-6 text-[#F29F67]" />
          </div>
          <h2 class="text-lg font-bold text-white tracking-tight uppercase">
            {{ siteTitle }}
          </h2>
          <h1 class="text-xl font-bold text-white mt-1">Set New Password</h1>
          <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
            Choose a strong, secure password for your account.
          </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email Input -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
              Email Address
            </label>
            <div class="relative">
              <input
                v-model="form.email"
                type="email"
                required
                autocomplete="username"
                class="w-full pl-10 pr-4 py-2.5 bg-white/[0.04] border border-white/[0.1] rounded-md text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              />
              <Mail class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
            </div>
            <p v-if="form.errors.email" class="text-xs text-rose-400 mt-1">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- New Password Input -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
              New Password
            </label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="••••••••••••"
                class="w-full pl-10 pr-10 py-2.5 bg-white/[0.04] border border-white/[0.1] rounded-md text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              />
              <Lock class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-3 text-slate-400 hover:text-slate-200"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs text-rose-400 mt-1">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Confirm Password Input -->
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
              Confirm Password
            </label>
            <div class="relative">
              <input
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                autocomplete="new-password"
                placeholder="••••••••••••"
                class="w-full pl-10 pr-10 py-2.5 bg-white/[0.04] border border-white/[0.1] rounded-md text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
              />
              <ShieldCheck class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-3 text-slate-400 hover:text-slate-200"
              >
                <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password_confirmation" class="text-xs text-rose-400 mt-1">
              {{ form.errors.password_confirmation }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white font-semibold text-xs uppercase tracking-wider shadow-sm hover:shadow-md transition duration-150 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 mt-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            <span>{{ form.processing ? 'Resetting Password...' : 'Reset Password' }}</span>
          </button>
        </form>

        <!-- Back to Sign In Link -->
        <div class="mt-6 pt-5 border-t border-white/[0.06] text-center">
          <Link
            href="/login"
            class="text-xs font-medium text-slate-400 hover:text-[#F29F67] transition-colors"
          >
            Back to Sign In
          </Link>
        </div>

      </div>
    </div>
  </div>
</template>
