<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  PenTool, 
  RotateCcw, 
  Save, 
  CheckCircle2, 
  FileCheck2,
  Trash2
} from 'lucide-vue-next';

const props = defineProps({
  settings: {
    type: Object,
    required: true
  }
});

const canvasRef = ref(null);
const isDrawing = ref(false);
const hasDrawn = ref(false);
let ctx = null;

const form = useForm({
  signature: props.settings.signature || '',
});

onMounted(() => {
  const canvas = canvasRef.value;
  if (!canvas) return;

  ctx = canvas.getContext('2d');
  ctx.strokeStyle = '#F29F67';
  ctx.lineWidth = 2.5;
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';

  // If existing signature exists, draw it on preview
  if (props.settings.signature) {
    const img = new Image();
    img.onload = () => {
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
      hasDrawn.value = true;
    };
    img.src = props.settings.signature;
  }
});

const getCoordinates = (event) => {
  const canvas = canvasRef.value;
  const rect = canvas.getBoundingClientRect();
  const clientX = event.touches ? event.touches[0].clientX : event.clientX;
  const clientY = event.touches ? event.touches[0].clientY : event.clientY;

  return {
    x: (clientX - rect.left) * (canvas.width / rect.width),
    y: (clientY - rect.top) * (canvas.height / rect.height)
  };
};

const startDrawing = (event) => {
  event.preventDefault();
  isDrawing.value = true;
  hasDrawn.value = true;
  const { x, y } = getCoordinates(event);
  ctx.beginPath();
  ctx.moveTo(x, y);
};

const draw = (event) => {
  if (!isDrawing.value) return;
  event.preventDefault();
  const { x, y } = getCoordinates(event);
  ctx.lineTo(x, y);
  ctx.stroke();
};

const stopDrawing = () => {
  if (!isDrawing.value) return;
  isDrawing.value = false;
  ctx.closePath();
  form.signature = canvasRef.value.toDataURL('image/png');
};

const clearSignature = () => {
  const canvas = canvasRef.value;
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  form.signature = '';
  hasDrawn.value = false;
};

const submit = () => {
  if (!form.signature && hasDrawn.value) {
    form.signature = canvasRef.value.toDataURL('image/png');
  }

  form.post('/admin/v2/settings/signature', {
    preserveScroll: true,
  });
};
</script>

<template>
  <div class="space-y-6">
    <div class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 sm:p-6 shadow-sm">
      <div class="flex items-center justify-between pb-4 mb-5 border-b border-slate-100 dark:border-white/[0.06]">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-lg bg-[#F29F67]/10 flex items-center justify-center text-[#F29F67]">
            <PenTool class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Authorized Digital Signature</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Used for official referee evaluation certificates, invoices, and director authorizations.</p>
          </div>
        </div>

        <button
          type="button"
          @click="clearSignature"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors cursor-pointer"
        >
          <RotateCcw class="w-3.5 h-3.5" />
          <span>Clear Canvas</span>
        </button>
      </div>

      <!-- Canvas Drawing Area -->
      <div class="space-y-3">
        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
          Draw Signature On Canvas
        </label>
        <div class="relative w-full max-w-xl mx-auto border-2 border-dashed border-slate-300 dark:border-white/[0.15] rounded-xl overflow-hidden bg-slate-50/80 dark:bg-[#262638]/60 shadow-inner flex items-center justify-center">
          <canvas
            ref="canvasRef"
            width="600"
            height="220"
            class="w-full h-[200px] cursor-crosshair touch-none"
            @mousedown="startDrawing"
            @mousemove="draw"
            @mouseup="stopDrawing"
            @mouseleave="stopDrawing"
            @touchstart="startDrawing"
            @touchmove="draw"
            @touchend="stopDrawing"
          ></canvas>

          <div v-if="!hasDrawn" class="absolute pointer-events-none text-center">
            <PenTool class="w-7 h-7 text-slate-400/50 mx-auto mb-1.5" />
            <span class="text-xs text-slate-400 font-medium select-none">Draw your signature with mouse or touch</span>
          </div>

          <!-- Bottom subtle baseline -->
          <div class="absolute bottom-6 left-12 right-12 h-[1px] bg-slate-300/60 dark:bg-white/[0.1] pointer-events-none"></div>
        </div>
        <p class="text-center text-[11px] text-slate-400">Signature automatically gets anti-aliased and stored in lossless PNG format.</p>
      </div>
    </div>

    <!-- Current Signature Preview Card (if exists) -->
    <div v-if="settings.signature" class="bg-white dark:bg-[#1E1E2C] border border-slate-200 dark:border-white/[0.08] rounded-xl p-5 shadow-sm">
      <div class="flex items-center gap-2 mb-3">
        <FileCheck2 class="w-4 h-4 text-emerald-500" />
        <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Current Saved Signature</span>
      </div>
      <div class="max-w-sm p-4 bg-slate-50 dark:bg-[#262638] rounded-lg border border-slate-200 dark:border-white/[0.08] flex items-center justify-center">
        <img :src="settings.signature" alt="Saved Signature" class="max-h-20 object-contain filter dark:invert-0" />
      </div>
    </div>

    <!-- Submit Bar -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button
        type="button"
        @click="submit"
        :disabled="form.processing || !hasDrawn"
        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-[#F29F67] to-[#E08A50] hover:from-[#e08a50] hover:to-[#d07b43] shadow-md shadow-[#F29F67]/20 disabled:opacity-50 transition-all cursor-pointer"
      >
        <Save class="w-4 h-4" />
        <span>{{ form.processing ? 'Saving...' : 'Save Signature' }}</span>
      </button>
    </div>
  </div>
</template>
