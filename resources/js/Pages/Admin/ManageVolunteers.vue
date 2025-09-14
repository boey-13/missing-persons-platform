<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router, usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { useToast } from '@/Composables/useToast'
defineOptions({ layout: AdminLayout })

const props = defineProps({ 
  applications: Object,
  filters: Object
})
const $page = usePage()



// Modal states for confirmations
const showApproveModal = ref(false)
const showRejectModal = ref(false)
const selectedApplication = ref(null)
const rejectReason = ref('')

function openApproveModal(app) {
  selectedApplication.value = app
  showApproveModal.value = true
}

function openRejectModal(app) {
  selectedApplication.value = app
  rejectReason.value = ''
  showRejectModal.value = true
}

const { success, error } = useToast()

function approveApplication() {
  router.post(route('admin.volunteers.status', selectedApplication.value.id), { 
    status: 'Approved' 
  }, {
    onSuccess: () => {
      showApproveModal.value = false
      selectedApplication.value = null
      success('Volunteer application approved successfully!')
    },
    onError: (errors) => {
      console.error('Approval failed:', errors)
      error('Failed to approve volunteer application. Please try again.')
    }
  })
}

function rejectApplication() {
  router.post(route('admin.volunteers.status', selectedApplication.value.id), { 
    status: 'Rejected',
    reason: rejectReason.value
  }, {
    onSuccess: () => {
      showRejectModal.value = false
      selectedApplication.value = null
      rejectReason.value = ''
      success('Volunteer application rejected successfully!')
    },
    onError: (errors) => {
      console.error('Rejection failed:', errors)
      error('Failed to reject volunteer application. Please try again.')
    }
  })
}

const showModal = ref(false)
const detail = ref(null)

function openDetail(app) {
  detail.value = app
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  detail.value = null
}

function deleteVolunteerApplication(applicationId) {
  if (confirm('Are you sure you want to delete this volunteer application? This action cannot be undone.')) {
    router.delete(`/admin/volunteers/${applicationId}`, {
      onSuccess: () => {
        success('Volunteer application deleted successfully!')
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        error('Failed to delete volunteer application. Please try again.')
      }
    })
  }
}

