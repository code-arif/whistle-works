<script setup>
import { useForm } from '@inertiajs/vue3';
import { 
  Sliders, 
  Terminal, 
  Radio, 
  ShieldAlert, 
  Save, 
  CheckCircle2, 
  AlertTriangle,
  Layers,
  Database
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const form = useForm({
  app_name: props.settings.app_name || 'Whistle Works',
  app_url: props.settings.app_url || '',
  app_debug: Boolean(props.settings.app_debug),
  access: Boolean(props.settings.access),
  reverb: Boolean(props.settings.reverb),
  recaptcha_enable: Boolean(props.settings.recaptcha_enable),
  mail_enabled: Boolean(props.settings.mail_enabled),
  sms_enabled: Boolean(props.settings.sms_enabled),
  pagination: Number(props.settings.pagination) || 10,
});

const submit = () => {
  form.post('/admin/v2/settings/system', {
    preserveScroll: true,
  });
};
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Environment Context Card -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67]">
          <Terminal class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Application Host & URL</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Core system endpoints and default pagination parameters.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Application Base URL
          </label>
          <input
            v-model="form.app_url"
            type="url"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="https://admin.whistleworks.org"
          />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Default Table Pagination Rows
          </label>
          <input
            v-model="form.pagination"
            type="number"
            min="5"
            max="100"
            class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
          />
        </div>
      </div>
    </div>

    <!-- System Diagnostic Toggles -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#34B1AA]/10 flex items-center justify-center text-[#34B1AA]">
          <Sliders class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Platform Switches & Diagnostics</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Toggle underlying framework subsystems, debugging, and service availability.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Debug Mode Toggle -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-slate-900 dark:text-white">APP_DEBUG (Debug Mode)</span>
              <span v-if="form.app_debug" class="px-1.5 py-0.5 text-[10px] font-bold font-mono bg-amber-500/15 text-amber-600 dark:text-amber-400 rounded">
                Active
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Displays detailed stack traces on errors. Keep disabled in production.</p>
          </div>
          <button
            type="button"
            @click="form.app_debug = !form.app_debug"
            :class="[
              form.app_debug ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.app_debug ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- Maintenance / Access Toggle -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <span class="text-sm font-medium text-slate-900 dark:text-white">Public Access Gate</span>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">When toggled off, non-admin visitors will see maintenance landing page.</p>
          </div>
          <button
            type="button"
            @click="form.access = !form.access"
            :class="[
              form.access ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.access ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- Reverb WebSockets Toggle -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <span class="text-sm font-medium text-slate-900 dark:text-white">Laravel Reverb WebSockets</span>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Enables high-frequency live updates without polling.</p>
          </div>
          <button
            type="button"
            @click="form.reverb = !form.reverb"
            :class="[
              form.reverb ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.reverb ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- reCAPTCHA Toggle -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <span class="text-sm font-medium text-slate-900 dark:text-white">reCAPTCHA Verification</span>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Enforce bot challenge on authentication and camp forms.</p>
          </div>
          <button
            type="button"
            @click="form.recaptcha_enable = !form.recaptcha_enable"
            :class="[
              form.recaptcha_enable ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.recaptcha_enable ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- Outbound Mail Toggle -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <span class="text-sm font-medium text-slate-900 dark:text-white">Email Notification Dispatcher</span>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Master switch to pause or resume outbound email queues.</p>
          </div>
          <button
            type="button"
            @click="form.mail_enabled = !form.mail_enabled"
            :class="[
              form.mail_enabled ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.mail_enabled ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- SMS Dispatcher Toggle -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <span class="text-sm font-medium text-slate-900 dark:text-white">SMS Gateway Integration</span>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Master switch to enable SMS dispatch for emergency camp cancellations.</p>
          </div>
          <button
            type="button"
            @click="form.sms_enabled = !form.sms_enabled"
            :class="[
              form.sms_enabled ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.sms_enabled ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
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
        <span>{{ form.processing ? 'Saving Changes...' : 'Save System Settings' }}</span>
      </button>
    </div>
  </form>
</template>
