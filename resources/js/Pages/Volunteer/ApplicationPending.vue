<script setup>
import MainLayout from '@/Layouts/MainLayout.vue'
import { Link } from '@inertiajs/vue3'
defineOptions({ layout: MainLayout })
const props = defineProps({ application: Object })
</script>

<template>
  <div class="max-w-3xl mx-auto py-12 text-center">
    <!-- Success Icon -->
    <div class="mb-6">
      <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>

    <!-- Main Content -->
    <h1 class="text-3xl font-extrabold text-gray-900 mb-4">Application Submitted Successfully!</h1>
    <p class="text-lg text-gray-700 mb-4">Your volunteer application has been submitted and is now pending review.</p>
    
    <!-- Application Details -->
    <div class="bg-gray-50 rounded-lg p-6 mb-8 max-w-md mx-auto">
      <h3 class="text-lg font-semibold text-gray-900 mb-3">Application Details</h3>
      <div class="text-left space-y-2">
        <div class="flex justify-between">
          <span class="text-gray-600">Reference ID:</span>
          <span class="font-mono text-gray-900">{{ application?.id }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Status:</span>
          <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full font-medium">Pending Review</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Submitted:</span>
          <span class="text-gray-900">{{ new Date(application?.created_at).toLocaleDateString() }}</span>
        </div>
      </div>
    </div>

    <!-- Information -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-8 max-w-md mx-auto">
      <div class="flex items-start">
        <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <div class="text-left">
          <p class="text-sm text-blue-800">
            <strong>What happens next?</strong><br>
            Our admin team will review your application within 2-3 business days. You'll receive a notification once the review is complete.
          </p>
        </div>
      </div>
    </div>

    <!-- Action Button -->
    <div class="flex justify-center">
      <Link
        href="/"
        class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
      >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
        </svg>
        Go Back to Home
      </Link>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for the application pending page */
.bg-green-100 {
  background-color: #dcfce7;
}

.bg-green-600 {
  color: #16a34a;
}

.bg-yellow-100 {
  background-color: #fef3c7;
}

.text-yellow-800 {
  color: #92400e;
}

.bg-blue-50 {
  background-color: #eff6ff;
}

.border-blue-200 {
  border-color: #bfdbfe;
}

.text-blue-600 {
  color: #2563eb;
}

.text-blue-800 {
  color: #1e40af;
}

/* Hover effects */
.hover\:bg-blue-700:hover {
  background-color: #1d4ed8;
}

.hover\:bg-gray-200:hover {
  background-color: #e5e7eb;
}

/* Focus ring effects */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.focus\:ring-blue-500:focus {
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.focus\:ring-gray-500:focus {
  box-shadow: 0 0 0 2px rgba(107, 114, 128, 0.5);
}

.focus\:ring-offset-2:focus {
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 1), 0 0 0 4px rgba(59, 130, 246, 0.5);
}
</style>


