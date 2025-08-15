<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import axios from 'axios'

defineOptions({ layout: MainLayout })

const props = defineProps({
  user: Object,
  sightingReports: Array,
  communityProjects: Array,
  pointsHistory: Array,
  totalPoints: Number
})

const activeTab = ref('overview')
// Modal states
const showEditModal = ref(false)
const showPointsModal = ref(false)
const showRejectionModal = ref(false)
const selectedRejectedReport = ref(null)

// User's missing reports
const missingReports = ref([])
const loadingReports = ref(false)

const editForm = useForm({
  name: '',
  email: '',
  phone: '',
  avatar: null
})

// Watch for props.user changes and update form data
watch(() => props.user, (newUser) => {
  if (newUser) {
    editForm.name = newUser.name || ''
    editForm.email = newUser.email || ''
    editForm.phone = newUser.phone || ''
    editForm.avatar = null
  }
}, { immediate: true })

// Fetch user's missing reports
async function fetchUserReports() {
  loadingReports.value = true
  try {
    const response = await axios.get('/api/user/missing-reports')
    missingReports.value = response.data.data
  } catch (error) {
    console.error('Error fetching user reports:', error)
  } finally {
    loadingReports.value = false
  }
}

onMounted(() => {
  fetchUserReports()
})

const userRole = computed(() => {
  if (props.user?.role === 'admin') return 'Administrator'
  if (props.user?.role === 'volunteer') return 'Volunteer'
  return 'User'
})

const roleColor = computed(() => {
  const colors = {
    admin: 'bg-red-100 text-red-800',
    volunteer: 'bg-green-100 text-green-800',
    user: 'bg-blue-100 text-blue-800'
  }
  return colors[props.user?.role] || colors.user
})

function editProfile() {
  // Ensure form is populated with current user data
  editForm.name = props.user?.name || ''
  editForm.email = props.user?.email || ''
  editForm.phone = props.user?.phone || ''
  editForm.avatar = null
  showEditModal.value = true
}

function updateProfile() {
  console.log('Submitting form with data:', {
    name: editForm.name,
    email: editForm.email,
    phone: editForm.phone,
    avatar: editForm.avatar
  })
  
  // Check if form data is valid before submitting
  if (!editForm.name || !editForm.email) {
    console.error('Form validation failed: name or email is empty')
    return
  }
  
  // Use Inertia's form submission directly
  editForm.patch('/profile', {
    forceFormData: true,
    onSuccess: () => {
      console.log('Profile update successful')
      showEditModal.value = false
      router.reload()
    },
    onError: (errors) => {
      console.log('Form errors:', errors)
    }
  })
}

