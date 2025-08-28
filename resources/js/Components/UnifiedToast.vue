<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to-class="transform translate-y-0 opacity-100 sm:translate-x-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="transform translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    >
      <div
        v-if="isVisible"
        class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50 max-w-sm w-full"
        @mouseenter="pauseTimer"
        @mouseleave="resumeTimer"
      >
        <div
          class="rounded-lg shadow-lg border-l-4 p-4 bg-white"
          :class="toastClasses"
        >
          <div class="flex items-start">
            <!-- Icon -->
            <div class="flex-shrink-0">
              <component :is="iconComponent" class="h-5 w-5" :class="iconClasses" />
            </div>
            
            <!-- Content -->
            <div class="ml-3 flex-1">
              <p class="text-sm font-medium" :class="textClasses">
                {{ message }}
              </p>
            </div>
            
            <!-- Close Button -->
            <div class="ml-4 flex-shrink-0 flex">
              <button
                @click="close"
                class="inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  message: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  duration: {
    type: Number,
    default: 5000 // 5 seconds
  },
  persistent: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const isVisible = ref(true)
let timer = null

// Toast styling based on type
const toastClasses = computed(() => {
  const baseClasses = 'rounded-lg shadow-lg border-l-4 p-4 bg-white'
  const typeClasses = {
    success: 'border-green-500',
    error: 'border-red-500',
    warning: 'border-yellow-500',
    info: 'border-blue-500'
  }
  return `${baseClasses} ${typeClasses[props.type]}`
})

const textClasses = computed(() => {
  const typeClasses = {
    success: 'text-green-800',
    error: 'text-red-800',
    warning: 'text-yellow-800',
    info: 'text-blue-800'
  }
  return typeClasses[props.type]
})

const iconClasses = computed(() => {
  const typeClasses = {
    success: 'text-green-500',
    error: 'text-red-500',
    warning: 'text-yellow-500',
    info: 'text-blue-500'
  }
  return typeClasses[props.type]
})

const iconComponent = computed(() => {
  const icons = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon
  }
  return icons[props.type]
})

// Icon components
const CheckCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
}

const XCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
}

const ExclamationTriangleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
    </svg>
  `
}

const InformationCircleIcon = {
  template: `
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  `
}

function startTimer() {
  if (props.persistent || props.duration <= 0) return
  
  timer = setTimeout(() => {
    close()
  }, props.duration)
}

function pauseTimer() {
  if (timer) {
    clearTimeout(timer)
    timer = null
  }
}

function resumeTimer() {
  if (!props.persistent && props.duration > 0) {
    startTimer()
  }
}

function close() {
  isVisible.value = false
  emit('close')
}

onMounted(() => {
  startTimer()
})

onBeforeUnmount(() => {
  if (timer) {
    clearTimeout(timer)
  }
})
</script>
