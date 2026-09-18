<template>
  <div class="min-h-screen bg-[#1E1E2C] text-slate-100 flex flex-col justify-between relative overflow-hidden font-sans select-none selection:bg-[#F29F67]/30 selection:text-[#F29F67]">
    <!-- Ambient Background Lighting -->
    <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full blur-[140px] pointer-events-none opacity-40 transition-colors duration-700"
         :style="{ backgroundColor: currentConfig.glowColor }"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 rounded-full blur-[140px] pointer-events-none opacity-25 transition-colors duration-700"
         :style="{ backgroundColor: currentConfig.glowColor }"></div>

    <!-- Background Grid Pattern -->
    <div class="absolute inset-0 bg-[linear-gradient(to_right,#ffffff05_1px,transparent_1px),linear-gradient(to_bottom,#ffffff05_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_50%,#000_70%,transparent_100%)] pointer-events-none"></div>

    <!-- Header / Brand -->
    <header class="relative z-10 w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <a href="/admin/v2/dashboard" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#2A2A3E] to-[#1E1E2C] border border-white/10 flex items-center justify-center shadow-lg shadow-black/30 group-hover:border-[#F29F67]/40 transition-all duration-300">
            <span class="text-lg font-bold text-[#F29F67]">W</span>
          </div>
          <div>
            <div class="text-sm font-bold tracking-wider uppercase text-white font-display flex items-center gap-2">
              Whistle Works
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-medium bg-white/5 text-slate-400 border border-white/10">v2.0</span>
            </div>
            <div class="text-[11px] text-slate-400 font-mono tracking-tight">Executive Management Hub</div>
          </div>
        </a>
      </div>

      <!-- Quick status badge -->
      <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs font-mono text-slate-300">
        <span class="w-2 h-2 rounded-full animate-pulse" :style="{ backgroundColor: currentConfig.accentColor }"></span>
        <span>HTTP Status: {{ errorStatus }}</span>
      </div>
    </header>

    <!-- Main Content Stage -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-6 py-12">
      <div class="w-full max-w-2xl text-center">
        <!-- Giant Status Hero with Layered Glow -->
        <div class="relative inline-flex items-center justify-center mb-8">
          <!-- Outer Pulsing Ring -->
          <div class="absolute inset-0 rounded-full blur-2xl opacity-30 animate-pulse transition-colors duration-500"
               :style="{ backgroundColor: currentConfig.accentColor }"></div>

          <!-- Icon Emblem Container -->
          <div class="relative w-28 h-28 sm:w-32 sm:h-32 rounded-3xl bg-[#252538]/90 border border-white/10 backdrop-blur-xl flex items-center justify-center shadow-2xl shadow-black/60">
            <!-- Inner Icon Component -->
            <component 
              :is="currentConfig.icon" 
              class="w-14 h-14 sm:w-16 sm:h-16 transition-all duration-300 transform hover:scale-110" 
              :style="{ color: currentConfig.accentColor }" 
            />

            <!-- Mini Corner Status Pill -->
            <div class="absolute -bottom-2.5 -right-2.5 px-2.5 py-0.5 rounded-full text-xs font-mono font-bold border shadow-lg"
                 :class="currentConfig.badgeClass">
              {{ errorStatus }}
            </div>
          </div>
        </div>

        <!-- Error Code & Category Tag -->
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium tracking-wide uppercase mb-4 border"
             :class="currentConfig.tagClass">
          <span>{{ currentConfig.category }}</span>
          <span class="opacity-40">•</span>
          <span>Code {{ errorStatus }}</span>
        </div>

        <!-- Headline -->
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4 font-display">
          {{ currentConfig.title }}
        </h1>

        <!-- Human-Readable Description -->
        <p class="text-base sm:text-lg text-slate-300 max-w-xl mx-auto mb-8 leading-relaxed">
          {{ customMessage || currentConfig.description }}
        </p>

        <!-- Throttling / 429 Countdown Notice -->
        <div v-if="errorStatus === 429 && countdown > 0" class="mb-8 max-w-md mx-auto p-4 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 flex items-center justify-center gap-3">
          <Timer class="w-5 h-5 animate-spin" />
          <span class="text-sm font-mono font-medium">Auto cooldown active: Retry available in <strong class="text-white">{{ countdown }}s</strong></span>
        </div>

        <!-- Action Control Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-3 mb-10">
          <!-- Primary Action -->
          <button 
            v-if="errorStatus === 419"
            @click="reloadPage"
            class="px-6 py-2.5 rounded-xl font-medium text-sm text-slate-900 bg-[#F29F67] hover:bg-[#ffb07d] transition-all duration-200 shadow-lg shadow-[#F29F67]/20 flex items-center gap-2 font-display cursor-pointer active:scale-95"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': isReloading }" />
            <span>Refresh & Re-authenticate</span>
          </button>

          <button 
            v-else-if="errorStatus === 429"
            @click="retryRequest"
            :disabled="countdown > 0"
            class="px-6 py-2.5 rounded-xl font-medium text-sm text-slate-900 bg-[#F29F67] hover:bg-[#ffb07d] disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg shadow-[#F29F67]/20 flex items-center gap-2 font-display cursor-pointer active:scale-95"
          >
            <RotateCcw class="w-4 h-4" />
            <span>{{ countdown > 0 ? `Wait (${countdown}s)` : 'Retry Request' }}</span>
          </button>

          <a 
            v-else-if="errorStatus === 401"
            href="/login"
            class="px-6 py-2.5 rounded-xl font-medium text-sm text-slate-900 bg-[#F29F67] hover:bg-[#ffb07d] transition-all duration-200 shadow-lg shadow-[#F29F67]/20 flex items-center gap-2 font-display cursor-pointer active:scale-95"
          >
            <LogIn class="w-4 h-4" />
            <span>Sign In with Credentials</span>
          </a>

          <a 
            v-else
            href="/admin/v2/dashboard"
            class="px-6 py-2.5 rounded-xl font-medium text-sm text-slate-900 bg-[#F29F67] hover:bg-[#ffb07d] transition-all duration-200 shadow-lg shadow-[#F29F67]/20 flex items-center gap-2 font-display cursor-pointer active:scale-95"
          >
            <Home class="w-4 h-4" />
            <span>Return to Dashboard</span>
          </a>

          <!-- Secondary Action: Go Back -->
          <button 
            @click="goBack"
            class="px-5 py-2.5 rounded-xl font-medium text-sm text-slate-200 bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 transition-all duration-200 flex items-center gap-2 font-display cursor-pointer active:scale-95"
          >
            <ArrowLeft class="w-4 h-4 text-slate-400" />
            <span>Previous Page</span>
          </button>

          <!-- Tertiary Action: Technical Details Toggle -->
          <button 
            @click="showDiagnostics = !showDiagnostics"
            class="px-4 py-2.5 rounded-xl font-mono text-xs text-slate-400 hover:text-slate-200 bg-white/5 hover:bg-white/10 border border-white/10 transition-all duration-200 flex items-center gap-2 cursor-pointer"
          >
            <Terminal class="w-3.5 h-3.5" />
            <span>{{ showDiagnostics ? 'Hide Diagnostics' : 'Diagnostics' }}</span>
          </button>
        </div>

        <!-- Collapsible Diagnostic Drawer -->
        <transition
          enter-active-class="transition duration-300 ease-out"
          enter-from-class="transform opacity-0 -translate-y-2 scale-95"
          enter-to-class="transform opacity-100 translate-y-0 scale-100"
          leave-active-class="transition duration-200 ease-in"
          leave-from-class="transform opacity-100 translate-y-0 scale-100"
          leave-to-class="transform opacity-0 -translate-y-2 scale-95"
        >
          <div v-if="showDiagnostics" class="mt-6 text-left max-w-xl mx-auto rounded-2xl bg-[#181824]/90 border border-white/10 p-5 backdrop-blur-xl shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-3">
              <div class="flex items-center gap-2 text-xs font-mono text-slate-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>System Diagnostic Telemetry</span>
              </div>
              <button 
                @click="copyDiagnostics"
                class="text-[11px] font-mono px-2.5 py-1 rounded bg-white/5 hover:bg-white/10 text-[#F29F67] border border-white/10 flex items-center gap-1.5 transition-colors cursor-pointer"
              >
                <Check v-if="copied" class="w-3 h-3 text-emerald-400" />
                <Copy v-else class="w-3 h-3" />
                <span>{{ copied ? 'Copied' : 'Copy Telemetry' }}</span>
              </button>
            </div>

            <div class="space-y-2 font-mono text-xs text-slate-400">
              <div class="flex justify-between py-1 border-b border-white/5">
                <span class="text-slate-500">HTTP Status:</span>
                <span class="text-slate-200">{{ errorStatus }} ({{ currentConfig.title }})</span>
              </div>
              <div class="flex justify-between py-1 border-b border-white/5">
                <span class="text-slate-500">Timestamp (UTC):</span>
                <span class="text-slate-200">{{ currentTimestamp }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-white/5">
                <span class="text-slate-500">Incident Tracking ID:</span>
                <span class="text-amber-400">{{ incidentId }}</span>
              </div>
              <div class="flex justify-between py-1 border-b border-white/5">
                <span class="text-slate-500">Target Resource:</span>
                <span class="text-slate-200 truncate max-w-xs">{{ currentUrl }}</span>
              </div>
              <div v-if="customMessage" class="pt-2">
                <span class="text-slate-500 block mb-1">Exception Message:</span>
                <pre class="p-2.5 rounded-lg bg-black/40 border border-white/5 text-[11px] text-rose-300 whitespace-pre-wrap overflow-x-auto">{{ customMessage }}</pre>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full max-w-7xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-white/5 text-xs text-slate-400">
      <div class="flex items-center gap-2">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        <span>Whistle Works Cluster v2.0 • Operational Architecture</span>
      </div>
      <div class="flex items-center gap-6">
        <a href="/admin/v2/terms-privacy" class="hover:text-white transition-colors">Privacy & Terms</a>
        <a href="mailto:support@whistleworks.org" class="hover:text-white transition-colors flex items-center gap-1.5">
          <HelpCircle class="w-3.5 h-3.5 text-[#F29F67]" />
          <span>Support Desk</span>
        </a>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
  SearchX, 
  ShieldAlert, 
  Lock, 
  ClockAlert, 
  ServerCrash, 
  Wrench, 
  WifiOff, 
  Gauge, 
  Timer, 
  Home, 
  ArrowLeft, 
  RefreshCw, 
  RotateCcw, 
  LogIn, 
  Terminal, 
  Copy, 
  Check, 
  HelpCircle,
  AlertOctagon
} from 'lucide-vue-next';