function getStatusColor(status) {
  const colors = {
    'Pending': 'bg-yellow-100 text-yellow-800',
    'Approved': 'bg-green-100 text-green-800',
    'Rejected': 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getProjectStatusColor(status) {
  const colors = {
    'active': 'bg-blue-100 text-blue-800',
    'upcoming': 'bg-purple-100 text-purple-800',
    'completed': 'bg-green-100 text-green-800',
    'cancelled': 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function getApplicationStatusColor(status) {
  const colors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
    'withdrawn': 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

// Filter states
const statusFilter = ref(props.filters?.status || '')
const searchFilter = ref(props.filters?.search || '')
const skillsFilter = ref(props.filters?.skills || '')
const languagesFilter = ref(props.filters?.languages || '')

// Common skills and languages for filter options
const commonSkills = [
  'First Aid', 'CPR', 'Search & Rescue', 'Communication', 'Leadership',
  'Medical', 'Technical', 'Logistics', 'Coordination', 'Translation'
]

const commonLanguages = [
  'English', 'Chinese', 'Malay', 'Tamil', 'Spanish', 'French', 'German',
  'Japanese', 'Korean', 'Arabic', 'Hindi', 'Thai', 'Vietnamese'
]

function applyFilters() {
  const params = {}
  if (statusFilter.value) params.status = statusFilter.value
  if (searchFilter.value) params.search = searchFilter.value
  if (skillsFilter.value) params.skills = skillsFilter.value
  if (languagesFilter.value) params.languages = languagesFilter.value
  
  router.get('/admin/volunteers', params)
}

function clearFilters() {
  statusFilter.value = ''
  searchFilter.value = ''
  skillsFilter.value = ''
  languagesFilter.value = ''
  router.get('/admin/volunteers')
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Volunteer Applications</h1>
    


    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select v-model="statusFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input
            v-model="searchFilter"
            type="text"
            placeholder="Search by name or email..."
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Skills</label>
          <select v-model="skillsFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Skills</option>
            <option v-for="skill in commonSkills" :key="skill" :value="skill">{{ skill }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Languages</label>
          <select v-model="languagesFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Languages</option>
            <option v-for="language in commonLanguages" :key="language" :value="language">{{ language }}</option>
          </select>
        </div>
      </div>
      <div class="flex gap-2 mt-4">
        <button @click="applyFilters" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
          Apply Filters
        </button>
        <button @click="clearFilters" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
          Clear
        </button>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <!-- Desktop Table View -->
      <div class="hidden lg:block overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">User</th>
            <th class="px-4 py-3 text-left">Motivation</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Projects</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="a in applications.data" :key="a.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3 font-medium">#{{ a.id }}</td>
            <td class="px-4 py-3">
              <div class="font-medium">{{ a.user?.name }}</div>
              <div class="text-xs text-gray-500">{{ a.user?.email }}</div>
            </td>
            <td class="px-4 py-3 max-w-md">
              <div class="truncate">{{ a.motivation }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(a.status)">
                {{ a.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="text-xs">
                <div v-if="a.project_applications && a.project_applications.length > 0" class="space-y-1">
                  <div v-for="project in a.project_applications.slice(0, 2)" :key="project.id" class="flex items-center space-x-1">
                    <span class="w-2 h-2 rounded-full" :class="getProjectStatusColor(project.project_status)"></span>
                    <span class="truncate max-w-32">{{ project.project_title }}</span>
                    <span class="text-xs px-1 py-0.5 rounded" :class="getApplicationStatusColor(project.application_status)">
                      {{ project.application_status }}
                    </span>
                  </div>
                  <div v-if="a.project_applications.length > 2" class="text-gray-500">
                    +{{ a.project_applications.length - 2 }} more
                  </div>
                </div>
                <div v-else class="text-gray-400">No projects</div>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2">
                <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs" @click="openDetail(a)">
                  View
                </button>
                
                <!-- Pending Status Actions -->
                <template v-if="a.status === 'Pending'">
                  <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(a)">
                    Approve
                  </button>
                  <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(a)">
                    Reject
                  </button>
                  <button 
                    @click="deleteVolunteerApplication(a.id)"
                    class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors text-xs"
                  >
                    Delete
                  </button>
                </template>
                
                <!-- Approved Status Actions -->
                <template v-if="a.status === 'Approved'">
                  <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(a)">
                    Approve
                  </button>
                  <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(a)">
                    Reject
                  </button>
                  <button 
                    @click="deleteVolunteerApplication(a.id)"
                    class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors text-xs"
                  >
                    Delete
                  </button>
                </template>
                
                <!-- Rejected Status Actions -->
                <template v-if="a.status === 'Rejected'">
                  <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(a)">
                    Approve
                  </button>
                  <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(a)">
                    Reject
                  </button>
                  <button 
                    @click="deleteVolunteerApplication(a.id)"
                    class="px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition-colors text-xs"
                  >
                    Delete
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      </div>

      <!-- Mobile Card View -->
      <div class="lg:hidden space-y-4 p-4">
        <div v-for="a in applications.data" :key="a.id" class="bg-white rounded-xl shadow border border-gray-200 p-4">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">{{ a.user?.name }}</h3>
              <p class="text-sm text-gray-600">{{ a.user?.email }}</p>
              <p class="text-xs text-gray-500">Application #{{ a.id }}</p>
            </div>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getStatusColor(a.status)">
              {{ a.status }}
            </span>
          </div>
          
          <div class="space-y-3 mb-4">
            <div>
              <span class="text-sm font-medium text-gray-700">Motivation:</span>
              <p class="text-sm text-gray-600 mt-1 break-words">{{ a.motivation }}</p>
            </div>
            
            <div>
              <span class="text-sm font-medium text-gray-700">Projects:</span>
              <div class="mt-1">
                <div v-if="a.project_applications && a.project_applications.length > 0" class="space-y-2">
                  <div v-for="project in a.project_applications.slice(0, 2)" :key="project.id" class="flex items-center justify-between p-2 bg-gray-50 rounded">
                    <div class="flex items-center space-x-2">
                      <span class="w-2 h-2 rounded-full" :class="getProjectStatusColor(project.project_status)"></span>
                      <span class="text-sm truncate">{{ project.project_title }}</span>
                    </div>
                    <span class="text-xs px-2 py-1 rounded" :class="getApplicationStatusColor(project.application_status)">
                      {{ project.application_status }}
                    </span>
                  </div>
                  <div v-if="a.project_applications.length > 2" class="text-sm text-gray-500">
                    +{{ a.project_applications.length - 2 }} more projects
                  </div>
                </div>
                <div v-else class="text-sm text-gray-400">No projects</div>
              </div>
            </div>
          </div>
          
          <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
            <button class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs" @click="openDetail(a)">
              View
            </button>
            
            <!-- Pending Status Actions -->
            <template v-if="a.status === 'Pending'">
              <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(a)">
                Approve
              </button>
              <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(a)">
                Reject
              </button>
            </template>
            
            <!-- Approved Status Actions -->
            <template v-if="a.status === 'Approved'">
              <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(a)">
                Approve
              </button>
              <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(a)">
                Reject
              </button>
            </template>
            
            <!-- Rejected Status Actions -->
            <template v-if="a.status === 'Rejected'">
              <button class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs" @click="openApproveModal(a)">
                Approve
              </button>
              <button class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs" @click="openRejectModal(a)">
                Reject
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="!props.applications.data || props.applications.data.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No volunteer applications found</h3>
        <p class="text-gray-500">Try adjusting your filters or search terms.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="props.applications && props.applications.total > props.applications.per_page" class="mt-6 flex justify-center">
      <div class="flex items-center space-x-4">
        <button
          :disabled="props.applications.current_page <= 1"
          @click="router.get('/admin/volunteers', { page: props.applications.current_page - 1, status: statusFilter, search: searchFilter, skills: skillsFilter, languages: languagesFilter })"
          class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Previous
        </button>
        
        <button
          v-for="page in Math.ceil(props.applications.total / props.applications.per_page)"
          :key="page"
          @click="router.get('/admin/volunteers', { page, status: statusFilter, search: searchFilter, skills: skillsFilter, languages: languagesFilter })"
          :class="[
            'text-sm px-3 py-1 rounded',
            page === props.applications.current_page
              ? 'bg-blue-600 text-white'
              : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
          ]"
        >
          {{ page }}
        </button>
        
        <button
          :disabled="props.applications.current_page >= Math.ceil(props.applications.total / props.applications.per_page)"
          @click="router.get('/admin/volunteers', { page: props.applications.current_page + 1, status: statusFilter, search: searchFilter, skills: skillsFilter, languages: languagesFilter })"
          class="text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
          Next
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Detail Modal -->
    <teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-[95%] max-w-6xl max-h-[90vh] overflow-y-auto">
          <!-- Header -->
          <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 rounded-t-2xl">
            <div class="flex justify-between items-center">
              <div class="flex items-center space-x-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <h2 class="text-2xl font-bold">Volunteer Application #{{ detail?.id }}</h2>
              </div>
              <button 
                class="text-white hover:text-gray-200 transition-colors p-2 rounded-full hover:bg-white/10"
                @click="closeModal"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
          </div>

          <div v-if="detail" class="p-6 space-y-6">
            <!-- Basic Information Card -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
              <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Basic Information
              </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                  <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">User</div>
                  <div class="text-gray-900 font-medium">{{ detail.user?.name }}</div>
                  <div class="text-sm text-gray-500">{{ detail.user?.email }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-blue-200">
                  <div class="text-xs font-medium text-blue-600 uppercase tracking-wide mb-1">Status</div>
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getStatusColor(detail.status)">
                    {{ detail.status }}
                  </span>
                  <div v-if="detail.status_reason" class="text-xs text-gray-500 mt-2">Reason: {{ detail.status_reason }}</div>
              </div>
              </div>
            </div>

            <!-- Motivation Card -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
              <h3 class="text-lg font-semibold text-purple-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Motivation
              </h3>
              <div class="bg-white rounded-lg p-4 border border-purple-200">
                <div class="text-gray-900 leading-relaxed whitespace-pre-wrap">{{ detail.motivation }}</div>
              </div>
            </div>

            <!-- Skills & Languages Card -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
              <h3 class="text-lg font-semibold text-green-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Skills & Languages
              </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-green-200">
                  <div class="text-xs font-medium text-green-600 uppercase tracking-wide mb-2">Skills</div>
                <div class="flex flex-wrap gap-2">
                    <span v-for="s in (detail.skills || [])" :key="s" class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">{{ s }}</span>
                  </div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-green-200">
                  <div class="text-xs font-medium text-green-600 uppercase tracking-wide mb-2">Languages</div>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="l in (detail.languages || [])" :key="l" class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">{{ l }}</span>
              </div>
                </div>
              </div>
            </div>

            <!-- Availability & Roles Card -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-6 border border-orange-200">
              <h3 class="text-lg font-semibold text-orange-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Availability & Roles
              </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-orange-200">
                  <div class="text-xs font-medium text-orange-600 uppercase tracking-wide mb-2">Availability</div>
                <div class="flex flex-wrap gap-2">
                    <span v-for="d in (detail.availability || [])" :key="d" class="px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-800">{{ d }}</span>
                  </div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-orange-200">
                  <div class="text-xs font-medium text-orange-600 uppercase tracking-wide mb-2">Preferred Roles</div>
                  <div class="flex flex-wrap gap-2">
                    <span v-for="r in (detail.preferred_roles || [])" :key="r" class="px-2 py-1 rounded-full text-xs bg-orange-100 text-orange-800">{{ r }}</span>
              </div>
                </div>
              </div>
            </div>

            <!-- Additional Information Card -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-6 border border-red-200">
              <h3 class="text-lg font-semibold text-red-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Additional Information
              </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white rounded-lg p-4 border border-red-200">
                  <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Areas Willing to Help</div>
                  <div class="text-gray-900 font-medium">{{ detail.areas || '-' }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-red-200">
                  <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Transport Mode</div>
                  <div class="text-gray-900 font-medium">{{ detail.transport_mode || '-' }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-red-200">
                  <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Emergency Contact</div>
                  <div class="text-gray-900 font-medium">{{ detail.emergency_contact_name }}</div>
                  <div class="text-sm text-gray-500">{{ detail.emergency_contact_phone }}</div>
                </div>
                <div class="bg-white rounded-lg p-4 border border-red-200">
                  <div class="text-xs font-medium text-red-600 uppercase tracking-wide mb-1">Prior Experience</div>
                  <div class="text-gray-900">{{ detail.prior_experience || '-' }}</div>
              </div>
              </div>
            </div>

            <!-- Projects Participation Card -->
            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-6 border border-indigo-200">
              <h3 class="text-lg font-semibold text-indigo-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Projects Participation
              </h3>
              <div class="bg-white rounded-lg p-4 border border-indigo-200">
                <div v-if="detail.project_applications && detail.project_applications.length > 0" class="space-y-3">
                  <div v-for="project in detail.project_applications" :key="project.id" class="border border-gray-200 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-2">
                      <h4 class="font-medium text-gray-900">{{ project.project_title }}</h4>
                      <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getProjectStatusColor(project.project_status)">
                          {{ project.project_status }}
                        </span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getApplicationStatusColor(project.application_status)">
                          {{ project.application_status }}
                        </span>
                      </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 text-sm">
                      <div>
                        <span class="text-gray-500">Location:</span>
                        <span class="text-gray-900">{{ project.project_location }}</span>
                      </div>
              <div>
                        <span class="text-gray-500">Date:</span>
                        <span class="text-gray-900">{{ project.project_date }}</span>
              </div>
              <div>
                        <span class="text-gray-500">Applied:</span>
                        <span class="text-gray-900">{{ project.applied_at }}</span>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                  <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                  <p>No projects participated yet</p>
                </div>
              </div>
            </div>

            <!-- Supporting Documents Card -->
            <div v-if="detail.supporting_documents && detail.supporting_documents.length" class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
              <h3 class="text-lg font-semibold text-yellow-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Supporting Documents
              </h3>
              <div class="bg-white rounded-lg p-4 border border-yellow-200">
                <div class="flex flex-wrap gap-2">
                  <a v-for="(doc, i) in detail.supporting_documents" :key="i" :href="'/storage/' + doc" target="_blank" class="inline-flex items-center px-3 py-2 bg-yellow-100 text-yellow-800 rounded-lg hover:bg-yellow-200 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Document {{ i + 1 }}
                  </a>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
              <button
                @click="closeModal"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      </div>
    </teleport>

    <!-- Approve Confirmation Modal -->
    <div v-if="showApproveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-md">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Approve Volunteer Application</h2>
            <button @click="showApproveModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <p class="text-gray-600 mb-6">Are you sure you want to approve this volunteer application? This will grant the user access to volunteer projects and activities.</p>
          
          <div class="flex gap-3">
            <button 
              @click="approveApplication"
              class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors"
            >
              Approve Application
            </button>
            <button 
              @click="showApproveModal = false"
              class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Confirmation Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-md">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Reject Volunteer Application</h2>
            <button @click="showRejectModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <p class="text-gray-600 mb-4">Are you sure you want to reject this volunteer application? Please provide a reason for the rejection.</p>
          
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason (Optional)</label>
            <textarea
              v-model="rejectReason"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
              placeholder="Enter reason for rejection..."
            ></textarea>
          </div>
          
          <div class="flex gap-3">
            <button 
              @click="rejectApplication"
              class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors"
            >
              Reject Application
            </button>
            <button 
              @click="showRejectModal = false"
              class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
            >
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>


