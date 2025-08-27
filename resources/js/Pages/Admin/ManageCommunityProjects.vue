<script setup>
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, useForm, usePage, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

// Props from backend
const props = defineProps({
  projects: Array
})

// UI State
const activeTab = ref('projects')
const showAddProjectModal = ref(false)
const showEditProjectModal = ref(false)
const showApplicationModal = ref(false)
const selectedApplication = ref(null)
const selectedProject = ref(null)
const applications = ref([])
const applicationStatusFilter = ref('all')

// ===== Flash（顶部居中 + 3秒自动消失）=====
const page = usePage()
// 来自后端（Inertia redirect + session flash）
const successMsg = computed(() => page.props.flash?.success || '')
const errorMsg   = computed(() => page.props.flash?.error || '')
// 本地（给 fetch JSON 用）
const localMsg   = ref('')
const localType  = ref('success') // 'success' | 'error'

// 统一用于模板展示的消息 & 类型（优先后端的 error/success，再退回本地）
const displayMsg  = computed(() => errorMsg.value || successMsg.value || localMsg.value)
const displayType = computed(() =>
  errorMsg.value ? 'error'
  : (successMsg.value ? 'success' : (localType.value || 'success'))
)

const showFlash = ref(false)
let hideTimer = null

function showToast(msg, type = 'success') {
  localMsg.value  = msg
  localType.value = type
  showFlash.value = true
  clearTimeout(hideTimer)
  hideTimer = setTimeout(() => {
    showFlash.value = false
    // 只清本地消息，不影响后端 flash
    localMsg.value = ''
  }, 3000)
}

// 监听后端 flash 或本地消息，有就显示 3 秒
watch([successMsg, errorMsg, localMsg], ([s, e, l]) => {
  clearTimeout(hideTimer)
  showFlash.value = !!(s || e || l)
  if (showFlash.value) {
    hideTimer = setTimeout(() => {
      showFlash.value = false
      localMsg.value = ''
    }, 3000)
  }
}, { immediate: true })

onBeforeUnmount(() => {
  clearTimeout(hideTimer)
})

// ===== Forms =====
const newProjectForm = useForm({
  title: '',
  location: '',
  date: '',
  time: '',
  duration: '',
  volunteers_needed: '',
  points_reward: '',
  description: '',
  category: 'search',
  status: 'active',
  latest_news: '',
  photos: []
})

const editProjectForm = useForm({
  title: '',
  location: '',
  date: '',
  time: '',
  duration: '',
  volunteers_needed: '',
  points_reward: '',
  description: '',
  category: '',
  status: '',
  latest_news: '',
  photos: []
})

const categories = [
  { value: 'search', label: 'Search Operations' },
  { value: 'awareness', label: 'Awareness Campaigns' },
  { value: 'training', label: 'Training & Workshops' }
]

// ===== Computed =====
const pendingApplications = computed(() => {
  return applications.value.filter(app => app.status === 'pending')
})

const filteredApplications = computed(() => {
  if (applicationStatusFilter.value === 'all') {
    return applications.value
  }
  return applications.value.filter(app => app.status === applicationStatusFilter.value)
})

// ===== Actions =====
function addProject() {
  newProjectForm.post('/admin/community-projects', {
    forceFormData: true,
    onSuccess: () => {
      showAddProjectModal.value = false
      newProjectForm.reset()
      // 成功提示来自后端 flash，这里不额外本地 toast，避免重复
    }
  })
}

function updateProjectStatus(projectId, newStatus) {
  router.post(
    `/admin/community-projects/${projectId}/status`,
    { status: newStatus },
    {
      preserveScroll: true,
      onSuccess: () => {
        // 提示来自后端 flash
      },
    }
  )
}

function editProject(project) {
  selectedProject.value = project
  editProjectForm.title = project.title
  editProjectForm.location = project.location
  // Handle date format: convert ISO date to YYYY-MM-DD
  editProjectForm.date = project.date ? project.date.split('T')[0] : ''
  // Handle time format
  if (project.time && project.time.includes('T')) {
    const timePart = project.time.split('T')[1]
    editProjectForm.time = timePart.substring(0, 5)
  } else if (project.time && project.time.includes(' ')) {
    const timePart = project.time.split(' ')[1]
    editProjectForm.time = timePart.substring(0, 5)
  } else {
    editProjectForm.time = project.time || ''
  }
  editProjectForm.duration = project.duration
  editProjectForm.volunteers_needed = project.volunteers_needed
  editProjectForm.points_reward = project.points_reward
  editProjectForm.description = project.description
  editProjectForm.category = project.category
  editProjectForm.status = project.status
  editProjectForm.latest_news = project.latest_news || ''
  editProjectForm.photos = []
  showEditProjectModal.value = true
}

