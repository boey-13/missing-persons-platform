<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-start justify-center pt-20">
    <div class="bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm mx-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <i :class="iconClass" class="text-xl"></i>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-gray-900">
            {{ message }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const props = defineProps({
  message: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'success',
    validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
  },
  duration: {
    type: Number,
    default: 4000
  }
})

const iconClass = computed(() => {
  const classes = {
    success: 'fas fa-check-circle text-green-500',
    error: 'fas fa-exclamation-circle text-red-500',
    warning: 'fas fa-exclamation-triangle text-yellow-500',
    info: 'fas fa-info-circle text-blue-500'
  }
  return classes[props.type] || classes.success
})

const show = ref(true)

onMounted(() => {
  setTimeout(() => {
    show.value = false
  }, props.duration)
})
</script>
