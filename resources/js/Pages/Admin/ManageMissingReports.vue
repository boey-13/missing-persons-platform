<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, onMounted } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ 
  items: Array, 
  pagination: Object,
  filters: Object
})

// Modal states
const showViewModal = ref(false)
const showEditModal = ref(false)
const showRejectModal = ref(false)
const showApproveModal = ref(false)
const showStatusUpdateModal = ref(false)

// Current report data
const currentReport = ref(null)
const selectedReportId = ref(null)
const selectedReports = ref([]) // For multi-select

// Forms
const rejectForm = useForm({
  rejection_reason: '',
  preset_reason: ''
})

const statusUpdateForm = useForm({
  new_status: ''
})

// Get available status options based on current status
function getAvailableStatusOptions(currentStatus) {
  const statusFlow = {
    'Pending': ['Approved', 'Rejected'],
    'Approved': ['Missing', 'Found', 'Closed'],
    'Rejected': ['Approved'], // Can be re-approved if user fixes issues
    'Missing': ['Found', 'Closed'],
    'Found': ['Closed'],
    'Closed': [] // Terminal state
  }
  return statusFlow[currentStatus] || []
}

// Preset rejection reasons
const presetRejectionReasons = [
  { value: 'incomplete_info', label: 'Incomplete Information', description: 'Missing essential details like age, gender, or contact information' },
  { value: 'poor_photos', label: 'Poor Quality Photos', description: 'Photos are unclear, blurry, or insufficient for identification' },
  { value: 'vague_location', label: 'Vague Location Details', description: 'Last seen location is too general or unclear' },
  { value: 'insufficient_description', label: 'Insufficient Physical Description', description: 'Physical description lacks important identifying features' },
  { value: 'duplicate_report', label: 'Duplicate Report', description: 'This case has already been reported or is a duplicate' },
  { value: 'false_report', label: 'False Report', description: 'Report appears to be false or malicious' },
  { value: 'other', label: 'Other Reason', description: 'Specify a different reason for rejection' }
]

const editForm = useForm({
  full_name: '',
  age: '',
  gender: '',
  last_seen_location: '',
  last_seen_date: '',
  physical_description: '',
  additional_notes: ''
})

// Filters
const statusFilter = ref(props.filters?.status || '')
const searchFilter = ref(props.filters?.search || '')

// Functions
async function openViewModal(report) {
  try {
    // Fetch complete report details from the server
    const response = await fetch(`/admin/missing-reports/${report.id}`)
    const reportData = await response.json()
    currentReport.value = reportData
    showViewModal.value = true
  } catch (error) {
    console.error('Error fetching report details:', error)
    // Fallback to using the table data
    currentReport.value = report
    showViewModal.value = true
  }
}

function openEditModal(report) {
  currentReport.value = report
  editForm.full_name = report.full_name || ''
  editForm.age = report.age || ''
  editForm.gender = report.gender || ''
  editForm.last_seen_location = report.last_seen || ''
  editForm.last_seen_date = report.last_seen_date || ''
  editForm.physical_description = report.physical_description || ''
  editForm.additional_notes = report.additional_notes || ''
  showEditModal.value = true
}

function openRejectModal(report) {
  selectedReportId.value = report.id
  rejectForm.rejection_reason = ''
  rejectForm.preset_reason = ''
  showRejectModal.value = true
}

function handlePresetReasonChange() {
  const selectedPreset = presetRejectionReasons.find(reason => reason.value === rejectForm.preset_reason)
  if (selectedPreset && selectedPreset.value !== 'other') {
    rejectForm.rejection_reason = selectedPreset.description
  } else if (selectedPreset && selectedPreset.value === 'other') {
    rejectForm.rejection_reason = ''
  }
}

function openApproveModal(report) {
  selectedReportId.value = report.id
  showApproveModal.value = true
}

function openStatusUpdateModal(report) {
  selectedReportId.value = report.id
  currentReport.value = report
  statusUpdateForm.new_status = report.status
  showStatusUpdateModal.value = true
}

function openBulkStatusUpdateModal() {
  if (selectedReports.value.length === 0) {
    alert('Please select at least one report to update.')
    return
  }
  selectedReportId.value = null // Clear for bulk update
  currentReport.value = null // Clear for bulk update
  showStatusUpdateModal.value = true
}

