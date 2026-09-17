<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  Globe, 
  Flame, 
  Map, 
  ShieldCheck, 
  Save, 
  Eye, 
  EyeOff, 
  ExternalLink 
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const form = useForm({
  google_client_id: props.settings.google_client_id || '',
  google_client_secret: props.settings.google_client_secret || '',
  google_redirect_uri: props.settings.google_redirect_uri || '',
  firebase_credentials: props.settings.firebase_credentials || '',
  google_maps_api_key: props.settings.google_maps_api_key || '',
  recaptcha_site_key: props.settings.recaptcha_site_key || '',
  recaptcha_secret_key: props.settings.recaptcha_secret_key || '',
});

const showGoogleSecret = ref(false);
const showRecaptchaSecret = ref(false);

const submit = () => {
  form.post('/admin/v2/settings/integrations', {
    preserveScroll: true,
  });
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Section 1: Google OAuth Social Login -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-rose-500/10 flex items-center justify-center text-rose-500">
            <Globe class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Google OAuth Authentication</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Enables one-click "Sign in with Google" for Referees and Evaluators.</p>
          </div>
        </div>

        <a 
          href="https://console.cloud.google.com/apis/credentials" 
          target="_blank" 
          rel="noopener noreferrer" 
          class="inline-flex items-center gap-1.5 text-xs text-[#F29F67] hover:underline"
        >
          <span>Google Console</span>
          <ExternalLink class="w-3.5 h-3.5" />
        </a>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Google Client ID
          </label>
          <input
            v-model="form.google_client_id"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="xxxxxx.apps.googleusercontent.com"
          />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Google Client Secret
            </label>
            <button 
              type="button" 
              @click="showGoogleSecret = !showGoogleSecret"
              class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <EyeOff v-if="showGoogleSecret" class="w-3 h-3" />
              <Eye v-else class="w-3 h-3" />
              <span>{{ showGoogleSecret ? 'Hide' : 'Reveal' }}</span>
            </button>
          </div>
          <input
            v-model="form.google_client_secret"
            :type="showGoogleSecret ? 'text' : 'password'"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="GOCSPX-..."
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Authorized Redirect URI
          </label>
          <input
            v-model="form.google_redirect_uri"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="https://yourdomain.com/auth/google/callback"
          />
        </div>
      </div>
    </div>

    <!-- Section 2: Firebase Push Notifications -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-500">
          <Flame class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Firebase Cloud Messaging (FCM)</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Powers real-time referee notifications, assignment alerts, and mobile push services.</p>
        </div>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
          Firebase Service Account Key (JSON String or File Path)
        </label>
        <textarea
          v-model="form.firebase_credentials"
          rows="3"
          class="w-full px-3.5 py-2 text-xs font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors resize-none"
          placeholder='storage/app/firebase-auth.json or {"type": "service_account", ...}'
        ></textarea>
        <p class="text-[11px] text-slate-400 mt-1">Provide relative path from project root or raw JSON string.</p>
      </div>
    </div>

    <!-- Section 3: Google Maps & reCAPTCHA -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#34B1AA]/10 flex items-center justify-center text-[#34B1AA]">
          <Map class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Maps & Bot Protection</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Google Maps Geocoding for camp venues and reCAPTCHA bot security.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Google Maps JavaScript API Key
          </label>
          <input
            v-model="form.google_maps_api_key"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="AIzaSy..."
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            reCAPTCHA Site Key (Public)
          </label>
          <input
            v-model="form.recaptcha_site_key"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="6LeIxacTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"
          />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              reCAPTCHA Secret Key
            </label>
            <button 
              type="button" 
              @click="showRecaptchaSecret = !showRecaptchaSecret"
              class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <EyeOff v-if="showRecaptchaSecret" class="w-3 h-3" />
              <Eye v-else class="w-3 h-3" />
              <span>{{ showRecaptchaSecret ? 'Hide' : 'Reveal' }}</span>
            </button>
          </div>
          <input
            v-model="form.recaptcha_secret_key"
            :type="showRecaptchaSecret ? 'text' : 'password'"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="6LeIxacTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe"
          />
        </div>
      </div>
    </div>

    <!-- Submit Bar -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button
        type="submit"
        :disabled="form.processing"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#F29F67] to-[#E08A50] hover:from-[#e08a50] hover:to-[#d07b43] shadow-md shadow-[#F29F67]/20 disabled:opacity-60 transition-all cursor-pointer"
      >
        <Save class="w-4 h-4" />
        <span>{{ form.processing ? 'Saving Changes...' : 'Save Integrations' }}</span>
      </button>
    </div>
  </form>
</template>
