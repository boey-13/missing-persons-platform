<script setup>
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, useForm, usePage, Link } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AdminLayout })

// Props from backend
const props = defineProps({
  projects: Object,
  filters: Object
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

// Search and Filter State
const search = ref(props.filters?.search || '')
const categoryFilter = ref(props.filters?.category || 'all')
const statusFilter = ref(props.filters?.status || 'all')
const sortBy = ref(props.filters?.sort_by || 'created_at')
const sortOrder = ref(props.filters?.sort_order || 'desc')

// ===== Flash Message Handling =====
const page = usePage()
const { success, error } = useToast()

// Handle backend flash messages
watch(() => page.props.flash?.success, (newMessage) => {
  if (newMessage) {
    success(newMessage)
  }
})

watch(() => page.props.flash?.error, (newMessage) => {
  if (newMessage) {
    error(newMessage)
  }
})

// ===== Forms =====
const newProjectForm = useForm({
  title: '',
  location: '',
  date: '',
  time: '',
  duration: '',
  volunteers_needed: 1,
  points_reward: 0,
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
  volunteers_needed: 1,
  points_reward: 0,
  description: '',
  category: 'search',
  status: 'active',
  latest_news: '',
  photos: []
})

const categories = [
  { value: 'search', label: 'Search Operations' },
  { value: 'awareness', label: 'Awareness Campaigns' },
  { value: 'training', label: 'Training & Workshops' }
]

const statuses = [
  { value: 'upcoming', label: 'Upcoming' },
  { value: 'active', label: 'Active' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
]

const sortOptions = [
  { value: 'created_at', label: 'Date Created' },
  { value: 'title', label: 'Title' },
  { value: 'date', label: 'Project Date' },
  { value: 'points_reward', label: 'Points Reward' }
]

// ===== Computed =====
const pendingApplications = computed(() => {
  if (!Array.isArray(applications.value)) {
    return []
  }
  return applications.value.filter(app => app.status === 'pending')
})

const filteredApplications = computed(() => {
  if (applicationStatusFilter.value === 'all') {
    return applications.value
  }
  return applications.value.filter(app => app.status === applicationStatusFilter.value)
})

// ===== Actions =====
function applyFilters() {
  router.get('/admin/community-projects', {
    search: search.value,
    category: categoryFilter.value,
    status: statusFilter.value,
    sort_by: sortBy.value,
    sort_order: sortOrder.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

function clearFilters() {
  search.value = ''
  categoryFilter.value = 'all'
  statusFilter.value = 'all'
  sortBy.value = 'created_at'
  sortOrder.value = 'desc'
  applyFilters()
}

function handleSearch() {
  applyFilters()
}

function handleSort() {
  applyFilters()
}

function addProject() {
  newProjectForm.post('/admin/community-projects', {
    preserveScroll: true,
    onSuccess: () => {
      showAddProjectModal.value = false
      newProjectForm.reset()
    },
    onError: (errors) => {
      console.error('Create failed:', errors)
      error('Failed to create project. Please check the form and try again.')
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
  editProjectForm.date = project.date ? project.date.split('T')[0] : ''
  
  let timeValue = ''
  if (project.time) {
    if (project.time.includes('T')) {
      const timePart = project.time.split('T')[1]
      timeValue = timePart.substring(0, 5)
    } else if (project.time.includes(' ')) {
      const timePart = project.time.split(' ')[1]
      timeValue = timePart.substring(0, 5)
    } else if (project.time.includes(':')) {
      timeValue = project.time.substring(0, 5)
    } else {
      timeValue = project.time
    }
  }
  editProjectForm.time = timeValue
  
  editProjectForm.duration = project.duration
  editProjectForm.volunteers_needed = parseInt(project.volunteers_needed) || 1
  editProjectForm.points_reward = parseInt(project.points_reward) || 0
  editProjectForm.description = project.description
  editProjectForm.category = project.category || 'search'
  editProjectForm.status = project.status || 'active'
  editProjectForm.latest_news = project.latest_news || ''
  editProjectForm.photos = []
  showEditProjectModal.value = true
}

function updateProject() {
  if (!selectedProject.value) return

  editProjectForm.put(`/admin/community-projects/${selectedProject.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      showEditProjectModal.value = false
      editProjectForm.reset()
      selectedProject.value = null
    },
    onError: (errors) => {
      console.error('Update failed:', errors)
      if (errors && typeof errors === 'object') {
        const errorMessages = Object.values(errors).flat()
        error(`Validation failed: ${errorMessages.join(', ')}`)
      } else {
        error('Failed to update project. Please check the form and try again.')
      }
    }
  })
}

function deleteProject(projectId) {
  if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
    router.delete(`/admin/community-projects/${projectId}`, {
      preserveScroll: true,
      onSuccess: () => {
        // 提示来自后端 flash
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        error('Failed to delete project. Please try again.')
      }
    })
  }
}

function switchToProjects() {
  activeTab.value = 'projects'
}

function switchToApplications() {
  activeTab.value = 'applications'
  fetchApplications()
}

// ===== Applications（JSON 接口）=====
function fetchApplications() {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  
  fetch('/admin/community-projects/applications', {
    headers: {
      'X-CSRF-TOKEN': csrfToken || '',
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
      // Handle both array and object responses
      let applicationsArray = []
      if (Array.isArray(data)) {
        applicationsArray = data
      } else if (data && typeof data === 'object') {
        // Convert object with numeric keys to array
        applicationsArray = Object.values(data)
      }
      applications.value = applicationsArray
    })
    .catch(error => {
      console.error('Error fetching applications:', error)
      applications.value = []
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
        success(data?.message || 'Application approved successfully')
      } else {
        error(data?.message || 'Approve failed')
      }
    })
    .catch(() => error('Approve failed'))
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
        success(data?.message || 'Application rejected')
      } else {
        error(data?.message || 'Reject failed')
      }
    })
    .catch(() => error('Reject failed'))
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

onMounted(() => {
  console.log('Component mounted!')
  console.log('Initial activeTab:', activeTab.value)
})
</script>

<template>
  <div class="min-h-screen bg-gray-50">


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
          <button @click="switchToProjects" :class="[
            'py-4 px-1 border-b-2 font-medium text-sm',
            activeTab === 'projects'
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]">
            Projects ({{ props.projects.total || 0 }})
          </button>
          <button @click="switchToApplications" :class="[
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

        <!-- Search and Filter Bar -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Search -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
              <div class="relative">
                <input
                  v-model="search"
                  @keyup.enter="handleSearch"
                  type="text"
                  placeholder="Search projects..."
                  class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
            </div>

            <!-- Category Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
              <select
                v-model="categoryFilter"
                @change="applyFilters"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">All Categories</option>
                <option v-for="category in categories" :key="category.value" :value="category.value">
                  {{ category.label }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
              <select
                v-model="statusFilter"
                @change="applyFilters"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">All Statuses</option>
                <option v-for="status in statuses" :key="status.value" :value="status.value">
                  {{ status.label }}
                </option>
              </select>
            </div>

            <!-- Sort By -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
              <select
                v-model="sortBy"
                @change="handleSort"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option v-for="option in sortOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <!-- Sort Order -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
              <select
                v-model="sortOrder"
                @change="handleSort"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="desc">Descending</option>
                <option value="asc">Ascending</option>
              </select>
            </div>
          </div>

          <!-- Filter Actions -->
          <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600">
              Showing {{ props.projects.from || 0 }} to {{ props.projects.to || 0 }} of {{ props.projects.total || 0 }} projects
            </div>
            <div class="flex space-x-2">
              <button
                @click="handleSearch"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors"
              >
                Apply Filters
              </button>
              <button
                @click="clearFilters"
                class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors"
              >
                Clear All
              </button>
            </div>
          </div>
        </div>

        <!-- Projects Grid -->
        <div v-if="props.projects.data && props.projects.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="project in props.projects.data"
            :key="project.id"
            class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow flex flex-col">
            <!-- Project Image -->
            <div class="h-48 relative">
              <img :src="project.photo_url || '/signup.png'" :alt="project.title" class="w-full h-full object-cover" />
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

        <!-- Pagination -->
        <div v-if="props.projects && props.projects.total > props.projects.per_page" class="mt-8">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
              Showing {{ props.projects.from || 0 }} to {{ props.projects.to || 0 }} of {{ props.projects.total || 0 }} results
            </div>
            <div class="flex items-center space-x-2">
              <!-- Previous Page -->
              <Link
                v-if="props.projects.prev_page_url"
                :href="props.projects.prev_page_url"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors flex items-center"
                preserve-scroll
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
              </Link>
              <span
                v-else
                class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center"
              >
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Previous
              </span>

              <!-- Page Numbers -->
              <template v-for="(link, index) in props.projects.links" :key="index">
                <Link
                  v-if="link.url && !link.active && link.label !== '...' && !link.label.includes('&')"
                  :href="link.url"
                  class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors"
                  preserve-scroll
                >
                  {{ link.label }}
                </Link>
                <span
                  v-else-if="link.active"
                  class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg"
                >
                  {{ link.label }}
                </span>
                <span
                  v-else-if="link.label === '...'"
                  class="px-3 py-2 text-sm font-medium text-gray-500"
                >
                  {{ link.label }}
                </span>
              </template>

              <!-- Next Page -->
              <Link
                v-if="props.projects.next_page_url"
                :href="props.projects.next_page_url"
                class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors flex items-center"
                preserve-scroll
              >
                Next
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </Link>
              <span
                v-else
                class="px-3 py-2 text-sm font-medium text-gray-300 bg-gray-100 border border-gray-200 rounded-lg cursor-not-allowed flex items-center"
              >
                Next
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </span>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No projects found</h3>
          <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
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
        <div v-if="filteredApplications.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
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
        
        <!-- Empty State for Applications -->
        <div v-else-if="applications.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No applications found</h3>
          <p class="mt-1 text-sm text-gray-500">Applications will appear here when volunteers apply to projects.</p>
        </div>
        
        <!-- No filtered results -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No applications match your filter</h3>
          <p class="mt-1 text-sm text-gray-500">Try adjusting your filter criteria.</p>
        </div>
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
          
          <form @submit.prevent="addProject" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Title -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Title *</label>
                <input
                  v-model="newProjectForm.title"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter project title"
                />
                <div v-if="newProjectForm.errors.title" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.title }}
                </div>
              </div>

              <!-- Location -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                <input
                  v-model="newProjectForm.location"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter project location"
                />
                <div v-if="newProjectForm.errors.location" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.location }}
                </div>
              </div>

              <!-- Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                <input
                  v-model="newProjectForm.date"
                  type="date"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="newProjectForm.errors.date" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.date }}
                </div>
              </div>

              <!-- Time -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Time *</label>
                <input
                  v-model="newProjectForm.time"
                  type="time"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="newProjectForm.errors.time" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.time }}
                </div>
              </div>

              <!-- Duration -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration *</label>
                <input
                  v-model="newProjectForm.duration"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="e.g., 2 hours"
                />
                <div v-if="newProjectForm.errors.duration" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.duration }}
                </div>
              </div>

              <!-- Volunteers Needed -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Volunteers Needed *</label>
                <input
                  v-model="newProjectForm.volunteers_needed"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="newProjectForm.errors.volunteers_needed" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.volunteers_needed }}
                </div>
              </div>

              <!-- Points Reward -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Points Reward</label>
                <input
                  v-model="newProjectForm.points_reward"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="newProjectForm.errors.points_reward" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.points_reward }}
                </div>
              </div>

              <!-- Category -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <select
                  v-model="newProjectForm.category"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="category in categories" :key="category.value" :value="category.value">
                    {{ category.label }}
                  </option>
                </select>
                <div v-if="newProjectForm.errors.category" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.category }}
                </div>
              </div>

              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select
                  v-model="newProjectForm.status"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="status in statuses" :key="status.value" :value="status.value">
                    {{ status.label }}
                  </option>
                </select>
                <div v-if="newProjectForm.errors.status" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.status }}
                </div>
              </div>

              <!-- Description -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea
                  v-model="newProjectForm.description"
                  rows="4"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter project description"
                ></textarea>
                <div v-if="newProjectForm.errors.description" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.description }}
                </div>
              </div>

              <!-- Latest News -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Latest News</label>
                <textarea
                  v-model="newProjectForm.latest_news"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter latest news or updates"
                ></textarea>
                <div v-if="newProjectForm.errors.latest_news" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.latest_news }}
                </div>
              </div>

              <!-- Photos -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Photos</label>
                <input
                  @change="handlePhotoUpload"
                  type="file"
                  multiple
                  accept="image/*"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="newProjectForm.errors.photos" class="text-red-500 text-sm mt-1">
                  {{ newProjectForm.errors.photos }}
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <button
                type="button"
                @click="showAddProjectModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="newProjectForm.processing"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
              >
                <span v-if="newProjectForm.processing">Creating...</span>
                <span v-else>Create Project</span>
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
          
          <form @submit.prevent="updateProject" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Title -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Project Title *</label>
                <input
                  v-model="editProjectForm.title"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter project title"
                />
                <div v-if="editProjectForm.errors.title" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.title }}
                </div>
              </div>

              <!-- Location -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                <input
                  v-model="editProjectForm.location"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter project location"
                />
                <div v-if="editProjectForm.errors.location" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.location }}
                </div>
              </div>

              <!-- Date -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                <input
                  v-model="editProjectForm.date"
                  type="date"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="editProjectForm.errors.date" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.date }}
                </div>
              </div>

              <!-- Time -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Time *</label>
                <input
                  v-model="editProjectForm.time"
                  type="time"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="editProjectForm.errors.time" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.time }}
                </div>
              </div>

              <!-- Duration -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration *</label>
                <input
                  v-model="editProjectForm.duration"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="e.g., 2 hours"
                />
                <div v-if="editProjectForm.errors.duration" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.duration }}
                </div>
              </div>

              <!-- Volunteers Needed -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Volunteers Needed *</label>
                <input
                  v-model="editProjectForm.volunteers_needed"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="editProjectForm.errors.volunteers_needed" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.volunteers_needed }}
                </div>
              </div>

              <!-- Points Reward -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Points Reward</label>
                <input
                  v-model="editProjectForm.points_reward"
                  type="number"
                  min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="editProjectForm.errors.points_reward" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.points_reward }}
                </div>
              </div>

              <!-- Category -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                <select
                  v-model="editProjectForm.category"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="category in categories" :key="category.value" :value="category.value">
                    {{ category.label }}
                  </option>
                </select>
                <div v-if="editProjectForm.errors.category" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.category }}
                </div>
              </div>

              <!-- Status -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <select
                  v-model="editProjectForm.status"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option v-for="status in statuses" :key="status.value" :value="status.value">
                    {{ status.label }}
                  </option>
                </select>
                <div v-if="editProjectForm.errors.status" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.status }}
                </div>
              </div>

              <!-- Description -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                <textarea
                  v-model="editProjectForm.description"
                  rows="4"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter project description"
                ></textarea>
                <div v-if="editProjectForm.errors.description" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.description }}
                </div>
              </div>

              <!-- Latest News -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Latest News</label>
                <textarea
                  v-model="editProjectForm.latest_news"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter latest news or updates"
                ></textarea>
                <div v-if="editProjectForm.errors.latest_news" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.latest_news }}
                </div>
              </div>

              <!-- Photos -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Photos</label>
                <input
                  @change="handleEditPhotoUpload"
                  type="file"
                  multiple
                  accept="image/*"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <div v-if="editProjectForm.errors.photos" class="text-red-500 text-sm mt-1">
                  {{ editProjectForm.errors.photos }}
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
              <button
                type="button"
                @click="showEditProjectModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="editProjectForm.processing"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
              >
                <span v-if="editProjectForm.processing">Updating...</span>
                <span v-else>Update Project</span>
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
          <!-- Application details content here -->
        </div>
      </div>
    </div>
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

