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
  Database,
  Lock
} from 'lucide-vue-next';
import Dropdown from '../Common/Dropdown.vue';

const sameSiteOptions = [
  { label: 'Lax (Standard CSRF protection — Recommended)', value: 'lax' },
  { label: 'Strict (Restricted to first-party navigation only)', value: 'strict' },
  { label: 'None (Third-party contexts — Requires HTTPS)', value: 'none' },
];

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const form = useForm({
  app_name: props.settings.app_name || 'Whistle Works',
  app_url: props.settings.app_url || '',
  frontend_url: props.settings.frontend_url || '',
  app_debug: Boolean(props.settings.app_debug),
  access: Boolean(props.settings.access),
  mail_enabled: Boolean(props.settings.mail_enabled),
  sms_enabled: Boolean(props.settings.sms_enabled),
  session_http_only: props.settings.session_http_only ?? true,
  session_secure_cookie: Boolean(props.settings.session_secure_cookie),
  session_same_site: props.settings.session_same_site || 'lax',
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
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Application Host & Domain URLs</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Backend API environment endpoints and frontend client portal URLs.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
            Backend App URL (APP_URL)
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
            Frontend Client URL (FRONTEND)
          </label>
          <input
            v-model="form.frontend_url"
            type="url"
            class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
            placeholder="https://whistleworks.org"
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

    <!-- Session & Cookie Security Policy Card -->
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="w-8 h-8 rounded-lg bg-[#3B8FF3]/10 flex items-center justify-center text-[#3B8FF3]">
          <Lock class="w-4 h-4" />
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Session Security & Cookie Policies</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Configure cookie security hardening, XSS prevention, and HTTPS-only flags.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- HTTP Only Cookie Switch -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-slate-900 dark:text-white">SESSION_HTTP_ONLY</span>
              <span v-if="form.session_http_only" class="px-1.5 py-0.5 text-[10px] font-bold font-mono bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 rounded">
                Protected
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Prevents JavaScript from reading session cookies to protect against XSS token theft.</p>
          </div>
          <button
            type="button"
            @click="form.session_http_only = !form.session_http_only"
            :class="[
              form.session_http_only ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.session_http_only ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- HTTPS-Only Secure Cookie Switch -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-medium text-slate-900 dark:text-white">SESSION_SECURE_COOKIE</span>
              <span :class="[
                form.session_secure_cookie ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-amber-500/15 text-amber-600 dark:text-amber-400',
                'px-1.5 py-0.5 text-[10px] font-bold font-mono rounded'
              ]">
                {{ form.session_secure_cookie ? 'HTTPS Only' : 'HTTP / Local Mode' }}
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Transmits cookies only over SSL/HTTPS. Keep OFF in local development, toggle ON in production.</p>
          </div>
          <button
            type="button"
            @click="form.session_secure_cookie = !form.session_secure_cookie"
            :class="[
              form.session_secure_cookie ? 'bg-[#34B1AA]' : 'bg-slate-300 dark:bg-[#36364E]',
              'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out'
            ]"
          >
            <span
              :class="[
                form.session_secure_cookie ? 'translate-x-5' : 'translate-x-0',
                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out'
              ]"
            />
          </button>
        </div>

        <!-- SameSite Cookie Policy -->
        <div class="p-4 rounded-xl bg-slate-50/70 dark:bg-[#262638]/50 border border-slate-200/80 dark:border-white/[0.06] flex flex-col sm:flex-row sm:items-center justify-between gap-3 md:col-span-2">
          <div>
            <span class="text-sm font-medium text-slate-900 dark:text-white">SESSION_SAME_SITE Policy</span>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Controls cross-site cookie transmission to mitigate CSRF attacks.</p>
          </div>
          <Dropdown
            v-model="form.session_same_site"
            :options="sameSiteOptions"
            size="sm"
            align="right"
            menu-class="w-full sm:w-80"
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
        <span>{{ form.processing ? 'Saving Changes...' : 'Save System Settings' }}</span>
      </button>
    </div>
  </form>
</template>
