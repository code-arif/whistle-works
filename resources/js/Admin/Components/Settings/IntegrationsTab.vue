<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  Globe, 
  Map, 
  Save, 
  Eye, 
  EyeOff, 
  ExternalLink,
  Clock,
  MessageSquare
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
  google_maps_api_key: props.settings.google_maps_api_key || '',
  twilio_sid: props.settings.twilio_sid || '',
  twilio_token: props.settings.twilio_token || '',
  twilio_from: props.settings.twilio_from || '',
});

const showGoogleSecret = ref(false);
const showTwilioToken = ref(false);

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
            <div class="flex items-center gap-2.5 flex-wrap">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Google OAuth Authentication</h3>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider bg-[#F29F67]/10 text-[#F29F67] border border-[#F29F67]/20">
                <Clock class="w-2.5 h-2.5" />
                For Future Use
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Enables one-click "Sign in with Google" for Referees and Evaluators (Prepared for upcoming rollout).</p>
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

    <!-- Section 2: Google Maps Integration -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#34B1AA]/10 flex items-center justify-center text-[#34B1AA]">
          <Map class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Google Maps Integration</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Google Maps Geocoding and Places API key for camp venue locations.</p>
        </div>
      </div>

      <div>
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
    </div>

    <!-- Section 3: Twilio SMS Gateway -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-[#3B8FF3]/10 flex items-center justify-center text-[#3B8FF3]">
            <MessageSquare class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Twilio SMS Gateway</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Credentials for automated SMS alerts, emergency camp notifications, and updates.</p>
          </div>
        </div>

        <a 
          href="https://console.twilio.com" 
          target="_blank" 
          rel="noopener noreferrer" 
          class="inline-flex items-center gap-1.5 text-xs text-[#F29F67] hover:underline"
        >
          <span>Twilio Console</span>
          <ExternalLink class="w-3.5 h-3.5" />
        </a>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Twilio Account SID (TWILIO_SID)
          </label>
          <input
            v-model="form.twilio_sid"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="AC..."
          />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Twilio Auth Token (TWILIO_TOKEN)
            </label>
            <button 
              type="button" 
              @click="showTwilioToken = !showTwilioToken"
              class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <EyeOff v-if="showTwilioToken" class="w-3 h-3" />
              <Eye v-else class="w-3 h-3" />
              <span>{{ showTwilioToken ? 'Hide' : 'Reveal' }}</span>
            </button>
          </div>
          <input
            v-model="form.twilio_token"
            :type="showTwilioToken ? 'text' : 'password'"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="Your Auth Token"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Twilio From / Sender Number (TWILIO_FROM)
          </label>
          <input
            v-model="form.twilio_from"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="+12296027901"
          />
        </div>
      </div>
    </div>

    <!-- Submit Bar -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button
        type="submit"
        :disabled="form.processing"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-bold text-slate-950 bg-[#F29F67] hover:bg-[#E08A50] active:bg-[#C06D35] shadow-xs disabled:opacity-60 transition-all cursor-pointer"
      >
        <Save class="w-4 h-4" />
        <span>{{ form.processing ? 'Saving Changes...' : 'Save Integrations' }}</span>
      </button>
    </div>
  </form>
</template>
