<script setup>
import { computed } from 'vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { Mail, LogOut, Loader2, Sparkles, CheckCircle2, Send } from 'lucide-vue-next';

const props = defineProps({
  status: {
    type: String,
    default: null,
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

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');

const form = useForm({});

const submit = () => {
  form.post('/email/verification-notification');
};

const logoutForm = useForm({});
const logout = () => {
  logoutForm.post('/logout');
};
</script>

<template>
  <Head title="Verify Email — Check Your Inbox" />

  <div class="min-h-screen relative flex flex-col justify-center items-center p-4 sm:p-6 bg-[#12121A] text-slate-100 selection:bg-[#F29F67]/30 selection:text-[#F29F67] overflow-hidden">
    <!-- Ambient Background Glow -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-[#F29F67]/15 via-[#E0B50F]/10 to-transparent rounded-full blur-3xl opacity-60"></div>
      <div class="absolute -bottom-40 right-10 w-[450px] h-[450px] bg-gradient-to-br from-[#3B8FF3]/15 to-transparent rounded-full blur-3xl opacity-50"></div>
      <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px] opacity-40"></div>
    </div>

    <!-- Centered Card -->
    <div class="relative w-full max-w-[460px]">
      <div class="bg-[#1E1E2C] border border-white/[0.08] rounded-lg shadow-2xl p-6 sm:p-8 backdrop-blur-xl text-center">
        
        <!-- Brand & Icon -->
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-[#F29F67]/10 border border-[#F29F67]/20 text-[#F29F67] mb-4">
          <Mail class="w-7 h-7" />
        </div>

        <h1 class="text-xl font-bold text-white">Verify Your Email</h1>
        
        <p class="text-xs text-slate-400 mt-2 leading-relaxed max-w-sm mx-auto">
          Thanks for signing up! Before getting started, please check your inbox and click the verification link we just emailed to you.
        </p>

        <!-- Link Sent Notification -->
        <div
          v-if="verificationLinkSent"
          class="mt-4 p-3 rounded-md bg-[#34B1AA]/10 border border-[#34B1AA]/20 text-[#34B1AA] text-xs flex items-center justify-center gap-2"
        >
          <CheckCircle2 class="w-4 h-4 shrink-0" />
          <span>A new verification link has been sent to your email address.</span>
        </div>

        <!-- Resend Button -->
        <form @submit.prevent="submit" class="mt-6">
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 rounded-md bg-[#F29F67] hover:bg-[#E08A50] text-white font-semibold text-xs uppercase tracking-wider shadow-sm transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
            <Send v-else class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Sending...' : 'Resend Verification Email' }}</span>
          </button>
        </form>

        <!-- Log Out Option -->
        <div class="mt-5 pt-4 border-t border-white/[0.06] flex items-center justify-center">
          <button
            type="button"
            @click="logout"
            :disabled="logoutForm.processing"
            class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
          >
            <LogOut class="w-3.5 h-3.5" />
            <span>Sign Out</span>
          </button>
        </div>

      </div>
    </div>
  </div>
</template>
