<script setup>
import { ref, onMounted, computed, watch, onBeforeUnmount } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'
import { router, useForm, Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: MainLayout })

// 👉 Add: flash + local toast state
const page = usePage()
const successMsg = computed(() => page.props.flash?.success || '')
const errorMsg   = computed(() => page.props.flash?.error || '')

const localMsg  = ref('')
const localType = ref('success')

// Final message/type used by the toast
const displayMsg  = computed(() => errorMsg.value || successMsg.value || localMsg.value)
const displayType = computed(() => errorMsg.value ? 'error' : (successMsg.value ? 'success' : (localType.value === 'info' ? 'success' : localType.value)))

const showFlash = ref(false)
let hideTimer = null

// 👉 Add: use this for JSON endpoints (fetch/axios) to show toast
function showToast(message, type = 'success') {
  localMsg.value  = message
  localType.value = type
  showFlash.value = true
  clearTimeout(hideTimer)
  hideTimer = setTimeout(() => {
    showFlash.value = false
    localMsg.value = ''
  }, 3000)
}

// 👉 Add: auto-show for backend flash & local toast
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

onBeforeUnmount(() => clearTimeout(hideTimer))

// Props from backend
const props = defineProps({
  projects: Object, // 现在是分页对象
  flash: Object
})

// UI State
const selectedProject = ref(null)
const showDetailModal = ref(false)
const showApplicationModal = ref(false)

const filters = ref({
  category: 'all',
  status: 'all',
  location: ''
})

// 监听过滤器变化，重新请求数据
watch(filters, (newFilters) => {
  // 构建查询参数
  const params = new URLSearchParams()
  if (newFilters.category !== 'all') params.append('category', newFilters.category)
  if (newFilters.status !== 'all') params.append('status', newFilters.status)
  if (newFilters.location) params.append('location', newFilters.location)
  
  // 重新请求数据
  router.get(route('volunteer.projects'), params.toString() ? Object.fromEntries(params) : {}, {
    preserveState: true,
    preserveScroll: true
  })
}, { deep: true })

// 清除过滤器
function clearFilters() {
  filters.value = { category: 'all', status: 'all', location: '' }
  // 重新请求数据
  router.get(route('volunteer.projects'), {}, {
    preserveState: true,
    preserveScroll: true
  })
}

// 解码 HTML 实体
function decodeHtmlEntities(text) {
  const textarea = document.createElement('textarea')
  textarea.innerHTML = text
  return textarea.value
}

// 判断是否是导航链接（Previous/Next）
function isNavigationLink(label) {
  return label === '&laquo; Previous' || label === 'Next &raquo;' || 
         label === 'Previous' || label === 'Next'
}

// Application form
const applicationForm = useForm({
  experience: '',
  motivation: ''
})

// Check if user has applied to a project
function getUserApplicationStatus(projectId) {
  // This would need to be passed from backend or fetched separately
  // For now, we'll handle this through the backend response
  return null
}

function getApplicationButtonText(project) {
  if (!project.user_application_status) {
    return 'Apply Now'
  }
  
  switch (project.user_application_status) {
    case 'pending':
      return 'Application Pending'
    case 'approved':
      return 'Approved ✓'
    case 'rejected':
      return 'Re-apply'
    default:
      return 'Apply Now'
  }
}

function getApplicationButtonClass(project) {
  if (!project.user_application_status) {
    return 'w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors'
  }
  
  switch (project.user_application_status) {
    case 'pending':
      return 'w-full bg-yellow-500 text-white py-3 px-4 rounded-lg font-semibold cursor-not-allowed opacity-75'
    case 'approved':
      return 'w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold cursor-not-allowed opacity-75'
    case 'rejected':
      return 'w-full bg-red-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors'
    default:
      return 'w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors'
  }
}

function canApplyToProject(project) {
  return !project.user_application_status || project.user_application_status === 'rejected'
}

