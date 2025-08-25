<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

defineOptions({ layout: MainLayout })

const props = defineProps({
  project: Object,
  userRole: String,
  hasVolunteerApplication: Boolean
})

const categoryColors = {
  search: 'bg-red-100 text-red-800',
  awareness: 'bg-yellow-100 text-yellow-800',
  training: 'bg-purple-100 text-purple-800'
}
</script>

<template>
  <Head title="Access Denied - Community Project" />

  <div class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto py-12 px-4">
      <!-- Project Header -->
      <div class="bg-white border border-gray-200 p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ project.title }}</h1>
            <p class="text-gray-600 mt-1">Community Project</p>
          </div>
          <div class="flex items-center space-x-3">
            <span :class="`px-3 py-1 text-sm font-medium ${categoryColors[project.category]}`">
              {{ project.category }}
            </span>
            <span class="px-3 py-1 text-sm font-medium bg-gray-100 text-gray-800">
              {{ project.status }}
            </span>
          </div>
        </div>
      </div>

      <!-- Access Denied Message -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-yellow-100 flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-3">Access Restricted</h2>
        <p class="text-lg text-gray-600">You don't have permission to view this project's details.</p>
      </div>

      <!-- Role-based Guidance -->
      <div class="bg-white border border-gray-200 p-6 mb-8">
        <div v-if="userRole === 'user' && !hasVolunteerApplication" class="text-center">
          <h3 class="text-xl font-semibold text-gray-900 mb-3">Become a Volunteer First</h3>
          <p class="text-gray-600 mb-6">
            To view community project details and participate in volunteer activities, 
            you need to become an approved volunteer first.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer/apply"
              class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
            >
              Apply to Become a Volunteer
            </Link>
            <Link 
              href="/"
              class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors"
            >
              Go to Homepage
            </Link>
          </div>
        </div>

        <div v-else-if="userRole === 'volunteer' && !hasVolunteerApplication" class="text-center">
          <h3 class="text-xl font-semibold text-gray-900 mb-3">Volunteer Application Pending</h3>
          <p class="text-gray-600 mb-6">
            Your volunteer application is still being reviewed. Once approved, 
            you'll be able to view and participate in community projects.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer"
              class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors"
            >
              Check Application Status
            </Link>
            <Link 
              href="/"
              class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors"
            >
              Go to Homepage
            </Link>
          </div>
        </div>

        <div v-else-if="userRole === 'volunteer' && hasVolunteerApplication" class="text-center">
          <h3 class="text-xl font-semibold text-gray-900 mb-3">Apply to This Project</h3>
          <p class="text-gray-600 mb-6">
            You're an approved volunteer! To view this project's details, 
            you need to apply to participate in it first.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer/projects"
              class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-medium hover:bg-green-700 transition-colors"
            >
              Browse Available Projects
            </Link>
            <Link 
              href="/"
              class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors"
            >
              Go to Homepage
            </Link>
          </div>
        </div>

        <div v-else class="text-center">
          <h3 class="text-xl font-semibold text-gray-900 mb-3">Access Restricted</h3>
          <p class="text-gray-600 mb-6">
            This project is only accessible to approved volunteers who have applied to participate.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer/projects"
              class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors"
            >
              Browse Projects
            </Link>
            <Link 
              href="/"
              class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors"
            >
              Go to Homepage
            </Link>
          </div>
        </div>
      </div>

      <!-- Project Preview -->
      <div class="bg-white border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Project Preview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-medium text-gray-900 mb-2">Project Information</h4>
            <div class="space-y-2 text-sm text-gray-600">
              <div>
                <span class="font-medium">Category:</span> 
                <span :class="`ml-2 px-2 py-1 text-xs font-medium ${categoryColors[project.category]}`">
                  {{ project.category }}
                </span>
              </div>
              <div>
                <span class="font-medium">Status:</span> 
                <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-800">
                  {{ project.status }}
                </span>
              </div>
            </div>
          </div>
          <div>
            <h4 class="font-medium text-gray-900 mb-2">How to Participate</h4>
            <div class="text-sm text-gray-600 space-y-2">
              <p>• Become an approved volunteer</p>
              <p>• Apply to participate in this project</p>
              <p>• Get notified when your application is approved</p>
              <p>• View project details and updates</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
