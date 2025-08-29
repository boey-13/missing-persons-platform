<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

defineOptions({ layout: MainLayout })

const props = defineProps({
  userRole: String,
  requestedUrl: String,
  userName: String
})
</script>

<template>
  <Head title="Access Denied - Admin Panel" />

  <div class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto py-8 sm:py-12 px-4">
      <!-- Main Content -->
      <div class="text-center mb-6 sm:mb-8">
        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-red-100 flex items-center justify-center mx-auto mb-4 sm:mb-6">
          <svg class="w-8 h-8 sm:w-10 sm:h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
          </svg>
        </div>
        
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2 sm:mb-3">Admin Access Required</h1>
        <p class="text-base sm:text-lg text-gray-600 mb-6 sm:mb-8">You don't have permission to access the admin panel.</p>
      </div>

      <!-- User Status -->
      <div class="bg-white border border-gray-200 p-4 sm:p-6 mb-6 sm:mb-8">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4">Current User Status</h2>
        <div class="space-y-2.5 sm:space-y-3 text-xs sm:text-sm">
          <div v-if="userName" class="flex justify-between">
            <span class="font-medium text-gray-700">Name:</span>
            <span class="text-gray-900">{{ userName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium text-gray-700">Role:</span>
            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium">{{ userRole }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium text-gray-700">Requested Page:</span>
            <span class="text-gray-600 text-xs">{{ requestedUrl }}</span>
          </div>
        </div>
      </div>

      <!-- Role-based Guidance -->
      <div class="bg-white border border-gray-200 p-4 sm:p-6 mb-6 sm:mb-8">
        <div v-if="userRole === 'user'" class="text-center">
          <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2.5 sm:mb-3">Welcome to FindMe!</h2>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">
            You're a regular user. You can report missing persons, report sightings, 
            and participate in community activities. If you're interested in becoming 
            a volunteer, you can apply for volunteer status.
          </p>
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
            <Link 
              href="/"
              class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors text-sm sm:text-base"
            >
              Go to Homepage
            </Link>
            <Link 
              href="/volunteer/apply"
              class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors text-sm sm:text-base"
            >
              Apply to Become Volunteer
            </Link>
          </div>
        </div>

        <div v-else-if="userRole === 'volunteer'" class="text-center">
          <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2.5 sm:mb-3">Volunteer Dashboard</h2>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">
            You're an approved volunteer! You can participate in community projects, 
            help with search operations, and earn points for your contributions. 
            The admin panel is only accessible to platform administrators.
          </p>
          <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center">
            <Link 
              href="/volunteer/projects"
              class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 bg-purple-600 text-white font-medium hover:bg-purple-700 transition-colors text-sm sm:text-base"
            >
              Browse Projects
            </Link>
            <Link 
              href="/"
              class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors text-sm sm:text-base"
            >
              Go to Homepage
            </Link>
          </div>
        </div>

        <div v-else class="text-center">
          <h2 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2.5 sm:mb-3">Access Restricted</h2>
          <p class="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6">
            The admin panel is only accessible to platform administrators. 
            Please contact the system administrator if you believe you should have access.
          </p>
          <Link 
            href="/"
            class="inline-flex items-center px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors text-sm sm:text-base"
          >
            Go to Homepage
          </Link>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white border border-gray-200 p-4 sm:p-6">
        <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-3 sm:mb-4 text-center">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
          <Link 
            href="/missing-persons/report"
            class="text-center p-3 sm:p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-6 h-6 sm:w-8 sm:h-8 bg-blue-100 flex items-center justify-center mx-auto mb-1.5 sm:mb-2">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
            </div>
            <div class="text-xs sm:text-sm font-medium text-gray-900">Report Missing Person</div>
          </Link>

          <Link 
            href="/sighting-reports/report"
            class="text-center p-3 sm:p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-6 h-6 sm:w-8 sm:h-8 bg-green-100 flex items-center justify-center mx-auto mb-1.5 sm:mb-2">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
            </div>
            <div class="text-xs sm:text-sm font-medium text-gray-900">Report Sighting</div>
          </Link>

          <Link 
            href="/rewards"
            class="text-center p-3 sm:p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-6 h-6 sm:w-8 sm:h-8 bg-yellow-100 flex items-center justify-center mx-auto mb-1.5 sm:mb-2">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <div class="text-xs sm:text-sm font-medium text-gray-900">Browse Rewards</div>
          </Link>

          <Link 
            href="/profile"
            class="text-center p-3 sm:p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-6 h-6 sm:w-8 sm:h-8 bg-purple-100 flex items-center justify-center mx-auto mb-1.5 sm:mb-2">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="text-xs sm:text-sm font-medium text-gray-900">View Profile</div>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
