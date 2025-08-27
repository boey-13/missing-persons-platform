<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { router, useForm, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'

defineOptions({ layout: MainLayout })

const props = defineProps({
  user: Object,
  sightingReports: Array,
  communityProjects: Array,
  pointsHistory: Array,
  totalPoints: Number
})

const page = usePage()

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
  console.log('Profile page mounted successfully')
  console.log('User data:', props.user)
  console.log('CSRF token available:', !!page.props.csrf_token)
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
  
  // If there's a file, use post method with _method patch
  if (editForm.avatar) {
    editForm._method = 'PATCH'
    editForm.post('/profile', {
      onSuccess: () => {
        console.log('Profile update successful')
        showEditModal.value = false
        alert('Profile updated successfully!')
      },
      onError: (errors) => {
        console.error('Form errors:', errors)
        if (errors.name) alert('Name error: ' + errors.name)
        if (errors.email) alert('Email error: ' + errors.email)
        if (errors.phone) alert('Phone error: ' + errors.phone)
        if (errors.avatar) alert('Avatar error: ' + errors.avatar)
      }
    })
  } else {
    // No file, use patch method
    editForm.patch('/profile', {
      onSuccess: () => {
        console.log('Profile update successful')
        showEditModal.value = false
        alert('Profile updated successfully!')
      },
      onError: (errors) => {
        console.error('Form errors:', errors)
        if (errors.name) alert('Name error: ' + errors.name)
        if (errors.email) alert('Email error: ' + errors.email)
        if (errors.phone) alert('Phone error: ' + errors.phone)
        if (errors.avatar) alert('Avatar error: ' + errors.avatar)
      }
    })
  }
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
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <!-- Header：纯白 + 底边框 -->
    <header class="bg-white border-b">
      <div class="max-w-5xl mx-auto px-4 py-6 flex items-end justify-between">
        <div class="flex items-center gap-4">
          <!-- 头像：唯一使用圆形 -->
          <div class="w-24 h-24 rounded-full overflow-hidden border border-gray-200 bg-gray-100">
            <img
              v-if="props.user?.avatar_url"
              :src="props.user.avatar_url"
              :alt="props.user?.name"
              class="w-full h-full object-cover"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-3xl font-semibold text-gray-500">
              {{ props.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
            </div>
          </div>

          <div>
            <h1 class="text-2xl font-bold">{{ props.user?.name || 'User Name' }}</h1>
            <p class="text-gray-600">{{ props.user?.email }}</p>
            <span :class="['inline-block mt-2 px-2.5 py-1 rounded text-xs font-semibold', roleColor]">
              {{ userRole }}
            </span>
            <div class="mt-1 text-sm text-gray-500">
              Member since {{ formatDate(props.user?.created_at) }}
            </div>
          </div>
        </div>

        <button
          @click="editProfile"
          class="inline-flex items-center gap-2 border border-gray-300 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-50"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
          </svg>
          Edit Profile
        </button>
      </div>
    </header>

    <!-- Stats 条：直角卡片 + 细边框 -->
    <section class="bg-white">
      <div class="max-w-5xl mx-auto px-4 py-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-3 border border-gray-200 rounded-md">
          <div class="text-[11px] uppercase tracking-wide text-gray-500">Reports</div>
          <div class="mt-1 text-2xl font-semibold">{{ missingReports?.length || 0 }}</div>
        </div>
        <div class="p-3 border border-gray-200 rounded-md">
          <div class="text-[11px] uppercase tracking-wide text-gray-500">Sightings</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.sightingReports?.length || 0 }}</div>
        </div>
        <div v-if="props.user?.role !== 'user'" class="p-3 border border-gray-200 rounded-md">
          <div class="text-[11px] uppercase tracking-wide text-gray-500">Projects</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.communityProjects?.length || 0 }}</div>
        </div>
        <div class="p-3 border border-gray-200 rounded-md">
          <div class="text-[11px] uppercase tracking-wide text-gray-500">Points</div>
          <div class="mt-1 text-2xl font-semibold">{{ props.totalPoints || 0 }}</div>
        </div>
      </div>
    </section>

    <!-- Tabs（下划线风格，无圆角） -->
    <nav class="bg-white border-y">
      <div class="max-w-5xl mx-auto px-4">
        <div class="flex gap-6">
          <button
            @click="activeTab = 'overview'"
            :class="['py-3 -mb-px border-b-2 text-sm',
                     activeTab === 'overview'
                       ? 'border-blue-600 text-blue-600'
                       : 'border-transparent text-gray-600 hover:text-gray-900']">
            Activity
          </button>

          <button
            @click="activeTab = 'reports'"
            :class="['py-3 -mb-px border-b-2 text-sm',
                     activeTab === 'reports'
                       ? 'border-blue-600 text-blue-600'
                       : 'border-transparent text-gray-600 hover:text-gray-900']">
            My Reports <span class="ml-1 text-xs text-gray-500">({{ missingReports?.length || 0 }})</span>
          </button>

          <button
            v-if="props.user?.role !== 'user' && props.communityProjects?.length > 0"
            @click="activeTab = 'projects'"
            :class="['py-3 -mb-px border-b-2 text-sm',
                     activeTab === 'projects'
                       ? 'border-blue-600 text-blue-600'
                       : 'border-transparent text-gray-600 hover:text-gray-900']">
            Community Projects <span class="ml-1 text-xs text-gray-500">({{ props.communityProjects?.length || 0 }})</span>
          </button>
        </div>
      </div>
    </nav>

    <!-- 主内容：一个容器到底 -->
    <main class="max-w-5xl mx-auto px-4 py-8">
      <!-- Overview -->
      <div v-if="activeTab === 'overview'" class="space-y-6">

        <!-- 最近活动 -->
        <div class="bg-white border border-gray-200 rounded-md p-4">
          <h3 class="text-base font-semibold mb-3">Recent Activity</h3>
          <div class="space-y-2">
            <div v-if="missingReports?.length > 0" class="flex items-center gap-2 text-gray-700">
              <span class="w-1.5 h-1.5 bg-blue-600 rounded-full"></span>
              Submitted a missing person report
              <span class="text-sm text-gray-500">— {{ formatDate(missingReports[0].created_at) }}</span>
            </div>
            <div v-if="props.sightingReports?.length > 0" class="flex items-center gap-2 text-gray-700">
              <span class="w-1.5 h-1.5 bg-emerald-600 rounded-full"></span>
              Reported a sighting
              <span class="text-sm text-gray-500">— {{ formatDate(props.sightingReports[0].created_at) }}</span>
            </div>
            <div v-if="!missingReports?.length && !props.sightingReports?.length" class="text-gray-500 py-2">
              No recent activity
            </div>
          </div>
        </div>

        <!-- 快捷操作 -->
        <div class="flex flex-wrap gap-3">
          <button
            @click="showPointsModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            View Points History
          </button>
          <Link href="/rewards" class="bg-gray-900 text-white px-4 py-2 rounded-md hover:bg-black">
            My Rewards
          </Link>
          <Link href="/rewards/my-vouchers" class="bg-emerald-600 text-white px-4 py-2 rounded-md hover:bg-emerald-700">
            My Vouchers
          </Link>
        </div>
      </div>

      <!-- Reports -->
      <div v-if="activeTab === 'reports'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold">My Missing Person Reports</h3>
          <button
            @click="router.visit('/missing-persons/report')"
            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Report New Case
          </button>
        </div>

        <div v-if="loadingReports" class="text-center py-12">
          <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
          <p class="mt-4 text-gray-600">Loading your reports...</p>
        </div>

        <div v-else-if="missingReports && missingReports.length" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="report in missingReports"
            :key="report.id"
            class="bg-white border border-gray-200 rounded-md p-4 flex flex-col items-center hover:shadow-sm transition"
            :class="{ 'border-red-300 bg-red-50': report.case_status === 'Rejected' }"
          >
            <div class="w-28 h-28 bg-gray-100 rounded-md flex items-center justify-center mb-3 overflow-hidden border border-gray-200">
              <img v-if="report.photo_url" :src="report.photo_url" :alt="report.full_name" class="w-full h-full object-cover" />
              <div v-else class="text-xl font-bold text-gray-500">
                {{ report.full_name?.charAt(0)?.toUpperCase() || 'U' }}
              </div>
            </div>

            <div v-if="report.case_status" class="mb-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="getStatusColor(report.case_status)">
                {{ getDisplayStatus(report.case_status) }}
              </span>
            </div>

            <div class="text-center flex-1">
              <div class="font-medium">{{ report.full_name || 'Name:xx' }}</div>
              <div class="text-sm text-gray-600">AGE: {{ report.age || 'xx' }}</div>
              <div class="text-sm text-gray-600 truncate max-w-[200px] mx-auto">{{ report.last_seen_location || 'xx' }}</div>
              <div class="text-xs text-gray-500 mt-1">{{ formatDate(report.created_at) }}</div>

              <div v-if="report.case_status === 'Rejected' && report.rejection_reason" class="mt-2">
                <div class="text-xs text-red-600 font-medium">Rejection Reason:</div>
                <div class="text-xs text-red-500 truncate max-w-[220px] mx-auto">
                  {{ report.rejection_reason.length > 60 ? report.rejection_reason.substring(0, 60) + '...' : report.rejection_reason }}
                </div>
              </div>
            </div>

            <button
              @click="viewReport(report)"
              class="mt-3 w-full px-3 py-2 rounded-md text-sm font-medium"
              :class="report.case_status === 'Rejected' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-blue-600 text-white hover:bg-blue-700'">
              {{ report.case_status === 'Rejected' ? 'View Rejection' : 'View Details' }}
            </button>
          </div>
        </div>

        <div v-else class="text-center py-12">
          <svg class="w-14 h-14 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <h3 class="text-base font-medium mb-1">No reports yet</h3>
          <p class="text-gray-500 mb-5">Start helping by reporting a missing person case.</p>
          <button
            @click="router.visit('/missing-persons/report')"
            class="bg-blue-600 text-white px-5 py-2 rounded-md hover:bg-blue-700">
            Report Your First Case
          </button>
        </div>
      </div>

      <!-- Projects -->
      <div v-if="activeTab === 'projects' && props.user?.role !== 'user'" class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-base font-semibold">My Community Projects</h3>
          <button @click="router.visit('/volunteer/projects')" class="bg-emerald-600 text-white px-4 py-2 rounded-md hover:bg-emerald-700">
            Browse Projects
          </button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="project in props.communityProjects"
            :key="project.id"
            class="bg-white border border-gray-200 rounded-md overflow-hidden hover:shadow-sm transition"
          >
            <div class="h-44 relative bg-gray-100">
              <img v-if="project.photo_url" :src="project.photo_url" :alt="project.title" class="w-full h-full object-cover" />
              <div class="absolute top-3 left-3">
                <span :class="['px-2 py-0.5 rounded text-xs font-medium', getStatusColor(project.status)]">
                  {{ project.status }}
                </span>
              </div>
              <div class="absolute bottom-3 right-3 bg-white border border-gray-200 rounded px-2 py-1 text-right">
                <div class="text-sm font-bold">{{ project.points_reward }}</div>
                <div class="text-[10px] text-gray-600">Points</div>
              </div>
            </div>

            <div class="p-4">
              <h4 class="font-semibold mb-1 line-clamp-1">{{ project.title }}</h4>
              <p class="text-sm text-gray-600 mb-3 line-clamp-1">{{ project.location }}</p>

              <div class="space-y-1 mb-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">Date:</span>
                  <span class="font-medium">{{ formatDate(project.date) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Volunteers:</span>
                  <span class="font-medium">{{ project.volunteers_joined }}/{{ project.volunteers_needed }}</span>
                </div>
              </div>

              <div class="w-full bg-gray-200 rounded h-1.5 mb-3">
                <div
                  class="bg-emerald-600 h-1.5 rounded"
                  :style="{ width: `${(project.volunteers_joined / project.volunteers_needed) * 100}%` }"
                ></div>
              </div>

              <button
                @click="router.visit(`/volunteer/projects/${project.id}`)"
                class="w-full bg-emerald-600 text-white py-2 rounded-md text-sm font-medium hover:bg-emerald-700">
                View Details
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Edit Profile Modal（保留逻辑，仅收敛样式） -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-md border border-gray-200 max-w-md w-full">
        <div class="p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold">Edit Profile</h2>
            <button @click="showEditModal = false" class="text-gray-500 hover:text-gray-800">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="updateProfile" class="space-y-4">
            <!-- CSRF Token -->
            <input type="hidden" name="_token" :value="page.props.csrf_token">
            
            <div class="text-center">
              <div class="w-20 h-20 mx-auto mb-3 rounded-full overflow-hidden border border-gray-200 bg-gray-100 flex items-center justify-center">
                <img v-if="props.user?.avatar_url" :src="props.user.avatar_url" :alt="props.user?.name" class="w-full h-full object-cover" />
                <span v-else class="text-xl font-semibold text-gray-500">{{ props.user?.name?.charAt(0)?.toUpperCase() || 'U' }}</span>
              </div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
              <input type="file" @change="handleAvatarUpload" accept="image/*"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"/>
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
              <input v-model="editForm.name" type="text" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input v-model="editForm.email" type="email" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
              <input v-model="editForm.phone" type="tel"
                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500"/>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
              <button type="submit" :disabled="editForm.processing"
                      class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700 disabled:opacity-50">
                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
              <button type="button" @click="showEditModal = false"
                      class="flex-1 bg-gray-100 text-gray-800 py-2 rounded-md hover:bg-gray-200">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Points History Modal -->
    <div v-if="showPointsModal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-md border border-gray-200 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold">Points History</h2>
            <button @click="showPointsModal = false" class="text-gray-500 hover:text-gray-800">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div class="space-y-3">
            <div v-for="point in props.pointsHistory" :key="point.id"
                 class="flex items-center justify-between p-3 bg-gray-50 rounded">
              <div>
                <p class="font-medium">{{ point.description }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(point.created_at) }}</p>
              </div>
              <span class="text-base font-bold text-emerald-700">
                +{{ point.points }} point{{ point.points > 1 ? 's' : '' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rejection Modal -->
    <div v-if="showRejectionModal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-md border border-gray-200 w-[90%] max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-semibold text-red-600">Report Rejected</h2>
            <button @click="showRejectionModal = false" class="text-gray-500 hover:text-gray-800">✕</button>
          </div>

          <div v-if="selectedRejectedReport" class="space-y-5">
            <div class="bg-gray-50 border border-gray-200 rounded-md p-4">
              <h3 class="text-base font-semibold mb-3">Report Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Name:</span> <div class="text-gray-900">{{ selectedRejectedReport.full_name }}</div></div>
                <div><span class="text-gray-500">Age/Gender:</span> <div class="text-gray-900">{{ selectedRejectedReport.age }}/{{ selectedRejectedReport.gender }}</div></div>
                <div><span class="text-gray-500">Last Seen:</span> <div class="text-gray-900">{{ selectedRejectedReport.last_seen_location }}</div></div>
                <div><span class="text-gray-500">Report Date:</span> <div class="text-gray-900">{{ formatDate(selectedRejectedReport.created_at) }}</div></div>
              </div>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-md p-4">
              <h3 class="text-base font-semibold text-red-800 mb-2">Rejection Reason</h3>
              <div class="text-red-800 whitespace-pre-wrap text-sm">{{ selectedRejectedReport.rejection_reason }}</div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
              <h3 class="text-base font-semibold text-blue-800 mb-2">What You Can Do</h3>
              <ul class="text-blue-800 text-sm space-y-1 list-disc pl-5">
                <li>Review the rejection reason above</li>
                <li>Update your report with the missing or improved information</li>
                <li>Submit a new report with the corrections</li>
              </ul>
            </div>

            <div class="flex gap-3 pt-2">
              <button @click="router.visit('/missing-persons/report')" class="flex-1 bg-blue-600 text-white py-2 rounded-md hover:bg-blue-700">
                Submit New Report
              </button>
              <button @click="showRejectionModal = false" class="flex-1 bg-gray-100 text-gray-800 py-2 rounded-md hover:bg-gray-200">
                Close
              </button>
            </div>
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