function updateProject() {
  if (!selectedProject.value) return
  editProjectForm.put(`/admin/community-projects/${selectedProject.value.id}`, {
    forceFormData: true,
    onSuccess: () => {
      showEditProjectModal.value = false
      editProjectForm.reset()
      selectedProject.value = null
      // 提示来自后端 flash
    }
  })
}

function deleteProject(projectId) {
  if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
    router.delete(`/admin/community-projects/${projectId}`, {
      preserveScroll: true,
      onSuccess: () => {
        // 提示来自后端 flash（控制器里记得 ->with('success', '...')）
      }
    })
  }
}

// ===== Applications（JSON 接口）=====
function fetchApplications() {
  fetch('/admin/community-projects/applications', {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    credentials: 'same-origin'
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      return response.json()
    })
    .then(data => {
      applications.value = data
    })
    .catch(error => {
      console.error('Error fetching applications:', error)
      showToast('Failed to load applications', 'error')
    })
}

function viewApplication(application) {
  selectedApplication.value = application
  showApplicationModal.value = true
}

function approveApplication(applicationId) {
  fetch(`/admin/community-projects/applications/${applicationId}/approve`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json'
    }
  })
    .then(response => response.json())
    .then(data => {
      if (data?.success) {
        showApplicationModal.value = false
        fetchApplications()
        showToast(data?.message || 'Application approved successfully')
      } else {
        showToast(data?.message || 'Approve failed', 'error')
      }
    })
    .catch(() => showToast('Approve failed', 'error'))
}

function rejectApplication(applicationId) {
  const reason = prompt('Please provide a reason for rejection (optional):')
  fetch(`/admin/community-projects/applications/${applicationId}/reject`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ rejection_reason: reason || '' })
  })
    .then(response => response.json())
    .then(data => {
      if (data?.success) {
        showApplicationModal.value = false
        fetchApplications()
        showToast(data?.message || 'Application rejected')
      } else {
        showToast(data?.message || 'Reject failed', 'error')
      }
    })
    .catch(() => showToast('Reject failed', 'error'))
}

function handlePhotoUpload(event) {
  const files = Array.from(event.target.files || [])
  newProjectForm.photos = files
}

