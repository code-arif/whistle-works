<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  Mail, 
  Server, 
  Send, 
  Save, 
  Eye, 
  EyeOff, 
  CheckCircle2, 
  AlertCircle,
  X,
  Sparkles
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const form = useForm({
  mail_mailer: props.settings.mail_mailer || 'smtp',
  mail_host: props.settings.mail_host || '',
  mail_port: props.settings.mail_port || 587,
  mail_username: props.settings.mail_username || '',
  mail_password: props.settings.mail_password || '',
  mail_encryption: props.settings.mail_encryption || 'tls',
  mail_from_address: props.settings.mail_from_address || '',
  mail_from_name: props.settings.mail_from_name || '',
});

const testForm = useForm({
  receiver: '',
  subject: 'Whistle-Works SMTP Verification Test',
  content: 'Hello! This is a test email sent from Whistle-Works Admin V2 settings to verify SMTP server connectivity.',
});

const showPassword = ref(false);
const showTestModal = ref(false);

const submit = () => {
  form.post('/admin/v2/settings/mail', {
    preserveScroll: true,
  });
};

const sendTest = () => {
  testForm.post('/admin/v2/settings/mail/test', {
    preserveScroll: true,
    onSuccess: () => {
      showTestModal.value = false;
      testForm.reset('receiver');
    }
  });
};
</script>

<template>
  <div class="space-y-6">
    <form @submit.prevent="submit" class="space-y-6">
      <!-- Section 1: SMTP Server Configuration -->
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[#3B8FF3]/10 flex items-center justify-center text-[#3B8FF3]">
              <Server class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-semibold text-slate-900 dark:text-white">SMTP Transport Configuration</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">Configure outbound email delivery servers (SendGrid, Mailgun, Postmark, AWS SES, or custom SMTP).</p>
            </div>
          </div>

          <button
            type="button"
            @click="showTestModal = true"
            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-semibold text-[#3B8FF3] bg-[#3B8FF3]/10 hover:bg-[#3B8FF3]/20 border border-[#3B8FF3]/25 transition-all cursor-pointer shrink-0"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Send Test Email</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Mail Driver / Mailer <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.mail_mailer"
              type="text"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="smtp"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              SMTP Host <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.mail_host"
              type="text"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="smtp.mailgun.org"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Port <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.mail_port"
              type="number"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="587"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              SMTP Username
            </label>
            <input
              v-model="form.mail_username"
              type="text"
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="postmaster@whistleworks.org"
            />
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                SMTP Password
              </label>
              <button 
                type="button" 
                @click="showPassword = !showPassword"
                class="text-xs text-[#F29F67] hover:underline flex items-center gap-1 cursor-pointer"
              >
                <EyeOff v-if="showPassword" class="w-3 h-3" />
                <Eye v-else class="w-3 h-3" />
                <span>{{ showPassword ? 'Hide' : 'Reveal' }}</span>
              </button>
            </div>
            <input
              v-model="form.mail_password"
              :type="showPassword ? 'text' : 'password'"
              class="w-full px-3.5 py-2 text-sm font-mono bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="••••••••••••"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Encryption Protocol
            </label>
            <select
              v-model="form.mail_encryption"
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors cursor-pointer"
            >
              <option value="tls">TLS (Recommended - Port 587)</option>
              <option value="ssl">SSL (Port 465)</option>
              <option value="null">None (Port 25)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section 2: Sender Address & Name -->
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center gap-3 pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
          <div class="w-8 h-8 rounded-lg bg-[#34B1AA]/10 flex items-center justify-center text-[#34B1AA]">
            <Mail class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Default Sender Identity</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">The "From" name and email address that will appear in users' inboxes.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              From Email Address <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.mail_from_address"
              type="email"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="no-reply@whistleworks.org"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              From Sender Name <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="form.mail_from_name"
              type="text"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#F29F67]/50 focus:border-[#F29F67] text-slate-900 dark:text-white transition-colors"
              placeholder="Whistle Works Team"
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
          <span>{{ form.processing ? 'Saving Changes...' : 'Save Mail Settings' }}</span>
        </button>
      </div>
    </form>

    <!-- Test Email Modal -->
    <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200">
      <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-white/[0.08]">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-[#3B8FF3]/15 flex items-center justify-center text-[#3B8FF3]">
              <Send class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Send Test Verification Email</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">Verifies your SMTP settings with live delivery.</p>
            </div>
          </div>
          <button 
            type="button" 
            @click="showTestModal = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1 rounded-md cursor-pointer"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <form @submit.prevent="sendTest" class="space-y-4 pt-1">
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Recipient Email Address <span class="text-rose-500">*</span>
            </label>
            <input
              v-model="testForm.receiver"
              type="email"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3B8FF3]/50 focus:border-[#3B8FF3] text-slate-900 dark:text-white transition-colors"
              placeholder="your-personal@email.com"
            />
            <span v-if="testForm.errors.receiver" class="text-xs text-rose-500 mt-1 block">{{ testForm.errors.receiver }}</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Email Subject
            </label>
            <input
              v-model="testForm.subject"
              type="text"
              required
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3B8FF3]/50 focus:border-[#3B8FF3] text-slate-900 dark:text-white transition-colors"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
              Message Content
            </label>
            <textarea
              v-model="testForm.content"
              rows="3"
              class="w-full px-3.5 py-2 text-sm bg-slate-50 dark:bg-[#262638] border border-slate-200 dark:border-white/[0.08] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#3B8FF3]/50 focus:border-[#3B8FF3] text-slate-900 dark:text-white transition-colors resize-none"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="showTestModal = false"
              class="px-4 py-2 text-xs font-medium text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-[#262638] rounded-lg transition-colors cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="testForm.processing"
              class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-[#3B8FF3] hover:bg-blue-600 shadow-md shadow-[#3B8FF3]/25 disabled:opacity-60 transition-all cursor-pointer"
            >
              <Send class="w-3.5 h-3.5" />
              <span>{{ testForm.processing ? 'Dispatching...' : 'Dispatch Test Email' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
