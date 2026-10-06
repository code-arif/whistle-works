<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { KeyRound, ArrowLeft, Loader2, Sparkles, RefreshCw } from 'lucide-vue-next';

const props = defineProps({
  email: {
    type: String,
    default: '',
  },
});

const page = usePage();

const siteLogo = computed(() => {
  const logo = page.props.settings?.logo;
  if (!logo || typeof logo !== 'string') return null;
  if (logo.startsWith('http') || logo.startsWith('data:')) return logo;
  return '/' + logo.replace(/^\//, '');
});

const siteTitle = computed(() => page.props.settings?.site_title || 'WHISTLE WORKS');

const form = useForm({
  email: props.email,
  otp: '',
});

const resendForm = useForm({
  email: props.email,
});

const submit = () => {
  form.post('/verify/otp');
};

const resendOtp = () => {
  resendForm.post('/verify/otp/resend');
};
</script>

<template>
  <Head title="Verify OTP — One-Time Password" />

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
          <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-[#F29F67]/10 border border-[#F29F67]/20 shadow-inner mb-3">
            <KeyRound class="w-6 h-6 text-[#F29F67]" />
          </div>
          <h1 class="text-xl font-bold text-white">Enter Verification Code</h1>
          <p class="text-xs text-slate-400 mt-1.5 leading-relaxed">
            Please enter the one-time verification code sent to <strong class="text-white">{{ email }}</strong>
          </p>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5">
              OTP Code
            </label>
            <input
              v-model="form.otp"
              type="text"
              required
              autofocus
              placeholder="e.g. 123456"
              class="w-full text-center tracking-[0.5em] text-lg font-mono px-4 py-3 bg-white/[0.04] border border-white/[0.1] rounded-md text-white placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-[#F29F67] focus:border-[#F29F67] transition"
            />
            <p v-if="form.errors.otp" class="text-xs text-rose-400 mt-1.5 text-center">
              {{ form.errors.otp }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white font-semibold text-xs uppercase tracking-wider shadow-sm transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 mt-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            <span>{{ form.processing ? 'Verifying...' : 'Verify Code' }}</span>
          </button>
        </form>

        <!-- Resend Code -->
        <div class="mt-6 pt-5 border-t border-white/[0.06] flex items-center justify-between text-xs">
          <Link
            href="/login"
            class="inline-flex items-center gap-1 text-slate-400 hover:text-white transition"
          >
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Sign In</span>
          </Link>

          <button
            type="button"
            @click="resendOtp"
            :disabled="resendForm.processing"
            class="inline-flex items-center gap-1.5 text-[#F29F67] hover:underline cursor-pointer disabled:opacity-50"
          >
            <RefreshCw class="w-3.5 h-3.5" :class="resendForm.processing ? 'animate-spin' : ''" />
            <span>Resend OTP</span>
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
