<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

defineOptions({ layout: MainLayout })

const props = defineProps({
  userRole: String,
  requestedUrl: String,
  userName: String,
  volunteerStatus: String
})
</script>

<template>
  <Head title="Access Denied - Volunteer Area" />

  <div class="bg-gray-50 min-h-screen">
    <div class="max-w-[1400px] mx-auto py-12 px-4">
      <!-- Main Content -->
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-orange-100 flex items-center justify-center mx-auto mb-6">
          <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-3">Volunteer Access Required</h1>
        <p class="text-lg text-gray-600 mb-8">You need to be an approved volunteer to access this feature.</p>
      </div>

      <!-- User Status -->
      <div class="bg-white border border-gray-200 p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Current User Status</h2>
        <div class="space-y-3 text-sm">
          <div v-if="userName" class="flex justify-between">
            <span class="font-medium text-gray-700">Name:</span>
            <span class="text-gray-900">{{ userName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium text-gray-700">Role:</span>
            <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium">{{ userRole }}</span>
          </div>
          <div v-if="volunteerStatus" class="flex justify-between">
            <span class="font-medium text-gray-700">Volunteer Status:</span>
            <span class="px-2 py-1 bg-orange-100 text-orange-800 text-xs font-medium">{{ volunteerStatus }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium text-gray-700">Requested Page:</span>
            <span class="text-gray-600 text-xs">{{ requestedUrl }}</span>
          </div>
        </div>
      </div>

      <!-- Role-based Guidance -->
      <div class="bg-white border border-gray-200 p-6 mb-8">
        <div v-if="!userName" class="text-center">
          <h2 class="text-xl font-semibold text-gray-900 mb-3">Please Log In</h2>
          <p class="text-gray-600 mb-6">
            You need to be logged in to access volunteer features. Please log in to your account first.
          </p>
          <Link 
            href="/login"
            class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
          >
            Log In
          </Link>
        </div>

        <div v-else-if="volunteerStatus === 'No Application'" class="text-center">
          <h2 class="text-xl font-semibold text-gray-900 mb-3">Become a Volunteer</h2>
          <p class="text-gray-600 mb-6">
            You haven't applied to become a volunteer yet. To access community projects and volunteer features, 
            you need to submit a volunteer application first.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer/apply"
              class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors"
            >
              Apply to Become Volunteer
            </Link>
            <Link 
              href="/"
              class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors"
            >
              Go to Homepage
            </Link>
          </div>
        </div>

        <div v-else-if="volunteerStatus === 'Pending'" class="text-center">
          <h2 class="text-xl font-semibold text-gray-900 mb-3">Application Under Review</h2>
          <p class="text-gray-600 mb-6">
            Your volunteer application is currently being reviewed by our admin team. 
            You'll be able to access volunteer features once your application is approved.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer/application-pending"
              class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors"
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

        <div v-else-if="volunteerStatus === 'Rejected'" class="text-center">
          <h2 class="text-xl font-semibold text-gray-900 mb-3">Application Rejected</h2>
          <p class="text-gray-600 mb-6">
            Your previous volunteer application was not approved. You can submit a new application 
            with updated information if you'd like to try again.
          </p>
          <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <Link 
              href="/volunteer/apply"
              class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-medium hover:bg-orange-700 transition-colors"
            >
              Submit New Application
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
          <h2 class="text-xl font-semibold text-gray-900 mb-3">Access Restricted</h2>
          <p class="text-gray-600 mb-6">
            This volunteer feature is only accessible to approved volunteers. 
            Please contact support if you believe you should have access.
          </p>
          <Link 
            href="/"
            class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-medium hover:bg-gray-700 transition-colors"
          >
            Go to Homepage
          </Link>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 text-center">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <Link 
            href="/missing-persons"
            class="text-center p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-8 h-8 bg-blue-100 flex items-center justify-center mx-auto mb-2">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
            </div>
            <div class="text-sm font-medium text-gray-900">View Cases</div>
          </Link>

          <Link 
            href="/missing-persons/report"
            class="text-center p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-8 h-8 bg-green-100 flex items-center justify-center mx-auto mb-2">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
            </div>
            <div class="text-sm font-medium text-gray-900">Report Case</div>
          </Link>

          <Link 
            href="/volunteer/apply"
            class="text-center p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-8 h-8 bg-orange-100 flex items-center justify-center mx-auto mb-2">
              <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="text-sm font-medium text-gray-900">Become Volunteer</div>
          </Link>

          <Link 
            href="/profile"
            class="text-center p-4 border border-gray-200 hover:bg-gray-50 transition-colors"
          >
            <div class="w-8 h-8 bg-purple-100 flex items-center justify-center mx-auto mb-2">
              <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="text-sm font-medium text-gray-900">View Profile</div>
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