function handleAvatarUpload(event) {
  const file = event.target.files[0]
  if (file) {
    editForm.avatar = file
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function getStatusColor(status) {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    active: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800'
  }
  return colors[status?.toLowerCase()] || 'bg-gray-100 text-gray-800'
}

function getDisplayStatus(status) {
  const displayStatus = {
    'Pending': 'Pending Verify',
    'Approved': 'Verified',
    'Rejected': 'Rejected',
    'Missing': 'Missing',
    'Found': 'Found',
    'Closed': 'Closed'
  }
  return displayStatus[status] || status
}

function viewReport(report) {
  if (report.case_status === 'Rejected' && report.rejection_reason) {
    selectedRejectedReport.value = report
    showRejectionModal.value = true
  } else {
    // For other statuses, navigate to the report details page
    router.visit(`/missing-persons/${report.id}`)
  }
}

function shareToSocial(reportId) {
  const url = `${window.location.origin}/missing-persons/${reportId}`
  navigator.clipboard.writeText(url)
  alert('Link copied to clipboard! Share it on social media to earn points.')
}
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Simple Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900">My Profile</h1>
          <button
            @click="editProfile"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors"
          >
            Edit Profile
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Left Sidebar -->
        <div class="lg:col-span-1 space-y-6">
          
          <!-- Profile Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-center">
              <!-- Avatar -->
              <div class="w-24 h-24 mx-auto mb-4 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                <img 
                  v-if="props.user?.avatar_url" 
                  :src="props.user.avatar_url" 
                  :alt="props.user?.name"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-3xl font-bold text-gray-600">
                  {{ props.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                </span>
              </div>
              
              <!-- User Info -->
              <h2 class="text-xl font-bold text-gray-900 mb-2">{{ props.user?.name || 'User Name' }}</h2>
              <p class="text-gray-600 mb-3">{{ props.user?.email }}</p>
              
              <!-- Role Badge -->
              <span :class="`inline-block px-3 py-1 rounded-full text-sm font-medium ${roleColor} mb-3`">
                {{ userRole }}
              </span>
              
              <!-- Join Date -->
              <p class="text-sm text-gray-500">Member since {{ formatDate(props.user?.created_at) }}</p>
            </div>
          </div>
          
          <!-- Points Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="text-center">
              <div class="text-3xl font-bold text-blue-600 mb-2">{{ props.totalPoints || 0 }}</div>
              <div class="text-gray-600 mb-4">Total Points</div>
              <button
                @click="showPointsModal = true"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors"
              >
                View History
              </button>
            </div>
          </div>
          
          <!-- Quick Stats -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity Summary</h3>
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Missing Reports</span>
                <span class="font-semibold text-blue-600">{{ missingReports?.length || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Sighting Reports</span>
                <span class="font-semibold text-green-600">{{ props.sightingReports?.length || 0 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Projects Joined</span>
                <span class="font-semibold text-purple-600">{{ props.communityProjects?.length || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="lg:col-span-3">
          
          <!-- Tabs Navigation -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
            <div class="border-b border-gray-200">
              <nav class="flex space-x-8 px-6">
                <button
                  @click="activeTab = 'overview'"
                  :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'overview'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  ]"
                >
                  Overview
                </button>
                <button
                  @click="activeTab = 'reports'"
                  :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'reports'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  ]"
                >
                  My Reports ({{ missingReports?.length || 0 }})
                </button>
                <button
                  v-if="props.user?.role === 'volunteer'"
                  @click="activeTab = 'projects'"
                  :class="[
                    'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
                    activeTab === 'projects'
                      ? 'border-blue-500 text-blue-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  ]"
                >
                  Community Projects ({{ props.communityProjects?.length || 0 }})
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
              
              <!-- Overview Tab -->
              <div v-if="activeTab === 'overview'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                    <div class="flex items-center">
                      <div class="p-3 bg-blue-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                      </div>
                      <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600">Missing Reports</p>
                        <p class="text-2xl font-bold text-blue-900">{{ missingReports?.length || 0 }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                    <div class="flex items-center">
                      <div class="p-3 bg-green-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                      </div>
                      <div class="ml-4">
                        <p class="text-sm font-medium text-green-600">Sighting Reports</p>
                        <p class="text-2xl font-bold text-green-900">{{ props.sightingReports?.length || 0 }}</p>
                      </div>
                    </div>
                  </div>

                  <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
                    <div class="flex items-center">
                      <div class="p-3 bg-purple-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                      </div>
                      <div class="ml-4">
                        <p class="text-sm font-medium text-purple-600">Community Projects</p>
                        <p class="text-2xl font-bold text-purple-900">{{ props.communityProjects?.length || 0 }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                  <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h3>
                  <div class="space-y-3">
                    <div v-if="missingReports?.length > 0" class="flex items-center space-x-3">
                      <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                      <span class="text-gray-700">Submitted a missing person report</span>
                      <span class="text-sm text-gray-500">{{ formatDate(missingReports[0].created_at) }}</span>
                    </div>
                    <div v-if="props.sightingReports?.length > 0" class="flex items-center space-x-3">
                      <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                      <span class="text-gray-700">Reported a sighting</span>
                      <span class="text-sm text-gray-500">{{ formatDate(props.sightingReports[0].created_at) }}</span>
                    </div>
                    <div v-if="!missingReports?.length && !props.sightingReports?.length" class="text-gray-500 text-center py-4">
                      No recent activity
                    </div>
                  </div>
                </div>
              </div>

              <!-- Reports Tab -->
              <div v-if="activeTab === 'reports'" class="space-y-6">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold text-gray-900">My Missing Person Reports</h3>
                  <button
                    @click="router.visit('/missing-persons/report')"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors"
                  >
                    Report New Case
                  </button>
                </div>

                <!-- Loading State -->
                <div v-if="loadingReports" class="text-center py-12">
                  <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                  <p class="mt-4 text-gray-600">Loading your reports...</p>
                </div>

                <!-- Reports Grid -->
                <div v-else-if="missingReports && missingReports.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                  <div
                    v-for="report in missingReports"
                    :key="report.id"
                    class="bg-white rounded-lg border border-gray-200 p-4 flex flex-col items-center hover:shadow-md transition h-80"
                    :class="{ 'border-red-200 bg-red-50': report.case_status === 'Rejected' }"
                  >
                    <!-- 更大的头像区域 -->
                    <div class="w-32 h-32 bg-[#B3D4FC] rounded-xl flex items-center justify-center mb-3 overflow-hidden">
                      <img 
                        v-if="report.photo_url" 
                        :src="report.photo_url" 
                        :alt="report.full_name"
                        class="w-full h-full rounded object-cover"
                      />
                      <div v-else class="w-20 h-20 bg-[#87CEEB] rounded-full flex items-center justify-center">
                        <span class="text-2xl font-bold text-white">{{ report.full_name?.charAt(0)?.toUpperCase() || 'U' }}</span>
                      </div>
                    </div>
                    
                    <!-- Status Badge -->
                    <div v-if="report.case_status" class="mb-2">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(report.case_status)">
                        {{ getDisplayStatus(report.case_status) }}
                      </span>
                    </div>
                    
                    <div class="text-center flex-1 flex flex-col justify-center">
                      <div class="font-medium text-md">{{ report.full_name || 'Name:xx' }}</div>
                      <div class="text-sm text-gray-600">AGE: {{ report.age || 'xx' }}</div>
                      <div class="text-sm text-gray-600 leading-snug truncate max-w-[200px] mx-auto">{{ report.last_seen_location || 'xx' }}</div>
                      <div class="text-xs text-gray-500 mt-1">{{ formatDate(report.created_at) }}</div>
                      
                      <!-- Rejection Reason Preview -->
                      <div v-if="report.case_status === 'Rejected' && report.rejection_reason" class="mt-2">
                        <div class="text-xs text-red-600 font-medium">Rejection Reason:</div>
                        <div class="text-xs text-red-500 leading-tight truncate max-w-[200px] mx-auto">
                          {{ report.rejection_reason.length > 60 ? report.rejection_reason.substring(0, 60) + '...' : report.rejection_reason }}
                        </div>
                      </div>
                    </div>
                    
                    <button
                      @click="viewReport(report)"
                      class="mt-auto px-4 py-2 rounded-lg font-medium transition"
                      :class="report.case_status === 'Rejected' 
                        ? 'bg-red-600 text-white hover:bg-red-700' 
                        : 'bg-blue-600 text-white hover:bg-blue-700'"
                    >
                      {{ report.case_status === 'Rejected' ? 'View Rejection' : 'View Details' }}
                    </button>
                  </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
                  <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No reports yet</h3>
                  <p class="text-gray-500 mb-6">Start helping by reporting a missing person case.</p>
                  <button
                    @click="router.visit('/missing-persons/report')"
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors"
                  >
                    Report Your First Case
                  </button>
                </div>
              </div>

              <!-- Projects Tab (Volunteers only) -->
              <div v-if="activeTab === 'projects' && props.user?.role === 'volunteer'" class="space-y-6">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-semibold text-gray-900">My Community Projects</h3>
                  <button
                    @click="router.visit('/volunteer/projects')"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors"
                  >
                    Browse Projects
                  </button>
                </div>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                  <div
                    v-for="project in props.communityProjects"
                    :key="project.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow"
                  >
                    <div class="h-48 relative">
                      <div v-if="!project.photo_url" class="absolute inset-0 bg-gray-200"></div>
                      <img 
                        v-if="project.photo_url" 
                        :src="project.photo_url" 
                        :alt="project.title"
                        class="w-full h-full object-cover"
                      />
                      <div class="absolute top-4 left-4">
                        <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(project.status)}`">
                          {{ project.status }}
                        </span>
                      </div>
                      <div class="absolute bottom-4 right-4 bg-white rounded-lg px-3 py-1 shadow-sm">
                        <div class="text-lg font-bold text-gray-900">{{ project.points_reward }}</div>
                        <div class="text-xs text-gray-600">Points</div>
                      </div>
                    </div>

                    <div class="p-4">
                      <h4 class="text-lg font-semibold text-gray-900 mb-2">{{ project.title }}</h4>
                      <p class="text-sm text-gray-600 mb-4">{{ project.location }}</p>
                      
                      <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                          <span class="text-gray-600">Date:</span>
                          <span class="font-medium">{{ formatDate(project.date) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                          <span class="text-gray-600">Volunteers:</span>
                          <span class="font-medium">{{ project.volunteers_joined }}/{{ project.volunteers_needed }}</span>
                        </div>
                      </div>

                      <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                        <div 
                          class="bg-green-600 h-2 rounded-full transition-all duration-300"
                          :style="{ width: `${(project.volunteers_joined / project.volunteers_needed) * 100}%` }"
                        ></div>
                      </div>

                      <button
                        @click="router.visit(`/volunteer/projects/${project.id}`)"
                        class="w-full bg-green-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors"
                      >
                        View Details
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Profile Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Edit Profile</h2>
            <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <form @submit.prevent="updateProfile" class="space-y-4">
            <!-- Avatar Upload -->
            <div class="text-center">
              <div class="w-20 h-20 mx-auto mb-4 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                <img 
                  v-if="props.user?.avatar_url" 
                  :src="props.user.avatar_url" 
                  :alt="props.user?.name"
                  class="w-full h-full object-cover"
                />
                <span v-else class="text-2xl font-bold text-gray-600">
                  {{ props.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                </span>
              </div>
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                <input
                  type="file"
                  @change="handleAvatarUpload"
                  accept="image/*"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <p class="text-xs text-gray-500 mt-1">Upload a new profile picture (JPG, PNG, GIF)</p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input
                v-model="editForm.email"
                type="email"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
              <input
                v-model="editForm.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
              <button
                type="submit"
                :disabled="editForm.processing"
                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
              >
                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
              <button
                type="button"
                @click="showEditModal = false"
                class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Points History Modal -->
    <div v-if="showPointsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900">Points History</h2>
            <button @click="showPointsModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div class="space-y-4">
            <div
              v-for="point in props.pointsHistory"
              :key="point.id"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
            >
              <div>
                <p class="font-medium text-gray-900">{{ point.description }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(point.created_at) }}</p>
              </div>
              <span class="text-lg font-bold text-green-600">
                +{{ point.points }} point{{ point.points > 1 ? 's' : '' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Rejection Details Modal -->
  <div v-if="showRejectionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
  <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-2xl max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-red-600">Report Rejected</h2>
        <button @click="showRejectionModal = false" class="text-gray-500 hover:text-black text-2xl">
          ✕
        </button>
      </div>
      
      <div v-if="selectedRejectedReport" class="space-y-6">
        <!-- Report Info -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-gray-900 mb-3">Report Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <span class="text-sm font-medium text-gray-500">Name:</span>
              <div class="text-gray-900">{{ selectedRejectedReport.full_name }}</div>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-500">Age/Gender:</span>
              <div class="text-gray-900">{{ selectedRejectedReport.age }}/{{ selectedRejectedReport.gender }}</div>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-500">Last Seen:</span>
              <div class="text-gray-900">{{ selectedRejectedReport.last_seen_location }}</div>
            </div>
            <div>
              <span class="text-sm font-medium text-gray-500">Report Date:</span>
              <div class="text-gray-900">{{ formatDate(selectedRejectedReport.created_at) }}</div>
            </div>
          </div>
        </div>
        
        <!-- Rejection Reason -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-red-800 mb-3">Rejection Reason</h3>
          <div class="text-red-700 whitespace-pre-wrap">{{ selectedRejectedReport.rejection_reason }}</div>
        </div>
        
        <!-- Next Steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h3 class="text-lg font-semibold text-blue-800 mb-3">What You Can Do</h3>
          <div class="space-y-2 text-blue-700">
            <div class="flex items-start space-x-2">
              <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
              <div>Review the rejection reason above</div>
            </div>
            <div class="flex items-start space-x-2">
              <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
              <div>Update your report with the missing or improved information</div>
            </div>
            <div class="flex items-start space-x-2">
              <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
              <div>Submit a new report with the corrections</div>
            </div>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex gap-3 pt-4">
          <button
            @click="router.visit('/missing-persons/report')"
            class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
          >
            Submit New Report
          </button>
          <button
            @click="showRejectionModal = false"
            class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-medium hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
          >
            Close
          </button>
        </div>
      </div>
          </div>
    </div>
  </div>
</template>

<style scoped>
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
