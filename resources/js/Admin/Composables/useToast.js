import { ref, readonly } from 'vue';

const toasts = ref([]);
let toastId = 0;

export function useToast() {
  const show = (message, type = 'success', duration = 3500) => {
    if (!message) return;
    
    const id = ++toastId;
    const toast = {
      id,
      message,
      type, // 'success' | 'error' | 'info' | 'warning'
      duration,
    };

    // Keep only the latest 3 toasts to avoid screen clutter
    if (toasts.value.length >= 3) {
      toasts.value.shift();
    }
    
    toasts.value.push(toast);

    if (duration > 0) {
      setTimeout(() => {
        dismiss(id);
      }, duration);
    }

    return id;
  };

  const success = (message, duration = 3500) => show(message, 'success', duration);
  const error = (message, duration = 4500) => show(message, 'error', duration);
  const info = (message, duration = 3500) => show(message, 'info', duration);
  const warning = (message, duration = 4000) => show(message, 'warning', duration);

  const dismiss = (id) => {
    const idx = toasts.value.findIndex(t => t.id === id);
    if (idx !== -1) {
      toasts.value.splice(idx, 1);
    }
  };

  const clear = () => {
    toasts.value = [];
  };

  return {
    toasts: readonly(toasts),
    show,
    success,
    error,
    info,
    warning,
    dismiss,
    clear,
  };
}
