import { ref } from 'vue'

// Global toast state
const toasts = ref([])
let nextId = 1

export function useToast() {
  const showToast = (message, type = 'info', options = {}) => {
    const id = nextId++
    const toast = {
      id,
      message,
      type,
      duration: options.duration || 3000, // 3 seconds
      persistent: options.persistent || false
    }
    
    toasts.value.push(toast)
    
    // Auto remove after duration (unless persistent)
    if (!toast.persistent && toast.duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, toast.duration)
    }
    
    return id
  }
  
  const removeToast = (id) => {
    const index = toasts.value.findIndex(toast => toast.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const clearAllToasts = () => {
    toasts.value = []
  }
  
  // Convenience methods
  const success = (message, options = {}) => showToast(message, 'success', options)
  const error = (message, options = {}) => showToast(message, 'error', options)
  const warning = (message, options = {}) => showToast(message, 'warning', options)
  const info = (message, options = {}) => showToast(message, 'info', options)
  
  return {
    toasts,
    showToast,
    removeToast,
    clearAllToasts,
    success,
    error,
    warning,
    info
  }
}