function handleEditPhotoUpload(event) {
  const files = Array.from(event.target.files || [])
  editProjectForm.photos = files
}

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function formatTime(timeString) {
  if (!timeString) return ''
  let timePart = ''
  if (timeString.includes('T')) {
    timePart = timeString.split('T')[1].substring(0, 5)
  } else if (timeString.includes(' ')) {
    timePart = timeString.split(' ')[1].substring(0, 5)
  } else {
    timePart = timeString.substring(0, 5)
  }
  const [hours, minutes] = timePart.split(':')
  const hour = parseInt(hours, 10)
  if (Number.isNaN(hour)) return timePart
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes} ${ampm}`
}

function getStatusColor(status) {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    upcoming: 'bg-indigo-100 text-indigo-800',
    active: 'bg-green-100 text-green-800',
    completed: 'bg-gray-200 text-gray-700',
    cancelled: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getCategoryColor(category) {
  const colors = {
    search: 'bg-red-100 text-red-800',
    awareness: 'bg-yellow-100 text-yellow-800',
    training: 'bg-purple-100 text-purple-800'
  }
  return colors[category] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
  fetchApplications()
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <!-- 顶部居中 Flash（3 秒自动消失 + 动画；支持后端 flash 与本地 toast） -->
    <teleport to="body">
      <transition name="fade-down">
        <div v-if="showFlash"
             class="pointer-events-none fixed inset-x-0 top-4 z-[9999] flex justify-center px-4">
          <div class="pointer-events-auto max-w-md w-full">
            <div
              role="alert"
              class="rounded-xl px-4 py-3 text-white text-center shadow-lg"
              :class="displayType === 'error' ? 'bg-red-600/90' : 'bg-green-600/90'">
              {{ displayMsg }}
            </div>
          </div>
        </div>
      </transition>
    </teleport>

    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="px-6 py-4">
        <h1 class="text-2xl font-bold text-gray-900">Community Projects Management</h1>
        <p class="text-gray-600 mt-1">Manage volunteer projects and applications</p>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white border-b border-gray-200">
      <div class="px-6">
        <nav class="flex space-x-8">
          <button @click="activeTab = 'projects'" :class="[
            'py-4 px-1 border-b-2 font-medium text-sm',
            activeTab === 'projects'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
            Projects ({{ props.projects.length }})
          </button>
          <button @click="activeTab = 'applications'" :class="[
            'py-4 px-1 border-b-2 font-medium text-sm',
            activeTab === 'applications'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
            Applications ({{ pendingApplications.length }} pending)
          </button>
        </nav>
      </div>
    </div>

    <!-- Content -->
    <div class="p-6">
      <!-- Projects Tab -->
      <div v-if="activeTab === 'projects'">
        <!-- Action Bar -->
        <div class="flex justify-between items-center mb-6">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">All Projects</h2>
            <p class="text-sm text-gray-600">Manage community volunteer projects</p>
          </div>
          <button
            @click="showAddProjectModal = true"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add New Project
          </button>
        </div>

        <!-- Projects Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="project in props.projects"
            :key="project.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
            <!-- Project Image -->
            <div class="h-48 relative">
              <img :src="project.photo_url || '/default-avatar.jpg'" :alt="project.title" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
              <div class="absolute top-4 left-4">
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(project.status)}`">
                  {{ project.status }}
                </span>
              </div>
              <div class="absolute top-4 right-4">
                <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getCategoryColor(project.category)}`">
                  {{ project.category }}
                </span>
              </div>
              <div class="absolute bottom-4 right-4 bg-white bg-opacity-90 rounded-lg px-3 py-1">
                <div class="text-lg font-bold text-gray-900">{{ project.points_reward }}</div>
                <div class="text-xs text-gray-600">Points</div>
              </div>
            </div>

            <!-- Project Content -->
            <div class="p-4 flex flex-col flex-1">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ project.title }}</h3>
              <p class="text-sm text-gray-600 mb-4">{{ project.location }}</p>

              <div class="space-y-2 mb-4">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Date:</span>
                  <span class="font-medium">{{ formatDate(project.date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Time:</span>
                  <span class="font-medium">{{ formatTime(project.time) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Volunteers:</span>
                  <span class="font-medium">{{ project.volunteers_joined }}/{{ project.volunteers_needed }}</span>
                </div>
              </div>

              <div class="flex flex-col space-y-2 mt-auto">
                <div class="flex space-x-2">
                  <Link :href="`/community-projects/${project.id}`"
                        class="flex-1 bg-blue-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors text-center">
                    View Details
                  </Link>
                  <Link :href="`/community-projects/${project.id}`"
                        class="flex-1 bg-green-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors text-center">
                    Add News
                  </Link>
                </div>
                <div class="flex space-x-2">
                  <button @click="editProject(project)"
                          class="flex-1 bg-yellow-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-yellow-700 transition-colors">
                    Edit
                  </button>
                  <button @click="deleteProject(project.id)"
                          class="flex-1 bg-red-600 text-white py-2 px-3 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors">
                    Delete
                  </button>
                </div>
                <!-- Status Update -->
                <div class="flex items-center space-x-2">
                  <label class="text-xs text-gray-600">Status:</label>
                  <select
                    :value="project.status"
                    @change="updateProjectStatus(project.id, $event.target.value)"
                    class="flex-1 text-xs border border-gray-300 rounded px-2 py-1 focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                    <option value="upcoming">Upcoming</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div> <!-- /projects -->

      <!-- Applications Tab -->
      <div v-if="activeTab === 'applications'">
        <div class="mb-6">
          <h2 class="text-lg font-semibold text-gray-900">Volunteer Applications</h2>
          <p class="text-sm text-gray-600">Review and manage volunteer applications</p>
        </div>

        <!-- Status Filter -->
        <div class="mb-4">
          <div class="flex items-center space-x-4">
            <label class="text-sm font-medium text-gray-700">Filter by status:</label>
            <select v-model="applicationStatusFilter"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="all">All Applications ({{ applications.length }})</option>
              <option value="pending">Pending ({{ pendingApplications.length }})</option>
              <option value="approved">Approved ({{ applications.filter(app => app.status === 'approved').length }})</option>
              <option value="rejected">Rejected ({{ applications.filter(app => app.status === 'rejected').length }})</option>
            </select>
          </div>
        </div>

        <!-- Applications Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volunteer</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied On</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="application in filteredApplications" :key="application.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div>
                      <div class="text-sm font-medium text-gray-900">{{ application.volunteerName }}</div>
                      <div class="text-sm text-gray-500">{{ application.email }}</div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ application.projectTitle }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(application.status)}`">
                      {{ application.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ formatDate(application.created_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="viewApplication(application)" class="text-blue-600 hover:text-blue-900 mr-3">
                      View
                    </button>
                    <button v-if="application.status === 'pending'"
                            @click="approveApplication(application.id)"
                            class="text-green-600 hover:text-green-900 mr-3">
                      Approve
                    </button>
                    <button v-if="application.status === 'pending'"
                            @click="rejectApplication(application.id)"
                            class="text-red-600 hover:text-red-900">
                      Reject
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div> <!-- /table -->
      </div> <!-- /applications -->
    </div> <!-- /content -->

    <!-- Add Project Modal -->
    <div v-if="showAddProjectModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Add New Project</h2>
            <button @click="showAddProjectModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="addProject" class="space-y-6" enctype="multipart/form-data">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Title</label>
                <input v-model="newProjectForm.title" type="text" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select v-model="newProjectForm.category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option v-for="category in categories" :key="category.value" :value="category.value">
                    {{ category.label }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
              <input v-model="newProjectForm.location" type="text" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input v-model="newProjectForm.date" type="date" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                <input v-model="newProjectForm.time" type="time" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                <input v-model="newProjectForm.duration" type="text" placeholder="e.g., 4 hours" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Volunteers Needed</label>
                <input v-model="newProjectForm.volunteers_needed" type="number" min="1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Points Reward</label>
                <input v-model="newProjectForm.points_reward" type="number" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Photos</label>
              <input @change="handlePhotoUpload" type="file" multiple accept="image/*"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <p class="text-xs text-gray-500 mt-1">You can select multiple photos (JPEG, PNG, JPG, GIF up to 2MB each)</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="newProjectForm.description" rows="4" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Latest News & Updates</label>
              <textarea v-model="newProjectForm.latest_news" rows="3"
                        placeholder="Enter latest news, meeting point updates, or important announcements..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
              <p class="text-xs text-gray-500 mt-1">This will be visible to approved volunteers</p>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
              <button type="submit" :disabled="newProjectForm.processing"
                      class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50">
                {{ newProjectForm.processing ? 'Creating...' : 'Create Project' }}
              </button>
              <button type="button" @click="showAddProjectModal = false"
                      class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Edit Project Modal -->
    <div v-if="showEditProjectModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Edit Project</h2>
            <button @click="showEditProjectModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="updateProject" class="space-y-6" enctype="multipart/form-data">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Title</label>
                <input v-model="editProjectForm.title" type="text" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select v-model="editProjectForm.category" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                  <option v-for="category in categories" :key="category.value" :value="category.value">
                    {{ category.label }}
                  </option>
                </select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
              <input v-model="editProjectForm.location" type="text" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input v-model="editProjectForm.date" type="date" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                <input v-model="editProjectForm.time" type="time" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration</label>
                <input v-model="editProjectForm.duration" type="text" placeholder="e.g., 4 hours" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Volunteers Needed</label>
                <input v-model="editProjectForm.volunteers_needed" type="number" min="1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Points Reward</label>
                <input v-model="editProjectForm.points_reward" type="number" min="0" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Photos</label>
              <div v-if="selectedProject && selectedProject.photo_url" class="mb-3">
                <p class="text-sm text-gray-600 mb-2">Current photo:</p>
                <img :src="selectedProject.photo_url" :alt="selectedProject.title"
                     class="w-32 h-32 object-cover rounded-lg border">
              </div>
              <input @change="handleEditPhotoUpload" type="file" multiple accept="image/*"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
              <p class="text-xs text-gray-500 mt-1">
                You can select multiple photos (JPEG, PNG, JPG, GIF up to 2MB each). Leave empty to keep current photos.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea v-model="editProjectForm.description" rows="4" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Latest News & Updates</label>
              <textarea v-model="editProjectForm.latest_news" rows="3"
                        placeholder="Enter latest news, meeting point updates, or important announcements..."
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
              <p class="text-xs text-gray-500 mt-1">This will be visible to approved volunteers</p>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
              <button type="submit" :disabled="editProjectForm.processing"
                      class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50">
                {{ editProjectForm.processing ? 'Saving...' : 'Save Changes' }}
              </button>
              <button type="button" @click="showEditProjectModal = false"
                      class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Application Detail Modal -->
    <div v-if="showApplicationModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Application Details</h2>
            <button @click="showApplicationModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <div v-if="selectedApplication" class="space-y-6">
            <!-- Volunteer Info -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Volunteer Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-600">Name</label>
                  <p class="text-sm text-gray-900 font-medium">{{ selectedApplication.volunteerName }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Email</label>
                  <p class="text-sm text-gray-900">{{ selectedApplication.email }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Phone</label>
                  <p class="text-sm text-gray-900">{{ selectedApplication.phone || 'Not provided' }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">User ID</label>
                  <p class="text-sm text-gray-900">{{ selectedApplication.user_id }}</p>
                </div>
              </div>
            </div>

            <!-- Project Info -->
            <div class="bg-blue-50 rounded-lg p-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Project Information</h3>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-600">Project Title</label>
                  <p class="text-sm text-gray-900 font-medium">{{ selectedApplication.projectTitle }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Category</label>
                  <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getCategoryColor(selectedApplication.projectCategory)}`">
                    {{ selectedApplication.projectCategory }}
                  </span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Location</label>
                  <p class="text-sm text-gray-900">{{ selectedApplication.projectLocation }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Date & Time</label>
                  <p class="text-sm text-gray-900">{{ formatDate(selectedApplication.projectDate) }} at {{ selectedApplication.projectTime }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Duration</label>
                  <p class="text-sm text-gray-900">{{ selectedApplication.projectDuration }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Points Reward</label>
                  <p class="text-sm text-gray-900 font-medium text-blue-600">{{ selectedApplication.projectPoints }} points</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Volunteers</label>
                  <p class="text-sm text-gray-900">{{ selectedApplication.projectVolunteersJoined }}/{{ selectedApplication.projectVolunteersNeeded }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Project Status</label>
                  <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(selectedApplication.projectStatus)}`">
                    {{ selectedApplication.projectStatus }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Application Details -->
            <div class="bg-green-50 rounded-lg p-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-3">Application Details</h3>
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="block text-sm font-medium text-gray-600">Application Status</label>
                  <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(selectedApplication.status)}`">
                    {{ selectedApplication.status }}
                  </span>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-600">Applied On</label>
                  <p class="text-sm text-gray-900">{{ formatDate(selectedApplication.created_at) }}</p>
                </div>
                <div v-if="selectedApplication.approved_at">
                  <label class="block text-sm font-medium text-gray-600">Approved On</label>
                  <p class="text-sm text-gray-900">{{ formatDate(selectedApplication.approved_at) }}</p>
                </div>
                <div v-if="selectedApplication.rejected_at">
                  <label class="block text-sm font-medium text-gray-600">Rejected On</label>
                  <p class="text-sm text-gray-900">{{ formatDate(selectedApplication.rejected_at) }}</p>
                </div>
              </div>
              <div v-if="selectedApplication.rejection_reason">
                <label class="block text-sm font-medium text-gray-600">Rejection Reason</label>
                <p class="text-sm text-gray-900 bg-red-50 p-3 rounded-lg border border-red-200">
                  {{ selectedApplication.rejection_reason }}
                </p>
              </div>
            </div>

            <!-- Experience & Motivation -->
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Previous Experience</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200">
                  {{ selectedApplication.experience }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivation</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200">
                  {{ selectedApplication.motivation }}
                </p>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="selectedApplication.status === 'pending'" class="flex gap-3 pt-4 border-t border-gray-200">
              <button @click="approveApplication(selectedApplication.id)"
                      class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors">
                Approve Application
              </button>
              <button @click="rejectApplication(selectedApplication.id)"
                      class="flex-1 bg-red-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                Reject Application
              </button>
            </div>
            <div v-else class="pt-4 border-t border-gray-200">
              <span :class="`px-3 py-2 rounded-lg text-sm font-medium ${getStatusColor(selectedApplication.status)}`">
                {{ selectedApplication.status === 'approved' ? 'Application Approved' : 'Application Rejected' }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- /Application Modal -->
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* 顶部提示：淡入向下动画 */
.fade-down-enter-active,
.fade-down-leave-active { transition: all .18s ease; }
.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>