const categories = [
  { value: 'all', label: 'All Categories' },
  { value: 'search', label: 'Search Operations' },
  { value: 'awareness', label: 'Awareness Campaigns' },
  { value: 'training', label: 'Training & Workshops' }
]

const statuses = [
  { value: 'all', label: 'All Status' },
  { value: 'active', label: 'Active' },
  { value: 'upcoming', label: 'Upcoming' }
]

const filteredProjects = computed(() => {
  return props.projects?.data || []
})

function viewProjectDetail(project) {
  selectedProject.value = project
  showDetailModal.value = true
}

function applyToProject(project) {
  if (!canApplyToProject(project)) {
    // Show appropriate message for non-applicable states
    let message = ''
    if (project.user_application_status === 'pending') {
      message = 'Your application is currently pending review.'
    } else if (project.user_application_status === 'approved') {
      message = 'You have already been approved for this project!'
    }
    
    showToast(message, 'success')
    return
  }
  
  selectedProject.value = project
  
  // Pre-fill form if re-applying after rejection
  if (project.user_application_status === 'rejected' && project.user_application) {
    applicationForm.experience = project.user_application.experience
    applicationForm.motivation = project.user_application.motivation
  } else {
    applicationForm.reset()
  }
  
  showApplicationModal.value = true
}

function submitApplication() {
  console.log('Submitting application for project:', selectedProject.value.id)
  console.log('Form data:', applicationForm.data())
  
  // Frontend validation
  if (applicationForm.experience.length < 10) {
    showToast('Experience must be at least 10 characters long.', 'error')
    return
  }
  
  if (applicationForm.motivation.length < 10) {
    showToast('Motivation must be at least 10 characters long.', 'error')
    return
  }
  
  applicationForm.post(`/volunteer/projects/${selectedProject.value.id}/apply`, {
    onSuccess: () => {
      console.log('Application submitted successfully')
      showApplicationModal.value = false
      applicationForm.reset()
      // Show success notification
      showToast('Application submitted successfully!', 'success')
    },
    onError: (errors) => {
      console.error('Form errors:', errors)
      console.error('Form error keys:', Object.keys(errors))
      
      // Show error notification with more details
      let errorMessage = 'Failed to submit application. Please try again.'
      
      if (errors.experience) {
        errorMessage = errors.experience
      } else if (errors.motivation) {
        errorMessage = errors.motivation
      } else if (errors.message) {
        errorMessage = errors.message
      } else if (typeof errors === 'string') {
        errorMessage = errors
      }
      
      showToast(errorMessage, 'error')
    },
    onFinish: () => {
      console.log('Form submission finished')
    }
  })
}

function closeModal() {
  showDetailModal.value = false
  selectedProject.value = null
}

function closeApplicationModal() {
  showApplicationModal.value = false
  selectedProject.value = null
  applicationForm.reset()
}

