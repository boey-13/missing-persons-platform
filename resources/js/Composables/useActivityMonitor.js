import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

export function useActivityMonitor() {
  const lastActivity = ref(Date.now())
  const isWarningShown = ref(false)
  const warningTimeout = ref(null)
  const logoutTimeout = ref(null)
  
  // Configuration
  const WARNING_TIME = 25 * 60 * 1000 // 25 minutes (show warning)
  const LOGOUT_TIME = 30 * 60 * 1000  // 30 minutes (auto logout)
  
  // Update activity timestamp
  const updateActivity = () => {
    lastActivity.value = Date.now()
    
    // Clear existing timeouts
    if (warningTimeout.value) {
      clearTimeout(warningTimeout.value)
      warningTimeout.value = null
    }
    if (logoutTimeout.value) {
      clearTimeout(logoutTimeout.value)
      logoutTimeout.value = null
    }
    
    // Hide warning if it was shown
    if (isWarningShown.value) {
      isWarningShown.value = false
    }
    
    // Set new timeouts
    setTimeouts()
  }
  
  // Set warning and logout timeouts
  const setTimeouts = () => {
    // Warning timeout
    warningTimeout.value = setTimeout(() => {
      isWarningShown.value = true
      showWarning()
    }, WARNING_TIME)
    
    // Logout timeout
    logoutTimeout.value = setTimeout(() => {
      autoLogout()
    }, LOGOUT_TIME)
  }
  
  // Show warning message
  const showWarning = () => {
    // You can customize this warning UI
    const warningMessage = 'Your session will expire in 5 minutes due to inactivity. Click anywhere to stay logged in.'
    
    // Create a simple alert (you can replace this with a custom modal)
    if (confirm(warningMessage)) {
      updateActivity()
    }
  }
  
  // Auto logout
  const autoLogout = () => {
    // Clear any stored data
    localStorage.clear()
    sessionStorage.clear()
    
    // Redirect to login with message
    router.visit('/login', {
      data: {
        message: 'You have been automatically logged out due to inactivity.'
      }
    })
  }
  
  // Event listeners
  const setupEventListeners = () => {
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    
    events.forEach(event => {
      document.addEventListener(event, updateActivity, true)
    })
  }
  
  const removeEventListeners = () => {
    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click']
    
    events.forEach(event => {
      document.removeEventListener(event, updateActivity, true)
    })
  }
  
  // Lifecycle
  onMounted(() => {
    setupEventListeners()
    setTimeouts()
  })
  
  onUnmounted(() => {
    removeEventListeners()
    
    if (warningTimeout.value) {
      clearTimeout(warningTimeout.value)
    }
    if (logoutTimeout.value) {
      clearTimeout(logoutTimeout.value)
    }
  })
  
  return {
    lastActivity,
    isWarningShown,
    updateActivity,
    showWarning,
    autoLogout
  }
}
