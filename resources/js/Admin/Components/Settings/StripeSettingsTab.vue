<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  CreditCard, 
  KeyRound, 
  Webhook, 
  Save, 
  Eye, 
  EyeOff, 
  Copy, 
  Check, 
  ExternalLink,
  ShieldCheck,
  AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const form = useForm({
  stripe_key: props.settings.stripe_key || '',
  stripe_secret: props.settings.stripe_secret || '',
  stripe_webhook_secret: props.settings.stripe_webhook_secret || '',
  stripe_checkout_webhook_secret: props.settings.stripe_checkout_webhook_secret || '',
  stripe_rented_webhook_secret: props.settings.stripe_rented_webhook_secret || '',
  stripe_client_id: props.settings.stripe_client_id || '',
  stripe_redirect_url: props.settings.stripe_redirect_url || '',
});

const showSecret = ref(false);
const showWebhookSecret = ref(false);
const copiedWebhook = ref(false);

const copyWebhook = () => {
  if (!props.settings.webhook_url) return;
  navigator.clipboard.writeText(props.settings.webhook_url);
  copiedWebhook.value = true;
  setTimeout(() => copiedWebhook.value = false, 2000);
};

const submit = () => {
  form.post('/admin/v2/settings/stripe', {
    preserveScroll: true,
  });
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Webhook Endpoint helper card -->
    <div class="bg-gradient-to-r from-[#3B8FF3]/10 via-[#3B8FF3]/5 to-transparent border border-[#3B8FF3]/25 rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg bg-[#3B8FF3]/20 flex items-center justify-center text-[#3B8FF3] shrink-0 mt-0.5">
          <Webhook class="w-4 h-4" />
        </div>
        <div>
          <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Stripe Webhook Listener URL</h4>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Register this exact URL in your Stripe Dashboard (<code class="text-[11px] font-mono text-[#3B8FF3]">Developers &gt; Webhooks</code>).
          </p>
          <div class="mt-2 flex items-center gap-2">
            <span class="font-mono text-xs px-2.5 py-1 rounded bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] text-slate-800 dark:text-slate-200 break-all select-all">
              {{ settings.webhook_url }}
            </span>
            <button 
              type="button" 
              @click="copyWebhook" 
              class="p-1.5 rounded-md hover:bg-[#3B8FF3]/15 text-[#3B8FF3] transition-colors cursor-pointer"
              title="Copy URL"
            >
              <Check v-if="copiedWebhook" class="w-4 h-4 text-emerald-500" />
              <Copy v-else class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
      <a 
        href="https://dashboard.stripe.com/webhooks" 
        target="_blank" 
        rel="noopener noreferrer" 
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-[#3B8FF3] hover:text-[#2563EB] dark:hover:text-blue-300 transition-colors shrink-0"
      >
        <span>Open Stripe Console</span>
        <ExternalLink class="w-3.5 h-3.5" />
      </a>
    </div>

    <!-- Section 1: Standard API Credentials -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#34B1AA]/10 flex items-center justify-center text-[#34B1AA]">
          <KeyRound class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">API Authentication Keys</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Public and Secret keys used for direct card charges and checkout sessions.</p>
        </div>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Publishable Key (pk_...)
          </label>
          <input
            v-model="form.stripe_key"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="pk_live_... or pk_test_..."
          />
        </div>

        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
              Secret Key (sk_...)
            </label>
            <button 
              type="button" 
              @click="showSecret = !showSecret"
              class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <EyeOff v-if="showSecret" class="w-3 h-3" />
              <Eye v-else class="w-3 h-3" />
              <span>{{ showSecret ? 'Hide' : 'Reveal' }}</span>
            </button>
          </div>
          <input
            v-model="form.stripe_secret"
            :type="showSecret ? 'text' : 'password'"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="sk_live_... or sk_test_..."
          />
        </div>
      </div>
    </div>

    <!-- Section 2: Webhook Signing Secrets -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67]">
          <ShieldCheck class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Webhook Signing Secrets</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Used by Laravel to cryptographically verify incoming event payloads from Stripe.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Primary Webhook Secret (whsec_...)
          </label>
          <input
            v-model="form.stripe_webhook_secret"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="whsec_..."
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Checkout Webhook Secret (Optional)
          </label>
          <input
            v-model="form.stripe_checkout_webhook_secret"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="whsec_..."
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Rented Webhook Secret (Optional)
          </label>
          <input
            v-model="form.stripe_rented_webhook_secret"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="whsec_..."
          />
        </div>
      </div>
    </div>

    <!-- Section 3: Stripe Connect Onboarding -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-500">
          <CreditCard class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Stripe Connect & Onboarding</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Settings for onboarding camp directors and evaluators for direct payout settlements.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Stripe Client ID (ca_...)
          </label>
          <input
            v-model="form.stripe_client_id"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="ca_..."
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Stripe Redirect URL
          </label>
          <input
            v-model="form.stripe_redirect_url"
            type="text"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="https://yourdomain.com/admin/stripe/connect"
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
        <span>{{ form.processing ? 'Saving Changes...' : 'Save Stripe Credentials' }}</span>
      </button>
    </div>
  </form>
</template>