function formatDate(dateString) {
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
    // Handle ISO format: 2025-08-14T08:07:00.000000Z
    timePart = timeString.split('T')[1].substring(0, 5)
  } else if (timeString.includes(' ')) {
    // Handle space format: 2025-08-14 06:22:00
    timePart = timeString.split(' ')[1].substring(0, 5)
  } else {
    // Handle simple format: 06:22
    timePart = timeString.substring(0, 5)
  }
  
  // Convert to 12-hour format with AM/PM
  const [hours, minutes] = timePart.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${minutes} ${ampm}`
}

function getStatusColor(status) {
  const colors = {
    active: 'bg-green-100 text-green-800',
    upcoming: 'bg-blue-100 text-blue-800',
    completed: 'bg-gray-100 text-gray-800',
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
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- 👉 原有的 Flash Messages 和 Custom Notification 已移除，统一使用新的顶部居中提示 -->

    <!-- Header Section -->
    <div class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center">
          <h1 class="text-4xl font-bold text-gray-900 mb-4">Community Projects</h1>
          <p class="text-xl text-gray-600 mb-2">Join our community initiatives and make a difference</p>
                      <div class="inline-flex items-center bg-blue-50 text-blue-700 px-4 py-2 rounded-full text-sm font-medium">
              <svg class="w-4 h-12 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
              Points Available: {{ filteredProjects.reduce((total, project) => total + project.points_reward, 0) }}
            </div>
        </div>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
            <input
              v-model="filters.location"
              type="text"
              placeholder="Search by location..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select
              v-model="filters.category"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option v-for="category in categories" :key="category.value" :value="category.value">
                {{ category.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              v-model="filters.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option v-for="status in statuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
          </div>
          <div class="flex items-end">
            <button
              @click="clearFilters"
              class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Clear Filters
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Projects Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <div v-if="filteredProjects.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="project in filteredProjects"
          :key="project.id"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow flex flex-col"
        >
          <!-- Project Image -->
          <div class="h-48 relative">
            <div v-if="!project.photo_url" class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-500"></div>
            <img 
              v-if="project.photo_url" 
              :src="project.photo_url" 
              :alt="project.title"
              class="w-full h-full object-cover"
            />
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
          <div class="p-6 flex flex-col flex-1">
            <h3 class="text-xl font-semibold text-gray-900 mb-2 line-clamp-2">{{ project.title }}</h3>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ project.description }}</p>
            
            <div class="space-y-3 mb-6">
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ project.location }}
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{ formatDate(project.date) }} at {{ formatTime(project.time) }}
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ project.duration }}
              </div>
            </div>

            <!-- Volunteer Progress -->
            <div class="mb-6">
              <div class="flex justify-between text-sm text-gray-600 mb-2">
                <span>Volunteers: {{ project.volunteers_joined }}/{{ project.volunteers_needed }}</span>
                <span>{{ Math.round((project.volunteers_joined / project.volunteers_needed) * 100) }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                  :style="{ width: `${(project.volunteers_joined / project.volunteers_needed) * 100}%` }"
                ></div>
              </div>
            </div>

            <!-- Action Buttons - Now at the bottom -->
            <div class="flex space-x-2 mt-auto">
              <Link
                :href="`/community-projects/${project.id}`"
                class="flex-1 bg-gray-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors text-center"
              >
                View Details
              </Link>
              <button
                @click="applyToProject(project)"
                :class="getApplicationButtonClass(project)"
                :disabled="!canApplyToProject(project)"
                class="flex-1"
              >
                {{ getApplicationButtonText(project) }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No projects found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your filters or check back later for new opportunities.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="props.projects?.data && props.projects.data.length > 0" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
      <div class="flex justify-center">
        <div class="flex items-center space-x-2">
          <!-- Previous Page -->
          <Link
            v-if="props.projects.prev_page_url"
            :href="props.projects.prev_page_url"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            &lt;
          </Link>
          <span v-else class="text-gray-300">&lt;</span>

          <!-- Page Numbers -->
          <template v-for="(link, index) in props.projects.links" :key="index">
            <Link
              v-if="link.url && !link.active && !isNavigationLink(link.label)"
              :href="link.url"
              class="text-gray-600 hover:text-gray-900 transition-colors"
            >
              {{ link.label }}
            </Link>
            <span
              v-else-if="link.active"
              class="text-gray-900 underline"
            >
              {{ link.label }}
            </span>
          </template>

          <!-- Next Page -->
          <Link
            v-if="props.projects.next_page_url"
            :href="props.projects.next_page_url"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            &gt;
          </Link>
          <span v-else class="text-gray-300">&gt;</span>
        </div>
      </div>
    </div>

    <!-- Project Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Project Details</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div v-if="selectedProject" class="space-y-6">
            <!-- Project Image -->
            <div class="h-64 rounded-lg relative overflow-hidden">
              <div v-if="!selectedProject.photo_url" class="absolute inset-0 bg-gradient-to-br from-blue-400 to-purple-500"></div>
              <img 
                v-if="selectedProject.photo_url" 
                :src="selectedProject.photo_url" 
                :alt="selectedProject.title"
                class="w-full h-full object-cover"
              />
              <div class="absolute inset-0 bg-black bg-opacity-20"></div>
              <div class="absolute top-4 left-4">
                <span :class="`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(selectedProject.status)}`">
                  {{ selectedProject.status }}
                </span>
              </div>
              <div class="absolute top-4 right-4">
                <span :class="`px-3 py-1 rounded-full text-sm font-medium ${getCategoryColor(selectedProject.category)}`">
                  {{ selectedProject.category }}
                </span>
              </div>
            </div>

            <!-- Project Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h3 class="text-xl font-semibold text-gray-900 mb-4">{{ selectedProject.title }}</h3>
                <p class="text-gray-600 mb-6">{{ selectedProject.description }}</p>
                
                <div class="space-y-3">
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    {{ selectedProject.location }}
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{ formatDate(selectedProject.date) }} at {{ formatTime(selectedProject.time) }}
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ selectedProject.duration }}
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 rounded-lg p-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Project Statistics</h4>
                <div class="space-y-4">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Volunteers Needed:</span>
                    <span class="font-semibold">{{ selectedProject.volunteers_needed }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Volunteers Joined:</span>
                    <span class="font-semibold">{{ selectedProject.volunteers_joined }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Points Reward:</span>
                    <span class="font-semibold text-blue-600">{{ selectedProject.points_reward }}</span>
                  </div>
                  <div class="pt-4">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                      <span>Progress</span>
                      <span>{{ Math.round((selectedProject.volunteers_joined / selectedProject.volunteers_needed) * 100) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                      <div
                        class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                        :style="{ width: `${(selectedProject.volunteers_joined / selectedProject.volunteers_needed) * 100}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3 pt-6 border-t border-gray-200">
              <button
                @click="applyToProject(selectedProject)"
                :class="getApplicationButtonClass(selectedProject)"
                :disabled="!canApplyToProject(selectedProject)"
              >
                {{ getApplicationButtonText(selectedProject) }}
              </button>
              <button
                @click="closeModal"
                class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Application Modal -->
    <div v-if="showApplicationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
              {{ selectedProject?.user_application_status === 'rejected' ? 'Re-apply to Project' : 'Apply to Project' }}
            </h2>
            <button @click="closeApplicationModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <div v-if="selectedProject" class="space-y-6">
            <!-- Project Info -->
            <div class="bg-blue-50 rounded-lg p-4">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ selectedProject.title }}</h3>
              <p class="text-sm text-gray-600">{{ selectedProject.location }} • {{ formatDate(selectedProject.date) }}</p>
            </div>

            <!-- Application Form -->
            <form @submit.prevent="submitApplication" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Previous Experience</label>
                <textarea
                  v-model="applicationForm.experience"
                  rows="4"
                  required
                  minlength="10"
                  placeholder="Describe your relevant experience, skills, or background that would be valuable for this project..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivation</label>
                <textarea
                  v-model="applicationForm.motivation"
                  rows="4"
                  required
                  minlength="10"
                  placeholder="Explain why you want to join this project and what you hope to contribute..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">Minimum 10 characters required</p>
              </div>

              <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button
                  type="submit"
                  :disabled="applicationForm.processing"
                  class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
                >
                  {{ applicationForm.processing 
                    ? (selectedProject?.user_application_status === 'rejected' ? 'Resubmitting...' : 'Submitting...') 
                    : (selectedProject?.user_application_status === 'rejected' ? 'Resubmit Application' : 'Submit Application') 
                  }}
                </button>
                <button
                  type="button"
                  @click="closeApplicationModal"
                  class="flex-1 bg-gray-100 text-gray-700 py-3 px-4 rounded-lg font-semibold hover:bg-gray-200 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 👉 Add: 顶部居中 Flash（3 秒自动消失 + 动画；支持后端 flash 与本地 toast） -->
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
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* 👉 Add: 顶部提示：淡入向下动画 */
.fade-down-enter-active,
.fade-down-leave-active { transition: all .18s ease; }
.fade-down-enter-from,
.fade-down-leave-to { opacity: 0; transform: translateY(-8px); }
</style>