const props = defineProps({
  status: {
    type: [Number, String],
    default: 404,
  },
  message: {
    type: String,
    default: null,
  },
});

const errorStatus = computed(() => Number(props.status) || 404);
const customMessage = computed(() => props.message);

const showDiagnostics = ref(false);
const copied = ref(false);
const isReloading = ref(false);
const countdown = ref(15);
let timerInterval = null;

const currentTimestamp = ref(new Date().toISOString());
const currentUrl = ref(typeof window !== 'undefined' ? window.location.pathname : '');
const incidentId = ref(`WW-${Math.abs(errorStatus.value)}-${Math.random().toString(36).substring(2, 7).toUpperCase()}`);

// Comprehensive Configuration Table for All Key Modern Web HTTP Errors
const errorDefinitions = {
  400: {
    category: 'Client Request Error',
    title: 'Malformed Request',
    description: 'The server could not understand or parse the request due to invalid syntax or unexpected parameter formats.',
    icon: AlertOctagon,
    accentColor: '#F59E0B',
    glowColor: 'rgba(245, 158, 11, 0.25)',
    badgeClass: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
    tagClass: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
  },
  401: {
    category: 'Security & Authentication',
    title: 'Authentication Required',
    description: 'You must be signed in with verified credentials to access this administrative resource.',
    icon: Lock,
    accentColor: '#A855F7',
    glowColor: 'rgba(168, 85, 247, 0.25)',
    badgeClass: 'bg-purple-500/20 text-purple-300 border-purple-500/30',
    tagClass: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
  },
  403: {
    category: 'Access Governance',
    title: 'Access Restricted',
    description: 'You do not have the required role or security permissions to access this administrative governance zone.',
    icon: ShieldAlert,
    accentColor: '#F43F5E',
    glowColor: 'rgba(244, 63, 94, 0.25)',
    badgeClass: 'bg-rose-500/20 text-rose-300 border-rose-500/30',
    tagClass: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
  },
  404: {
    category: 'Routing Error',
    title: 'Page Not Found',
    description: 'The destination you navigated to does not exist, has been removed, or has moved to an updated URL.',
    icon: SearchX,
    accentColor: '#F29F67',
    glowColor: 'rgba(242, 159, 103, 0.25)',
    badgeClass: 'bg-[#F29F67]/20 text-[#F29F67] border-[#F29F67]/30',
    tagClass: 'bg-[#F29F67]/10 text-[#F29F67] border-[#F29F67]/20',
  },
  405: {
    category: 'Protocol Method Error',
    title: 'HTTP Method Not Allowed',
    description: 'The HTTP verb used for this action (e.g., GET instead of POST) is disallowed by the server endpoint.',
    icon: AlertOctagon,
    accentColor: '#E11D48',
    glowColor: 'rgba(225, 29, 72, 0.25)',
    badgeClass: 'bg-rose-500/20 text-rose-300 border-rose-500/30',
    tagClass: 'bg-rose-500/10 text-rose-400 border-rose-500/20',
  },
  419: {
    category: 'Session Lifecyle',
    title: 'Security Session Expired',
    description: 'Your security CSRF token expired due to inactivity. Refreshing the browser will automatically restore a secure session.',
    icon: ClockAlert,
    accentColor: '#38BDF8',
    glowColor: 'rgba(56, 189, 248, 0.25)',
    badgeClass: 'bg-sky-500/20 text-sky-300 border-sky-500/30',
    tagClass: 'bg-sky-500/10 text-sky-400 border-sky-500/20',
  },
  429: {
    category: 'Traffic & Rate Limiting',
    title: 'Rate Limit Throttled',
    description: 'Whoa! You have initiated too many consecutive requests in a short window. Please wait for the cooldown timer to expire.',
    icon: Gauge,
    accentColor: '#FBBF24',
    glowColor: 'rgba(251, 191, 36, 0.25)',
    badgeClass: 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30',
    tagClass: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  },
  500: {
    category: 'Internal Infrastructure',
    title: 'Internal Server Anomaly',
    description: 'An unexpected exception halted processing on our backend. Our monitoring telemetry has logged this event.',
    icon: ServerCrash,
    accentColor: '#EF4444',
    glowColor: 'rgba(239, 68, 68, 0.25)',
    badgeClass: 'bg-red-500/20 text-red-300 border-red-500/30',
    tagClass: 'bg-red-500/10 text-red-400 border-red-500/20',
  },
  502: {
    category: 'Gateway Communication',
    title: 'Bad Gateway Response',
    description: 'The edge server received an invalid or corrupt response from the upstream application microservice.',
    icon: WifiOff,
    accentColor: '#818CF8',
    glowColor: 'rgba(129, 140, 248, 0.25)',
    badgeClass: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30',
    tagClass: 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
  },
  503: {
    category: 'Scheduled Maintenance',
    title: 'Service Under Maintenance',
    description: 'Whistle-Works infrastructure is temporarily performing scheduled maintenance or database synchronization.',
    icon: Wrench,
    accentColor: '#FB923C',
    glowColor: 'rgba(251, 146, 60, 0.25)',
    badgeClass: 'bg-orange-500/20 text-orange-300 border-orange-500/30',
    tagClass: 'bg-orange-500/10 text-orange-400 border-orange-500/20',
  },
  504: {
    category: 'Gateway Timeout',
    title: 'Gateway Request Timeout',
    description: 'The upstream server took too long to finish processing the request. This may indicate network latency or heavy server load.',
    icon: WifiOff,
    accentColor: '#60A5FA',
    glowColor: 'rgba(96, 165, 250, 0.25)',
    badgeClass: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
    tagClass: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  },
};

const currentConfig = computed(() => {
  return errorDefinitions[errorStatus.value] || errorDefinitions[500];
});

const reloadPage = () => {
  isReloading.value = true;
  window.location.reload();
};

const retryRequest = () => {
  if (countdown.value === 0) {
    window.location.reload();
  }
};

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    window.location.href = '/admin/v2/dashboard';
  }
};

const copyDiagnostics = async () => {
  const telemetry = [
    `Incident ID: ${incidentId.value}`,
    `HTTP Status: ${errorStatus.value} (${currentConfig.value.title})`,
    `Timestamp: ${currentTimestamp.value}`,
    `Target URL: ${currentUrl.value}`,
    props.message ? `Details: ${props.message}` : '',
  ].filter(Boolean).join('\n');

  try {
    await navigator.clipboard.writeText(telemetry);
    copied.value = true;
    setTimeout(() => {
      copied.value = false;
    }, 2500);
  } catch (err) {
    console.error('Failed to copy telemetry:', err);
  }
};

onMounted(() => {
  if (errorStatus.value === 429) {
    timerInterval = setInterval(() => {
      if (countdown.value > 0) {
        countdown.value--;
      } else {
        clearInterval(timerInterval);
      }
    }, 1000);
  }
});

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
});
</script>