function toggleReportSelection(report) {
  const index = selectedReports.value.findIndex(r => r.id === report.id)
  if (index > -1) {
    selectedReports.value.splice(index, 1)
  } else {
    selectedReports.value.push(report)
  }
}

function selectAllReports() {
  selectedReports.value = [...props.items]
}

function clearSelection() {
  selectedReports.value = []
}

function approveReport() {
  router.post(`/admin/missing-reports/${selectedReportId.value}/status`, {
    status: 'Approved'
  }, {
    onSuccess: () => {
      showApproveModal.value = false
      selectedReportId.value = null
    }
  })
}

function updateStatus() {
  if (selectedReportId.value) {
    // Single report update
    router.post(`/admin/missing-reports/${selectedReportId.value}/status`, {
      status: statusUpdateForm.new_status
    }, {
      onSuccess: () => {
        showStatusUpdateModal.value = false
        selectedReportId.value = null
        statusUpdateForm.reset()
        // Refresh the page to show updated data
        window.location.reload()
      },
      onError: (errors) => {
        console.error('Status update failed:', errors)
        alert('Failed to update status. Please try again.')
      }
    })
  } else if (selectedReports.value.length > 0) {
    // Bulk update
    const promises = selectedReports.value.map(report => 
      fetch(`/admin/missing-reports/${report.id}/status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ status: statusUpdateForm.new_status })
      })
    )
    
    Promise.all(promises).then(() => {
      showStatusUpdateModal.value = false
      selectedReports.value = []
      statusUpdateForm.reset()
      // Refresh the page to show updated data
      window.location.reload()
    }).catch(error => {
      console.error('Bulk status update failed:', error)
      alert('Failed to update status. Please try again.')
    })
  } else {
    alert('Please select at least one report to update.')
  }
}

function rejectReport() {
  rejectForm.post(`/admin/missing-reports/${selectedReportId.value}/status`, {
    status: 'Rejected'
  }, {
    onSuccess: () => {
      showRejectModal.value = false
      selectedReportId.value = null
      rejectForm.reset()
    }
  })
}

function updateReport() {
  editForm.put(`/admin/missing-reports/${currentReport.value.id}`, {
    onSuccess: () => {
      showEditModal.value = false
      currentReport.value = null
      editForm.reset()
    }
  })
}

function applyFilters() {
  router.get('/admin/missing-reports', {
    status: statusFilter.value,
    search: searchFilter.value
  })
}

function clearFilters() {
  statusFilter.value = ''
  searchFilter.value = ''
  router.get('/admin/missing-reports')
}

function getStatusColor(status) {
  const colors = {
    'Pending': 'bg-yellow-100 text-yellow-800',
    'Approved': 'bg-green-100 text-green-800',
    'Rejected': 'bg-red-100 text-red-800',
    'Missing': 'bg-blue-100 text-blue-800',
    'Found': 'bg-green-100 text-green-800',
    'Closed': 'bg-gray-100 text-gray-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function viewRelatedSightings(reportId) {
  router.get(`/admin/missing-reports/${reportId}/sightings`)
}

function createProjectFromMissing(reportId) {
  router.get(`/admin/missing-reports/${reportId}/create-project`)
}

function handleImageError(event) {
  console.error('Image failed to load:', event.target.src)
  event.target.style.display = 'none'
  // Show error message
  const errorDiv = document.createElement('div')
  errorDiv.className = 'w-32 h-32 bg-red-100 border border-red-300 rounded-lg flex items-center justify-center text-red-600 text-xs'
  errorDiv.textContent = 'Image Error'
  event.target.parentNode.appendChild(errorDiv)
}

function handleImageLoad(event) {
  console.log('Image loaded successfully:', event.target.src)
}
</script>

<template>
  <div>
    <h1 class="text-3xl font-extrabold mb-8">Manage Missing Person Reports</h1>
    
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
      <div class="flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <select v-model="statusFilter" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <option value="">All Status</option>
            <option value="Pending">Pending</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
            <option value="Missing">Missing</option>
            <option value="Found">Found</option>
            <option value="Closed">Closed</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
          <input 
            v-model="searchFilter"
            type="text" 
            placeholder="Search by name..."
            class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>
        <div class="flex gap-2">
          <button @click="applyFilters" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
            Apply Filters
          </button>
          <button @click="clearFilters" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
            Clear
          </button>
        </div>
      </div>
    </div>

    <!-- Reports Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <!-- Bulk Actions -->
      <div v-if="selectedReports.length > 0" class="bg-blue-50 border-b border-blue-200 p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <span class="text-sm font-medium text-blue-900">
              {{ selectedReports.length }} report{{ selectedReports.length > 1 ? 's' : '' }} selected
            </span>
            <button 
              @click="openBulkStatusUpdateModal"
              class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm"
            >
              Update Status
            </button>
          </div>
          <button 
            @click="clearSelection"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            Clear Selection
          </button>
        </div>
      </div>
      
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">
              <div class="flex items-center space-x-2">
                <input 
                  type="checkbox" 
                  @change="selectAllReports"
                  :checked="selectedReports.length === props.items.length && props.items.length > 0"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-xs text-gray-500">All</span>
              </div>
            </th>
            <th class="px-4 py-3 text-left">Case ID</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Age/Gender</th>
            <th class="px-4 py-3 text-left">Report By</th>
            <th class="px-4 py-3 text-left">Date & Time</th>
            <th class="px-4 py-3 text-left">Last Seen</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in props.items" :key="row.id" class="border-t hover:bg-gray-50">
            <td class="px-4 py-3">
              <input 
                type="checkbox" 
                @change="toggleReportSelection(row)"
                :checked="selectedReports.some(r => r.id === row.id)"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
            </td>
            <td class="px-4 py-3 font-medium">#{{ row.id }}</td>
            <td class="px-4 py-3 font-medium">{{ row.name }}</td>
            <td class="px-4 py-3">{{ row.age }}/{{ row.gender }}</td>
            <td class="px-4 py-3">
              <div>
                <div class="font-medium">{{ row.reporter }}</div>
                <div class="text-xs text-gray-500">{{ row.reporter_phone }}</div>
              </div>
            </td>
            <td class="px-4 py-3">{{ formatDate(row.created_at) }}</td>
            <td class="px-4 py-3 max-w-xs truncate">{{ row.last_seen }}</td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(row.status)">
                {{ row.status }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2">
                <button 
                  @click="openViewModal(row)"
                  class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs"
                >
                  View
                </button>
                
                <!-- Pending Status Actions -->
                <template v-if="row.status === 'Pending'">
                  <button 
                    @click="openApproveModal(row)"
                    class="px-3 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors text-xs"
                  >
                    Approve
                  </button>
                  <button 
                    @click="openRejectModal(row)"
                    class="px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors text-xs"
                  >
                    Reject
                  </button>
                </template>
                
                <!-- Approved Status Actions -->
                <template v-if="row.status === 'Approved'">
                  <button
                    @click="openEditModal(row)"
                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition-colors text-xs"
                  >
                    Edit
                  </button>
                  <button 
                    @click="openStatusUpdateModal(row)"
                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs"
                  >
                    Update Status
                  </button>
                  <button 
                    @click="viewRelatedSightings(row.id)"
                    class="px-3 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition-colors text-xs"
                  >
                    Sightings
                  </button>
                  <button 
                    @click="createProjectFromMissing(row.id)"
                    class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition-colors text-xs"
                  >
                    Create Project
                  </button>
                </template>
                
                <!-- Rejected Status Actions -->
                <template v-if="row.status === 'Rejected'">
                  <button 
                    @click="openStatusUpdateModal(row)"
                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs"
                  >
                    Update Status
                  </button>
                </template>
                
                <!-- Missing Status Actions -->
                <template v-if="row.status === 'Missing'">
                  <button
                    @click="openEditModal(row)"
                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition-colors text-xs"
                  >
                    Edit
                  </button>
                  <button 
                    @click="openStatusUpdateModal(row)"
                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs"
                  >
                    Update Status
                  </button>
                  <button 
                    @click="viewRelatedSightings(row.id)"
                    class="px-3 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200 transition-colors text-xs"
                  >
                    Sightings
                  </button>
                  <button 
                    @click="createProjectFromMissing(row.id)"
                    class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200 transition-colors text-xs"
                  >
                    Create Project
                  </button>
                </template>
                
                <!-- Found Status Actions -->
                <template v-if="row.status === 'Found'">
                  <button
                    @click="openEditModal(row)"
                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition-colors text-xs"
                  >
                    Edit
                  </button>
                  <button 
                    @click="openStatusUpdateModal(row)"
                    class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors text-xs"
                  >
                    Update Status
                  </button>
                </template>
                
                <!-- Closed Status Actions -->
                <template v-if="row.status === 'Closed'">
                  <button
                    @click="openEditModal(row)"
                    class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition-colors text-xs"
                  >
                    Edit
                  </button>
                </template>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Empty State -->
      <div v-if="!props.items || props.items.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No reports found</h3>
        <p class="text-gray-500">Try adjusting your filters or search terms.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="props.pagination && props.pagination.total > props.pagination.per_page" class="mt-6 flex justify-center">
      <div class="flex space-x-2">
        <button 
          v-for="page in Math.ceil(props.pagination.total / props.pagination.per_page)" 
          :key="page"
          @click="router.get('/admin/missing-reports', { page, ...$page.props.filters })"
          :class="[
            'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
            page === props.pagination.current_page 
              ? 'bg-blue-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- View Modal -->
    <div v-if="showViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Missing Person Report Details</h2>
            <button @click="showViewModal = false" class="text-gray-500 hover:text-black text-2xl">
              ✕
            </button>
          </div>
          
          <div v-if="currentReport" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Full Name</label>
                  <p class="mt-1 text-sm text-gray-900">{{ currentReport.full_name }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Age</label>
                    <p class="mt-1 text-sm text-gray-900">{{ currentReport.age }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Gender</label>
                    <p class="mt-1 text-sm text-gray-900">{{ currentReport.gender }}</p>
                  </div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Last Seen Date</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(currentReport.last_seen_date) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Last Seen Location</label>
                  <p class="mt-1 text-sm text-gray-900">{{ currentReport.last_seen }}</p>
                </div>
              </div>
            </div>

            <!-- Reporter Information -->
            <div class="space-y-4">
              <h3 class="text-lg font-semibold text-gray-900">Reporter Information</h3>
              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700">Reporter Name</label>
                  <p class="mt-1 text-sm text-gray-900">{{ currentReport.reporter }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Phone</label>
                  <p class="mt-1 text-sm text-gray-900">{{ currentReport.reporter_phone }}</p>
                </div>
                <div v-if="currentReport.reporter_email">
                  <label class="block text-sm font-medium text-gray-700">Email</label>
                  <p class="mt-1 text-sm text-gray-900">{{ currentReport.reporter_email }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Report Date</label>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(currentReport.created_at) }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Status</label>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1" :class="getStatusColor(currentReport.status)">
                    {{ currentReport.status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Photos -->
            <div v-if="currentReport.photo_paths && currentReport.photo_paths.length > 0" class="md:col-span-2">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Photos</h3>
              <div class="flex gap-4 flex-wrap">
                <div 
                  v-for="(photo, index) in currentReport.photo_paths" 
                  :key="index"
                  class="relative"
                >
                  <img 
                    :src="photo"
                    :alt="`Photo ${index + 1}`"
                    class="w-32 h-32 object-cover rounded-lg border"
                    @error="handleImageError"
                    @load="handleImageLoad"
                  />
                  <div class="text-xs text-gray-500 mt-1">Photo {{ index + 1 }}</div>
                </div>
              </div>
            </div>
            <div v-else class="md:col-span-2">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Photos</h3>
              <div class="text-gray-500">No photos available</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Edit Missing Person Report</h2>
            <button @click="showEditModal = false" class="text-gray-500 hover:text-black text-2xl">
              ✕
            </button>
          </div>
          
          <form @submit.prevent="updateReport" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <input 
                  v-model="editForm.full_name"
                  type="text" 
                  required
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Age</label>
                <input 
                  v-model="editForm.age"
                  type="number" 
                  required
                  min="0" max="150"
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                <select 
                  v-model="editForm.gender"
                  required
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select Gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Last Seen Date</label>
                <input 
                  v-model="editForm.last_seen_date"
                  type="date" 
                  required
                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Last Seen Location</label>
              <input 
                v-model="editForm.last_seen_location"
                type="text" 
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Physical Description</label>
              <textarea 
                v-model="editForm.physical_description"
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
              <textarea 
                v-model="editForm.additional_notes"
                rows="3"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
              <button 
                type="submit"
                :disabled="editForm.processing"
                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
              >
                {{ editForm.processing ? 'Updating...' : 'Update Report' }}
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

    <!-- Approve Modal -->
    <div v-if="showApproveModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-md">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Approve Report</h2>
            <button @click="showApproveModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <p class="text-gray-600 mb-6">Are you sure you want to approve this missing person report? This will make it visible to all users.</p>
          
          <div class="flex gap-3">
            <button 
              @click="approveReport"
              class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors"
            >
              Approve Report
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

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Reject Report</h2>
            <button @click="showRejectModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <form @submit.prevent="rejectReport" class="space-y-6">
            <!-- Preset Reasons -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">Select Rejection Reason</label>
              <div class="space-y-2 max-h-48 overflow-y-auto">
                <div 
                  v-for="reason in presetRejectionReasons" 
                  :key="reason.value"
                  class="flex items-start space-x-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                  :class="{ 'bg-blue-50 border-blue-300': rejectForm.preset_reason === reason.value }"
                  @click="rejectForm.preset_reason = reason.value; handlePresetReasonChange()"
                >
                  <input 
                    type="radio" 
                    :value="reason.value"
                    v-model="rejectForm.preset_reason"
                    @change="handlePresetReasonChange"
                    class="mt-1 text-red-600 focus:ring-red-500"
                  />
                  <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ reason.label }}</div>
                    <div class="text-sm text-gray-600">{{ reason.description }}</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Custom Reason -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Custom Reason {{ rejectForm.preset_reason === 'other' ? '(Required)' : '(Optional)' }}
              </label>
              <textarea 
                v-model="rejectForm.rejection_reason"
                rows="4"
                :required="rejectForm.preset_reason === 'other'"
                placeholder="Please provide additional details or a custom reason for rejection..."
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
              ></textarea>
              <p class="text-xs text-gray-500 mt-1">
                This reason will be shown to the user who submitted the report.
              </p>
            </div>
            
            <!-- Warning -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-red-800">Important</h3>
                  <div class="mt-2 text-sm text-red-700">
                    <p>Rejecting this report will notify the user and allow them to resubmit with improvements. Please provide a clear reason to help them understand what needs to be corrected.</p>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="flex gap-3 pt-4">
              <button 
                type="submit"
                :disabled="rejectForm.processing || (!rejectForm.rejection_reason && rejectForm.preset_reason !== 'other')"
                class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
              >
                {{ rejectForm.processing ? 'Rejecting...' : 'Reject Report' }}
              </button>
              <button 
                type="button"
                @click="showRejectModal = false"
                class="flex-1 bg-gray-100 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-200 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="showStatusUpdateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-xl shadow-xl w-[90%] max-w-md">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold">Update Report Status</h2>
            <button @click="showStatusUpdateModal = false" class="text-gray-500 hover:text-black">
              ✕
            </button>
          </div>
          
          <div class="mb-4">
            <p class="text-gray-600 mb-6">Select a new status for this missing person report.</p>
          </div>
          
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
              <select 
                v-model="statusUpdateForm.new_status"
                required
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="">Select Status</option>
                <option value="Pending">Pending</option>
                <option value="Approved">Approved</option>
                <option value="Rejected">Rejected</option>
                <option value="Missing">Missing</option>
                <option value="Found">Found</option>
                <option value="Closed">Closed</option>
              </select>
            </div>
          </div>
          
          <div class="flex gap-3 pt-4">
            <button 
              @click="updateStatus"
              :disabled="statusUpdateForm.processing || !statusUpdateForm.new_status"
              class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50"
            >
              {{ statusUpdateForm.processing ? 'Updating...' : 'Update Status' }}
            </button>
            <button 
              type="button"
              @click="showStatusUpdateModal = false"
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


