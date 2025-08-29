<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { router, useForm, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: MainLayout })

const props = defineProps({
  user: Object,
  sightingReports: Array,
  communityProjects: Array,
  pointsHistory: Array,
  totalPoints: Number
})

const page = usePage()

const activeTab = ref('reports')
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

const { success, error, info } = useToast()

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

// Generate recent activities (last 10)
const recentActivities = computed(() => {
  const activities = []

  // Add missing reports activities
  if (missingReports.value && missingReports.value.length > 0) {
    missingReports.value.slice(0, 5).forEach((report, index) => {
      activities.push({
        id: `report-${report.id}`,
        description: `Submitted missing person report for ${report.full_name}`,
        date: report.created_at,
        color: 'bg-blue-600'
      })
    })
  }

  // Add sighting reports activities
  if (props.sightingReports && props.sightingReports.length > 0) {
    props.sightingReports.slice(0, 3).forEach((sighting, index) => {
      activities.push({
        id: `sighting-${sighting.id}`,
        description: 'Reported a sighting',
        date: sighting.created_at,
        color: 'bg-emerald-600'
      })
    })
  }

  // Add community projects activities (for non-user roles)
  if (props.user?.role !== 'user' && props.communityProjects && props.communityProjects.length > 0) {
    props.communityProjects.slice(0, 2).forEach((project, index) => {
      activities.push({
        id: `project-${project.id}`,
        description: `Applied for project: ${project.title}`,
        date: project.created_at,
        color: 'bg-purple-600'
      })
    })
  }

  // Sort by date (newest first) and take only the last 10
  return activities
    .sort((a, b) => new Date(b.date) - new Date(a.date))
    .slice(0, 10)
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
  

  // If there's a file, use post method with _method patch
  if (editForm.avatar) {
    editForm._method = 'PATCH'
    editForm.post('/profile', {
      onSuccess: () => {
        showEditModal.value = false
        success('Profile updated successfully!')
      },
      onError: (errors) => {
        if (errors.name) error('Name error: ' + errors.name)
        if (errors.email) error('Email error: ' + errors.email)
        if (errors.phone) error('Phone error: ' + errors.phone)
        if (errors.avatar) error('Avatar error: ' + errors.avatar)
      }
    })
  } else {
    // No file, use patch method
    editForm.patch('/profile', {
      onSuccess: () => {
        showEditModal.value = false
        success('Profile updated successfully!')
      },
      onError: (errors) => {
        if (errors.name) error('Name error: ' + errors.name)
        if (errors.email) error('Email error: ' + errors.email)
        if (errors.phone) error('Phone error: ' + errors.phone)
        if (errors.avatar) error('Avatar error: ' + errors.avatar)
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
  info('Link copied to clipboard! Share it on social media to earn points.')
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-900">
    <!-- ===== Hero + Profile Card ===== -->
    <!-- Top cover: background photo + white semi-transparent overlay -->
    <div class="relative h-[180px] sm:h-[200px] md:h-[240px]">
      <!-- Your cover photo, place it in public folder, e.g. /images/profile-hero.jpg -->
      <img src="/contact.jpg" alt="Cover" class="absolute inset-0 w-full h-full object-cover" />
      <!-- White semi-transparent overlay (adjustable opacity: /60 /65 /70 /75 etc.) -->
      <div class="absolute inset-0 bg-black/65"></div>
    </div>

    <!-- Profile card moved up to overlap cover height (adjust negative margin based on height above) -->
    <header class="-mt-16 sm:-mt-20 md:-mt-24">

      <div class="max-w-7xl mx-auto px-4">
        <div
          class="rounded-2xl bg-white/90 backdrop-blur shadow-xl border border-white/60 px-6 py-5 md:py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-5">
            <div class="w-20 h-20 rounded-full overflow-hidden ring-4 ring-white/80 shadow-md bg-gray-100 shrink-0">
              <img v-if="props.user?.avatar_url" :src="props.user.avatar_url" :alt="props.user?.name"
                class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-3xl font-semibold text-gray-500">
                {{ props.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
              </div>
            </div>

            <div>
              <h1 class="text-2xl font-bold leading-tight">{{ props.user?.name || 'User Name' }}</h1>
              <p class="text-gray-600">{{ props.user?.email }}</p>
              <div class="mt-2 flex items-center gap-2">
                <span :class="['inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold', roleColor]">
                  {{ userRole }}
                </span>
                <span class="text-sm text-gray-500">Member since {{ formatDate(props.user?.created_at) }}</span>
              </div>
            </div>
          </div>

          <button @click="editProfile"
            class="mt-4 sm:mt-0 inline-flex items-center gap-2 border border-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Profile
          </button>
        </div>
      </div>
    </header>

    <!-- ===== Quick Stats ===== -->
    <section class="mt-6">
      <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card-stat">
          <div class="stat-label">Reports</div>
          <div class="stat-value">{{ missingReports?.length || 0 }}</div>
        </div>
        <div class="card-stat">
          <div class="stat-label">Sightings</div>
          <div class="stat-value">{{ props.sightingReports?.length || 0 }}</div>
        </div>
        <div v-if="props.user?.role !== 'user'" class="card-stat">
          <div class="stat-label">Projects</div>
          <div class="stat-value">{{ props.communityProjects?.length || 0 }}</div>
        </div>
        <div class="card-stat">
          <div class="stat-label">Points</div>
          <div class="stat-value">{{ props.totalPoints || 0 }}</div>
        </div>
      </div>
    </section>

    <!-- ===== Tabs ===== -->
    <nav class="mt-6">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center gap-2 bg-white rounded-full p-1 border border-gray-200 shadow-sm w-fit">
          <button @click="activeTab = 'reports'" :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            activeTab === 'reports' ? 'bg-gray-900 text-white shadow' : 'text-gray-700 hover:bg-gray-100'
          ]">
            My Reports <span class="ml-1 text-xs opacity-80">({{ missingReports?.length || 0 }})</span>
          </button>

          <button v-if="props.user?.role !== 'user' && props.communityProjects?.length > 0"
            @click="activeTab = 'projects'" :class="[
              'px-4 py-2 rounded-full text-sm font-medium transition',
              activeTab === 'projects' ? 'bg-gray-900 text-white shadow' : 'text-gray-700 hover:bg-gray-100'
            ]">
            Community Projects <span class="ml-1 text-xs opacity-80">({{ props.communityProjects?.length || 0 }})</span>
          </button>

          <button @click="activeTab = 'overview'" :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition',
            activeTab === 'overview' ? 'bg-gray-900 text-white shadow' : 'text-gray-700 hover:bg-gray-100'
          ]">
            Activity
          </button>
        </div>
      </div>
    </nav>

    <!-- ===== Content + Sidebar ===== -->
    <main class="max-w-7xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- ===== Main ===== -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Activity -->
          <div v-if="activeTab === 'overview'" class="card">
            <h3 class="card-title">Recent Activity</h3>
            <div v-if="recentActivities.length" class="mt-3 space-y-4">
              <div v-for="a in recentActivities" :key="a.id" class="relative pl-6">
                <span class="timeline-dot" :class="a.color"></span>
                <div class="text-gray-800">
                  {{ a.description }}
                  <span class="text-sm text-gray-500">— {{ formatDate(a.date) }}</span>
                </div>
              </div>
            </div>
            <div v-else class="empty">
              No recent activity
            </div>
          </div>

          <!-- Reports -->
          <div v-if="activeTab === 'reports'" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="card-title !mb-0">My Missing Person Reports</h3>
            </div>

            <div v-if="loadingReports" class="empty py-16">
              <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 mx-auto"></div>
              <p class="mt-4 text-gray-600">Loading your reports...</p>
            </div>

            <div v-else-if="missingReports && missingReports.length" class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
              <div v-for="report in missingReports" :key="report.id" :class="[
                'group rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-sm hover:shadow-md transition',
                report.case_status === 'Rejected' ? 'ring-1 ring-red-200 bg-red-50/40' : ''
              ]">
                <!-- 图 -->
                <div class="relative h-40 bg-gray-100">
                  <img v-if="report.photo_url" :src="report.photo_url" :alt="report.full_name"
                    class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full flex items-center justify-center text-2xl font-bold text-gray-500">
                    {{ report.full_name?.charAt(0)?.toUpperCase() || 'U' }}
                  </div>

                  <div class="absolute top-3 left-3">
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-black/5"
                      :class="getStatusColor(report.case_status)">
                      <span class="w-1.5 h-1.5 rounded-full bg-current/60"></span>
                      {{ getDisplayStatus(report.case_status) }}
                    </span>
                  </div>
                </div>

                <!-- 文本 -->
                <div class="p-4">
                  <div class="font-semibold truncate">{{ report.full_name || 'Name:xx' }}</div>
                  <div class="mt-0.5 text-sm text-gray-600">Age: {{ report.age || 'xx' }}</div>
                  <div class="text-sm text-gray-600 truncate" :title="report.last_seen_location">
                    {{ report.last_seen_location || '—' }}
                  </div>
                  <div class="text-xs text-gray-500 mt-1">{{ formatDate(report.created_at) }}</div>

                  <div v-if="report.case_status === 'Rejected' && report.rejection_reason"
                    class="mt-3 rounded-md border border-red-200 bg-red-50 p-2 text-xs text-red-700">
                    <div class="font-semibold mb-0.5">Rejection Reason</div>
                    <div class="line-clamp-2">
                      {{ report.rejection_reason }}
                    </div>
                  </div>

                  <button @click="viewReport(report)" class="mt-4 w-full inline-flex items-center justify-center h-10 rounded-lg text-sm font-medium transition
                           text-white" :class="report.case_status === 'Rejected'
                            ? 'bg-red-600 hover:bg-red-700'
                            : 'bg-blue-600 hover:bg-blue-700'">
                    {{ report.case_status === 'Rejected' ? 'View Rejection' : 'View Details' }}
                  </button>
                </div>
              </div>
            </div>

            <div v-else class="card empty py-16">
              <svg class="w-14 h-14 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <h3 class="text-base font-medium mb-1">No reports yet</h3>
              <p class="text-gray-500 mb-5">Start helping by reporting a missing person case.</p>
              <button @click="router.visit('/missing-persons/report')" class="btn-primary">
                Report Your First Case
              </button>
            </div>
          </div>

          <!-- Projects -->
          <div v-if="activeTab === 'projects' && props.user?.role !== 'user'" class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="card-title !mb-0">My Community Projects</h3>
            </div>

            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
              <div v-for="project in props.communityProjects" :key="project.id"
                class="rounded-2xl overflow-hidden bg-white border border-gray-200 shadow-sm hover:shadow-md transition">
                <div class="h-44 relative bg-gray-100">
                  <img :src="project.photo_url || '/signup.png'" :alt="project.title"
                    class="w-full h-full object-cover" />
                  <div class="absolute top-3 left-3">
                    <span
                      :class="['px-2.5 py-1 rounded-full text-xs font-medium ring-1 ring-black/5', getStatusColor(project.status)]">
                      {{ project.status }}
                    </span>
                  </div>
                  <div
                    class="absolute bottom-3 right-3 bg-white/90 backdrop-blur border border-gray-200 rounded px-2 py-1 text-right shadow">
                    <div class="text-sm font-bold">{{ project.points_reward }}</div>
                    <div class="text-[10px] text-gray-600">Points</div>
                  </div>
                </div>

                <div class="p-4">
                  <h4 class="font-semibold mb-1 line-clamp-1">{{ project.title }}</h4>
                  <p class="text-sm text-gray-600 mb-3 line-clamp-1">{{ project.location }}</p>

                  <div class="space-y-1.5 mb-3 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600">Date</span>
                      <span class="font-medium">{{ formatDate(project.date) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600">Volunteers</span>
                      <span class="font-medium">{{ project.volunteers_joined }}/{{ project.volunteers_needed }}</span>
                    </div>
                  </div>

                  <div class="w-full bg-gray-200 rounded h-1.5 mb-3 overflow-hidden">
                    <div class="bg-emerald-600 h-1.5"
                      :style="{ width: `${(project.volunteers_joined / project.volunteers_needed) * 100}%` }"></div>
                  </div>

                  <button @click="router.visit(`/community-projects/${project.id}`)" class="btn-emerald w-full">
                    View Details
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== Sidebar ===== -->
        <aside class="lg:col-span-1">
          <div class="space-y-6 sticky top-6">
            <!-- Points -->
            <div class="card">
              <h3 class="card-title">Points & Rewards</h3>
              <div class="text-center my-3">
                <div class="text-4xl font-extrabold text-emerald-600">{{ props.totalPoints || 0 }}</div>
                <div class="text-sm text-gray-600">Total Points</div>
              </div>
              <button @click="showPointsModal = true" class="btn-primary w-full">
                View Points History
              </button>
            </div>

            <!-- Quick Actions -->
            <div class="card">
              <h3 class="card-title">Quick Actions</h3>
              <div class="mt-3 space-y-3">
                <Link href="/rewards" class="btn-dark w-full text-center">My Rewards</Link>
                <Link href="/rewards/my-vouchers" class="btn-emerald w-full text-center">My Vouchers</Link>
                <button @click="router.visit('/missing-persons/report')" class="btn-primary w-full">Report New
                  Case</button>
                <button v-if="props.user?.role !== 'user'" @click="router.visit('/volunteer/projects')"
                  class="btn-emerald w-full">
                  Browse Projects
                </button>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </main>

    <!-- ===== Edit Profile Modal ===== -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl border border-gray-200 max-w-md w-full shadow-xl">
        <div class="p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold">Edit Profile</h2>
            <button @click="showEditModal = false" class="text-gray-500 hover:text-gray-800">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="updateProfile" class="space-y-4">
            <input type="hidden" name="_token" :value="page.props.csrf_token">

            <div class="text-center">
              <div
                class="w-20 h-20 mx-auto mb-3 rounded-full overflow-hidden border border-gray-200 bg-gray-100 flex items-center justify-center">
                <img v-if="props.user?.avatar_url" :src="props.user.avatar_url" :alt="props.user?.name"
                  class="w-full h-full object-cover" />
                <span v-else class="text-xl font-semibold text-gray-500">{{ props.user?.name?.charAt(0)?.toUpperCase()
                  || 'U' }}</span>
              </div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
              <input type="file" @change="handleAvatarUpload" accept="image/*"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500" />
              <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF</p>
            </div>

            <div>
              <label class="label">Full Name</label>
              <input v-model="editForm.name" type="text" required class="input" />
            </div>
            <div>
              <label class="label">Email</label>
              <input v-model="editForm.email" type="email" required class="input" />
            </div>
            <div>
              <label class="label">Phone Number</label>
              <input v-model="editForm.phone" type="tel" class="input" />
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
              <button type="submit" :disabled="editForm.processing" class="btn-primary flex-1 disabled:opacity-50">
                {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
              <button type="button" @click="showEditModal = false" class="btn-light flex-1">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- ===== Points History Modal ===== -->
    <div v-if="showPointsModal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl border border-gray-200 max-w-2xl w-full max-h-[80vh] overflow-y-auto shadow-xl">
        <div class="p-6">
          <div class="flex items-center justify-between mb-5">
            <h2 class="text-lg font-semibold">Points History</h2>
            <button @click="showPointsModal = false" class="text-gray-500 hover:text-gray-800">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="space-y-3">
            <div v-for="point in props.pointsHistory" :key="point.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div>
                <p class="font-medium">{{ point.description }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(point.created_at) }}</p>
              </div>
              <span class="text-base font-bold" :class="point.type === 'earned' ? 'text-emerald-700' : 'text-red-700'">
                {{ point.type === 'earned' ? '+' : '-' }}{{ point.points }} point{{ point.points > 1 ? 's' : '' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== Rejection Modal ===== -->
    <div v-if="showRejectionModal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-2xl border border-gray-200 w-[90%] max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl">
        <div class="p-6">
          <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-semibold text-red-600">Report Rejected</h2>
            <button @click="showRejectionModal = false" class="text-gray-500 hover:text-gray-800">✕</button>
          </div>

          <div v-if="selectedRejectedReport" class="space-y-5">
            <div class="rounded-xl bg-gray-50 border border-gray-200 p-4">
              <h3 class="card-title mb-3">Report Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div><span class="text-gray-500">Name:</span>
                  <div class="text-gray-900">{{ selectedRejectedReport.full_name }}</div>
                </div>
                <div><span class="text-gray-500">Age/Gender:</span>
                  <div class="text-gray-900">{{ selectedRejectedReport.age }}/{{ selectedRejectedReport.gender }}</div>
                </div>
                <div><span class="text-gray-500">Last Seen:</span>
                  <div class="text-gray-900">{{ selectedRejectedReport.last_seen_location }}</div>
                </div>
                <div><span class="text-gray-500">Report Date:</span>
                  <div class="text-gray-900">{{ formatDate(selectedRejectedReport.created_at) }}</div>
                </div>
              </div>
            </div>

            <div class="rounded-xl bg-red-50 border border-red-200 p-4">
              <h3 class="card-title text-red-800 mb-2">Rejection Reason</h3>
              <div class="text-red-800 whitespace-pre-wrap text-sm">{{ selectedRejectedReport.rejection_reason }}</div>
            </div>

            <div class="rounded-xl bg-blue-50 border border-blue-200 p-4">
              <h3 class="card-title text-blue-800 mb-2">What You Can Do</h3>
              <ul class="text-blue-800 text-sm space-y-1 list-disc pl-5">
                <li>Review the rejection reason above</li>
                <li>Update your report with the missing or improved information</li>
                <li>Submit a new report with the corrections</li>
              </ul>
            </div>

            <div class="flex gap-3 pt-2">
              <button @click="router.visit('/missing-persons/report')" class="btn-primary flex-1">
                Submit New Report
              </button>
              <button @click="showRejectionModal = false" class="btn-light flex-1">
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
/* 小组件样式工具类 */
.card {
  @apply bg-white border border-gray-200 rounded-2xl p-5 shadow-sm;
}

.card-title {
  @apply text-base font-semibold mb-2;
}

.card-stat {
  @apply bg-white border border-gray-200 rounded-2xl p-4 shadow-sm;
}

.stat-label {
  @apply text-[11px] uppercase tracking-wide text-gray-500;
}

.stat-value {
  @apply mt-1 text-2xl font-semibold;
}

.btn-primary {
  @apply inline-flex items-center justify-center px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition;
}

.btn-emerald {
  @apply inline-flex items-center justify-center px-4 py-2 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition;
}

.btn-dark {
  @apply inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-900 text-white font-medium hover:bg-black transition;
}

.btn-light {
  @apply inline-flex items-center justify-center px-4 py-2 rounded-lg bg-gray-100 text-gray-800 font-medium hover:bg-gray-200 transition;
}

.input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:outline-none;
}

.label {
  @apply block text-sm font-medium text-gray-700 mb-1;
}

.empty {
  @apply text-center text-gray-600;
}

.timeline-dot {
  @apply absolute left-0 top-1.5 -translate-x-1/2 w-2 h-2 rounded-full shadow-sm;
}

/* 仅本页滚动条 */
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
