import { ref } from 'vue'

const toasts = ref([])
let toastId = 0

function showToast(message, type = 'success', duration = 3000) {
  const id = ++toastId
  toasts.value.push({ id, message, type })
  setTimeout(() => {
    removeToast(id)
  }, duration)
}

function removeToast(id) {
  toasts.value = toasts.value.filter(t => t.id !== id)
}

export function useToast() {
  return {
    toasts,
    showToast,
    removeToast,
    toast: {
      success: (msg, dur) => showToast(msg, 'success', dur),
      error: (msg, dur) => showToast(msg, 'error', dur),
      info: (msg, dur) => showToast(msg, 'info', dur)
    }
  }
}
